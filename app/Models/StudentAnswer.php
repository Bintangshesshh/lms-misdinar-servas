<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
