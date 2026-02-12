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
$restore_action = $is_cellphone_module ? 'restore_cellphone' : 'restore_computer';
$delete_permanent_action = $is_cellphone_module ? 'delete_permanent_cellphone' : 'delete_permanent_computer';
$id_field = $is_cellphone_module ? 'cellphone_id' : 'computer_id';
$identifier_field = $is_cellphone_module ? 'phone_number' : 'hostname';
$search_placeholder = !empty($module_config['list_search_placeholder']) ? $module_config['list_search_placeholder'] : 'Filtrar...';
$trash_storage_key = !empty($module_config['trash_filters_storage_key']) ? $module_config['trash_filters_storage_key'] : 'ccs_trash_filters';
$details_return_to = $show_trash ? 'trash' : 'list';
$module_param = 'module=' . urlencode($current_module);
?>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">

        <div class="flex flex-col gap-3 w-full sm:w-auto">
            <div class="flex gap-2 w-full">
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    class="block flex-1 sm:w-64 px-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="<?php echo esc_attr($search_placeholder); ?>">

                <button onclick="toggleFilters()"
                    class="inline-flex items-center gap-2 px-3 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span class="hidden sm:inline">Filtros</span>
                </button>
            </div>

            <?php
            $is_filter_no_photos = isset($_GET['filter']) && $_GET['filter'] === 'no_photos';
            $no_photos_class = $is_filter_no_photos ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
            ?>

            <div id="filterPanel" class="hidden overflow-hidden transition-all duration-300 ease-in-out">
                <div class="flex flex-wrap gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200 items-center">
                    <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo esc_attr($no_photos_class); ?>"
                        title="Mostrar itens sem fotos">
                        <input type="checkbox" class="h-4 w-4 text-amber-600 border-slate-300 rounded"
                            <?php echo $is_filter_no_photos ? 'checked' : ''; ?>
                            data-filter-type="status" data-filter-value="no_photos"
                            onchange="applyFilters(this)">
                        <span>Sem Fotos</span>
                    </label>

                    <div class="w-px h-6 bg-slate-300 mx-1"></div>

                    <?php if (!$is_cellphone_module): ?>
                        <?php
                        $is_type_desktop = isset($_GET['type_desktop']) && $_GET['type_desktop'] === '1';
                        $desktop_class = $is_type_desktop ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                        $is_type_notebook = isset($_GET['type_notebook']) && $_GET['type_notebook'] === '1';
                        $notebook_class = $is_type_notebook ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                        ?>
                        <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo esc_attr($desktop_class); ?>">
                            <input type="checkbox" id="filter_desktop" class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                                <?php echo $is_type_desktop ? 'checked' : ''; ?> onchange="applyFilters(this)">
                            <span>Desktops</span>
                        </label>

                        <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo esc_attr($notebook_class); ?>">
                            <input type="checkbox" id="filter_notebook" class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                                <?php echo $is_type_notebook ? 'checked' : ''; ?> onchange="applyFilters(this)">
                            <span>Notebooks</span>
                        </label>

                        <div class="w-px h-6 bg-slate-300 mx-1"></div>
                    <?php endif; ?>

                    <?php foreach ($status_labels as $status_key => $status_label):
                        $param_name = 'status_' . $status_key;
                        $is_active = isset($_GET[$param_name]) && $_GET[$param_name] === '1';
                        $active_class = $is_active ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                        ?>
                        <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo esc_attr($active_class); ?>">
                            <input type="checkbox" data-filter-param="<?php echo esc_attr($param_name); ?>"
                                class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                                <?php echo $is_active ? 'checked' : ''; ?> onchange="applyFilters(this)">
                            <span><?php echo esc_html($status_label); ?></span>
                        </label>
                    <?php endforeach; ?>

                    <div class="w-px h-6 bg-slate-300 mx-1"></div>

                    <?php if ($is_cellphone_module): ?>
                        <?php
                        $department_filters = [
                            'dept_comercial_rn' => 'COMERCIAL-RN',
                            'dept_fabrica_rn' => 'FABRICA-RN',
                            'dept_outro' => 'Outro',
                            'dept_sem' => 'Sem departamento',
                        ];
                        foreach ($department_filters as $param_name => $label):
                            $is_active = isset($_GET[$param_name]) && $_GET[$param_name] === '1';
                            $active_class = $is_active ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                            ?>
                            <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo esc_attr($active_class); ?>">
                                <input type="checkbox" data-filter-param="<?php echo esc_attr($param_name); ?>"
                                    class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                                    <?php echo $is_active ? 'checked' : ''; ?> onchange="applyFilters(this)">
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                        $locations = [
                            'loc_fabrica' => 'Fabrica',
                            'loc_centro' => 'Centro',
                            'loc_perdido' => 'Perdido',
                            'loc_manutencao' => 'Manutencao',
                            'loc_sem_local' => 'Sem Local',
                        ];
                        foreach ($locations as $param_name => $label):
                            $is_active = isset($_GET[$param_name]) && $_GET[$param_name] === '1';
                            $active_class = $is_active ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                            ?>
                            <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo esc_attr($active_class); ?>">
                                <input type="checkbox" data-filter-param="<?php echo esc_attr($param_name); ?>"
                                    class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                                    <?php echo $is_active ? 'checked' : ''; ?> onchange="applyFilters(this)">
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                function applyFilters(element) {
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('module', <?php echo wp_json_encode($current_module); ?>);

                    if (element.dataset.filterType === 'status') {
                        if (element.checked) {
                            urlParams.set('filter', element.dataset.filterValue);
                        } else if (urlParams.get('filter') === element.dataset.filterValue) {
                            urlParams.delete('filter');
                        }
                    }

                    if (element.id === 'filter_desktop') {
                        if (element.checked) urlParams.set('type_desktop', '1');
                        else urlParams.delete('type_desktop');
                    }

                    if (element.id === 'filter_notebook') {
                        if (element.checked) urlParams.set('type_notebook', '1');
                        else urlParams.delete('type_notebook');
                    }

                    if (element.dataset.filterParam) {
                        if (element.checked) urlParams.set(element.dataset.filterParam, '1');
                        else urlParams.delete(element.dataset.filterParam);
                    }

                    window.location.search = urlParams.toString();
                }
            </script>

            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500">Total: <strong id="visibleCount" class="text-slate-700"><?php echo count($computers); ?></strong></span>
            </div>
        </div>

        <div class="flex items-center">
            <?php if ($show_trash): ?>
                <a href="?<?php echo esc_attr($module_param); ?>&view=list" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar para Ativos
                </a>
            <?php else: ?>
                <a href="?<?php echo esc_attr($module_param); ?>&view=trash" class="text-sm text-slate-500 hover:text-red-600 flex items-center transition-colors">
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
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo $is_cellphone_module ? 'Numero' : 'Hostname'; ?></th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo $is_cellphone_module ? 'Observacao' : 'Anotacoes'; ?></th>
                    <?php if ($is_cellphone_module): ?>
                        <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Departamento</th>
                        <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Usuario</th>
                    <?php else: ?>
                        <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Usuario / Local</th>
                    <?php endif; ?>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Acoes</th>
                </tr>
            </thead>
            <tbody id="computerTableBody" class="divide-y divide-slate-100">
                <?php foreach ($computers as $pc):
                    $status_color = match ($pc->status) {
                        'active' => 'bg-emerald-100 text-emerald-800',
                        'backup' => 'bg-amber-100 text-amber-800',
                        'maintenance' => 'bg-rose-100 text-rose-800',
                        default => 'bg-slate-100 text-slate-800',
                    };
                    $status_label = $status_labels[$pc->status] ?? ucfirst((string) $pc->status);
                    $identifier_value = (string) ($pc->$identifier_field ?? '');
                    if (!$is_cellphone_module) {
                        $identifier_value = strtoupper($identifier_value);
                    }
                    $identifier_search_value = $identifier_value;
                    if ($is_cellphone_module) {
                        $identifier_search_value .= ' ' . preg_replace('/\D+/', '', $identifier_value);
                    }
                    ?>
                    <tr class="computer-row hover:bg-slate-50"
                        data-search-terms="<?php echo esc_attr(strtolower(($identifier_search_value ?? '') . ' ' . ($pc->user_name ?? '') . ' ' . ($pc->department ?? '') . ' ' . ($pc->location ?? '') . ' ' . ($pc->notes ?? '') . ' ' . ($pc->search_meta ?? ''))); ?>">
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($status_color); ?>">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 font-medium text-slate-900">
                            <a href="?<?php echo esc_attr($module_param); ?>&view=details&id=<?php echo intval($pc->id); ?>&return_to=<?php echo esc_attr($details_return_to); ?>"
                                class="text-indigo-600 hover:text-indigo-900">
                                <?php echo esc_html($identifier_value !== '' ? $identifier_value : '-'); ?>
                            </a>
                        </td>
                        <td class="px-4 py-2 text-slate-600 text-xs"><?php echo esc_html($pc->notes ?? ''); ?></td>

                        <?php if ($is_cellphone_module): ?>
                            <td class="px-4 py-2 text-slate-600"><?php echo esc_html($pc->department ?: '-'); ?></td>
                            <td class="px-4 py-2 text-slate-600"><?php echo esc_html($pc->user_name ?: '-'); ?></td>
                        <?php else: ?>
                            <td class="px-4 py-2 text-slate-600 capitalize"><?php echo esc_html($pc->type ?? '-'); ?></td>
                            <td class="px-4 py-2 text-slate-600">
                                <div class="font-medium text-slate-900"><?php echo esc_html($pc->user_name ?: '-'); ?></div>
                                <div class="text-xs text-slate-400"><?php echo esc_html($pc->location ?? ''); ?></div>
                            </td>
                        <?php endif; ?>

                        <td class="px-4 py-2">
                            <?php if ($show_trash): ?>
                                <?php if ($can_edit): ?>
                                    <form method="post" action="?" class="inline" data-ajax="true"
                                        data-confirm="Tem certeza que deseja restaurar este item?">
                                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                                        <input type="hidden" name="ccs_action" value="<?php echo esc_attr($restore_action); ?>">
                                        <input type="hidden" name="<?php echo esc_attr($id_field); ?>" value="<?php echo intval($pc->id); ?>">
                                        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">
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
                                    <form method="post" action="?" class="inline ml-2" data-ajax="true"
                                        data-confirm="Tem certeza que deseja excluir PERMANENTEMENTE este item? Esta acao nao pode ser desfeita.">
                                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                                        <input type="hidden" name="ccs_action" value="<?php echo esc_attr($delete_permanent_action); ?>">
                                        <input type="hidden" name="<?php echo esc_attr($id_field); ?>" value="<?php echo intval($pc->id); ?>">
                                        <input type="hidden" name="module" value="<?php echo esc_attr($current_module); ?>">
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-900 font-medium text-xs flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                            Excluir Permanentemente
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Somente visualizacao</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="?<?php echo esc_attr($module_param); ?>&view=details&id=<?php echo intval($pc->id); ?>&return_to=<?php echo esc_attr($details_return_to); ?>"
                                    class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Gerenciar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($computers)): ?>
                    <tr class="no-results-row">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            <?php echo $is_cellphone_module ? 'Nenhum celular encontrado.' : 'Nenhum computador encontrado.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50 text-xs text-slate-500 flex justify-between items-center"></div>
</div>

<script>
(function() {
    const currentParams = window.location.search;
    const storageKey = <?php echo wp_json_encode($trash_storage_key); ?>;
    if (currentParams && currentParams.includes('view=trash')) {
        sessionStorage.setItem(storageKey, currentParams);
    } else {
        sessionStorage.removeItem(storageKey);
    }
})();
</script>
