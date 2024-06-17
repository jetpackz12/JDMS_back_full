<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $render_data = [
            "users" => DB::table('users')->get()
        ];

        return response()->json($render_data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function login(Request $request)
    {
        try {

            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) return response()->json($this->renderMessage('Error', 'Invalid credentials'), Response::HTTP_UNAUTHORIZED);

            $token = $user->createToken('auth_token', ['*'], now()->addDays(2))->plainTextToken;

            $render_data = ['token' => $token, 'user' => $user];

            return response()->json($this->renderMessage('Success', 'Login success.', $render_data));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkAuth(Request $request) 
    {
        try {

            $request->validate([
                'token' => 'required',
            ]);

            $arr_token = explode('|', $request->token);

            $user = PersonalAccessToken::join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')->select('users.*')->where('personal_access_tokens.id', '=', $arr_token[0])->first();

            $render_data = ['user' => $user];

            return response()->json($this->renderMessage('Authenticated', 'User is authenticate.', $render_data));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logout(Request $request) 
    {
        try {

            $request->validate([
                'token' => 'required',
            ]);

            $arr_token = explode('|', $request->token);

            PersonalAccessToken::where('id', '=', $arr_token[0])->delete();

            return response()->json($this->renderMessage('Deleted Token', 'Token is deleted successfully.'));
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
