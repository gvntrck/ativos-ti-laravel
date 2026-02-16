<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Cellphone;
use App\Models\ComputerHistory;
use App\Models\CellphoneHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    public function storeComputerHistory(Request $request, Computer $computer)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'description' => 'required|string',
            'photos' => 'nullable|string', // Assuming stored as text/json or url
        ]);

        ComputerHistory::create([
            'computer_id' => $computer->id,
            'event_type' => $validated['event_type'],
            'description' => $validated['description'],
            'photos' => $validated['photos'],
            'user_id' => Auth::id(), // Ensure user is authenticated
            'created_at' => now(),
        ]);

        return redirect()->route('computers.show', $computer)->with('success', 'Histórico adicionado.');
    }

    public function storeCellphoneHistory(Request $request, Cellphone $cellphone)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'description' => 'required|string',
            'photos' => 'nullable|string',
        ]);

        CellphoneHistory::create([
            'cellphone_id' => $cellphone->id,
            'event_type' => $validated['event_type'],
            'description' => $validated['description'],
            'photos' => $validated['photos'],
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('cellphones.show', $cellphone)->with('success', 'Histórico adicionado.');
    }
}
