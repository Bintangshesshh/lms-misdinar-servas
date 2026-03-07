<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $exam_id
 * @property string $question_type
 * @property string $question_text
 * @property string|null $option_a
 * @property string|null $option_b
 * @property string|null $option_c
 * @property string|null $option_d
 * @property string|null $correct_answer
 * @property int $points
 * @property int $order
 */
class Question extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'points' => 'integer',
        'order' => 'integer',
    ];

    public function isEssay(): bool
    {
        return $this->question_type === 'essay';
    }

    public function isMultipleChoice(): bool
    {
        return $this->question_type === 'multiple_choice' || $this->question_type === null;
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get student answers for this question.
     */
    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}
