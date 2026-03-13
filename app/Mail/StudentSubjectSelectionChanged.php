<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentSubjectSelectionChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public array $selectedSubjects,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Subject Selection Update - {$this->student->full_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-subject-selection-changed',
            with: [
                'studentName' => $this->student->full_name,
                'studentClass' => $this->student->class?->class . ($this->student->class?->section ? '-' . $this->student->class->section : ''),
                'stream' => $this->student->group_stream,
                'subjects' => $this->selectedSubjects,
                'selectionUrl' => route('principal.student-selections.show', $this->student->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
