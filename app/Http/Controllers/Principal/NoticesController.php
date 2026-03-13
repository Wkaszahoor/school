<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Notice, SchoolClass, TeacherProfile};
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoticesController extends Controller
{
    public function index()
    {
        $notices = Notice::with('postedBy')
            ->latest()
            ->paginate(20);

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section', 'academic_year']);
        $teachers = TeacherProfile::with('user')->where('is_active', true)->get();

        return Inertia::render('Principal/Notices/Index', compact('notices', 'classes', 'teachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'body'            => 'required|string',
            'target_scope'    => 'required|in:all,role,teacher,class',
            'target_role'     => 'nullable|string',
            'target_user_id'  => 'nullable|exists:users,id',
            'target_class_id' => 'nullable|exists:classes,id',
            'expires_at'      => 'nullable|date|after:now',
        ]);

        $data['posted_by'] = auth()->id();
        $data['is_active'] = true;

        Notice::create($data);

        return back()->with('success', 'Notice published successfully.');
    }

    public function toggle(Notice $notice)
    {
        $notice->update(['is_active' => !$notice->is_active]);

        $status = $notice->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Notice {$status} successfully.");
    }

    public function destroy(Notice $notice)
    {
        $notice->delete();

        return back()->with('success', 'Notice deleted successfully.');
    }
}
