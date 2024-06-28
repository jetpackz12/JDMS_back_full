<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TenantBillingPayment;

class TenantBillingPaymentController extends Controller
{
    const PAID = 0;

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'tenantBillingPayment' =>
            DB::table('tenant_billing_payments')
                ->join('rooms', 'tenant_billing_payments.room_id', '=', 'rooms.id')
                ->join('electricity_billing_payments', 'tenant_billing_payments.electricity_billing_payment_id', '=', 'electricity_billing_payments.id')
                ->join('water_billing_payments', 'tenant_billing_payments.water_billing_payment_id', '=', 'water_billing_payments.id')
                ->select('tenant_billing_payments.*', 'tenant_billing_payments.status AS tenant_billing_status', 'tenant_billing_payments.created_at AS tenant_billing_created_at', 'rooms.room', 'rooms.price', 'electricity_billing_payments.*', 'water_billing_payments.*', 'electricity_billing_payments.amount AS electricity_amount', 'electricity_billing_payments.due_date AS electricity_due_date', 'electricity_billing_payments.date_issue AS electricity_date_issue', 'water_billing_payments.amount AS water_amount', 'water_billing_payments.due_date AS water_due_date', 'water_billing_payments.date_issue AS water_date_issue')
                ->whereMonth('tenant_billing_payments.created_at', '=', $month)
                ->get(),
            'tenants' => DB::table('tenants')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('tenants.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS full_name"), 'rooms.room')->where('tenants.status', '=', '1')->get(),
        ];

        return response()->json($render_data);
    }

    public function updateStatus($id)
    {

        try {

            $tenantBillingPayment = TenantBillingPayment::findOrFail($id);
            $tenantBillingPayment->status = self::PAID;
            $tenantBillingPayment->save();

            return response()->json($this->renderMessage('Success', 'You have successfully updated this tenant billing status.', $tenantBillingPayment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function dateFilter(Request $request)
    {
        try {

            $request->validate([
                'dateFilter' => 'required|array',
            ]);

            $render_data = [
                'tenantBillingPayment' =>
                DB::table('tenant_billing_payments')
                    ->join('rooms', 'tenant_billing_payments.room_id', '=', 'rooms.id')
                    ->join('electricity_billing_payments', 'tenant_billing_payments.electricity_billing_payment_id', '=', 'electricity_billing_payments.id')
                    ->join('water_billing_payments', 'tenant_billing_payments.water_billing_payment_id', '=', 'water_billing_payments.id')
                    ->select('tenant_billing_payments.*', 'tenant_billing_payments.status AS tenant_billing_status', 'tenant_billing_payments.created_at AS tenant_billing_created_at', 'rooms.room', 'rooms.price', 'electricity_billing_payments.*', 'water_billing_payments.*', 'electricity_billing_payments.amount AS electricity_amount', 'electricity_billing_payments.due_date AS electricity_due_date', 'electricity_billing_payments.date_issue AS electricity_date_issue', 'water_billing_payments.amount AS water_amount', 'water_billing_payments.due_date AS water_due_date', 'water_billing_payments.date_issue AS water_date_issue')
                    ->whereBetween('tenant_billing_payments.created_at', $request->dateFilter)
                    ->get(),
            ];

            return response()->json($this->renderMessage('Success', 'You have successfully filter this billing payments.', $render_data));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function renderMessage($title, $message, $res_data = [])
    {
        return [
            'title' => $title,
            'message' => $message,
            'resData' => $res_data
        ];
    }
}
