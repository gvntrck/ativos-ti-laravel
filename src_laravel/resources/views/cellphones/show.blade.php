@extends('layouts.app')

@section('header', $cellphone->phone_number)
@section('subheader', $cellphone->brand_model . ' - ' . $cellphone->asset_code)

@section('actions')
    <a href="{{ route('cellphones.edit', $cellphone) }}"
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition ease-in-out duration-150">
        Editar
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Detalhes do Dispositivo</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Número</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $cellphone->phone_number }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($cellphone->status == 'active') bg-green-100 text-green-800 
                                    @elseif($cellphone->status == 'maintenance') bg-yellow-100 text-yellow-800 
                                    @elseif($cellphone->status == 'backup') bg-blue-100 text-blue-800 
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($cellphone->status) }}
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Usuário Atual</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $cellphone->user_name ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Departamento</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $cellphone->department ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Código do Ativo</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $cellphone->asset_code ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Propriedade</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $cellphone->property ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Observações</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $cellphone->notes ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- History Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Histórico de Eventos</h3>

                    <!-- Add History Form -->
                    <form action="{{ route('cellphones.history.store', $cellphone) }}" method="POST"
                        class="mb-8 bg-gray-50 p-4 rounded-md">
                        @csrf
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="event_type" class="block text-sm font-medium text-gray-700">Tipo de
                                    Evento</label>
                                <select name="event_type" id="event_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                                    <option value="checkup">Checkup</option>
                                    <option value="maintenance">Manutenção</option>
                                    <option value="transfer">Transferência</option>
                                    <option value="repair">Reparo</option>
                                    <option value="other">Outro</option>
                                </select>
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                                <textarea name="description" id="description" rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                    placeholder="Descreva o que foi feito..." required></textarea>
                            </div>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Adicionar Registro
                            </button>
                        </div>
                    </form>

                    <!-- History Timeline -->
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($cellphone->history as $history)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                                aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span
                                                    class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">{{ $history->description }} <span
                                                            class="font-medium text-gray-900">({{ ucfirst($history->event_type) }})</span>
                                                    </p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    <time
                                                        datetime="{{ $history->created_at }}">{{ $history->created_at->format('d/m/Y H:i') }}</time>
                                                    <div class="text-xs">por {{ $history->user->name ?? 'Sistema' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Photo -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Foto</h3>
                    @if($cellphone->photo_url)
                        <img src="{{ $cellphone->photo_url }}" alt="Foto do celular" class="w-full h-auto rounded-lg shadow-sm">
                    @else
                        <div
                            class="flex items-center justify-center h-48 bg-gray-100 rounded-lg border-2 border-dashed border-gray-300">
                            <span class="text-gray-400">Sem foto</span>
                        </div>
                    @endif
                    <div class="mt-4">
                        <p class="text-xs text-gray-500">Para atualizar a foto, edite o cadastro.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection