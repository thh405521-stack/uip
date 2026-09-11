<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    protected $fillable = ['name_en', 'name_ar', 'verification_status'];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
