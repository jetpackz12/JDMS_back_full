<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\ElectricityBillingPayment;

class ElectricityBillingPaymentController extends Controller
{

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'electricityBillingPayment' => DB::table('electricity_billing_payments')->join('tenants', 'electricity_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room')->whereMonth('date_issue', '=', $month)->get(),
            'tenants' => DB::table('tenants')->select('tenants.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS full_name"))->get(),
        ];

        return response()->json($render_data);
    }

    public function store(Request $request)
    {
        try {

            $form_data = $request->validate([
                'tenant_id' => 'required',
                'unit_con' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $month = Carbon::now()->format('m');
            $year = Carbon::now()->format('Y');

            $existingBilling = ElectricityBillingPayment::where('tenant_id', '=', $request->tenant_id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existingBilling) {
                return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);
            }

            $electricityBillingPayment = ElectricityBillingPayment::create($form_data);

            $successElectricityBillingPayment = ElectricityBillingPayment::join('tenants', 'electricity_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room')->where('electricity_billing_payments.id', '=', $electricityBillingPayment->id)->first();

            $form_data = [
                'transaction' => 2,
                'billing_payment_id' => $electricityBillingPayment->id,
                'description' => $successElectricityBillingPayment,
            ];

            Report::create($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully added new electricity billing payment.', $electricityBillingPayment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $form_data = $request->validate([
                'tenant_id' => 'required',
                'unit_con' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $month = Carbon::now()->format('m');
            $year = Carbon::now()->format('Y');

            $existingBilling = ElectricityBillingPayment::where('tenant_id', '=', $request->tenant_id)->where('id', '!=', $id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existingBilling) {
                return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);
            }

            $electricityBillingPayment = ElectricityBillingPayment::findOrFail($id);

            $electricityBillingPayment = $electricityBillingPayment->update($form_data);

            $updatedElectricityBillingPayment = ElectricityBillingPayment::join('tenants', 'electricity_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS tenant"), 'rooms.room')->where('electricity_billing_payments.id', '=', $id)->first();

            $form_data = [
                'transaction' => 2,
                'billing_payment_id' => $id,
                'description' => $updatedElectricityBillingPayment,
            ];

            Report::where('transaction', '=', 2)->where('billing_payment_id', '=', $id)->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this electricity billing payment.', $electricityBillingPayment));
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
                'electricityBillingPayment' => DB::table('electricity_billing_payments')->join('tenants', 'electricity_billing_payments.tenant_id', '=', 'tenants.id')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS full_name"), 'rooms.room')->whereBetween('date_issue', $request->dateFilter)->get(),
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
