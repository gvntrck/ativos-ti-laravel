<div class="max-w-2xl mx-auto">
    <form method="post" action="?" enctype="multipart/form-data"
        class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <?php wp_nonce_field('ccs_action_nonce'); ?>
        <input type="hidden" name="ccs_action" value="<?php echo $is_edit ? 'update_computer' : 'add_computer'; ?>">
        <?php if ($is_edit): ?><input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Hostname <span
                        class="text-red-500">*</span></label>
                <input type="text" name="hostname"
                    value="<?php echo $is_edit ? esc_attr(strtoupper($pc->hostname)) : ''; ?>" required
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
                <select name="type"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    <option value="desktop" <?php selected($is_edit ? $pc->type : '', 'desktop'); ?>>Desktop</option>
                    <option value="notebook" <?php selected($is_edit ? $pc->type : '', 'notebook'); ?>>Notebook</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                <select name="status"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    <option value="active" <?php selected($is_edit ? $pc->status : '', 'active'); ?>>Em Uso</option>
                    <option value="backup" <?php selected($is_edit ? $pc->status : '', 'backup'); ?>>Backup</option>
                    <option value="maintenance" <?php selected($is_edit ? $pc->status : '', 'maintenance'); ?>>Em
                        Manutenção</option>
                    <option value="retired" <?php selected($is_edit ? $pc->status : '', 'retired'); ?>>Aposentado
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome do Usuário</label>
                <input type="text" name="user_name" value="<?php echo $is_edit ? esc_attr($pc->user_name) : ''; ?>"
                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Localização</label>
            <input type="text" name="location" value="<?php echo $is_edit ? esc_attr($pc->location) : ''; ?>"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                placeholder="Ex: Financeiro, TI">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Especificações</label>
            <textarea name="specs" rows="3"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo $is_edit ? esc_textarea($pc->specs) : ''; ?></textarea>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-slate-700 mb-2">Anotações</label>
            <textarea name="notes" rows="2"
                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo $is_edit ? esc_textarea($pc->notes) : ''; ?></textarea>
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