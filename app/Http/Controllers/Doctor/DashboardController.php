<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\{SickRecord, Student, SchoolClass};
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'today_visits'        => SickRecord::whereDate('visit_date', today())->count(),
            'this_month_visits'   => SickRecord::whereMonth('visit_date', now()->month)->count(),
            'referred_this_month' => SickRecord::whereMonth('visit_date', now()->month)
                ->where('referred_to_hospital', true)->count(),
            'total_students'      => Student::where('is_active', true)->count(),
        ];

        $recentRecords = SickRecord::with(['student.class'])
            ->latest('visit_date')
            ->take(10)
            ->get();

        return Inertia::render('Doctor/Dashboard', compact('stats', 'recentRecords'));
    }

    public function records(Request $request)
    {
        $records = SickRecord::with(['student.class', 'doctor'])
            ->when($request->search, fn($q) =>
                $q->whereHas('student', fn($s) => $s->where('name', 'like', "%{$request->search}%")))
            ->when($request->date_from, fn($q) => $q->whereDate('visit_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('visit_date', '<=', $request->date_to))
            ->latest('visit_date')
            ->paginate(20)
            ->withQueryString();

        $students = Student::where('is_active', true)->get(['id', 'name', 'admission_no']);

        return Inertia::render('Doctor/Records', compact('records', 'students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'            => 'required|exists:students,id',
            'symptoms'              => 'required|string',
            'diagnosis'             => 'nullable|string',
            'treatment'             => 'nullable|string',
            'referred_to_hospital'  => 'boolean',
            'visit_date'            => 'required|date',
            'notes'                 => 'nullable|string',
        ]);

        $data['doctor_id'] = auth()->id();
        SickRecord::create($data);

        return back()->with('success', 'Medical record saved.');
    }
}
