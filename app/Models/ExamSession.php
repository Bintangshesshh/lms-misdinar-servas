<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $exam_id
 * @property \Carbon\Carbon|null $start_time
 * @property \Carbon\Carbon|null $end_time
 * @property \Carbon\Carbon|null $joined_at
 * @property float $score_academic
 * @property float $score_integrity
 * @property string $status
 */
class ExamSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'joined_at' => 'datetime',
        'score_academic' => 'float',
        'score_integrity' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function cheatLogs()
    {
        return $this->hasMany(CheatLog::class);
    }

    /**
     * Get all student answers for this session.
     */
    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}