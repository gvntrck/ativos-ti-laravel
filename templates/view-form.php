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
                        Manutenção</option>
                    <option value="retired" <?php selected($val_status, 'retired'); ?>>Aposentado
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome do Usuário</label>
                <?php $val_user_name = isset($form_data['user_name']) ? $form_data['user_name'] : ($is_edit ? $pc->user_name : ''); ?>
                <input type="text" name="user_name" value="<?php echo esc_attr($val_user_name); ?>"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Localização</label>
            <?php $val_location = isset($form_data['location']) ? $form_data['location'] : ($is_edit ? $pc->location : ''); ?>
            <input type="text" name="location" value="<?php echo esc_attr($val_location); ?>"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                placeholder="Ex: Financeiro, TI">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Especificações</label>
            <?php $val_specs = isset($form_data['specs']) ? $form_data['specs'] : ($is_edit ? $pc->specs : ''); ?>
            <textarea name="specs" rows="3"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo esc_textarea($val_specs); ?></textarea>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-slate-700 mb-2">Anotações</label>
            <?php $val_notes = isset($form_data['notes']) ? $form_data['notes'] : ($is_edit ? $pc->notes : ''); ?>
            <textarea name="notes" rows="2"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo esc_textarea($val_notes); ?></textarea>
        </div>

        <?php if (!$is_edit): ?>
            <div class="mb-8 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <label class="block text-sm font-medium text-slate-700 mb-2">Foto Inicial (Câmera)</label>
                <input type="file" name="photo" accept="image/*" capture="environment"
                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-1 text-xs text-slate-500">Tire uma foto do computador para o cadastro.</p>
            </div>
        <?php endif; ?>

        <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="?" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?php echo $is_edit ? 'Salvar Alterações' : 'Cadastrar'; ?>
            </button>
        </div>
    </form>
</div>