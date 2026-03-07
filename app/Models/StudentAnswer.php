<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    /**
     * Get the display answer (selected option letter OR essay text)
     */
    public function getDisplayAnswerAttribute(): string
    {
        if ($this->answer_text) {
            return $this->answer_text;
        }
        return $this->selected_answer ? strtoupper($this->selected_answer) : '-';
    }

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
