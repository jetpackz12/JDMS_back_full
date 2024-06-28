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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WaterBillingPaymentController extends Controller
{
    const DISABLE = 0;
    const ENABLED = 1;

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'waterBillingPayment' => DB::table('water_billing_payments')->join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*', 'rooms.room')->whereMonth('date_issue', '=', $month)->get(),
            'rooms' => DB::table('rooms')->get(),
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

            $existing_billing = WaterBillingPayment::where('room_id', '=', $request->room_id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existing_billing) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $water_billing_payment = WaterBillingPayment::create($form_data);

            $tenant_billing_payment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->where('tenant_billing_payments.water_billing_payment_id', '=', null)->whereMonth('electricity_billing_date_issue', '=', $month)->whereYear('electricity_billing_date_issue', '=', $year)->first();

            if ($tenant_billing_payment) {

                $tenant_billing_payment->water_billing_payment_id = $water_billing_payment->id;
                $tenant_billing_payment->water_billing_date_issue = $request->date_issue;
                $tenant_billing_payment->save();
            } else {

                $form_data = [
                    'room_id' => $request->room_id,
                    'water_billing_payment_id' => $water_billing_payment->id,
                    'water_billing_date_issue' => $request->date_issue,
                ];

                TenantBillingPayment::create($form_data);
            }

            $success_water_billing_payment = WaterBillingPayment::join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*', 'rooms.room')->where('water_billing_payments.id', '=', $water_billing_payment->id)->first();

            $form_data = [
                'transaction' => 1,
                'billing_payment_id' => $water_billing_payment->id,
                'description' => $success_water_billing_payment,
            ];

            Report::create($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully added new water billing payment.', $water_billing_payment));
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

            $existing_billing = WaterBillingPayment::where('room_id', '=', $request->room_id)->where('id', '!=', $id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existing_billing) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->where('water_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenant_billing_payment) return response()->json($this->renderMessage('Error', 'You cannot update this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $tenant_billing_payment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->whereMonth('electricity_billing_date_issue', '=', $month)->whereYear('electricity_billing_date_issue', '=', $year)->first();

            $existing_tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->where('water_billing_payment_id', '=', $id)->first();

            if ($tenant_billing_payment) {

                // Set value to null.
                $existing_tenant_billing_payment->water_billing_payment_id = null;
                $existing_tenant_billing_payment->water_billing_date_issue = null;
                $existing_tenant_billing_payment->save();

                // Saving updated value.
                $tenant_billing_payment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->whereMonth('electricity_billing_date_issue', '=', $month)->whereYear('electricity_billing_date_issue', '=', $year)->first();
                $tenant_billing_payment->water_billing_payment_id = $id;
                $tenant_billing_payment->water_billing_date_issue = $request->date_issue;
                $tenant_billing_payment->save();

                // Removing data if values is null.
                $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                if ($tenant_billing_payment) $tenant_billing_payment->delete();
            } else {

                // Set value to null.
                $existing_tenant_billing_payment->water_billing_payment_id = null;
                $existing_tenant_billing_payment->water_billing_date_issue = null;
                $existing_tenant_billing_payment->save();

                // Removing data if values is null.
                $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                if ($tenant_billing_payment) $tenant_billing_payment->delete();

                // Register new data
                $form_data_tenant = [
                    'room_id' => $request->room_id,
                    'water_billing_payment_id' => $id,
                    'water_billing_date_issue' => $request->date_issue,
                ];

                TenantBillingPayment::create($form_data_tenant);
            }

            $water_billing_payment = WaterBillingPayment::findOrFail($id);
            $water_billing_payment = $water_billing_payment->update($form_data);

            $updated_water_billing_payment = WaterBillingPayment::join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*', 'rooms.room')->where('water_billing_payments.id', '=', $id)->first();

            $form_data = [
                'transaction' => 1,
                'billing_payment_id' => $id,
                'description' => $updated_water_billing_payment,
            ];

            Report::where('transaction', '=', 1)->where('billing_payment_id', '=', $id)->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this water billing payment.', $updated_water_billing_payment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $tenant_billing_payment = TenantBillingPayment::where('water_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenant_billing_payment) return response()->json($this->renderMessage('Error', 'You cannot delete this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $tenant_billing_payment = TenantBillingPayment::where('water_billing_payment_id', '=', $id)->first();

            if ($tenant_billing_payment) {

                // Set value to null.
                $tenant_billing_payment->water_billing_payment_id = null;
                $tenant_billing_payment->water_billing_date_issue = null;
                $tenant_billing_payment->save();

                // Removing data if values is null.
                $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $tenant_billing_payment->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                if ($tenant_billing_payment) $tenant_billing_payment->delete();
            }

            $water_billing_payment = WaterBillingPayment::findOrFail($id);
            $water_billing_payment->delete();

            $reports = Report::where('transaction', '=', 1)->where('billing_payment_id', '=', $id)->first();
            $reports->delete();

            return response()->json($this->renderMessage('Success', 'You have successfully delete this water billing payment.', $water_billing_payment));
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

            $tenant_billing_payments = TenantBillingPayment::whereIn('water_billing_payment_id', $request->waterBillingIds)->get();
            foreach ($tenant_billing_payments as $tenant_billing_payment) {
                if ($tenant_billing_payment->status == 0) return response()->json($this->renderMessage('Error', 'You cannot delete this billings because some tenant has already paid it.'), Response::HTTP_BAD_REQUEST);
            }

            $water_billing_payments = WaterBillingPayment::whereIn('id', $request->waterBillingIds)->get();
            foreach ($water_billing_payments as $water_billing_payment) {

                $tenant_billing_payment = TenantBillingPayment::where('tenant_billing_payments.water_billing_payment_id', '=', $water_billing_payment->id)->first();

                if ($tenant_billing_payment) {

                    $tenant_billing_payment->water_billing_payment_id = null;
                    $tenant_billing_payment->save();

                    // Set value to null.
                    $tenant_billing_payment->water_billing_payment_id = null;
                    $tenant_billing_payment->water_billing_date_issue = null;
                    $tenant_billing_payment->save();

                    // Removing data if values is null.
                    $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $tenant_billing_payment->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                    if ($tenant_billing_payment) $tenant_billing_payment->delete();
                }

                $reports = Report::where('transaction', '=', 1)->where('billing_payment_id', '=', $water_billing_payment->id)->first();
                $reports->delete();

                $water_billing_payment->delete();
            }

            return response()->json($this->renderMessage('Success', 'You have successfully delete this water billing payments.', $water_billing_payments));
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
                'waterBillingPayment' => DB::table('water_billing_payments')->join('rooms', 'water_billing_payments.room_id', '=', 'rooms.id')->select('water_billing_payments.*', 'rooms.room')->whereBetween('date_issue', $request->dateFilter)->get(),
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
