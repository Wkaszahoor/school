<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    protected $fillable = [
        'student_id', 'document_type', 'title', 'file_path',
        'file_size', 'uploaded_by', 'notes', 'version',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
