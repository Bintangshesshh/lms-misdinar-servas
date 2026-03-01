<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $exam_session_id
 * @property string $violation_type
 * @property int $duration_seconds
 * @property \Carbon\Carbon|null $occurred_at
 */
class CheatLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'occurred_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    // Kebalikannya, Log ini milik sesi siapa?
    public function examSession() {
        return $this->belongsTo(ExamSession::class);
    }
}