<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{

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
            $status = $request->status === 1 ? 'disable' : 'enabled';

            $room = Room::where('id', '=', $id)->first();
            $room->status = $request->status === 1 ? 0 : 1;
            $room->save();

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
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('storage/room-images'), $imageName);

            return response()->json($imageName);
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
