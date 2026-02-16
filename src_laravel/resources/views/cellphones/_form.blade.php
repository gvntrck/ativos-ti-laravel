<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Phone Number -->
    <div>
        <label for="phone_number" class="block text-sm font-medium text-gray-700">Número de Telefone</label>
        <input type="text" name="phone_number" id="phone_number"
            value="{{ old('phone_number', $cellphone->phone_number ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
        @error('phone_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- Asset Code -->
    <div>
        <label for="asset_code" class="block text-sm font-medium text-gray-700">Código do Ativo</label>
        <input type="text" name="asset_code" id="asset_code"
            value="{{ old('asset_code', $cellphone->asset_code ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
        @error('asset_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- User Name -->
    <div>
        <label for="user_name" class="block text-sm font-medium text-gray-700">Usuário</label>
        <input type="text" name="user_name" id="user_name" value="{{ old('user_name', $cellphone->user_name ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
    </div>

    <!-- Status -->
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
            <option value="active" {{ old('status', $cellphone->status ?? '') == 'active' ? 'selected' : '' }}>Ativo
            </option>
            <option value="maintenance" {{ old('status', $cellphone->status ?? '') == 'maintenance' ? 'selected' : '' }}>
                Manutenção</option>
            <option value="backup" {{ old('status', $cellphone->status ?? '') == 'backup' ? 'selected' : '' }}>Backup
            </option>
            <option value="retired" {{ old('status', $cellphone->status ?? '') == 'retired' ? 'selected' : '' }}>
                Aposentado</option>
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- Brand Model -->
    <div>
        <label for="brand_model" class="block text-sm font-medium text-gray-700">Marca/Modelo</label>
        <input type="text" name="brand_model" id="brand_model"
            value="{{ old('brand_model', $cellphone->brand_model ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
    </div>

    <!-- Department -->
    <div>
        <label for="department" class="block text-sm font-medium text-gray-700">Departamento</label>
        <input type="text" name="department" id="department"
            value="{{ old('department', $cellphone->department ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
    </div>

    <!-- Property -->
    <div>
        <label for="property" class="block text-sm font-medium text-gray-700">Propriedade</label>
        <input type="text" name="property" id="property" value="{{ old('property', $cellphone->property ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
    </div>

    <!-- Notes -->
    <div class="col-span-1 md:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Observações</label>
        <textarea name="notes" id="notes" rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">{{ old('notes', $cellphone->notes ?? '') }}</textarea>
    </div>

    <!-- Photo URL -->
    <div class="col-span-1 md:col-span-2">
        <label for="photo_url" class="block text-sm font-medium text-gray-700">URL da Foto</label>
        <input type="url" name="photo_url" id="photo_url" value="{{ old('photo_url', $cellphone->photo_url ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
    </div>
</div>