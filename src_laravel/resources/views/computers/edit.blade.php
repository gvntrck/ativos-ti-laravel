@extends('layouts.app')

@section('header', 'Editar Computador')
@section('subheader', $computer->hostname)

@section('content')
    <div class="max-w-3xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <form method="POST" action="{{ route('computers.update', $computer) }}">
                @csrf
                @method('PUT')

                @include('computers._form')

                <div class="mt-6 flex items-center justify-end">
                    <a href="{{ route('computers.show', $computer) }}"
                        class="text-sm text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-8 max-w-3xl mx-auto">
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-red-800">Zona de Perigo</h3>
            <p class="mt-1 text-sm text-red-600">Excluir este computador removerá o registro da lista ativa.</p>
            <form method="POST" action="{{ route('computers.destroy', $computer) }}" class="mt-4"
                onsubmit="return confirm('Tem certeza que deseja excluir este computador?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="text-red-600 border border-red-600 px-4 py-2 rounded-md text-sm font-medium hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Excluir Computador
                </button>
            </form>
        </div>
    </div>
@endsection