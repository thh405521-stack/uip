<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExport extends Model
{
    protected $fillable = ['user_id', 'export_type', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
