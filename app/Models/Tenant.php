<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'first_name',
        'middle_name',
        'last_name',
        'address',
        'contact_number',
        'advance',
        'status',
    ];

}
