<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Tipo -->
    <div>
        <label for="type" class="block text-sm font-medium text-gray-700">Tipo</label>
        <select name="type" id="type"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="desktop" {{ old('type', $computer->type ?? '') == 'desktop' ? 'selected' : '' }}>Desktop
            </option>
            <option value="notebook" {{ old('type', $computer->type ?? '') == 'notebook' ? 'selected' : '' }}>Notebook
            </option>
        </select>
        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- Hostname -->
    <div>
        <label for="hostname" class="block text-sm font-medium text-gray-700">Hostname</label>
        <input type="text" name="hostname" id="hostname" value="{{ old('hostname', $computer->hostname ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        @error('hostname') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- User Name -->
    <div>
        <label for="user_name" class="block text-sm font-medium text-gray-700">Usuário</label>
        <input type="text" name="user_name" id="user_name" value="{{ old('user_name', $computer->user_name ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        @error('user_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- Status -->
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="active" {{ old('status', $computer->status ?? '') == 'active' ? 'selected' : '' }}>Ativo
            </option>
            <option value="maintenance" {{ old('status', $computer->status ?? '') == 'maintenance' ? 'selected' : '' }}>
                Manutenção</option>
            <option value="backup" {{ old('status', $computer->status ?? '') == 'backup' ? 'selected' : '' }}>Backup
            </option>
            <option value="retired" {{ old('status', $computer->status ?? '') == 'retired' ? 'selected' : '' }}>Aposentado
            </option>
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- Location -->
    <div>
        <label for="location" class="block text-sm font-medium text-gray-700">Localização</label>
        <input type="text" name="location" id="location" value="{{ old('location', $computer->location ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>

    <!-- Property -->
    <div>
        <label for="property" class="block text-sm font-medium text-gray-700">Patrimônio/Propriedade</label>
        <input type="text" name="property" id="property" value="{{ old('property', $computer->property ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>

    <!-- Specs -->
    <div class="col-span-1 md:col-span-2">
        <label for="specs" class="block text-sm font-medium text-gray-700">Especificações</label>
        <textarea name="specs" id="specs" rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('specs', $computer->specs ?? '') }}</textarea>
    </div>

    <!-- Notes -->
    <div class="col-span-1 md:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Observações</label>
        <textarea name="notes" id="notes" rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('notes', $computer->notes ?? '') }}</textarea>
    </div>

    <!-- Photo URL -->
    <div class="col-span-1 md:col-span-2">
        <label for="photo_url" class="block text-sm font-medium text-gray-700">URL da Foto</label>
        <input type="url" name="photo_url" id="photo_url" value="{{ old('photo_url', $computer->photo_url ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>
</div>