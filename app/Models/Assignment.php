<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'instructions',
        'total_marks',
        'due_date',
        'file_path',
        'file_name',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function mySubmission()
    {
        return $this->hasOne(AssignmentSubmission::class)
                    ->where('student_id', auth()->id());
    }
}