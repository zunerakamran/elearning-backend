<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscussionReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'discussion_question_id',
        'user_id',
        'content',
        'is_pinned',
        'is_accepted',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_accepted' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(DiscussionQuestion::class, 'discussion_question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(DiscussionReplyLike::class);
    }
}
