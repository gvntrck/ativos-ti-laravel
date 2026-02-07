<div class="max-w-2xl mx-auto">
    <form method="post" action="?" enctype="multipart/form-data" data-ajax="true"
        class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <?php wp_nonce_field('ccs_action_nonce'); ?>
        <input type="hidden" name="ccs_action" value="<?php echo $is_edit ? 'update_computer' : 'add_computer'; ?>">
        <?php if ($is_edit): ?><input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
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

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Hostname <span
                        class="text-red-500">*</span></label>
                <?php $val_hostname = isset($form_data['hostname']) ? $form_data['hostname'] : ($is_edit ? strtoupper($pc->hostname) : ''); ?>
                <input type="text" name="hostname" value="<?php echo esc_attr($val_hostname); ?>" required
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
                <?php $val_type = isset($form_data['type']) ? $form_data['type'] : ($is_edit ? $pc->type : 'desktop'); ?>
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
                <?php $val_status = isset($form_data['status']) ? $form_data['status'] : ($is_edit ? $pc->status : 'active'); ?>
                <select name="status"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    <option value="active" <?php selected($val_status, 'active'); ?>>Em Uso</option>
                    <option value="backup" <?php selected($val_status, 'backup'); ?>>Backup</option>
                    <option value="maintenance" <?php selected($val_status, 'maintenance'); ?>>Em
                        ManutenÃ§Ã£o</option>
                    <option value="retired" <?php selected($val_status, 'retired'); ?>>Aposentado
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome do UsuÃ¡rio</label>
                <?php $val_user_name = isset($form_data['user_name']) ? $form_data['user_name'] : ($is_edit ? $pc->user_name : ''); ?>
                <input type="text" name="user_name" value="<?php echo esc_attr($val_user_name); ?>"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">LocalizaÃ§Ã£o</label>
            <?php
            $val_location = isset($form_data['location']) ? $form_data['location'] : ($is_edit ? $pc->location : '');
            $predefined_locations = ['Fabrica', 'Centro', 'Perdido', 'ManutenÃ§Ã£o'];
            $is_other = !empty($val_location) && !in_array($val_location, $predefined_locations);
            $selected_option = $is_other ? 'other' : $val_location;
            ?>

            <div class="flex flex-col gap-3">
                <select id="locationSelect" name="location_select" onchange="toggleLocationInput(this)"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    <option value="" <?php selected($selected_option, ''); ?>>Selecione um local...</option>
                    <?php foreach ($predefined_locations as $loc): ?>
                        <option value="<?php echo esc_attr($loc); ?>" <?php selected($selected_option, $loc); ?>>
                            <?php echo esc_html($loc); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="other" <?php selected($selected_option, 'other'); ?>>Outro (Livre escolha)</option>
                </select>

                <input type="text" id="locationOtherInput" name="location"
                    value="<?php echo esc_attr($val_location); ?>"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm <?php echo $is_other ? '' : 'hidden'; ?>"
                    placeholder="Digite o local especÃ­fico" <?php echo $is_other ? '' : 'disabled'; ?>>
            </div>

            <script>
                function toggleLocationInput(select) {
                    const input = document.getElementById('locationOtherInput');
                    if (select.value === 'other') {
                        input.classList.remove('hidden');
                        input.disabled = false;
                        input.value = ''; // Clear for new input or keep? Better clear or let user decide. Let's keep empty for now as it's a switch.
                        input.focus();
                    } else {
                        input.classList.add('hidden');
                        input.disabled = true; // Disable so it's not sent if not visible, but we need to ensure the select value is sent?
                        // actually if disabled it won't be sent. 
                        // We need 'location' to be the name of the parameter sent to backend.
                        // If select is NOT other, we want select value to be 'location'.
                        // If select IS other, we want input value to be 'location'.

                        // Allow me to refine this:
                        // Easy way: name="location" on input is good. 
                        // But if select is not other, we simply copy select value to input (hidden)?
                        // OR we handle this in backend. Backend expects 'location'.
                        // Let's modify the JS to update the input value when select changes given it's a predefined one.

                        if (select.value) {
                            input.value = select.value;
                        }
                    }
                }

                // Initial sync script for better UX
                document.addEventListener('DOMContentLoaded', function () {
                    const select = document.getElementById('locationSelect');
                    const input = document.getElementById('locationOtherInput');

                    select.addEventListener('change', function () {
                        if (this.value === 'other') {
                            input.classList.remove('hidden');
                            input.disabled = false;
                            // Don't clear if it was already 'other' logic, but here it is fresh switch
                            if (input.value && <?php echo json_encode($predefined_locations); ?>.includes(input.value)) {
                                input.value = '';
                            }
                        } else {
                            input.classList.add('hidden');
                            // We keep it enabled but hidden? No, if we have two fields with same name?
                            // Actually, let's change name logic.
                            // Let's make select have no name that conflicts, or handle in backend?
                            // Let's fix this properly below.
                            input.value = this.value;
                        }
                    });
                });
            </script>

            <!-- Refined Logic Implementation -->
            <!-- We will use a hidden input for the real 'location' submitted value if we want to be pure, OR simpler: -->
            <!-- Use 'location_select' for the dropdown and 'location_custom' for the text. -->
            <!-- And in backend check: if location_select == 'other' use location_custom, else use location_select. -->
            <!-- However, to avoid modifying backend too much, let's use JS to populate a single field or use the 'location' name smartly. -->

            <!-- Let's go with: Select has name 'location_select', Input has name 'location_custom'. -->
            <!-- We need to modify backend to read these? OR we simply use JS to fill a hidden 'location' field. -->
            <!-- JS filling hidden field is safest for existing backend compatibility. -->

            <input type="hidden" name="location" id="finalLocation" value="<?php echo esc_attr($val_location); ?>">

            <script>
                // Redefining the script to work with the hidden field approach
                (function () {
                    const select = document.getElementById('locationSelect');
                    const customInput = document.getElementById('locationOtherInput');
                    const finalInput = document.getElementById('finalLocation');

                    // Helper to update final value
                    function updateFinalValue() {
                        if (select.value === 'other') {
                            finalInput.value = customInput.value;
                        } else {
                            finalInput.value = select.value;
                        }
                    }

                    select.addEventListener('change', function () {
                        if (this.value === 'other') {
                            customInput.classList.remove('hidden');
                            customInput.disabled = false;
                            if (!customInput.value || <?php echo json_encode($predefined_locations); ?>.includes(customInput.value)) {
                                customInput.value = '';
                            }
                        } else {
                            customInput.classList.add('hidden');
                            customInput.disabled = true; // Visual only
                        }
                        updateFinalValue();
                    });

                    customInput.addEventListener('input', updateFinalValue);

                    // Ensure state on load (already handled by PHP logic above but need to ensure input visibility matches)
                    // The PHP logic sets class='hidden' correctly.
                })();
            </script>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">EspecificaÃ§Ãµes</label>
            <?php $val_specs = isset($form_data['specs']) ? $form_data['specs'] : ($is_edit ? $pc->specs : ''); ?>
            <textarea name="specs" rows="3"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo esc_textarea($val_specs); ?></textarea>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-slate-700 mb-2">AnotaÃ§Ãµes</label>
            <?php $val_notes = isset($form_data['notes']) ? $form_data['notes'] : ($is_edit ? $pc->notes : ''); ?>
            <textarea name="notes" rows="2"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo esc_textarea($val_notes); ?></textarea>
        </div>

        <?php if (!$is_edit): ?>
            <div class="mb-8 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <label class="block text-sm font-medium text-slate-700 mb-2">Foto Inicial (CÃ¢mera)</label>

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

                <p class="mt-1 text-xs text-slate-500">Tire uma foto do computador para o cadastro.</p>
            </div>
        <?php endif; ?>

        <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="?" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?php echo $is_edit ? 'Salvar AlteraÃ§Ãµes' : 'Cadastrar'; ?>
            </button>
        </div>
    </form>
</div>