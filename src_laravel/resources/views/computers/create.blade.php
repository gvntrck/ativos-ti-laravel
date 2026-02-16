@extends('layouts.app')

@section('header', 'Novo Computador')
@section('subheader', 'Cadastrar novo equipamento')

@section('content')
    <div class="max-w-3xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <form method="POST" action="{{ route('computers.store') }}">
                @csrf

                @include('computers._form')

                <div class="mt-6 flex items-center justify-end">
                    <a href="{{ route('computers.index') }}"
                        class="text-sm text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection