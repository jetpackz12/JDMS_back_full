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
use Illuminate\Support\Facades\Redis;

class WaterBillingPaymentController extends Controller
{
    const DISABLE = 0;
    const ENABLED = 1;

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'waterBillingPayment' => DB::table('water_billing_payments')->join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*','rooms.room')->whereMonth('date_issue', '=', $month)->get(),
            'rooms' => DB::table('rooms')->where('availability', '=', 1)->get(),
        ];

        return response()->json($render_data);
    }

    public function store(Request $request)
    {
        try {

            $form_data = $request->validate([
                'room_id' => 'required',
                'prev_read' => 'required',
                'pres_read' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $month = Carbon::parse($request->date_issue)->format('m');
            $year = Carbon::parse($request->date_issue)->format('Y');

            $existingBilling = WaterBillingPayment::where('room_id', '=', $request->room_id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existingBilling) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $waterBillingPayment = WaterBillingPayment::create($form_data);

            $tenantBillingPayment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->where('tenant_billing_payments.water_billing_payment_id', '=', null)->first();

            if ($tenantBillingPayment) {

                $tenantBillingPayment->water_billing_payment_id = $waterBillingPayment->id;
                $tenantBillingPayment->save();
            } else {

                $form_data = [
                    'room_id' => $request->room_id,
                    'water_billing_payment_id' => $waterBillingPayment->id,
                ];

                TenantBillingPayment::create($form_data);
            }

            $successWaterBillingPayment = WaterBillingPayment::join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*', 'rooms.room')->where('water_billing_payments.id', '=', $waterBillingPayment->id)->first();

            $form_data = [
                'transaction' => 1,
                'billing_payment_id' => $waterBillingPayment->id,
                'description' => $successWaterBillingPayment,
            ];

            Report::create($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully added new water billing payment.', $existingBilling));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $form_data = $request->validate([
                'room_id' => 'required',
                'prev_read' => 'required',
                'pres_read' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $month = Carbon::parse($request->date_issue)->format('m');
            $year = Carbon::parse($request->date_issue)->format('Y');

            $existingBilling = WaterBillingPayment::where('room_id', '=', $request->room_id)->where('id', '!=', $id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existingBilling) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $waterBillingPayment = WaterBillingPayment::findOrFail($id);

            $tenantBillingPayment = TenantBillingPayment::where('room_id', '=', $request->room_id)->where('water_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenantBillingPayment) return response()->json($this->renderMessage('Error', 'You cannot update this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $waterBillingPayment = $waterBillingPayment->update($form_data);

            $updatedWaterBillingPayment = WaterBillingPayment::join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*', 'rooms.room')->where('water_billing_payments.id', '=', $id)->first();

            $form_data = [
                'transaction' => 1,
                'billing_payment_id' => $id,
                'description' => $updatedWaterBillingPayment,
            ];

            Report::where('transaction', '=', 1)->where('billing_payment_id', '=', $id)->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this water billing payment.', $updatedWaterBillingPayment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $tenantBillingPayment = TenantBillingPayment::where('water_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenantBillingPayment) return response()->json($this->renderMessage('Error', 'You cannot delete this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $tenantBillingPayment = TenantBillingPayment::where('water_billing_payment_id', '=', $id)->first();

            if ($tenantBillingPayment) {

                $tenantBillingPayment->water_billing_payment_id = null;
                $tenantBillingPayment->save();
            }

            $waterBillingPayment = WaterBillingPayment::findOrFail($id);
            $waterBillingPayment->delete();

            $reports = Report::where('transaction', '=', 1)->where('billing_payment_id', '=', $id)->first();
            $reports->delete();

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

            $tenantBillingPayments = TenantBillingPayment::whereIn('water_billing_payment_id', $request->waterBillingIds)->get();
            foreach ($tenantBillingPayments as $tenantBillingPayment) {
                if ($tenantBillingPayment->status == 0) return response()->json($this->renderMessage('Error', 'You cannot delete this billings because some tenant has already paid it.'), Response::HTTP_BAD_REQUEST);
             }

            $waterBillingPayments = WaterBillingPayment::whereIn('id', $request->waterBillingIds)->get();
            foreach ($waterBillingPayments as $waterBillingPayment) {

                $tenantBillingPayment = TenantBillingPayment::where('tenant_billing_payments.water_billing_payment_id', '=', $waterBillingPayment->id)->first();

                if ($tenantBillingPayment) {

                    $tenantBillingPayment->water_billing_payment_id = null;
                    $tenantBillingPayment->save();
                }

                $reports = Report::where('transaction', '=', 1)->where('billing_payment_id', '=', $waterBillingPayment->id)->first();
                $reports->delete();

                $waterBillingPayment->delete();
            }

            return response()->json($this->renderMessage('Success', 'You have successfully delete this water billing payments.', $waterBillingPayment));
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
                'waterBillingPayment' => DB::table('water_billing_payments')->join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*','rooms.room')->whereBetween('date_issue', $request->dateFilter)->get(),
                'rooms' => DB::table('rooms')->get(),
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
