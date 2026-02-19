<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property int $duration_minutes
 * @property bool $is_active
 * @property string|null $status
 * @property \Carbon\Carbon|null $started_at
 */
class Exam extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'show_answers' => 'boolean',
        'show_score_to_student' => 'boolean',
    ];

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    // Get students currently in the lobby
    public function lobbyStudents()
    {
        return $this->sessions()
            ->where('status', 'ongoing')
            ->whereNotNull('joined_at')
            ->with('user:id,name,email');
    }

    public function isLobbyOpen(): bool
    {
        return $this->status === 'lobby' && $this->is_active;
    }

    public function isStarted(): bool
    {
        return in_array($this->status, ['started', 'countdown']);
    }
}
