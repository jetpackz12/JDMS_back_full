<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{

    const DISABLE = 0;
    const ENABLED = 1;

    public function index()
    {
        $render_data = [
            'rooms' => DB::table('rooms')->orderBy('type')->get()
        ];

        return response()->json($render_data);
    }

    public function store(Request $request)
    {
        try {
            $form_data = $request->validate([
                'image' => 'required',
                'room' => 'required',
                'description' => 'required',
                'capacity' => 'required',
                'type' => 'required',
                'price' => 'required',
            ]);

            $room = Room::create($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully added new room.', $room));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $form_data = $request->validate([
                'image' => 'required',
                'room' => 'required',
                'description' => 'required',
                'capacity' => 'required',
                'type' => 'required',
                'price' => 'required',
            ]);

            $room = Room::where('id', '=', $id)->first();
            $room->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this room.', $room));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {

            $room = Room::where('id', '=', $id)->first();
            $room->status = $request->status === self::ENABLED ? self::DISABLE : self::ENABLED;
            $room->save();

            $status = $request->status === self::ENABLED ? 'disable' : 'enabled';

            return response()->json($this->renderMessage('Success', 'You have successfully ' . $status . ' this room.', $room));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function uploadImage(Request $request)
    {
        try {

            $request->validate([
                'image' => 'required|file|mimes:jpeg,png,jpg,gif',
            ]);

            $image = $request->file('image');
            $image_name = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('storage/room-images'), $image_name);

            return response()->json($image_name);
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
