<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'business_address',
        'city',
        'postal_code',
        'number_of_locations',
        'estimated_surplus_units',
        'heard_about_us',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function boxes()
    {
        return $this->hasMany(Box::class);
    }
}
