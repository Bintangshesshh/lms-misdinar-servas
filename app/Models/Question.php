<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = ['id'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
