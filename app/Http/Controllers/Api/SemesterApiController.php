<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\SemesterResource;
use App\Http\Resources\BaseResource;
use App\Http\Requests\Semester\SemesterStoreRequest;
use App\Http\Requests\Semester\SemesterUpdateRequest;

class SemesterApiController extends Controller
{
    /**
     * تحديد اتصال قاعدة البيانات بناءً على الدولة
     */
    private function getConnection(string $country): string
    {
        return match ($country) {
            'saudi'     => 'sa',
            'egypt'     => 'eg',
            'palestine' => 'ps',
            default     => 'jo',
        };
    }

    /**
     * قائمة الفصول
     */
    public function index(Request $request)
    {
        $country = $request->input('country', 'jordan');
        $connection = $this->getConnection($country);

        $semesters = Semester::on($connection)
            ->with(['schoolClass:id,grade_name'])
            ->orderBy('grade_level')
            ->get();

        return SemesterResource::collection($semesters)
            ->additional([
                'success' => true,
            ]);
    }

    /**
     * بيانات فصل واحد
     */
    public function show(Request $request, $id)
    {
        $country = $request->input('country', 'jordan');
        $connection = $this->getConnection($country);

        $semester = Semester::on($connection)
            ->with('schoolClass')
            ->find($id);

        if (!$semester) {
            return (new BaseResource(['message' => 'Semester not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        return new SemesterResource($semester);
    }

    /**
     * إنشاء فصل جديد
     */
    public function store(SemesterStoreRequest $request)
    {
        $connection = $this->getConnection($request->country);

        $semester = Semester::on($connection)->create([
            'semester_name' => $request->semester_name,
            'grade_level'   => $request->grade_level,
        ]);

        return (new SemesterResource($semester))
            ->additional([
                'message' => 'Semester created successfully.',
            ]);
    }

    /**
     * تحديث فصل
     */
    public function update(SemesterUpdateRequest $request, $id)
    {
        $connection = $this->getConnection($request->country);

        $semester = Semester::on($connection)->find($id);

        if (!$semester) {
            return (new BaseResource(['message' => 'Semester not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $semester->update([
            'semester_name' => $request->semester_name,
            'grade_level'   => $request->grade_level,
        ]);

        return (new SemesterResource($semester))
            ->additional([
                'message' => 'Semester updated successfully.',
            ]);
    }

    /**
     * حذف فصل
     */
    public function destroy(Request $request, $id)
    {
        $country = $request->input('country', 'jordan');
        $connection = $this->getConnection($country);

        $semester = Semester::on($connection)->find($id);

        if (!$semester) {
            return (new BaseResource(['message' => 'Semester not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $semester->delete();

        return new BaseResource([
            'message' => 'Semester deleted successfully.'
        ]);
    }
}
