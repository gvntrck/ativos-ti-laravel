@extends('layouts.app')

@section('header', 'Dashboard')
@section('subheader', 'Visão Geral')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="mb-2 text-sm font-medium text-gray-600">Computadores Ativos</p>
                        <p class="text-3xl font-semibold text-gray-700">{{ $totalComputers }}</p>
                        <a href="{{ route('computers.index') }}"
                            class="text-sm text-indigo-600 hover:text-indigo-900 mt-2 inline-block">Ver todos &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="mb-2 text-sm font-medium text-gray-600">Celulares Ativos</p>
                        <p class="text-3xl font-semibold text-gray-700">{{ $totalCellphones }}</p>
                        <a href="{{ route('cellphones.index') }}"
                            class="text-sm text-green-600 hover:text-green-900 mt-2 inline-block">Ver todos &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection