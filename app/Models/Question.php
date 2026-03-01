<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $exam_id
 * @property string $question_text
 * @property string $option_a
 * @property string $option_b
 * @property string $option_c
 * @property string $option_d
 * @property string $correct_answer
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
