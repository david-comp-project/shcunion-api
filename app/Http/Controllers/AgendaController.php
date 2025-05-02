<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgendaController extends Controller
{
    //
    public function getAllAgendas(User $user) {
        // $agendas = Agenda::latest()->get();
        // return response()->json([
           
        //     'agendas' => $agendas
        // ], 200);

        $agendas = $user->agendas()->latest()->get();

        if ($agendas->isEmpty()) {
            return response()->json([
                'message' => 'User Tidak Memiliki Agenda Apapun',
                'agendas' => []
            ], 404);
        }
        
        $agendaList = $agendas->map(function ($agenda) {
            return [
                'agenda_id' => $agenda->agenda_id,
                'category' => $agenda->category,
                'tanggal_agenda' => $agenda->tanggal_agenda,
                'waktu_agenda' => $agenda->waktu_agenda,
                'deskripsi' => $agenda->description,
                'completed' => $agenda->is_completed
            ];
        });

        return response()->json([
            'message' => 'Record Agenda Berhasil Diambil',
            'agendas' => $agendaList
        ], 200);
    }

    public function storeAgenda(Request $request, User $user) {
        try {
            $validator = Validator::make($request->all(), [
                'tanggal_agenda' => 'required|date',
                'description' => 'required|string',
                'is_completed' => 'nullable|boolean',
                'category' => 'required|string',
                'waktu_agenda' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();
            $validated['user_id'] = $user->user_id;

            $agenda = Agenda::create($validated);

            return response()->json(["message" => "Store Succesfully"], 200);


        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error Store Agenda',
                'error' => $th->getMessage()
            ], 400);
        }

    }
}
