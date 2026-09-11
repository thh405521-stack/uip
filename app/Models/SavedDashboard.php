<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedDashboard extends Model
{
    protected $fillable = ['user_id', 'name', 'is_default'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
