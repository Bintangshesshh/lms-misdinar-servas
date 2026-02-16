<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheatLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'occured_at' => 'datetime',
    ];

    // Kebalikannya, Log ini milik sesi siapa?
    public function examSession() {
        return $this->belongsTo(ExamSession::class);
    }
}