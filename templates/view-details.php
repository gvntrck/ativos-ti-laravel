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
$id_field = $is_cellphone_module ? 'cellphone_id' : 'computer_id';
$trash_action = $is_cellphone_module ? 'trash_cellphone' : 'trash_computer';
$checkup_action = $is_cellphone_module ? 'add_cellphone_checkup' : 'add_checkup';
$upload_action = $is_cellphone_module ? 'upload_cellphone_photo' : 'upload_photo';
$delete_history_action = $is_cellphone_module ? 'delete_cellphone_history' : 'delete_history';
$edit_url = '?module=' . urlencode($current_module) . '&view=edit&id=' . intval($pc->id);
$cellphone_code = trim((string) ($pc->asset_code ?? ''));
$identifier_value = $is_cellphone_module
    ? ($cellphone_code !== '' ? $cellphone_code : trim((string) ($pc->phone_number ?? '')))
    : strtoupper((string) ($pc->hostname ?? ''));
$identifier_value = $identifier_value !== '' ? $identifier_value : '-';
$status_value = (string) ($pc->status ?? '');
$status_label = $status_labels[$status_value] ?? $status_value;
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-900"><?php echo esc_html($identifier_value); ?></h2>
                <span class="text-sm text-slate-500 capitalize">
                    <?php echo esc_html($is_cellphone_module ? 'celular' : (string) ($pc->type ?? '')); ?>
                </span>
            </div>
            <div class="flex flex-col items-end gap-2">
                <?php if ($can_edit): ?>
                    <button type="button"
                        class="lg:hidden text-indigo-600 hover:text-indigo-800 p-2 rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 transition-colors"
                        title="Tirar Foto"
                        onclick="triggerCameraCapture()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                    <form method="post" action="?" data-ajax="true"
                        data-confirm="Tem certeza que deseja enviar este item para a lixeira? Ele nao sera excluido permanentemente.">
                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                        <input type="hidden" name="ccs_action" value="<?php echo esc_attr($trash_action); ?>">
                        <input type="hidden" name="<?php echo esc_attr($id_field); ?>" value="<?php echo intval($pc->id); ?>">
                        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">
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
                    <a href="<?php echo esc_url($edit_url); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Editar Informacoes</a>
                <?php else: ?>
                    <span class="text-xs text-slate-400">Somente visualizacao</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 text-sm">
            <div>
                <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Status</span>
                <span class="font-medium"><?php echo esc_html($status_label ?: '-'); ?></span>
            </div>
            <div>
                <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Usuario</span>
                <span class="font-medium"><?php echo esc_html($pc->user_name ?: '-'); ?></span>
            </div>

            <?php if ($is_cellphone_module): ?>
                <div>
                    <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">ID Celular</span>
                    <span class="font-medium"><?php echo esc_html($cellphone_code !== '' ? $cellphone_code : '-'); ?></span>
                </div>
                <div>
                    <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Numero</span>
                    <span class="font-medium"><?php echo esc_html($pc->phone_number ?: '-'); ?></span>
                </div>
                <div>
                    <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Marca / Modelo</span>
                    <span class="font-medium"><?php echo esc_html(($pc->brand_model ?? '') !== '' ? $pc->brand_model : '-'); ?></span>
                </div>
                <div>
                    <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Departamento</span>
                    <span class="font-medium"><?php echo esc_html($pc->department ?: '-'); ?></span>
                </div>
                <div>
                    <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Propriedade</span>
                    <span class="font-medium"><?php echo esc_html(($pc->property ?? '') !== '' ? $pc->property : '-'); ?></span>
                </div>
            <?php else: ?>
                <div>
                    <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Local</span>
                    <span class="font-medium"><?php echo esc_html($pc->location ?: '-'); ?></span>
                </div>
                <div>
                    <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Propriedade</span>
                    <span class="font-medium"><?php echo !empty($pc->property) ? esc_html($pc->property) : '-'; ?></span>
                </div>
            <?php endif; ?>

            <div>
                <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Atualizado em</span>
                <span class="font-medium"><?php echo date('d/m/Y H:i', strtotime($pc->updated_at)); ?></span>
            </div>
        </div>

        <?php if (!$is_cellphone_module && !empty($pc->specs)): ?>
            <div class="mt-6 pt-6 border-t border-slate-100">
                <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold mb-2">Especificacoes</span>
                <p class="text-slate-700 bg-slate-50 p-3 rounded-lg"><?php echo nl2br(esc_html($pc->specs)); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($pc->notes)): ?>
            <div class="mt-6 pt-6 border-t border-slate-100">
                <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold mb-2">
                    <?php echo $is_cellphone_module ? 'Observacao' : 'Anotacoes'; ?>
                </span>
                <p class="text-slate-700 bg-amber-50 border border-amber-100 p-3 rounded-lg text-sm"><?php echo nl2br(esc_html($pc->notes)); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="lg:col-start-3 lg:row-start-1 lg:row-span-2">
        <div class="lg:sticky lg:top-8 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-900 mb-4">Acoes Rapidas</h3>

                <button type="button" id="copyDataBtn" onclick="copyAssetData()"
                    class="w-full flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 p-3 rounded-lg transition-colors font-medium mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                        </path>
                    </svg>
                    <span id="copyBtnText">Copiar Dados</span>
                </button>

                <?php if ($can_edit && !$is_cellphone_module): ?>
                    <form method="post" action="?" data-ajax="true">
                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                        <input type="hidden" name="ccs_action" value="quick_windows_update">
                        <input type="hidden" name="computer_id" value="<?php echo intval($pc->id); ?>">
                        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">
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
                <?php elseif (!$can_edit): ?>
                    <p class="text-xs text-slate-500">Somente visualizacao.</p>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-900 mb-4">Novo Evento / Checkup</h3>
                <?php if ($can_edit): ?>
                    <form method="post" action="?" data-ajax="true">
                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                        <input type="hidden" name="ccs_action" value="<?php echo esc_attr($checkup_action); ?>">
                        <input type="hidden" name="<?php echo esc_attr($id_field); ?>" value="<?php echo intval($pc->id); ?>">
                        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">
                        <div class="mb-4">
                            <textarea name="description" rows="4"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm p-3"
                                placeholder="Descreva a manutencao, checkup ou movimentacao..." required></textarea>
                        </div>
                        <button type="submit" class="w-full btn btn-primary">Registrar</button>
                    </form>
                <?php else: ?>
                    <p class="text-xs text-slate-500">Somente visualizacao.</p>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-900 mb-4">Fotos do Equipamento</h3>
                <?php if ($can_edit): ?>
                    <form method="post" action="?" enctype="multipart/form-data" id="photoUploadForm" data-ajax="true"
                        data-loading-overlay-id="loadingOverlay" class="relative">
                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                        <input type="hidden" name="ccs_action" value="<?php echo esc_attr($upload_action); ?>">
                        <input type="hidden" name="<?php echo esc_attr($id_field); ?>" value="<?php echo intval($pc->id); ?>">
                        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">

                        <div class="mb-3">
                            <label for="cameraInput"
                                class="cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-indigo-300 rounded-xl bg-indigo-50 hover:bg-indigo-100 transition-colors group">
                                <div class="p-3 bg-indigo-100 rounded-full group-hover:bg-indigo-200 transition-colors mb-2">
                                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-indigo-700 font-semibold text-sm">Adicionar Foto</span>
                                <span class="text-indigo-400 text-xs mt-1">Toque para capturar varias e enviar de uma vez</span>
                            </label>
                            <input id="cameraInput" type="file" name="asset_photos[]" accept="image/*"
                                capture="environment" multiple class="hidden" onchange="handleCameraInputChange(this)">
                        </div>

                        <div id="photoQueuePanel" class="hidden mb-4 p-3 rounded-lg border border-slate-200 bg-slate-50">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span id="photoQueueCount" class="text-xs text-slate-600">Nenhuma foto selecionada.</span>
                                <button type="button" id="clearPhotoQueueBtn"
                                    class="text-xs text-slate-500 hover:text-red-600 underline">Limpar</button>
                            </div>
                            <div id="photoQueuePreview" class="flex gap-2 overflow-x-auto pb-1"></div>
                        </div>

                        <button type="submit" id="submitPhotoBatchBtn"
                            class="w-full btn btn-primary opacity-60 cursor-not-allowed"
                            disabled>
                            Enviar fotos selecionadas
                        </button>

                        <div id="loadingOverlay"
                            class="hidden absolute inset-0 bg-white/80 flex flex-col items-center justify-center rounded-xl z-10">
                            <svg class="animate-spin h-8 w-8 text-indigo-600 mb-2" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span class="text-sm font-medium text-indigo-700">Enviando fotos...</span>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-xs text-slate-500">Somente visualizacao.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-6">Historico</h3>
        <div class="space-y-6 relative before:absolute before:inset-0 before:ml-2.5 before:w-0.5 before:bg-slate-200">
            <?php foreach ($history as $h):
                $u = get_userdata($h->user_id);
                ?>
                <div class="relative flex gap-4 min-w-0">
                    <div class="absolute -left-1 w-2.5 h-2.5 rounded-full bg-indigo-500 ring-4 ring-white mt-1.5 ml-1"></div>
                    <div class="ml-6 flex-1 min-w-0">
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:items-baseline mb-1 min-w-0">
                            <span class="font-semibold text-slate-900 capitalize"><?php echo esc_html($h->event_type); ?></span>
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs text-slate-400 break-words whitespace-normal">
                                    <?php echo date('d/m H:i', strtotime($h->created_at)); ?> - <?php echo esc_html($u ? $u->display_name : 'Sistema'); ?>
                                </span>
                                <?php if ($can_edit): ?>
                                    <form method="post" action="?" data-ajax="true" class="inline"
                                        data-confirm="Tem certeza que deseja excluir este item do historico?">
                                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                                        <input type="hidden" name="ccs_action" value="<?php echo esc_attr($delete_history_action); ?>">
                                        <input type="hidden" name="<?php echo esc_attr($id_field); ?>" value="<?php echo intval($pc->id); ?>">
                                        <input type="hidden" name="history_id" value="<?php echo intval($h->id); ?>">
                                        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">
                                        <button type="submit"
                                            class="text-slate-400 hover:text-red-500 p-1 rounded transition-colors"
                                            title="Excluir item do historico">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-slate-600 text-sm break-words whitespace-normal"><?php echo esc_html($h->description); ?></p>

                        <?php
                        $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
                        if (!empty($photos)):
                            ?>
                            <div class="flex gap-2 mt-2 overflow-x-auto pb-2">
                                <?php foreach ($photos as $photo_index => $photo_url): ?>
                                    <a href="javascript:void(0)"
                                        onclick="openLightboxFromHistory(<?php echo intval($h->id); ?>, <?php echo intval($photo_index); ?>)"
                                        class="block flex-shrink-0 cursor-pointer">
                                        <img src="<?php echo esc_url($photo_url); ?>"
                                            class="h-16 w-16 object-cover rounded-lg border border-slate-200 hover:opacity-75 hover:ring-2 hover:ring-indigo-400 transition-all">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <p class="ml-6 text-slate-400 italic">Sem historico registrado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const assetData = {
        module: <?php echo json_encode($current_module); ?>,
        identifier: <?php echo json_encode($identifier_value); ?>,
        status: <?php echo json_encode($status_label ?: '-'); ?>,
        userName: <?php echo json_encode($pc->user_name ?: '-'); ?>,
        location: <?php echo json_encode($pc->location ?? ''); ?>,
        property: <?php echo json_encode($pc->property ?? ''); ?>,
        type: <?php echo json_encode($pc->type ?? ''); ?>,
        assetCode: <?php echo json_encode($pc->asset_code ?? ''); ?>,
        phoneNumber: <?php echo json_encode($pc->phone_number ?? ''); ?>,
        brandModel: <?php echo json_encode($pc->brand_model ?? ''); ?>,
        department: <?php echo json_encode($pc->department ?? ''); ?>,
        specs: <?php echo json_encode($pc->specs ?? ''); ?>,
        notes: <?php echo json_encode($pc->notes ?: '-'); ?>,
        updatedAt: <?php echo json_encode(date('d/m/Y H:i', strtotime($pc->updated_at))); ?>,
        history: [
            <?php foreach ($history as $index => $h):
                $u = get_userdata($h->user_id);
                $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
                ?>
                {
                    type: <?php echo json_encode($h->event_type); ?>,
                    date: <?php echo json_encode(date('d/m/Y H:i', strtotime($h->created_at))); ?>,
                    user: <?php echo json_encode($u ? $u->display_name : 'Sistema'); ?>,
                    description: <?php echo json_encode($h->description); ?>,
                    photos: <?php echo json_encode($photos); ?>
                }<?php echo $index < count($history) - 1 ? ',' : ''; ?>
            <?php endforeach; ?>
        ],
        pageUrl: <?php echo json_encode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>
    };

    function copyAssetData() {
        let text = '';

        if (assetData.module === 'cellphones') {
            text += '*FICHA DO CELULAR*\n\n';
            text += `*ID Celular:* ${assetData.assetCode || '-'}\n`;
            text += `*Numero:* ${assetData.phoneNumber || '-'}\n`;
            text += `*Marca / Modelo:* ${assetData.brandModel || '-'}\n`;
            text += `*Status:* ${assetData.status}\n`;
            text += `*Usuario:* ${assetData.userName}\n`;
            text += `*Propriedade:* ${assetData.property || '-'}\n`;
            text += `*Departamento:* ${assetData.department || '-'}\n`;
            text += `*Atualizado em:* ${assetData.updatedAt}\n`;
            if (assetData.notes && assetData.notes !== '-') {
                text += `\n*Observacao:*\n${assetData.notes}\n`;
            }
        } else {
            text += '*FICHA DO COMPUTADOR*\n\n';
            text += `*Hostname:* ${assetData.identifier}\n`;
            text += `*Tipo:* ${assetData.type || '-'}\n`;
            text += `*Status:* ${assetData.status}\n`;
            text += `*Usuario:* ${assetData.userName}\n`;
            text += `*Local:* ${assetData.location || '-'}\n`;
            text += `*Propriedade:* ${assetData.property || '-'}\n`;
            text += `*Atualizado em:* ${assetData.updatedAt}\n`;
            if (assetData.specs) {
                text += `\n*Especificacoes:*\n${assetData.specs}\n`;
            }
            if (assetData.notes && assetData.notes !== '-') {
                text += `\n*Anotacoes:*\n${assetData.notes}\n`;
            }
        }

        if (assetData.history.length > 0) {
            text += '\n*HISTORICO*\n';
            assetData.history.forEach((entry, index) => {
                text += `\n*${entry.date}* - _${entry.type}_\n${entry.description}\n`;
                if (entry.user) {
                    text += `Responsavel: ${entry.user}\n`;
                }
                if (entry.photos && entry.photos.length > 0) {
                    text += 'Fotos:\n';
                    entry.photos.forEach((photo, photoIndex) => {
                        text += `  ${photoIndex + 1}. ${photo}\n`;
                    });
                }
                if (index < assetData.history.length - 1) {
                    text += '\n';
                }
            });
        }

        text += `\n*Link:* ${assetData.pageUrl}`;

        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById('copyDataBtn');
            const btnText = document.getElementById('copyBtnText');
            if (!btn || !btnText) return;

            const originalText = btnText.textContent;
            btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'hover:bg-emerald-100');
            btn.classList.add('bg-emerald-500', 'text-white', 'border-emerald-600');
            btnText.textContent = 'Copiado!';

            setTimeout(() => {
                btn.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-600');
                btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'hover:bg-emerald-100');
                btnText.textContent = originalText;
            }, 2000);
        }).catch((err) => {
            alert('Erro ao copiar dados. Tente novamente.');
            console.error('Erro ao copiar:', err);
        });
    }

    const allPhotos = [];
    const photoIndexMap = {};

    <?php
    $global_index = 0;
    foreach ($history as $h):
        $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
        if (!empty($photos)):
            foreach ($photos as $pIdx => $pUrl):
                ?>
                allPhotos.push(<?php echo json_encode($pUrl); ?>);
                photoIndexMap['<?php echo intval($h->id); ?>_<?php echo intval($pIdx); ?>'] = <?php echo intval($global_index); ?>;
                <?php
                $global_index++;
            endforeach;
        endif;
    endforeach;
    ?>

    function openLightboxFromHistory(historyId, photoIndex) {
        const key = historyId + '_' + photoIndex;
        const globalIndex = photoIndexMap[key] !== undefined ? photoIndexMap[key] : 0;
        if (allPhotos.length > 0) {
            openLightbox(allPhotos, globalIndex);
        }
    }

    function triggerCameraCapture() {
        const cameraInput = document.getElementById('cameraInput');
        if (cameraInput) {
            cameraInput.click();
        }
    }

    const queuedPhotoFiles = [];
    const queuedPhotoPreviewUrls = [];
    const supportsDataTransfer = typeof DataTransfer !== 'undefined';

    function buildPhotoFingerprint(file) {
        return [file.name, file.size, file.lastModified].join(':');
    }

    function syncQueuedFilesToInput() {
        const cameraInput = document.getElementById('cameraInput');
        if (!cameraInput) {
            return false;
        }

        if (!supportsDataTransfer) {
            return false;
        }

        const dt = new DataTransfer();
        queuedPhotoFiles.forEach((file) => dt.items.add(file));
        cameraInput.files = dt.files;
        return true;
    }

    function clearQueuePreviewUrls() {
        while (queuedPhotoPreviewUrls.length > 0) {
            const previewUrl = queuedPhotoPreviewUrls.pop();
            URL.revokeObjectURL(previewUrl);
        }
    }

    function renderPhotoQueue() {
        const panel = document.getElementById('photoQueuePanel');
        const countLabel = document.getElementById('photoQueueCount');
        const preview = document.getElementById('photoQueuePreview');
        const submitButton = document.getElementById('submitPhotoBatchBtn');

        if (!panel || !countLabel || !preview || !submitButton) {
            return;
        }

        clearQueuePreviewUrls();
        preview.innerHTML = '';

        if (queuedPhotoFiles.length === 0) {
            panel.classList.add('hidden');
            countLabel.textContent = 'Nenhuma foto selecionada.';
            submitButton.disabled = true;
            submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            return;
        }

        panel.classList.remove('hidden');
        countLabel.textContent = queuedPhotoFiles.length + (queuedPhotoFiles.length === 1 ? ' foto selecionada.' : ' fotos selecionadas.');
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-60', 'cursor-not-allowed');

        queuedPhotoFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'relative flex-shrink-0';

            const img = document.createElement('img');
            const previewUrl = URL.createObjectURL(file);
            queuedPhotoPreviewUrls.push(previewUrl);
            img.src = previewUrl;
            img.className = 'h-16 w-16 object-cover rounded-lg border border-slate-200';
            img.alt = 'Foto ' + (index + 1);

            item.appendChild(img);
            preview.appendChild(item);
        });
    }

    function queueSelectedPhotos(fileList) {
        const incoming = Array.from(fileList || []).filter((file) => file && /^image\//.test(file.type));
        if (incoming.length === 0) {
            return;
        }

        if (!supportsDataTransfer) {
            queuedPhotoFiles.length = 0;
            incoming.forEach((file) => queuedPhotoFiles.push(file));
            renderPhotoQueue();
            return;
        }

        const known = new Set(queuedPhotoFiles.map(buildPhotoFingerprint));
        incoming.forEach((file) => {
            const fingerprint = buildPhotoFingerprint(file);
            if (!known.has(fingerprint)) {
                queuedPhotoFiles.push(file);
                known.add(fingerprint);
            }
        });

        syncQueuedFilesToInput();
        renderPhotoQueue();
    }

    function clearPhotoQueue() {
        queuedPhotoFiles.length = 0;
        syncQueuedFilesToInput();

        const cameraInput = document.getElementById('cameraInput');
        if (cameraInput) {
            cameraInput.value = '';
        }

        renderPhotoQueue();
    }

    function handleCameraInputChange(input) {
        if (!input || !input.files || input.files.length === 0) {
            return;
        }

        queueSelectedPhotos(input.files);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const photoUploadForm = document.getElementById('photoUploadForm');
        const clearBtn = document.getElementById('clearPhotoQueueBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');

        if (clearBtn) {
            clearBtn.addEventListener('click', clearPhotoQueue);
        }

        if (photoUploadForm) {
            // Capture phase ensures queue sync runs before generic AJAX submit handlers.
            photoUploadForm.addEventListener('submit', function (event) {
                if (queuedPhotoFiles.length === 0) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    alert('Selecione pelo menos uma foto antes de enviar.');
                    return;
                }

                if (supportsDataTransfer) {
                    syncQueuedFilesToInput();
                }
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                }
            }, true);
        }

        renderPhotoQueue();
    });
</script>
