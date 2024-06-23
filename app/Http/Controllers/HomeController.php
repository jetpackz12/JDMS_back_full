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
            'vacantRooms' => DB::table('rooms')->where('availability', '=', 1)->count(),
            'occupiedRooms' => DB::table('rooms')->where('availability', '=', 0)->count(),
            'totalTenants' => DB::table('tenants')->whereYear('created_at', '=', $year)->where('status', '=', 1)->count(),
            'totalGuests' => DB::table('guests')->whereYear('created_at', '=', $year)->where('status', '=', 1)->count(),
        ];

        return response()->json($render_data);
    }

}
