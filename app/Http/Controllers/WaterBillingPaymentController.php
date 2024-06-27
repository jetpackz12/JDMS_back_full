<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Tenant;
use App\Models\TenantBillingPayment;
use App\Models\WaterBillingPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WaterBillingPaymentController extends Controller
{
    const DISABLE = 0;
    const ENABLED = 1;

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'waterBillingPayment' => DB::table('water_billing_payments')->join('tenants', 'water_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('water_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room')->whereMonth('date_issue', '=', $month)->get(),
            'tenants' => DB::table('tenants')->select('tenants.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS full_name"))->get(),
        ];

        return response()->json($render_data);
    }

    public function store(Request $request)
    {
        try {

            $form_data = $request->validate([
                'tenant_id' => 'required',
                'prev_read' => 'required',
                'pres_read' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $existingBilling = WaterBillingPayment::where('tenant_id', '=', $request->tenant_id)->whereMonth('date_issue', '=', $request->date_issue)->whereYear('date_issue', '=', $request->date_issue)->first();

            if ($existingBilling) {
                return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);
            }

            $waterBillingPayment = WaterBillingPayment::create($form_data);

            $tenantBillingPayment = TenantBillingPayment::where('tenant_billing_payments.tenant_id', '=', $request->tenant_id)->where('tenant_billing_payments.water_billing_payment_id', '=', null)->first();

            if ($tenantBillingPayment) {

                $tenantBillingPayment->water_billing_payment_id = $waterBillingPayment->id;
                $tenantBillingPayment->save();
            } else {

                $form_data = [
                    'tenant_id' => $request->tenant_id,
                    'water_billing_payment_id' => $waterBillingPayment->id,
                ];

                TenantBillingPayment::create($form_data);
            }

            $successWaterBillingPayment = WaterBillingPayment::join('tenants', 'water_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('water_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room')->where('water_billing_payments.id', '=', $waterBillingPayment->id)->first();

            $form_data = [
                'transaction' => 1,
                'billing_payment_id' => $waterBillingPayment->id,
                'description' => $successWaterBillingPayment,
            ];

            Report::create($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully added new water billing payment.', $waterBillingPayment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $form_data = $request->validate([
                'tenant_id' => 'required',
                'prev_read' => 'required',
                'pres_read' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $existingBilling = WaterBillingPayment::where('tenant_id', '=', $request->tenant_id)->where('id', '!=', $id)->whereMonth('date_issue', '=', $request->date_issue)->whereYear('date_issue', '=', $request->date_issue)->first();

            if ($existingBilling) {
                return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);
            }

            $waterBillingPayment = WaterBillingPayment::findOrFail($id);

            $waterBillingPayment = $waterBillingPayment->update($form_data);

            $updatedWaterBillingPayment = WaterBillingPayment::join('tenants', 'water_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('water_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room')->where('water_billing_payments.id', '=', $id)->first();

            $form_data = [
                'transaction' => 1,
                'billing_payment_id' => $id,
                'description' => $updatedWaterBillingPayment,
            ];

            Report::where('transaction', '=', 1)->where('billing_payment_id', '=', $id)->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this water billing payment.', $waterBillingPayment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {

            $waterBillingPayment = WaterBillingPayment::findOrFail($id);

            $tenantBillingPayment = TenantBillingPayment::where('tenant_billing_payments.water_billing_payment_id', '=', $id)->first();

            if ($tenantBillingPayment) {

                $tenantBillingPayment->water_billing_payment_id = null;
                $tenantBillingPayment->save();
            }

            $waterBillingPayment->delete();

            return response()->json($this->renderMessage('Success', 'You have successfully delete this water billing payment.', $waterBillingPayment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroys(Request $request)
    {
        try {

            $request->validate([
                'waterBillingIds' => 'required|array',
            ]);

            $waterBillingPayments = WaterBillingPayment::whereIn('id', $request->waterBillingIds)->get();
            foreach ($waterBillingPayments as $waterBillingPayment) {

                $tenantBillingPayment = TenantBillingPayment::where('tenant_billing_payments.water_billing_payment_id', '=', $waterBillingPayment->id)->first();

                if ($tenantBillingPayment) {

                    $tenantBillingPayment->water_billing_payment_id = null;
                    $tenantBillingPayment->save();
                }

                $waterBillingPayment->delete();
            }

            return response()->json($this->renderMessage('Success', 'You have successfully delete this water billing payment.', $waterBillingPayment));
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
                'waterBillingPayment' => DB::table('water_billing_payments')->join('tenants', 'water_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('water_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room')->whereBetween('date_issue', $request->dateFilter)->get(),
                'tenants' => DB::table('tenants')->select('tenants.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS full_name"))->get(),
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
