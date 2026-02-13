<?php
$can_edit = isset($can_edit) ? (bool) $can_edit : false;
$current_module = isset($current_module) ? (string) $current_module : 'computers';
$module_config = isset($module_config) && is_array($module_config) ? $module_config : [];
$status_labels = isset($status_labels) && is_array($status_labels) ? $status_labels : [
    'active' => 'Em Uso',
    'backup' => 'Backup',
    'maintenance' => 'Manutencao',
    'retired' => 'Aposentado',
];
$is_cellphone_module = $current_module === 'cellphones';
$add_action = $is_cellphone_module ? 'add_cellphone' : 'add_computer';
$update_action = $is_cellphone_module ? 'update_cellphone' : 'update_computer';
$id_field = $is_cellphone_module ? 'cellphone_id' : 'computer_id';
$cancel_url = '?module=' . urlencode($current_module) . '&view=list';

if (!$can_edit): ?>
    <div class="max-w-2xl mx-auto mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg">
        Permissao insuficiente. Este perfil esta em modo somente visualizacao.
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="max-w-2xl mx-auto">
    <form id="mainForm" method="post" action="?" enctype="multipart/form-data" data-ajax="true"
        class="bg-white p-8 pb-24 lg:pb-8 rounded-xl shadow-sm border border-slate-200">
        <?php wp_nonce_field('ccs_action_nonce'); ?>
        <input type="hidden" name="ccs_action" value="<?php echo esc_attr($is_edit ? $update_action : $add_action); ?>">
        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="<?php echo esc_attr($id_field); ?>" value="<?php echo intval($pc->id); ?>">
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php echo esc_html($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$is_cellphone_module): ?>
            <?php $val_hostname = isset($form_data['hostname']) ? $form_data['hostname'] : ($is_edit ? strtoupper($pc->hostname) : ''); ?>
            <?php $val_type = isset($form_data['type']) ? $form_data['type'] : ($is_edit ? $pc->type : 'desktop'); ?>
            <?php $val_status = isset($form_data['status']) ? $form_data['status'] : ($is_edit ? $pc->status : 'active'); ?>
            <?php $val_user_name = isset($form_data['user_name']) ? $form_data['user_name'] : ($is_edit ? $pc->user_name : ''); ?>
            <?php $val_property = isset($form_data['property']) ? $form_data['property'] : (($is_edit && isset($pc->property)) ? $pc->property : ''); ?>
            <?php $val_specs = isset($form_data['specs']) ? $form_data['specs'] : ($is_edit ? $pc->specs : ''); ?>
            <?php $val_notes = isset($form_data['notes']) ? $form_data['notes'] : ($is_edit ? $pc->notes : ''); ?>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Hostname <span class="text-red-500">*</span></label>
                    <input type="text" name="hostname" value="<?php echo esc_attr($val_hostname); ?>" required
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
                    <select name="type"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        <option value="desktop" <?php selected($val_type, 'desktop'); ?>>Desktop</option>
                        <option value="notebook" <?php selected($val_type, 'notebook'); ?>>Notebook</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                    <select name="status"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        <?php foreach ($status_labels as $status_key => $status_label): ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($val_status, $status_key); ?>>
                                <?php echo esc_html($status_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nome do Usuario</label>
                    <input type="text" name="user_name" value="<?php echo esc_attr($val_user_name); ?>"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Propriedade</label>
                <select name="property"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    <option value="" <?php selected($val_property, ''); ?>>Selecione...</option>
                    <option value="Metalife" <?php selected($val_property, 'Metalife'); ?>>Metalife</option>
                    <option value="Selbetti" <?php selected($val_property, 'Selbetti'); ?>>Selbetti</option>
                </select>
            </div>

            <?php
            $val_location = isset($form_data['location']) ? $form_data['location'] : ($is_edit ? $pc->location : '');
            $predefined_locations = ['Fabrica', 'Centro', 'Perdido', 'Manutencao'];
            $is_other_location = !empty($val_location) && !in_array($val_location, $predefined_locations, true);
            $selected_location = $is_other_location ? 'other' : $val_location;
            ?>
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Localizacao</label>
                <div class="flex flex-col gap-3">
                    <select id="locationSelect" name="location_select"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        <option value="" <?php selected($selected_location, ''); ?>>Selecione um local...</option>
                        <?php foreach ($predefined_locations as $loc): ?>
                            <option value="<?php echo esc_attr($loc); ?>" <?php selected($selected_location, $loc); ?>>
                                <?php echo esc_html($loc); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="other" <?php selected($selected_location, 'other'); ?>>Outro</option>
                    </select>

                    <input type="text" id="locationOtherInput"
                        value="<?php echo esc_attr($is_other_location ? $val_location : ''); ?>"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm <?php echo $is_other_location ? '' : 'hidden'; ?>"
                        placeholder="Digite o local especifico" <?php echo $is_other_location ? '' : 'disabled'; ?>>
                </div>
                <input type="hidden" name="location" id="finalLocation" value="<?php echo esc_attr($val_location); ?>">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Especificacoes</label>
                <textarea name="specs" rows="3"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo esc_textarea($val_specs); ?></textarea>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium text-slate-700 mb-2">Anotacoes</label>
                <textarea name="notes" rows="2"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo esc_textarea($val_notes); ?></textarea>
            </div>

            <script>
                (function () {
                    const select = document.getElementById('locationSelect');
                    const customInput = document.getElementById('locationOtherInput');
                    const finalInput = document.getElementById('finalLocation');
                    if (!select || !customInput || !finalInput) return;

                    function updateFinalValue() {
                        finalInput.value = select.value === 'other' ? customInput.value : select.value;
                    }

                    select.addEventListener('change', function () {
                        if (this.value === 'other') {
                            customInput.classList.remove('hidden');
                            customInput.disabled = false;
                            customInput.focus();
                        } else {
                            customInput.classList.add('hidden');
                            customInput.disabled = true;
                        }
                        updateFinalValue();
                    });

                    customInput.addEventListener('input', updateFinalValue);
                })();
            </script>

        <?php else: ?>
            <?php $val_phone = isset($form_data['phone_number']) ? $form_data['phone_number'] : ($is_edit ? $pc->phone_number : ''); ?>
            <?php $val_asset_code = $is_edit ? (string) ($pc->asset_code ?? '') : ''; ?>
            <?php $val_status = isset($form_data['status']) ? $form_data['status'] : ($is_edit ? $pc->status : 'active'); ?>
            <?php $val_user_name = isset($form_data['user_name']) ? $form_data['user_name'] : ($is_edit ? $pc->user_name : ''); ?>
            <?php $val_brand_model = isset($form_data['brand_model']) ? $form_data['brand_model'] : ($is_edit ? ($pc->brand_model ?? '') : ''); ?>
            <?php $val_property = isset($form_data['property']) ? $form_data['property'] : ($is_edit ? ($pc->property ?? '') : ''); ?>
            <?php if ($val_property === 'Meralife') {
                $val_property = 'Metalife';
            } ?>
            <?php $val_notes = isset($form_data['notes']) ? $form_data['notes'] : ($is_edit ? $pc->notes : ''); ?>
            <?php
            $val_department = isset($form_data['department']) ? $form_data['department'] : ($is_edit ? $pc->department : '');
            $predefined_departments = ['COMERCIAL-RN', 'FABRICA-RN'];
            $is_other_department = !empty($val_department) && !in_array($val_department, $predefined_departments, true);
            $selected_department = $is_other_department ? 'other' : $val_department;
            ?>

            <?php if ($is_edit): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">ID Celular</label>
                    <input type="text" value="<?php echo esc_attr($val_asset_code !== '' ? $val_asset_code : '-'); ?>"
                        class="w-full rounded-lg border-slate-300 bg-slate-100 text-slate-700 shadow-sm"
                        readonly>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Numero do Celular</label>
                    <input type="text" id="phoneNumberInput" name="phone_number" value="<?php echo esc_attr($val_phone); ?>"
                        placeholder="(00) 00000-0000"
                        inputmode="numeric"
                        maxlength="15"
                        pattern="^\(\d{2}\)\s\d{4,5}-\d{4}$"
                        title="Use o formato (99) 99999-9999"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                    <select name="status"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        <?php foreach ($status_labels as $status_key => $status_label): ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($val_status, $status_key); ?>>
                                <?php echo esc_html($status_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Marca / Modelo</label>
                    <input type="text" name="brand_model" value="<?php echo esc_attr($val_brand_model); ?>"
                        placeholder="Ex.: Samsung A54"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Usuario</label>
                    <input type="text" name="user_name" value="<?php echo esc_attr($val_user_name); ?>"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Propriedade</label>
                <select name="property"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    <option value="" <?php selected($val_property, ''); ?>>Selecione...</option>
                    <option value="Metalife" <?php selected($val_property, 'Metalife'); ?>>Metalife</option>
                    <option value="Selbetti" <?php selected($val_property, 'Selbetti'); ?>>Selbetti</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Departamento</label>
                <div class="flex flex-col gap-3">
                    <select id="departmentSelect" name="department_select"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        <option value="" <?php selected($selected_department, ''); ?>>Selecione um departamento...</option>
                        <?php foreach ($predefined_departments as $department): ?>
                            <option value="<?php echo esc_attr($department); ?>" <?php selected($selected_department, $department); ?>>
                                <?php echo esc_html($department); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="other" <?php selected($selected_department, 'other'); ?>>Outro</option>
                    </select>

                    <input type="text" id="departmentOtherInput"
                        value="<?php echo esc_attr($is_other_department ? $val_department : ''); ?>"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm <?php echo $is_other_department ? '' : 'hidden'; ?>"
                        placeholder="Digite o departamento" <?php echo $is_other_department ? '' : 'disabled'; ?>>
                </div>
                <input type="hidden" name="department" id="finalDepartment" value="<?php echo esc_attr($val_department); ?>">
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium text-slate-700 mb-2">Observacao</label>
                <textarea name="notes" rows="3"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo esc_textarea($val_notes); ?></textarea>
            </div>

            <script>
                (function () {
                    const phoneInput = document.getElementById('phoneNumberInput');
                    const select = document.getElementById('departmentSelect');
                    const customInput = document.getElementById('departmentOtherInput');
                    const finalInput = document.getElementById('finalDepartment');

                    function maskBrazilPhone(value) {
                        const digits = (value || '').replace(/\D+/g, '').slice(0, 11);
                        if (digits.length === 0) return '';
                        if (digits.length <= 2) return digits;

                        const ddd = digits.slice(0, 2);
                        const rest = digits.slice(2);

                        if (rest.length <= 4) {
                            return `(${ddd}) ${rest}`;
                        }

                        if (digits.length <= 10) {
                            return `(${ddd}) ${rest.slice(0, 4)}-${rest.slice(4)}`;
                        }

                        return `(${ddd}) ${rest.slice(0, 5)}-${rest.slice(5)}`;
                    }

                    if (phoneInput) {
                        phoneInput.value = maskBrazilPhone(phoneInput.value);
                        phoneInput.addEventListener('input', function () {
                            this.value = maskBrazilPhone(this.value);
                        });
                        phoneInput.addEventListener('blur', function () {
                            this.value = maskBrazilPhone(this.value);
                        });
                    }

                    if (!select || !customInput || !finalInput) return;

                    function updateFinalValue() {
                        finalInput.value = select.value === 'other' ? customInput.value : select.value;
                    }

                    select.addEventListener('change', function () {
                        if (this.value === 'other') {
                            customInput.classList.remove('hidden');
                            customInput.disabled = false;
                            customInput.focus();
                        } else {
                            customInput.classList.add('hidden');
                            customInput.disabled = true;
                        }
                        updateFinalValue();
                    });

                    customInput.addEventListener('input', updateFinalValue);
                })();
            </script>
        <?php endif; ?>

        <?php if (!$is_edit): ?>
            <div class="mb-8 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <label class="block text-sm font-medium text-slate-700 mb-2">Foto Inicial (Camera)</label>

                <div class="mb-2">
                    <label for="formCameraInput"
                        class="cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-indigo-300 rounded-xl bg-white hover:bg-indigo-50 transition-colors group">
                        <div class="p-3 bg-indigo-50 rounded-full group-hover:bg-indigo-100 transition-colors mb-2">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <span class="text-indigo-700 font-semibold text-sm">Tirar/Escolher Foto</span>
                        <span id="fileNameDisplay" class="text-slate-400 text-xs mt-1">Nenhuma foto selecionada</span>
                    </label>
                    <input id="formCameraInput" type="file" name="photo" accept="image/*" capture="environment"
                        class="hidden"
                        onchange="if(this.files.length > 0) { document.getElementById('fileNameDisplay').textContent = this.files[0].name; document.getElementById('fileNameDisplay').classList.add('text-emerald-600', 'font-medium'); document.getElementById('fileNameDisplay').classList.remove('text-slate-400'); }">
                </div>

                <p class="mt-1 text-xs text-slate-500">Tire uma foto do equipamento para o cadastro.</p>
            </div>
        <?php endif; ?>

        <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?php echo esc_url($cancel_url); ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?php echo $is_edit ? 'Salvar Alteracoes' : 'Cadastrar'; ?>
            </button>
        </div>
    </form>
</div>

<nav id="mobileBottomBar" class="fixed bottom-0 inset-x-0 z-50 lg:hidden bg-white border-t border-slate-200 shadow-[0_-2px_10px_rgba(0,0,0,0.06)]">
    <div class="flex items-center gap-3 h-16 max-w-lg mx-auto px-4">
        <a href="<?php echo esc_url($cancel_url); ?>"
            class="mobile-nav-btn flex items-center justify-center gap-1.5 px-4 h-10 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Cancelar
        </a>
        <button type="button" onclick="document.getElementById('mainForm').requestSubmit()"
            class="mobile-nav-btn flex-1 flex items-center justify-center gap-2 h-10 rounded-lg bg-slate-700 text-white text-sm font-semibold shadow-sm transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo $is_edit ? 'Salvar Alteracoes' : 'Cadastrar'; ?>
        </button>
    </div>
</nav>
