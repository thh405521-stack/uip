<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['university_id', 'title', 'category', 'status'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}
