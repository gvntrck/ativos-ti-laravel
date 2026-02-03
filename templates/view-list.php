<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div
        class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">

        <div class="flex gap-2 w-full sm:w-auto">
            <input type="text" id="searchInput" onkeyup="filterTable()"
                class="block w-full sm:w-64 px-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="Filtrar computadores...">

            <?php
            $is_filter_outdated = isset($_GET['filter']) && $_GET['filter'] === 'outdated';
            $filter_url = $is_filter_outdated ? '?' : '?filter=outdated';
            $filter_class = $is_filter_outdated ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
            ?>
            <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo $filter_class; ?>"
                title="Mostrar computadores com Windows desatualizado (> 30 dias)">
                <input type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                    <?php echo $is_filter_outdated ? 'checked' : ''; ?>
                    onchange="window.location.href=this.checked ? '?filter=outdated' : '?';">
                <span>Desatualizados</span>
            </label>

        </div>

        <div class="flex items-center">
            <?php if ($show_trash): ?>
                <a href="?" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar para Ativos
                </a>
            <?php else: ?>
                <a href="?view=trash" class="text-sm text-slate-500 hover:text-red-600 flex items-center transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Lixeira
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Hostname</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Usuário / Local
                    </th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody id="computerTableBody" class="divide-y divide-slate-100">
                <?php foreach ($computers as $pc):
                    $status_color = match ($pc->status) {
                        'active' => 'bg-emerald-100 text-emerald-800',
                        'backup' => 'bg-amber-100 text-amber-800',
                        'maintenance' => 'bg-rose-100 text-rose-800',
                        default => 'bg-slate-100 text-slate-800'
                    };
                    $status_label = match ($pc->status) {
                        'active' => 'Em Uso', 'backup' => 'Backup', 'maintenance' => 'Manutenção', default => 'Aposentado'
                    };

                    // Windows Update Status Logic
                    $last_update = $pc->last_windows_update ? strtotime($pc->last_windows_update) : 0;
                    $days_since_update = floor((time() - $last_update) / (60 * 60 * 24));
                    $is_outdated = $days_since_update > 30;

                    if (!$pc->last_windows_update) {
                        $win_status_color = 'text-red-500';
                        $win_status_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                        $win_tooltip = 'Windows nunca atualizado';
                    } elseif ($is_outdated) {
                        $win_status_color = 'text-red-500';
                        $win_status_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                        $win_tooltip = "Atualizado há $days_since_update dias";
                    } else {
                        $win_status_color = 'text-emerald-500';
                        $win_status_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                        $win_tooltip = "Atualizado há $days_since_update dia(s)";
                    }
                    ?>
                    <tr class="hover:bg-slate-50"
                        data-search-terms="<?php echo esc_attr(strtolower(($pc->hostname ?? '') . ' ' . ($pc->user_name ?? '') . ' ' . ($pc->location ?? '') . ' ' . ($pc->type ?? '') . ' ' . ($pc->search_meta ?? ''))); ?>">
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                <?php echo $status_label; ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 font-medium text-slate-900">
                            <a href="?view=details&id=<?php echo $pc->id; ?>" class="text-indigo-600 hover:text-indigo-900">
                                <?php echo esc_html(strtoupper($pc->hostname)); ?>
                            </a>
                        </td>
                        <td class="px-4 py-2 text-slate-600 capitalize">
                            <?php echo $pc->type; ?>
                        </td>
                        <td class="px-4 py-2 text-slate-600">
                            <div class="font-medium text-slate-900">
                                <?php echo esc_html($pc->user_name ?: '-'); ?>
                            </div>
                            <div class="text-xs text-slate-400">
                                <?php echo esc_html($pc->location); ?>
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <?php if ($show_trash): ?>
                                <form method="post" action="?" class="inline"
                                    onsubmit="return confirm('Tem certeza que deseja restaurar este computador?');">
                                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                                    <input type="hidden" name="ccs_action" value="restore_computer">
                                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
                                    <button type="submit"
                                        class="text-emerald-600 hover:text-emerald-900 font-medium text-xs flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Restaurar
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="?view=details&id=<?php echo $pc->id; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Gerenciar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($computers)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">Nenhum computador encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>