<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $year = Carbon::now()->year;

        $render_data = [
            'tenants' => DB::table('tenants')->whereYear('created_at', '=', $year)->get(),
            'guests' => DB::table('guests')->whereYear('created_at', '=', $year)->get(),
            'waterBillingPayment' => DB::table('water_billing_payments')->whereYear('created_at', '=', $year)->get(),
            'electricityBillingPayment' => DB::table('electricity_billing_payments')->whereYear('created_at', '=', $year)->get(),
            'tenantBillingPayment' => DB::table('tenant_billing_payments')
            ->join('tenants', 'tenant_billing_payments.tenant_id', '=', 'tenants.id')
            ->join('rooms', 'tenants.room_id', '=', 'rooms.id')
            ->join('electricity_billing_payments', 'tenant_billing_payments.electricity_billing_payment_id', '=', 'electricity_billing_payments.id')
            ->join('water_billing_payments', 'tenant_billing_payments.water_billing_payment_id', '=', 'water_billing_payments.id')
            ->select('tenant_billing_payments.*', 'tenant_billing_payments.status AS tenant_billing_status', 'tenant_billing_payments.created_at AS tenant_billing_created_at', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room', 'rooms.price', 'electricity_billing_payments.*', 'water_billing_payments.*', 'electricity_billing_payments.amount AS electricity_amount', 'electricity_billing_payments.due_date AS electricity_due_date', 'electricity_billing_payments.date_issue AS electricity_date_issue', 'water_billing_payments.amount AS water_amount', 'water_billing_payments.due_date AS water_due_date', 'water_billing_payments.date_issue AS water_date_issue')->whereYear('tenant_billing_payments.created_at', '=', $year)->get(),
            'vacantRooms' => DB::table('rooms')->where('availability', '=', 1)->count(),
            'occupiedRooms' => DB::table('rooms')->where('availability', '=', 0)->count(),
            'totalTenants' => DB::table('tenants')->whereYear('created_at', '=', $year)->where('status', '=', 1)->count(),
            'totalGuests' => DB::table('guests')->whereYear('created_at', '=', $year)->where('status', '=', 1)->count(),
        ];

        return response()->json($render_data);
    }

}
