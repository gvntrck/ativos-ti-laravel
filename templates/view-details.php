<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Info Card (Position: 1st on Mobile, Left-Top on Desktop) -->
    <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">
                    <?php echo esc_html(strtoupper($pc->hostname)); ?>
                </h2>
                <span class="text-sm text-slate-500 capitalize">
                    <?php echo $pc->type; ?>
                </span>
            </div>
            <div class="flex flex-col items-end gap-2">
                <form method="post" action="?"
                    onsubmit="return confirm('Tem certeza que deseja enviar este computador para a lixeira? Ele não será excluído permanentemente, mas sairá da lista principal.');">
                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                    <input type="hidden" name="ccs_action" value="trash_computer">
                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
                    <button type="submit"
                        class="text-red-500 hover:text-red-700 p-2 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 transition-colors"
                        title="Mover para Lixeira">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </form>
                <a href="?view=edit&id=<?php echo $pc->id; ?>"
                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Editar Informações</a>
            </div>
        </div>


        <div class="grid grid-cols-2 gap-6 text-sm">
            <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Status</span>
                <span class="font-medium">
                    <?php echo ucfirst($pc->status); ?>
                </span>
            </div>
            <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Usuário</span>
                <span class="font-medium">
                    <?php echo $pc->user_name ?: '-'; ?>
                </span>
            </div>
            <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Local</span>
                <span class="font-medium">
                    <?php echo $pc->location ?: '-'; ?>
                </span>
            </div>
            <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Atualizado
                    em</span> <span class="font-medium">
                    <?php echo date('d/m/Y H:i', strtotime($pc->updated_at)); ?>
                </span></div>
        </div>
        <?php if ($pc->specs): ?>
            <div class="mt-6 pt-6 border-t border-slate-100">
                <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold mb-2">Especificações</span>
                <p class="text-slate-700 bg-slate-50 p-3 rounded-lg">
                    <?php echo nl2br(esc_html($pc->specs)); ?>
                </p>
            </div>
        <?php endif; ?>
        <?php if ($pc->notes): ?>
            <div class="mt-6 pt-6 border-t border-slate-100">
                <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold mb-2">Anotações</span>
                <p class="text-slate-700 bg-amber-50 border border-amber-100 p-3 rounded-lg text-sm">
                    <?php echo nl2br(esc_html($pc->notes)); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar (Position: 2nd on Mobile, Right-Column on Desktop) -->
    <div class="lg:col-start-3 lg:row-start-1 lg:row-span-2">
        <div class="lg:sticky lg:top-8 space-y-6">
            <!-- Quick Actions Card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-900 mb-4">Ações Rápidas</h3>
                <form method="post" action="?">
                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                    <input type="hidden" name="ccs_action" value="quick_windows_update">
                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 p-3 rounded-lg transition-colors font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Marcar Windows Atualizado
                    </button>
                </form>
            </div>

            <!-- New Event / Checkup Card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-900 mb-4">Novo Evento / Checkup</h3>
                <form method="post" action="?">
                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                    <input type="hidden" name="ccs_action" value="add_checkup">
                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
                    <div class="mb-4">
                        <textarea name="description" rows="4"
                            class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm p-3"
                            placeholder="Descreva a manutenção, checkup ou movimentação..." required></textarea>
                    </div>
                    <button type="submit" class="w-full btn btn-primary">Registrar</button>
                </form>
            </div>

            <!-- Photos Card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-900 mb-4">Fotos do Equipamento</h3>
                <form method="post" action="?" enctype="multipart/form-data">
                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                    <input type="hidden" name="ccs_action" value="upload_photo">
                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Adicionar Foto</label>
                        <input type="file" name="computer_photos[]" multiple accept="image/*" capture="environment"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <button type="submit" class="w-full btn btn-secondary">Enviar Foto</button>
                </form>
            </div>
        </div>
    </div>

    <!-- History (Position: 3rd on Mobile, Left-Bottom on Desktop) -->
    <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-6">Histórico</h3>
        <div class="space-y-6 relative before:absolute before:inset-0 before:ml-2.5 before:w-0.5 before:bg-slate-200">
            <?php foreach ($history as $h):
                $u = get_userdata($h->user_id);
                ?>
                <div class="relative flex gap-4">
                    <div class="absolute -left-1 w-2.5 h-2.5 rounded-full bg-indigo-500 ring-4 ring-white mt-1.5 ml-1">
                    </div>
                    <div class="ml-6 flex-1">
                        <div class="flex justify-between items-baseline mb-1">
                            <span class="font-semibold text-slate-900 capitalize">
                                <?php echo $h->event_type; ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                <?php echo date('d/m H:i', strtotime($h->created_at)); ?>
                                -
                                <?php echo $u ? $u->display_name : 'Sistema'; ?>
                            </span>
                        </div>
                        <p class="text-slate-600 text-sm">
                            <?php echo esc_html($h->description); ?>
                        </p>

                        <?php
                        $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
                        if (!empty($photos)):
                            ?>
                            <div class="flex gap-2 mt-2 overflow-x-auto pb-2">
                                <?php foreach ($photos as $photo_url): ?>
                                    <a href="<?php echo esc_url($photo_url); ?>" target="_blank" class="block flex-shrink-0">
                                        <img src="<?php echo esc_url($photo_url); ?>"
                                            class="h-16 w-16 object-cover rounded-lg border border-slate-200 hover:opacity-75 transition-opacity">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <p class="ml-6 text-slate-400 italic">Sem histórico registrado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>