<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{

    public function index()
    {
        $month = Carbon::now()->format('m');

        $render_data = [
            'reports' => DB::table('reports')->whereMonth('created_at', '=', $month)->orderBy('transaction')->get()->map(function ($payment) {
                $payment->description = json_decode($payment->description);
                return $payment;
            }),
        ];

        return response()->json($render_data);
    }

    public function dateFilter(Request $request)
    {
        try {

            $request->validate([
                'transaction' => 'required',
                'dateFilter' => 'required|array',
            ]);

            if ($request->transaction === 0) {
                $render_data = [
                    'reports' => DB::table('reports')->whereBetween('created_at', $request->dateFilter)->orderBy('transaction')->get()->map(function ($payment) {
                        $payment->description = json_decode($payment->description);
                        return $payment;
                    }),
                ];

                return response()->json($this->renderMessage('Success', 'You have successfully filter this reports.', $render_data));
            }

            $render_data = [
                'reports' => DB::table('reports')->where('transaction', '=',  $request->transaction)->whereBetween('created_at', $request->dateFilter)->get()->map(function ($payment) {
                    $payment->description = json_decode($payment->description);
                    return $payment;
                }),
            ];

            return response()->json($this->renderMessage('Success', 'You have successfully filter this reports.', $render_data));
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
