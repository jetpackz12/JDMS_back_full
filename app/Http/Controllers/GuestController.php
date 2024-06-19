<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    const ROOM_OCCUPIED = 0;
    const ROOM_VACANT = 1;
    const DISABLE = 0;
    const ENABLED = 1;

    public function index()
    {
        $render_data = [
            'guests' => DB::table('guests')->join('rooms', 'guests.room_id', '=', 'rooms.id')->select('guests.*', DB::raw("CONCAT(guests.first_name, ' ', guests.middle_name, ' ', guests.last_name) AS full_name"), 'rooms.room')->get(),
            'rooms' => DB::table('rooms')->orderBy('type')->get()
        ];

        return response()->json($render_data);
    }

    public function store(Request $request)
    {
        try {

            $form_data = $request->validate([
                'room_id' => 'required',
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required',
                'address' => 'required',
                'contact_number' => 'required',
                'duration' => 'required',
                'payment' => 'required',
            ]);

            $guest = Guest::create($form_data);

            $room = Room::findOrFail($guest->room_id);
            $room->availability = self::ROOM_OCCUPIED;
            $room->save();

            return response()->json($this->renderMessage('Success', 'You have successfully added new guest.', $guest));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $form_data = $request->validate([
                'room_id' => 'required',
                'first_name' => 'required',
                'middle_name' => 'required',
                'last_name' => 'required',
                'address' => 'required',
                'contact_number' => 'required',
                'duration' => 'required',
                'payment' => 'required',
            ]);

            $guest = Guest::findOrFail($id);

            $room = Room::findOrFail($guest->room_id);
            $room->availability = self::ROOM_VACANT;
            $room->save();

            $room = Room::findOrFail($request->room_id);
            $room->availability = self::ROOM_OCCUPIED;
            $room->save();

            $guest->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this guest.', $guest));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {

            $guest = Guest::findOrFail($id);
            $guest->status = self::DISABLE;
            $guest->save();

            $room = Room::findOrFail($guest->room_id);
            $room->availability = self::ENABLED;
            $room->save();

            return response()->json($this->renderMessage('Success', 'You have successfully delete this guest.', $guest));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroys(Request $request)
    {
        try {

            $request->validate([
                'guestIds' => 'required|array',
            ]);

            $guests = Guest::whereIn('id', $request->guestIds)->get();
            foreach ($guests as $guest) {
                $guest->status = self::DISABLE;
                $guest->save();

                $room = Room::find($guest->room_id);
                if ($room) {
                    $room->availability = self::ENABLED;
                    $room->save();
                }
            }

            return response()->json($this->renderMessage('Success', 'You have successfully delete this guests.', $guest));
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
