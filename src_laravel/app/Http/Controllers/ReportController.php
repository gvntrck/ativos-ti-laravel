<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Cellphone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function exportComputers()
    {
        $computers = Computer::all();
        $csvFileName = 'computadores_' . date('Y-m-d_H-i') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($computers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Type', 'Hostname', 'Status', 'User', 'Location', 'Property', 'Specs', 'Notes', 'Created At']);

            foreach ($computers as $computer) {
                fputcsv($file, [
                    $computer->id,
                    $computer->type,
                    $computer->hostname,
                    $computer->status,
                    $computer->user_name,
                    $computer->location,
                    $computer->property,
                    $computer->specs,
                    $computer->notes,
                    $computer->created_at
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportCellphones()
    {
        $cellphones = Cellphone::all();
        $csvFileName = 'celulares_' . date('Y-m-d_H-i') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($cellphones) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Phone Number', 'Asset Code', 'Status', 'User', 'Brand/Model', 'Department', 'Property', 'Notes', 'Created At']);

            foreach ($cellphones as $phone) {
                fputcsv($file, [
                    $phone->id,
                    $phone->phone_number,
                    $phone->asset_code,
                    $phone->status,
                    $phone->user_name,
                    $phone->brand_model,
                    $phone->department,
                    $phone->property,
                    $phone->notes,
                    $phone->created_at
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
