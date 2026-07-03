<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LessonFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
