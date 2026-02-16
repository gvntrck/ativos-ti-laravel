<?php

namespace App\Http\Controllers;

use App\Models\Cellphone;
use Illuminate\Http\Request;

class CellphoneController extends Controller
{
    public function index(Request $request)
    {
        $query = Cellphone::where('deleted', false);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cellphones = $query->paginate(20);

        return view('cellphones.index', compact('cellphones'));
    }

    public function create()
    {
        return view('cellphones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code' => 'nullable|string|max:20|unique:cellphones',
            'phone_number' => 'nullable|string|max:30',
            'status' => 'required|string|max:20',
            'user_name' => 'nullable|string|max:100',
            'brand_model' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:100',
            'property' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'photo_url' => 'nullable|url',
        ]);

        Cellphone::create($validated);

        return redirect()->route('cellphones.index')->with('success', 'Celular cadastrado com sucesso.');
    }

    public function show(Cellphone $cellphone)
    {
        $cellphone->load('history.user');
        return view('cellphones.show', compact('cellphone'));
    }

    public function edit(Cellphone $cellphone)
    {
        return view('cellphones.edit', compact('cellphone'));
    }

    public function update(Request $request, Cellphone $cellphone)
    {
        $validated = $request->validate([
            'asset_code' => 'nullable|string|max:20|unique:cellphones,asset_code,' . $cellphone->id,
            'phone_number' => 'nullable|string|max:30',
            'status' => 'required|string|max:20',
            'user_name' => 'nullable|string|max:100',
            'brand_model' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:100',
            'property' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'photo_url' => 'nullable|url',
        ]);

        $cellphone->update($validated);

        return redirect()->route('cellphones.show', $cellphone)->with('success', 'Celular atualizado com sucesso.');
    }

    public function destroy(Cellphone $cellphone)
    {
        $cellphone->update(['deleted' => true]);
        return redirect()->route('cellphones.index')->with('success', 'Celular movido para lixeira.');
    }
}
