@extends('layouts.app')

@section('header', 'Novo Celular')
@section('subheader', 'Cadastrar novo dispositivo')

@section('content')
    <div class="max-w-3xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <form method="POST" action="{{ route('cellphones.store') }}">
                @csrf

                @include('cellphones._form')

                <div class="mt-6 flex items-center justify-end">
                    <a href="{{ route('cellphones.index') }}"
                        class="text-sm text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection