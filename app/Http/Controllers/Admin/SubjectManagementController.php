<?php

namespace App\Http\Controllers\Admin;

use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\SchoolClass;
use App\Models\ClassStreamSubjectGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class SubjectManagementController extends Controller
{
    /**
     * Display subject management dashboard with all classes and groups
     */
    public function index()
    {
        $classes = SchoolClass::whereIn('class', [9, 10, 11, 12])
            ->with(['subjectGroups' => function($q) {
                $q->with('subjects');
            }])
            ->orderBy('class')
            ->get();

        $subjects = Subject::where('is_active', true)
            ->orderBy('subject_name')
            ->get();

        return Inertia::render('Admin/SubjectManagement/Index', [
            'classes' => $classes,
            'subjects' => $subjects,
            'totalGroups' => SubjectGroup::count(),
            'totalSubjects' => Subject::where('is_active', true)->count(),
        ]);
    }

    /**
     * Show detailed view of a specific class's subject groups
     */
    public function showClass(SchoolClass $class)
    {
        $class->load(['subjectGroups' => function($q) {
            $q->with(['subjects' => function($sq) {
                $sq->select('subjects.id', 'subject_name', 'subject_code')
                    ->withPivot('subject_type');
            }]);
        }]);

        $allSubjects = Subject::where('is_active', true)
            ->orderBy('subject_name')
            ->get();

        return Inertia::render('Admin/SubjectManagement/ClassDetail', [
            'class' => $class,
            'allSubjects' => $allSubjects,
        ]);
    }

    /**
     * Update subject group with new subjects and rules
     */
    public function updateGroupSubjects(Request $request, SubjectGroup $group)
    {
        $validated = $request->validate([
            'subjects' => 'required|array',
            'subjects.*.id' => 'required|exists:subjects,id',
            'subjects.*.subject_type' => 'required|in:compulsory,major,optional',
            'min_select' => 'required|integer|min:0',
            'max_select' => 'required|integer|min:1',
            'is_optional_group' => 'boolean',
        ]);

        // Sync subjects with their types
        $syncData = [];
        foreach ($validated['subjects'] as $subjectData) {
            $syncData[$subjectData['id']] = ['subject_type' => $subjectData['subject_type']];
        }

        $group->subjects()->sync($syncData);

        // Update group rules
        $group->update([
            'min_select' => $validated['min_select'],
            'max_select' => $validated['max_select'],
            'is_optional_group' => $validated['is_optional_group'] ?? false,
        ]);

        return back()->with('success', "Group '{$group->group_name}' updated successfully");
    }

    /**
     * Create a new subject group
     */
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'stream_key' => 'required|string',
            'group_name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'min_select' => 'required|integer|min:0',
            'max_select' => 'required|integer|min:1',
            'subjects' => 'required|array',
            'subjects.*.id' => 'required|exists:subjects,id',
            'subjects.*.subject_type' => 'required|in:compulsory,major,optional',
        ]);

        // Create subject group
        $group = SubjectGroup::create([
            'group_name' => $validated['group_name'],
            'stream' => $validated['stream_key'],
            'description' => $validated['description'],
            'min_select' => $validated['min_select'],
            'max_select' => $validated['max_select'],
            'is_active' => true,
        ]);

        // Sync subjects
        $syncData = [];
        foreach ($validated['subjects'] as $subjectData) {
            $syncData[$subjectData['id']] = ['subject_type' => $subjectData['subject_type']];
        }
        $group->subjects()->sync($syncData);

        // Link to class
        ClassStreamSubjectGroup::updateOrCreate(
            ['class_id' => $validated['class_id'], 'stream_key' => $validated['stream_key']],
            ['group_id' => $group->id]
        );

        return back()->with('success', "Group '{$group->group_name}' created successfully");
    }

    /**
     * Delete a subject group
     */
    public function destroyGroup(SubjectGroup $group)
    {
        $groupName = $group->group_name;
        $group->delete();

        return back()->with('success', "Group '{$groupName}' deleted successfully");
    }

    /**
     * Manage individual subjects
     */
    public function subjectsIndex()
    {
        $subjects = Subject::orderBy('subject_name')
            ->get();

        return Inertia::render('Admin/SubjectManagement/Subjects', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Create or update a subject
     */
    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:subjects,id',
            'subject_name' => 'required|string|max:120',
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code,' . ($request->input('id') ?? 'NULL'),
            'is_active' => 'boolean',
        ]);

        if ($request->input('id')) {
            $subject = Subject::findOrFail($request->input('id'));
            $subject->update($validated);
            $message = "Subject '{$subject->subject_name}' updated successfully";
        } else {
            $subject = Subject::create($validated);
            $message = "Subject '{$subject->subject_name}' created successfully";
        }

        return back()->with('success', $message);
    }

    /**
     * Delete a subject
     */
    public function destroySubject(Subject $subject)
    {
        $subjectName = $subject->subject_name;
        $subject->delete();

        return back()->with('success', "Subject '{$subjectName}' deleted successfully");
    }
}
