<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\SubjectResource;
use App\Http\Resources\BaseResource;

class SubjectApiController extends Controller
{
    /**
     * اختيار اتصال قاعدة البيانات بناءً على الدولة
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
     * إرجاع جميع المواد
     */
    public function index(Request $request)
    {
        $country = $request->input('country', 'jordan');
        $connection = $this->getConnection($country);

        $subjects = Subject::on($connection)
            ->orderBy('grade_level')
            ->orderBy('subject_name')
            ->get();

        return SubjectResource::collection($subjects)
            ->additional([
                'success' => true,
            ]);
    }

    /**
     * إرجاع مادة واحدة
     */
    public function show(Request $request, $id)
    {
        $country = $request->input('country', 'jordan');
        $connection = $this->getConnection($country);

        $subject = Subject::on($connection)->find($id);

        if (!$subject) {
            return (new BaseResource(['message' => 'Subject not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        return new SubjectResource($subject);
    }

    /**
     * إنشاء مادة جديدة
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_name' => 'required|string|max:255',
            'grade_level'  => 'required|integer|min:1|max:12',
            'country'      => 'required|string'
        ]);

        if ($validator->fails()) {
            return (new BaseResource(['errors' => $validator->errors()]))
                ->response($request)
                ->setStatusCode(422);
        }

        $connection = $this->getConnection($request->country);

        $subject = Subject::on($connection)->create([
            'subject_name' => $request->subject_name,
            'grade_level'  => $request->grade_level
        ]);

        return (new SubjectResource($subject))
            ->additional([
                'message' => 'Subject created successfully',
            ]);
    }

    /**
     * تحديث مادة
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'subject_name' => 'required|string|max:255',
            'grade_level'  => 'required|integer|min:1|max:12',
            'country'      => 'required|string'
        ]);

        if ($validator->fails()) {
            return (new BaseResource(['errors' => $validator->errors()]))
                ->response($request)
                ->setStatusCode(422);
        }

        $connection = $this->getConnection($request->country);
        $subject = Subject::on($connection)->find($id);

        if (!$subject) {
            return (new BaseResource(['message' => 'Subject not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $subject->update([
            'subject_name' => $request->subject_name,
            'grade_level'  => $request->grade_level
        ]);

        return (new SubjectResource($subject))
            ->additional([
                'message' => 'Subject updated successfully',
            ]);
    }

    /**
     * حذف مادة
     */
    public function destroy(Request $request, $id)
    {
        $country = $request->input('country', 'jordan');
        $connection = $this->getConnection($country);

        $subject = Subject::on($connection)->find($id);

        if (!$subject) {
            return (new BaseResource(['message' => 'Subject not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $subject->delete();

        return new BaseResource([
            'message' => 'Subject deleted successfully'
        ]);
    }
}
