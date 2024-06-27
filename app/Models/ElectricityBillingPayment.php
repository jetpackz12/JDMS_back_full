<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectricityBillingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'unit_con',
        'amount',
        'due_date',
        'date_issue',
        'status',
    ];
}
