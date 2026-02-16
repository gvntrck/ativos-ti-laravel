@extends('layouts.app')

@section('header', 'Entrar')

@section('content')
    <div class="min-h-[50vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email"
                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        type="email" name="email" value="{{ old('email') }}" required autofocus />
                    @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                    <input id="password"
                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        type="password" name="password" required autocomplete="current-password" />
                    @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Remember Me -->
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50"
                            name="remember">
                        <span class="ml-2 text-sm text-gray-600">Lembrar de mim</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button
                        class="ml-3 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Entrar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection