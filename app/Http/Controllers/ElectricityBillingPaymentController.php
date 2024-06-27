<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\TenantBillingPayment;
use App\Models\ElectricityBillingPayment;

class ElectricityBillingPaymentController extends Controller
{

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'electricityBillingPayment' => DB::table('electricity_billing_payments')->join('rooms', 'electricity_billing_payments.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', 'rooms.room')->whereMonth('date_issue', '=', $month)->get(),
            'rooms' => DB::table('rooms')->where('availability', '=', 1)->get(),
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

            $existingBilling = ElectricityBillingPayment::where('room_id', '=', $request->room_id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existingBilling) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $electricityBillingPayment = ElectricityBillingPayment::create($form_data);
            
            $tenantBillingPayment = TenantBillingPayment::where('tenant_billing_payments.room_id', '=', $request->room_id)->where('tenant_billing_payments.electricity_billing_payment_id', '=', null)->first();

            if ($tenantBillingPayment) {

                $tenantBillingPayment->electricity_billing_payment_id = $electricityBillingPayment->id;
                $tenantBillingPayment->save();
                
            } else {

                $form_data = [
                    'room_id' => $request->room_id,
                    'electricity_billing_payment_id' => $electricityBillingPayment->id,
                ];
    
                TenantBillingPayment::create($form_data);
            }

            $successElectricityBillingPayment = ElectricityBillingPayment::join('rooms', 'electricity_billing_payments.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', 'rooms.room')->where('electricity_billing_payments.id', '=', $electricityBillingPayment->id)->first();

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
                'room_id' => 'required',
                'unit_con' => 'required',
                'amount' => 'required',
                'due_date' => 'required',
                'date_issue' => 'required',
            ]);

            $month = Carbon::parse($request->date_issue)->format('m');
            $year = Carbon::parse($request->date_issue)->format('Y');

            $existingBilling = ElectricityBillingPayment::where('room_id', '=', $request->room_id)->where('id', '!=', $id)->whereMonth('date_issue', '=', $month)->whereYear('date_issue', '=', $year)->first();

            if ($existingBilling) return response()->json($this->renderMessage('Error', 'You have already issued billing for this tenant'), Response::HTTP_BAD_REQUEST);

            $electricityBillingPayment = ElectricityBillingPayment::findOrFail($id);

            $tenantBillingPayment = TenantBillingPayment::where('room_id', '=', $request->room_id)->where('water_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenantBillingPayment) return response()->json($this->renderMessage('Error', 'You cannot update this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $electricityBillingPayment = $electricityBillingPayment->update($form_data);

            $updatedElectricityBillingPayment = ElectricityBillingPayment::join('rooms', 'electricity_billing_payments.room_id', '=', 'rooms.id')->select('electricity_billing_payments.*', 'rooms.room')->where('electricity_billing_payments.id', '=', $id)->first();

            $form_data = [
                'transaction' => 2,
                'billing_payment_id' => $id,
                'description' => $updatedElectricityBillingPayment,
            ];

            Report::where('transaction', '=', 2)->where('billing_payment_id', '=', $id)->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this electricity billing payment.', $updatedElectricityBillingPayment));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {

            $tenantBillingPayment = TenantBillingPayment::where('water_billing_payment_id', '=', $id)->where('status', '=', 0)->first();

            if ($tenantBillingPayment) return response()->json($this->renderMessage('Error', 'You cannot delete this billing because the tenant has already paid it.'), Response::HTTP_BAD_REQUEST);

            $tenantBillingPayment = TenantBillingPayment::where('electricity_billing_payment_id', '=', $id)->first();

            if ($tenantBillingPayment) {

                $tenantBillingPayment->electricity_billing_payment_id = null;
                $tenantBillingPayment->save();
            }

            $electricityBillingPayment = ElectricityBillingPayment::findOrFail($id);
            $electricityBillingPayment->delete();

            $reports = Report::where('transaction', '=', 2)->where('billing_payment_id', '=', $id)->first();
            $reports->delete();

            return response()->json($this->renderMessage('Success', 'You have successfully delete this electricity billing payment.', $electricityBillingPayment));
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

            $tenantBillingPayments = TenantBillingPayment::whereIn('electricity_billing_payment_id', $request->electricityBillingIds)->get();
            foreach ($tenantBillingPayments as $tenantBillingPayment) {
                if ($tenantBillingPayment->status == 0) return response()->json($this->renderMessage('Error', 'You cannot delete this billings because some tenant has already paid it.'), Response::HTTP_BAD_REQUEST);
             }

            $electricityBillingPayments = ElectricityBillingPayment::whereIn('id', $request->electricityBillingIds)->get();
            foreach ($electricityBillingPayments as $waterBillingPayment) {

                $tenantBillingPayment = TenantBillingPayment::where('electricity_billing_payment_id', '=', $waterBillingPayment->id)->first();

                if ($tenantBillingPayment) {

                    $tenantBillingPayment->electricity_billing_payment_id = null;
                    $tenantBillingPayment->save();
                }

                $reports = Report::where('transaction', '=', 2)->where('billing_payment_id', '=', $waterBillingPayment->id)->first();
                $reports->delete();

                $waterBillingPayment->delete();
            }

            return response()->json($this->renderMessage('Success', 'You have successfully delete this electricity billing payments.', $waterBillingPayment));
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
