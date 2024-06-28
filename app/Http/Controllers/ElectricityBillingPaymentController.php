<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\TenantBillingPayment;
use App\Models\ElectricityBillingPayment;
use Illuminate\Support\Facades\Log;

class ElectricityBillingPaymentController extends Controller
{

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'electricityBillingPayment' => DB::table('electricity_billing_payments')->join('rooms', 'electricity_billing_payments.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', 'rooms.room')->whereMonth('date_issue', '=', $month)->get(),
            'rooms' => DB::table('rooms')->get(),
        ];

        return response()->json($render_data);
    }

    public function store(Request $request)
    {
        try {

            $form_data = $request->validate([
                'room_id' => 'required',
                'unit_con' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $month = Carbon::parse($request->date_issue)->format('m');
            $year = Carbon::parse($request->date_issue)->format('Y');

            $existing_billing = ElectricityBillingPayment::where('room_id', '=', $request->room_id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existing_billing) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $electricity_billing_payment = ElectricityBillingPayment::create($form_data);

            $tenant_billing_payment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->where('tenant_billing_payments.electricity_billing_payment_id', '=', null)->whereMonth('water_billing_date_issue', '=', $month)->whereYear('water_billing_date_issue', '=', $year)->first();

            if ($tenant_billing_payment) {

                $tenant_billing_payment->electricity_billing_payment_id = $electricity_billing_payment->id;
                $tenant_billing_payment->electricity_billing_date_issue = $request->date_issue;
                $tenant_billing_payment->save();
            } else {

                $form_data = [
                    'room_id' => $request->room_id,
                    'electricity_billing_payment_id' => $electricity_billing_payment->id,
                    'electricity_billing_date_issue' => $request->date_issue,
                ];

                TenantBillingPayment::create($form_data);
            }

            $success_electricity_billing_payment = ElectricityBillingPayment::join('rooms', 'electricity_billing_payments.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', 'rooms.room')->where('electricity_billing_payments.id', '=', $electricity_billing_payment->id)->first();

            $form_data = [
                'transaction' => 2,
                'billing_payment_id' => $electricity_billing_payment->id,
                'description' => $success_electricity_billing_payment,
            ];

            Report::create($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully added new electricity billing payment.', $electricity_billing_payment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $form_data = $request->validate([
                'room_id' => 'required',
                'unit_con' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $month = Carbon::parse($request->date_issue)->format('m');
            $year = Carbon::parse($request->date_issue)->format('Y');

            $existing_billing = ElectricityBillingPayment::where('room_id', '=', $request->room_id)->where('id', '!=', $id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existing_billing) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->where('electricity_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenant_billing_payment) return response()->json($this->renderMessage('Error', 'You cannot update this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $tenant_billing_payment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->whereMonth('water_billing_date_issue', '=', $month)->whereYear('water_billing_date_issue', '=', $year)->first();

            $existing_tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->where('electricity_billing_payment_id', '=', $id)->first();

            if ($tenant_billing_payment) {

                // Set value to null.
                $existing_tenant_billing_payment->electricity_billing_payment_id = null;
                $existing_tenant_billing_payment->electricity_billing_date_issue = null;
                $existing_tenant_billing_payment->save();

                // Saving updated value.
                $tenant_billing_payment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->whereMonth('water_billing_date_issue', '=', $month)->whereYear('water_billing_date_issue', '=', $year)->first();
                $tenant_billing_payment->electricity_billing_payment_id = $id;
                $tenant_billing_payment->electricity_billing_date_issue = $request->date_issue;
                $tenant_billing_payment->save();

                // Removing data if values is null.
                $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                if ($tenant_billing_payment) $tenant_billing_payment->delete();
            } else {

                // Set value to null.
                $existing_tenant_billing_payment->electricity_billing_payment_id = null;
                $existing_tenant_billing_payment->electricity_billing_date_issue = null;
                $existing_tenant_billing_payment->save();

                // Removing data if values is null.
                $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $request->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                if ($tenant_billing_payment) $tenant_billing_payment->delete();

                // Register new data
                $form_data_tenant = [
                    'room_id' => $request->room_id,
                    'electricity_billing_payment_id' => $id,
                    'electricity_billing_date_issue' => $request->date_issue,
                ];

                TenantBillingPayment::create($form_data_tenant);
            }


            $electricity_billing_payment = ElectricityBillingPayment::findOrFail($id);
            $electricity_billing_payment->update($form_data);

            $updated_electricity_billing_payment = ElectricityBillingPayment::join('rooms', 'electricity_billing_payments.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', 'rooms.room')->where('electricity_billing_payments.id', '=', $id)->first();

            $form_data = [
                'transaction' => 2,
                'billing_payment_id' => $id,
                'description' => $updated_electricity_billing_payment,
            ];

            Report::where('transaction', '=', 2)->where('billing_payment_id', '=', $id)->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this electricity billing payment.', $updated_electricity_billing_payment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {

            $tenant_billing_payment = TenantBillingPayment::where('water_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenant_billing_payment) return response()->json($this->renderMessage('Error', 'You cannot delete this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $tenant_billing_payment = TenantBillingPayment::where('electricity_billing_payment_id', '=', $id)->first();

            if ($tenant_billing_payment) {

                // Set value to null.
                $tenant_billing_payment->electricity_billing_payment_id = null;
                $tenant_billing_payment->electricity_billing_date_issue = null;
                $tenant_billing_payment->save();

                // Removing data if values is null.
                $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $tenant_billing_payment->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                if ($tenant_billing_payment) $tenant_billing_payment->delete();
            }

            $electricity_billing_payment = ElectricityBillingPayment::findOrFail($id);
            $electricity_billing_payment->delete();

            $reports = Report::where('transaction', '=', 2)->where('billing_payment_id', '=', $id)->first();
            $reports->delete();

            return response()->json($this->renderMessage('Success', 'You have successfully delete this electricity billing payment.', $electricity_billing_payment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroys(Request $request)
    {
        try {

            $request->validate([
                'electricityBillingIds' => 'required|array',
            ]);

            $tenant_billing_payments = TenantBillingPayment::whereIn('electricity_billing_payment_id', $request->electricityBillingIds)->get();
            foreach ($tenant_billing_payments as $tenant_billing_payment) {
                if ($tenant_billing_payment->status == 0) return response()->json($this->renderMessage('Error', 'You cannot delete this billings because some tenant has already paid it.'), Response::HTTP_BAD_REQUEST);
            }

            $electricity_billing_payments = ElectricityBillingPayment::whereIn('id', $request->electricityBillingIds)->get();
            foreach ($electricity_billing_payments as $electricity_billing_payment) {

                $tenant_billing_payment = TenantBillingPayment::where('electricity_billing_payment_id', '=', $electricity_billing_payment->id)->first();

                if ($tenant_billing_payment) {

                    // Set value to null.
                    $tenant_billing_payment->electricity_billing_payment_id = null;
                    $tenant_billing_payment->electricity_billing_date_issue = null;
                    $tenant_billing_payment->save();

                    // Removing data if values is null.
                    $tenant_billing_payment = TenantBillingPayment::where('room_id', '=', $tenant_billing_payment->room_id)->whereNull('water_billing_payment_id')->whereNull('water_billing_date_issue')->whereNull('electricity_billing_payment_id')->whereNull('electricity_billing_date_issue')->first();

                    if ($tenant_billing_payment) $tenant_billing_payment->delete();
                }

                $reports = Report::where('transaction', '=', 2)->where('billing_payment_id', '=', $electricity_billing_payment->id)->first();
                $reports->delete();

                $electricity_billing_payment->delete();
            }

            return response()->json($this->renderMessage('Success', 'You have successfully delete this electricity billing payments.', $electricity_billing_payments));
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
                'electricityBillingPayment' => DB::table('electricity_billing_payments')->join('rooms', 'electricity_billing_payments.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', 'rooms.room')->whereBetween('date_issue', $request->dateFilter)->get(),
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
