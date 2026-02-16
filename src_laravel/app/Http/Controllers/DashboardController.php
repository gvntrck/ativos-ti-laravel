<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Cellphone;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalComputers = Computer::where('deleted', false)->count();
        $totalCellphones = Cellphone::where('deleted', false)->count();

        // Add more stats as needed

        return view('dashboard', compact('totalComputers', 'totalCellphones'));
    }
}
