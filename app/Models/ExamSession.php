<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'joined_at' => 'datetime',
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