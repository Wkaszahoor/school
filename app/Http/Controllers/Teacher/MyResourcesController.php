<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{TeachingResource, ResourceDownload, Subject, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyResourcesController extends Controller
{
    public function index(Request $request)
    {
        $resources = auth()->user()->teachingResources()
            ->with('subject:id,subject_name')
            ->when($request->search, fn($q) => $q->where('resource_name', 'like', '%' . $request->search . '%'))
            ->when($request->type, fn($q) => $q->where('resource_type', $request->type))
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name']);

        return Inertia::render('Teacher/ProfessionalDevelopment/Resources/Index', compact('resources', 'subjects'));
    }

    public function show(TeachingResource $resource)
    {
        if ($resource->created_by !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $resource->load('subject:id,subject_name');
        $downloads = ResourceDownload::where('resource_id', $resource->id)
            ->with('downloadedBy:id,name,email')
            ->latest()
            ->paginate(15);

        return Inertia::render('Teacher/ProfessionalDevelopment/Resources/Show', compact('resource', 'downloads'));
    }

    public function create()
    {
        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name']);
        return Inertia::render('Teacher/ProfessionalDevelopment/Resources/Create', compact('subjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'resource_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'resource_type' => 'required|in:lesson_plan,worksheet,assessment,presentation,video,interactive,template,guide',
            'grade_level' => 'required|in:primary,secondary,senior,university',
            'file' => 'nullable|file|max:50000',
            'file_url' => 'nullable|url',
            'is_public' => 'boolean',
            'tags' => 'nullable|json',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('teaching-resources', 'public');
            $data['file_path'] = $filePath;
            $data['mime_type'] = $request->file('file')->getMimeType();
            $data['file_size'] = $request->file('file')->getSize();
        }

        $data['created_by'] = auth()->id();
        $data['is_public'] = $request->boolean('is_public', true);

        $resource = TeachingResource::create($data);
        AuditLog::log('create', 'TeachingResource', $resource->id, null, $data);

        return redirect()->route('teacher.professional-development.resources.show', $resource->id)
            ->with('success', 'Teaching resource created successfully.');
    }

    public function edit(TeachingResource $resource)
    {
        if ($resource->created_by !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name']);
        return Inertia::render('Teacher/ProfessionalDevelopment/Resources/Create', compact('resource', 'subjects'));
    }

    public function update(Request $request, TeachingResource $resource)
    {
        if ($resource->created_by !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $data = $request->validate([
            'resource_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'resource_type' => 'required|in:lesson_plan,worksheet,assessment,presentation,video,interactive,template,guide',
            'grade_level' => 'required|in:primary,secondary,senior,university',
            'file' => 'nullable|file|max:50000',
            'file_url' => 'nullable|url',
            'is_public' => 'boolean',
            'tags' => 'nullable|json',
        ]);

        if ($request->hasFile('file')) {
            if ($resource->file_path) {
                \Storage::disk('public')->delete($resource->file_path);
            }
            $filePath = $request->file('file')->store('teaching-resources', 'public');
            $data['file_path'] = $filePath;
            $data['mime_type'] = $request->file('file')->getMimeType();
            $data['file_size'] = $request->file('file')->getSize();
        }

        $data['is_public'] = $request->boolean('is_public', true);
        $oldValues = $resource->getAttributes();
        $resource->update($data);

        AuditLog::log('update', 'TeachingResource', $resource->id, $oldValues, $data);

        return back()->with('success', 'Teaching resource updated successfully.');
    }

    public function destroy(TeachingResource $resource)
    {
        if ($resource->created_by !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        if ($resource->file_path) {
            \Storage::disk('public')->delete($resource->file_path);
        }

        $oldValues = $resource->getAttributes();
        $resource->delete();
        AuditLog::log('delete', 'TeachingResource', $resource->id, $oldValues, null);

        return back()->with('success', 'Teaching resource deleted successfully.');
    }

    public function downloadResource(TeachingResource $resource)
    {
        if (!$resource->is_public && $resource->created_by !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized access.']);
        }

        if ($resource->file_path) {
            $resource->incrementDownloads();
            ResourceDownload::create([
                'resource_id' => $resource->id,
                'downloaded_by' => auth()->id(),
                'file_name' => $resource->resource_name,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'status' => 'completed',
            ]);

            return response()->download(storage_path('app/public/' . $resource->file_path), $resource->resource_name);
        }

        return back()->withErrors(['error' => 'File not found.']);
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'nullable|in:lesson_plan,worksheet,assessment,presentation,video,interactive,template,guide',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $resources = TeachingResource::where('is_public', true)
            ->where('resource_name', 'like', '%' . $data['query'] . '%')
            ->when($data['type'] ?? null, fn($q) => $q->where('resource_type', $data['type']))
            ->when($data['subject_id'] ?? null, fn($q) => $q->where('subject_id', $data['subject_id']))
            ->with('subject:id,subject_name', 'creator:id,name')
            ->latest()
            ->get();

        return response()->json([
            'data' => $resources,
            'total' => $resources->count(),
        ]);
    }
}
