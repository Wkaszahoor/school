<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use Inertia\Inertia;

class CertificationsController extends Controller
{
    /**
     * Display teacher's certifications.
     */
    public function index()
    {
        $certifications = Certification::where('teacher_id', auth()->id())
            ->with(['createdBy:id,name'])
            ->orderByDesc('issued_date')
            ->paginate(20);

        return Inertia::render('Teacher/ProfessionalDevelopment/MyCertifications/Index', compact('certifications'));
    }

    /**
     * Download certificate PDF.
     */
    public function downloadCertificate(Certification $certification)
    {
        // Ensure teacher can only download their own certificates
        if ($certification->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if ($certification->certificate_file_path) {
            return response()->download(storage_path('app/' . $certification->certificate_file_path));
        }

        return back()->withErrors(['error' => 'Certificate file not found.']);
    }
}
