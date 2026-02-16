<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComputerController extends Controller
{
    public function index(Request $request)
    {
        $query = Computer::where('deleted', false);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $computers = $query->paginate(20);

        return view('computers.index', compact('computers'));
    }

    public function create()
    {
        return view('computers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:desktop,notebook',
            'hostname' => 'required|string|max:100',
            'status' => 'required|string|max:20',
            'user_name' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'property' => 'nullable|string|max:20',
            'specs' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo_url' => 'nullable|url',
        ]);

        Computer::create($validated);

        return redirect()->route('computers.index')->with('success', 'Computador cadastrado com sucesso.');
    }

    public function show(Computer $computer)
    {
        $computer->load('history.user');
        return view('computers.show', compact('computer'));
    }

    public function edit(Computer $computer)
    {
        return view('computers.edit', compact('computer'));
    }

    public function update(Request $request, Computer $computer)
    {
        $validated = $request->validate([
            'type' => 'required|in:desktop,notebook',
            'hostname' => 'required|string|max:100',
            'status' => 'required|string|max:20',
            'user_name' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'property' => 'nullable|string|max:20',
            'specs' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo_url' => 'nullable|url',
        ]);

        $computer->update($validated);

        // Log update in history if needed? The legacy code had complex logging logic.
        // For now we just update.

        return redirect()->route('computers.show', $computer)->with('success', 'Computador atualizado com sucesso.');
    }

    public function destroy(Computer $computer)
    {
        $computer->update(['deleted' => true]);
        // Or $computer->delete(); if using SoftDeletes trait

        return redirect()->route('computers.index')->with('success', 'Computador movido para lixeira.');
    }
}
