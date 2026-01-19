<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends Controller
{
    private array $countries = [
        '1' => 'الأردن',
        '2' => 'السعودية',
        '3' => 'مصر',
        '4' => 'فلسطين'
    ];

    private function getConnection(string $country): string
    {
        return match ($country) {
            'saudi', '2' => 'sa',
            'egypt', '3' => 'eg',
            'palestine', '4' => 'ps',
            'jordan', '1' => 'jo',
            default => throw new NotFoundHttpException(__('Invalid country selected')),
        };
    }

    public function index(Request $request)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);

            $categories = Category::on($connection)
                ->withCount('news')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('content.dashboard.categories.index', [
                'categories' => $categories,
                'country' => $country,
                'countries' => $this->countries
            ]);
        } catch (NotFoundHttpException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error in categories index: ' . $e->getMessage());
            return back()->with('error', __('Error loading categories'));
        }
    }

    public function create(Request $request)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);
            $parentCategories = Category::on($connection)
                ->orderBy('name')
                ->get();
            return view('content.dashboard.categories.create', [
                'country' => $country,
                'countries' => $this->countries,
                'parentCategories' => $parentCategories
            ]);
        } catch (NotFoundHttpException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Starting category creation', $request->all());
            
            // التحقق من البيانات
            $validated = $request->validate([
                'country' => 'required|string',
                'name' => 'required|string|max:255',
                'is_active' => 'sometimes|boolean',
                'parent_id' => 'nullable|integer',
                'icon_image' => 'nullable|image|max:2048', // png,jpg,jpeg,webp,gif
            ]);

            $connection = $this->getConnection($validated['country']);
            Log::info('Using connection: ' . $connection);

            // إنشاء الفئة
            DB::connection($connection)->beginTransaction();
            
            $category = new Category();
            $category->setConnection($connection);
            $category->name = $validated['name'];
            $category->slug = Str::slug($validated['name']);
            $category->is_active = $request->boolean('is_active', true);
            $category->country = $validated['country'];
            // التأكد من أن الفئة الأب موجودة في نفس الاتصال
            if (!empty($validated['parent_id'])) {
                $parentExists = Category::on($connection)->where('id', $validated['parent_id'])->exists();
                if (!$parentExists) {
                    throw new \InvalidArgumentException(__('Selected parent category does not exist'));
                }
                $category->parent_id = $validated['parent_id'];
            }
            // رفع صورة الأيقونة (تخزين المسار في عمود icon)
            if ($request->hasFile('icon_image')) {
                $path = $request->file('icon_image')->store('category_icons', 'public');
                $category->icon = $path;
            }
            $category->save();

            DB::connection($connection)->commit();
            Log::info('Category created successfully', ['category_id' => $category->id]);

            return redirect()
                ->route('dashboard.categories.index', ['country' => $validated['country']])
                ->with('success', __('Category created successfully'));

        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($connection)) {
                DB::connection($connection)->rollBack();
            }

            return back()
                ->withInput()
                ->with('error', __('Error creating category: ') . $e->getMessage());
        }
    }

    public function edit($id, Request $request)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);

            $category = Category::on($connection)->findOrFail($id);
            $parentCategories = Category::on($connection)
                ->where('id', '!=', $id)
                ->orderBy('name')
                ->get();

            return view('content.dashboard.categories.edit', [
                'category' => $category,
                'country' => $country,
                'countries' => $this->countries,
                'parentCategories' => $parentCategories
            ]);
        } catch (NotFoundHttpException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error editing category: ' . $e->getMessage());
            abort(404, __('Category not found'));
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'country' => 'required|string',
                'name' => 'required|string|max:255',
                'is_active' => 'sometimes|boolean',
                'parent_id' => 'nullable|integer',
                'icon_image' => 'nullable|image|max:2048'
            ]);

            $connection = $this->getConnection($validated['country']);
            $category = Category::on($connection)->findOrFail($id);

            DB::connection($connection)->beginTransaction();

            try {
                $category->name = $validated['name'];
                $category->slug = Str::slug($validated['name']);
                $category->is_active = $request->boolean('is_active', true);
                $category->country = $validated['country'];
                // منع جعل الفئة أباً لنفسها
                if (!empty($validated['parent_id'])) {
                    if ((int)$validated['parent_id'] === (int)$category->id) {
                        throw new \InvalidArgumentException(__('A category cannot be its own parent'));
                    }
                    $parentExists = Category::on($connection)->where('id', $validated['parent_id'])->exists();
                    if (!$parentExists) {
                        throw new \InvalidArgumentException(__('Selected parent category does not exist'));
                    }
                    $category->parent_id = $validated['parent_id'];
                } else {
                    $category->parent_id = null;
                }
                // رفع صورة الأيقونة عند التعديل وحذف القديمة إن وُجدت
                if ($request->hasFile('icon_image')) {
                    if (!empty($category->icon)) {
                        Storage::disk('public')->delete($category->icon);
                    }
                    $path = $request->file('icon_image')->store('category_icons', 'public');
                    $category->icon = $path;
                }

                $category->save();

                DB::connection($connection)->commit();

                return redirect()
                    ->route('dashboard.categories.index', ['country' => $validated['country']])
                    ->with('success', __('Category updated successfully'));

            } catch (\Exception $e) {
                DB::connection($connection)->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', __('Error updating category: ') . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);

            $category = Category::on($connection)->findOrFail($id);

            if ($category->news()->count() > 0) {
                return back()->with('error', __('Cannot delete category with associated news'));
            }

            DB::connection($connection)->beginTransaction();

            try {
                // Delete stored icon file if exists to avoid orphaned files
                if (!empty($category->icon)) {
                    Storage::disk('public')->delete($category->icon);
                }
                $category->delete();
                DB::connection($connection)->commit();

                return redirect()
                    ->route('dashboard.categories.index', ['country' => $country])
                    ->with('success', __('Category deleted successfully'));

            } catch (\Exception $e) {
                DB::connection($connection)->rollBack();
                throw $e;
            }

        } catch (NotFoundHttpException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            return back()->with('error', __('Error deleting category'));
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);

            $category = Category::on($connection)->findOrFail($id);
            
            DB::connection($connection)->beginTransaction();
            
            try {
                $category->is_active = !$category->is_active;
                $category->save();
                
                DB::connection($connection)->commit();

                return response()->json([
                    'success' => true,
                    'message' => __('Category status updated successfully'),
                    'is_active' => $category->is_active
                ]);

            } catch (\Exception $e) {
                DB::connection($connection)->rollBack();
                throw $e;
            }

        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error toggling category status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Error updating category status')
            ], 500);
        }
    }

    public function frontIndex()
    {
        // عرض قائمة الفئات للواجهة الأمامية
        $database = session('database', 'jo');
        $connection = in_array($database, ['jo', 'sa', 'eg', 'ps']) ? $database : 'jo';

        $categories = Category::on($connection)
            ->where('is_active', true)
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return view('content.frontend.categories.index', compact('categories'));
    }

    public function show($category)
    {
        // جلب الفئة وعرضها
        $category = Category::where('slug', $category)->firstOrFail();

        return view('content.frontend.categories.show', compact('category'));
    }
}
