<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterBillingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'prev_read',
        'pres_read',
        'amount',
        'due_date',
        'date_issue',
        'status',
    ];
}
