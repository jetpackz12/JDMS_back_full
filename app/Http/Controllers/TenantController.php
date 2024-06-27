<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    const ROOM_OCCUPIED = 0;
    const ROOM_VACANT = 1;
    const DISABLE = 0;
    const ENABLED = 1;

    public function index()
    {
        $render_data = [
            'tenants' => DB::table('tenants')->join('rooms', 'tenants.room_id', '=', 'rooms.id')->select('tenants.*', DB::raw("CONCAT(tenants.first_name, ' ', tenants.middle_name, ' ', tenants.last_name) AS full_name"), 'rooms.room')->get(),
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
                'advance' => 'required',
            ]);

            $room = Room::findOrFail($request->room_id);

            if ($room->occupies >= $room->capacity) {
                return response()->json($this->renderMessage('Error', 'You cannot asign a tenant on this room. This room is already full.'));
            } else {
                $tenant = Tenant::create($form_data);
                $room->occupies = $room->occupies + 1;
                $room->save();

                if ($room->occupies === $room->capacity) {
                    $room->availability = self::ROOM_OCCUPIED;
                    $room->save();
                }
            }

            return response()->json($this->renderMessage('Success', 'You have successfully added new tenant.', $tenant));
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
                'advance' => 'required',
            ]);

            $select_room = Room::findOrFail($request->room_id);

            if ($select_room->occupies >= $select_room->capacity) return response()->json($this->renderMessage('Error', 'You cannot asign a tenant on this room. This room is already full.'));

            $tenant = Tenant::findOrFail($id);

            $current_room = Room::findOrFail($tenant->room_id);

            if ($current_room->availability === self::ROOM_OCCUPIED) {
                $current_room->availability = self::ROOM_VACANT;
                $current_room->save();
            }

            if ($current_room->occupies > 0) {
                $current_room->occupies = $current_room->occupies - 1;
                $current_room->save();
            }

            $select_room->occupies = $select_room->occupies + 1;
            $select_room->save();

            if ($select_room->occupies === $select_room->capacity) {
                $select_room->availability = self::ROOM_OCCUPIED;
                $select_room->save();
            }

            $tenant->update($form_data);

            return response()->json($this->renderMessage('Success', 'You have successfully updated this tenant.', $tenant));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {

            $tenant = Tenant::findOrFail($id);

            $room = Room::findOrFail($tenant->room_id);

            if ($room->availability === self::ROOM_OCCUPIED) {
                $room->availability = self::ROOM_VACANT;
                $room->save();
            }

            if ($room->occupies > 0) {
                $room->occupies = $room->occupies - 1;
                $room->save();
            }

            $tenant->status = self::DISABLE;
            $tenant->save();

            return response()->json($this->renderMessage('Success', 'You have successfully delete this tenant.', $tenant));
        } catch (\Throwable $th) {
            return response()->json($this->renderMessage('Error', 'An error occurred: ' . $th->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroys(Request $request)
    {
        try {

            $request->validate([
                'tenantIds' => 'required|array',
            ]);

            $tenants = Tenant::whereIn('id', $request->tenantIds)->get();
            foreach ($tenants as $tenant) {

                $room = Room::findOrFail($tenant->room_id);

                if ($room->availability === self::ROOM_OCCUPIED) {
                    $room->availability = self::ROOM_VACANT;
                    $room->save();
                }

                if ($room->occupies > 0) {
                    $room->occupies = $room->occupies - 1;
                    $room->save();
                }

                $tenant->status = self::DISABLE;
                $tenant->save();
            }

            return response()->json($this->renderMessage('Success', 'You have successfully delete this tenants.', $tenant));
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
