<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Box extends Model
{

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
