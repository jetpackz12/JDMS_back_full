<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantBillingPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_id',
        'water_billing_payment_id',
        'water_billing_date_issue',
        'electricity_billing_payment_id',
        'electricity_billing_date_issue',
    ];
}
