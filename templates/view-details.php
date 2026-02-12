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
                    data-confirm="Tem certeza que deseja enviar este computador para a lixeira? Ele não será excluído permanentemente, mas sairá da lista principal.">
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
            <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Propriedade</span>
                <span class="font-medium">
                    <?php echo !empty($pc->property) ? esc_html($pc->property) : '-'; ?>
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

                <!-- Botão Copiar Dados -->
                <button type="button" id="copyDataBtn" onclick="copyComputerData()"
                    class="w-full flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 p-3 rounded-lg transition-colors font-medium mb-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                        </path>
                    </svg>
                    <span id="copyBtnText">Copiar Dados</span>
                </button>

                <form method="post" action="?" data-ajax="true">
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
                <form method="post" action="?" data-ajax="true">
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
                <form method="post" action="?" enctype="multipart/form-data" id="photoUploadForm" data-ajax="true">
                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                    <input type="hidden" name="ccs_action" value="upload_photo">
                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">

                    <div class="mb-0">
                        <label for="cameraInput"
                            class="cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-indigo-300 rounded-xl bg-indigo-50 hover:bg-indigo-100 transition-colors group">
                            <div
                                class="p-3 bg-indigo-100 rounded-full group-hover:bg-indigo-200 transition-colors mb-2">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <span class="text-indigo-700 font-semibold text-sm">Tirar Foto</span>
                            <span class="text-indigo-400 text-xs mt-1">Toque para capturar e enviar</span>
                        </label>
                        <input id="cameraInput" type="file" name="computer_photos[]" accept="image/*"
                            capture="environment" class="hidden"
                            onchange="handleCameraInputChange(this)">
                    </div>

                    <!-- Loading Overlay -->
                    <div id="loadingOverlay"
                        class="hidden absolute inset-0 bg-white/80 flex flex-col items-center justify-center rounded-xl z-10">
                        <svg class="animate-spin h-8 w-8 text-indigo-600 mb-2" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="text-sm font-medium text-indigo-700">Enviando foto...</span>
                    </div>
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
                <div class="relative flex gap-4 min-w-0">
                    <div class="absolute -left-1 w-2.5 h-2.5 rounded-full bg-indigo-500 ring-4 ring-white mt-1.5 ml-1">
                    </div>
                    <div class="ml-6 flex-1 min-w-0">
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:items-baseline mb-1 min-w-0">
                            <span class="font-semibold text-slate-900 capitalize">
                                <?php echo $h->event_type; ?>
                            </span>
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs text-slate-400 break-words whitespace-normal">
                                    <?php echo date('d/m H:i', strtotime($h->created_at)); ?>
                                    -
                                    <?php echo $u ? $u->display_name : 'Sistema'; ?>
                                </span>
                                <form method="post" action="?" data-ajax="true" class="inline"
                                    data-confirm="Tem certeza que deseja excluir este item do histórico?">
                                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                                    <input type="hidden" name="ccs_action" value="delete_history">
                                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
                                    <input type="hidden" name="history_id" value="<?php echo $h->id; ?>">
                                    <button type="submit"
                                        class="text-slate-400 hover:text-red-500 p-1 rounded transition-colors"
                                        title="Excluir item do histórico">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <p class="text-slate-600 text-sm break-words whitespace-normal">
                            <?php echo esc_html($h->description); ?>
                        </p>

                        <?php
                        $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
                        if (!empty($photos)):
                            ?>
                            <div class="flex gap-2 mt-2 overflow-x-auto pb-2">
                                <?php foreach ($photos as $photo_index => $photo_url): ?>
                                    <a href="javascript:void(0)"
                                        onclick="openLightboxFromHistory(<?php echo $h->id; ?>, <?php echo $photo_index; ?>)"
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
                <p class="ml-6 text-slate-400 italic">Sem histórico registrado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Script para Copiar Dados -->
<script>
    // Dados do computador para cópia
    const computerData = {
        hostname: <?php echo json_encode(strtoupper($pc->hostname)); ?>,
        type: <?php echo json_encode($pc->type); ?>,
        status: <?php echo json_encode($pc->status); ?>,
        userName: <?php echo json_encode($pc->user_name ?: '-'); ?>,
        location: <?php echo json_encode($pc->location ?: '-'); ?>,
        property: <?php echo json_encode($pc->property ?: '-'); ?>,
        updatedAt: <?php echo json_encode(date('d/m/Y H:i', strtotime($pc->updated_at))); ?>,
        specs: <?php echo json_encode($pc->specs ?: '-'); ?>,
        notes: <?php echo json_encode($pc->notes ?: '-'); ?>,
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

    function copyComputerData() {
        // Formatar texto para WhatsApp (usando * para negrito)
        let text = '';

        // Header
        text += '🖥️ *FICHA DO COMPUTADOR*\n';
        text += '━━━━━━━━━━━━━━━━━━━━━━\n\n';

        // Dados principais
        text += `*Hostname:* ${computerData.hostname}\n`;
        text += `*Tipo:* ${computerData.type === 'desktop' ? 'Desktop' : 'Notebook'}\n`;
        text += `*Status:* ${computerData.status}\n`;
        text += `*Usuário:* ${computerData.userName}\n`;
        text += `*Local:* ${computerData.location}\n`;
        text += `*Propriedade:* ${computerData.property}\n`;
        text += `*Atualizado em:* ${computerData.updatedAt}\n`;

        // Especificações
        if (computerData.specs && computerData.specs !== '-') {
            text += '\n📋 *Especificações:*\n';
            text += computerData.specs + '\n';
        }

        // Anotações
        if (computerData.notes && computerData.notes !== '-') {
            text += '\n📝 *Anotações:*\n';
            text += computerData.notes + '\n';
        }

        // Histórico
        if (computerData.history.length > 0) {
            text += '\n━━━━━━━━━━━━━━━━━━━━━━\n';
            text += '📜 *HISTÓRICO*\n\n';

            computerData.history.forEach((entry, index) => {
                text += `*${entry.date}* - _${entry.type}_\n`;
                text += `${entry.description}\n`;
                if (entry.user) {
                    text += `👤 ${entry.user}\n`;
                }

                // Links das fotos
                if (entry.photos && entry.photos.length > 0) {
                    text += '📷 Fotos:\n';
                    entry.photos.forEach((photo, photoIndex) => {
                        text += `  ${photoIndex + 1}. ${photo}\n`;
                    });
                }

                if (index < computerData.history.length - 1) {
                    text += '\n';
                }
            });
        }

        // Link direto
        text += '\n━━━━━━━━━━━━━━━━━━━━━━\n';
        text += `🔗 *Link:* ${computerData.pageUrl}`;

        // Copiar para área de transferência
        navigator.clipboard.writeText(text).then(() => {
            // Feedback visual
            const btn = document.getElementById('copyDataBtn');
            const btnText = document.getElementById('copyBtnText');
            const originalText = btnText.textContent;

            btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'hover:bg-emerald-100');
            btn.classList.add('bg-emerald-500', 'text-white', 'border-emerald-600');
            btnText.textContent = 'Copiado! ✓';

            setTimeout(() => {
                btn.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-600');
                btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'hover:bg-emerald-100');
                btnText.textContent = originalText;
            }, 2000);
        }).catch(err => {
            alert('Erro ao copiar dados. Tente novamente.');
            console.error('Erro ao copiar:', err);
        });
    }

    // ========== Lightbox para Fotos ==========
    // Mapeamento de fotos por item do histórico
    const historyPhotos = {
        <?php foreach ($history as $h):
            $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
            if (!empty($photos)):
                ?>
                        <?php echo $h->id; ?>: <?php echo json_encode($photos); ?>,
            <?php endif; endforeach; ?>
    };

    // Coletar TODAS as fotos do PC para navegação global
    const allPhotos = [];
    const photoIndexMap = {}; // Mapeia history_id + photo_index para índice global

    <?php
    $global_index = 0;
    foreach ($history as $h):
        $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
        if (!empty($photos)):
            foreach ($photos as $pIdx => $pUrl):
                ?>
                allPhotos.push(<?php echo json_encode($pUrl); ?>);
                photoIndexMap['<?php echo $h->id; ?>_<?php echo $pIdx; ?>'] = <?php echo $global_index; ?>;
                <?php
                $global_index++;
            endforeach;
        endif;
    endforeach;
    ?>

    /**
     * Abre o lightbox com todas as fotos do PC a partir de um item do histórico
     * @param {number} historyId - ID do item do histórico
     * @param {number} photoIndex - Índice da foto dentro do histórico
     */
    function openLightboxFromHistory(historyId, photoIndex) {
        // Encontrar o índice global da foto
        const key = historyId + '_' + photoIndex;
        const globalIndex = photoIndexMap[key] !== undefined ? photoIndexMap[key] : 0;

        // Abrir lightbox com todas as fotos do PC
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

    function handleCameraInputChange(input) {
        if (!input || input.files.length === 0) {
            return;
        }

        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
        }

        const photoUploadForm = document.getElementById('photoUploadForm');
        if (photoUploadForm) {
            photoUploadForm.submit();
        }
    }
</script>
