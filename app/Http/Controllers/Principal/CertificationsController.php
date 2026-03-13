<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Certification, User, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificationsController extends Controller
{
    public function index(Request $request)
    {
        $certifications = Certification::with(['teacher:id,name,email', 'course:id,course_name'])
            ->when($request->search, fn($q) => $q->whereHas('teacher', fn($q) => $q->where('name', 'like', '%' . $request->search . '%')))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->level, fn($q) => $q->where('certification_level', $request->level))
            ->when($request->expiring_soon, fn($q) => $q->expiringWithinDays(30))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Principal/ProfessionalDevelopment/Certifications/Index', compact('certifications'));
    }

    public function show(Certification $certification)
    {
        $certification->load(['teacher:id,name,email,phone', 'course:id,course_name']);

        return Inertia::render('Principal/ProfessionalDevelopment/Certifications/Show', compact('certification'));
    }

    public function downloadCertificate(Certification $certification)
    {
        if ($certification->certificate_file_path) {
            return response()->download(storage_path('app/' . $certification->certificate_file_path));
        }

        return back()->withErrors(['error' => 'Certificate file not found.']);
    }

    public function revokeCertificate(Request $request, Certification $certification)
    {
        $data = $request->validate([
            'revocation_reason' => 'required|string|min:10',
        ]);

        $oldValues = $certification->getAttributes();
        $certification->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $data['revocation_reason'],
        ]);

        AuditLog::log('revoke_certification', 'Certification', $certification->id, $oldValues, [
            'status' => 'revoked',
            'revocation_reason' => $data['revocation_reason'],
        ]);

        return back()->with('success', 'Certificate revoked successfully.');
    }

    public function bulkDownloadCertificates(Request $request)
    {
        $data = $request->validate([
            'certification_ids' => 'required|array|min:1',
            'certification_ids.*' => 'exists:certifications,id',
        ]);

        $certifications = Certification::whereIn('id', $data['certification_ids'])
            ->where('status', 'active')
            ->get();

        if ($certifications->isEmpty()) {
            return back()->withErrors(['error' => 'No valid certifications found.']);
        }

        // Create ZIP file with multiple certificates
        $zip = new \ZipArchive();
        $zipPath = storage_path('app/temp/certificates_' . time() . '.zip');

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            foreach ($certifications as $cert) {
                if (file_exists(storage_path('app/' . $cert->certificate_file_path))) {
                    $zip->addFile(
                        storage_path('app/' . $cert->certificate_file_path),
                        basename($cert->certificate_file_path)
                    );
                }
            }
            $zip->close();

            return response()->download($zipPath, 'certificates.zip')->deleteFileAfterSend(true);
        }

        return back()->withErrors(['error' => 'Failed to create certificate archive.']);
    }

    public function generateReport(Request $request)
    {
        $query = Certification::with(['teacher:id,name', 'course:id,course_name'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->level, fn($q) => $q->where('certification_level', $request->level))
            ->when($request->date_from, fn($q) => $q->where('issue_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->where('issue_date', '<=', $request->date_to));

        $certifications = $query->get();

        $stats = [
            'total' => $certifications->count(),
            'active' => $certifications->where('status', 'active')->count(),
            'expired' => $certifications->where('status', 'expired')->count(),
            'revoked' => $certifications->where('status', 'revoked')->count(),
            'by_level' => $certifications->groupBy('certification_level')->map->count(),
        ];

        return Inertia::render('Principal/ProfessionalDevelopment/Certifications/Report', compact('certifications', 'stats'));
    }
}
