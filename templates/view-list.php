<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div
        class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">

        <div class="flex flex-col gap-3 w-full sm:w-auto">
            <!-- Search Bar -->
            <div class="flex gap-2 w-full">
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    class="block flex-1 sm:w-64 px-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Filtrar computadores...">
                
                <!-- Filter Toggle Button -->
                <button onclick="toggleFilters()" 
                    class="inline-flex items-center gap-2 px-3 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span class="hidden sm:inline">Filtros</span>
                    <?php 
                    $active_filter_count = 0;
                    if (isset($_GET['filter']) && $_GET['filter'] === 'no_photos') {
                        $active_filter_count = 1;
                    }
                    if ($active_filter_count > 0): 
                    ?>
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-indigo-600 rounded-full"><?php echo $active_filter_count; ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <?php
            // No Photos Filter
            $is_filter_no_photos = isset($_GET['filter']) && $_GET['filter'] === 'no_photos';
            $no_photos_class = $is_filter_no_photos ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';

            // [NEW] Type Filters
            $is_type_desktop = isset($_GET['type_desktop']) && $_GET['type_desktop'] === '1';
            $desktop_class = $is_type_desktop ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';

            $is_type_notebook = isset($_GET['type_notebook']) && $_GET['type_notebook'] === '1';
            $notebook_class = $is_type_notebook ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
            ?>

            <!-- Collapsible Filter Panel -->
            <div id="filterPanel" class="hidden overflow-hidden transition-all duration-300 ease-in-out">
                <div class="flex flex-wrap gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200 items-center">
                    <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo $no_photos_class; ?>"
                        title="Mostrar computadores sem fotos">
                        <input type="checkbox" class="h-4 w-4 text-amber-600 border-slate-300 rounded"
                            <?php echo $is_filter_no_photos ? 'checked' : ''; ?>
                            data-filter-type="status" data-filter-value="no_photos"
                            onchange="applyFilters(this)">
                        <span>Sem Fotos</span>
                    </label>
                    
                    <!-- Divider -->
                    <div class="w-px h-6 bg-slate-300 mx-1"></div>

                    <!-- Type Filters -->
                    <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo $desktop_class; ?>"
                        title="Mostrar apenas Desktops">
                        <input type="checkbox" id="filter_desktop" class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                            <?php echo $is_type_desktop ? 'checked' : ''; ?>
                            onchange="applyFilters(this)">
                        <span>Desktops</span>
                    </label>
                    
                    <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo $notebook_class; ?>"
                        title="Mostrar apenas Notebooks">
                        <input type="checkbox" id="filter_notebook" class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                            <?php echo $is_type_notebook ? 'checked' : ''; ?>
                            onchange="applyFilters(this)">
                        <span>Notebooks</span>
                    </label>

                    <!-- Divider -->
                    <div class="w-px h-6 bg-slate-300 mx-1"></div>
                    
                    <!-- Status Filters -->
                    <?php
                    $statuses = [
                        'active' => ['label' => 'Em Uso', 'class' => 'text-emerald-600'],
                        'backup' => ['label' => 'Backup', 'class' => 'text-amber-600'],
                        'maintenance' => ['label' => 'Manutenção', 'class' => 'text-rose-600'],
                        'retired' => ['label' => 'Aposentado', 'class' => 'text-slate-600']
                    ];
                    
                    foreach ($statuses as $key => $props):
                        $param_name = 'status_' . $key;
                        $is_active = isset($_GET[$param_name]) && $_GET[$param_name] === '1';
                        // Colors for active state
                        $active_class = $is_active 
                            ? 'bg-indigo-100 text-indigo-700 border-indigo-200' 
                            : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                    ?>
                    <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo $active_class; ?>"
                        title="Mostrar status: <?php echo $props['label']; ?>">
                        <input type="checkbox" id="filter_<?php echo $param_name; ?>" 
                            data-filter-param="<?php echo $param_name; ?>"
                            class="h-4 w-4 <?php echo $props['class']; ?> border-slate-300 rounded"
                            <?php echo $is_active ? 'checked' : ''; ?>
                            onchange="applyFilters(this)">
                        <span><?php echo $props['label']; ?></span>
                    </label>
                    <?php endforeach; ?>

                    <!-- Divider -->
                    <div class="w-px h-6 bg-slate-300 mx-1"></div>

                    <!-- Location Filters -->
                    <?php
                    $locations = ['Fabrica' => 'Fábrica', 'Centro' => 'Centro', 'Perdido' => 'Perdido', 'Manutenção' => 'Manut.'];
                    foreach ($locations as $slug => $label):
                        $param_name = 'loc_' . strtolower(str_replace('ç', 'c', str_replace('ã', 'a', $slug))); // loc_fabrica, loc_centro... usually just lower slug if simple
                        // Manual mapping for safety
                        $param_name = match($slug) {
                            'Fabrica' => 'loc_fabrica',
                            'Centro' => 'loc_centro',
                            'Perdido' => 'loc_perdido',
                            'Manutenção' => 'loc_manutencao',
                            default => 'loc_' . strtolower($slug)
                        };
                        
                        $is_active = isset($_GET[$param_name]) && $_GET[$param_name] === '1';
                        $active_class = $is_active ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                    ?>
                    <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo $active_class; ?>"
                        title="Mostrar local: <?php echo $label; ?>">
                        <input type="checkbox" id="filter_<?php echo $param_name; ?>" 
                            data-filter-param="<?php echo $param_name; ?>"
                            class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                            <?php echo $is_active ? 'checked' : ''; ?>
                            onchange="applyFilters(this)">
                        <span><?php echo $label; ?></span>
                    </label>
                    <?php endforeach; ?>
                    
                    <?php
                    // Filtro para computadores sem local definido
                    $is_sem_local = isset($_GET['loc_sem_local']) && $_GET['loc_sem_local'] === '1';
                    $sem_local_class = $is_sem_local ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50';
                    ?>
                    <label class="flex items-center gap-2 px-2 py-1 border rounded-md text-sm font-medium transition-colors whitespace-nowrap <?php echo $sem_local_class; ?>"
                        title="Mostrar computadores sem local definido">
                        <input type="checkbox" id="filter_loc_sem_local" 
                            data-filter-param="loc_sem_local"
                            class="h-4 w-4 text-indigo-600 border-slate-300 rounded"
                            <?php echo $is_sem_local ? 'checked' : ''; ?>
                            onchange="applyFilters(this)">
                        <span>Sem Local</span>
                    </label>
                </div>
            </div>

            <script>
            function applyFilters(element) {
                const urlParams = new URLSearchParams(window.location.search);
                
                // Handle specific logic filters in "filter" URL param
                if (element.dataset.filterType === 'status') {
                    if (element.checked) {
                        urlParams.set('filter', element.dataset.filterValue);
                    } else {
                        if (urlParams.get('filter') === element.dataset.filterValue) {
                            urlParams.delete('filter');
                        }
                    }
                }
                
                // Handle Type Filters (Cumulative)
                if (element.id === 'filter_desktop') {
                    if (element.checked) urlParams.set('type_desktop', '1');
                    else urlParams.delete('type_desktop');
                }
                
                if (element.id === 'filter_notebook') {
                    if (element.checked) urlParams.set('type_notebook', '1');
                    else urlParams.delete('type_notebook');
                }

                // Handle Generic Cumulative Filters (Locations & New Statuses)
                if (element.dataset.filterParam) {
                    if (element.checked) urlParams.set(element.dataset.filterParam, '1');
                    else urlParams.delete(element.dataset.filterParam);
                }
                
                window.location.search = urlParams.toString();
            }
            </script>

            <!-- Item Count -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500">Total: <strong id="visibleCount" class="text-slate-700"><?php echo count($computers); ?></strong></span>
            </div>
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
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Anotações</th>
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

                    ?>
                    <tr class="computer-row hover:bg-slate-50"
                        data-search-terms="<?php echo esc_attr(strtolower(($pc->hostname ?? '') . ' ' . ($pc->user_name ?? '') . ' ' . ($pc->location ?? '') . ' ' . ($pc->property ?? '') . ' ' . ($pc->type ?? '') . ' ' . ($pc->search_meta ?? ''))); ?>">
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
                        <td class="px-4 py-2 text-slate-600 text-xs">
                            <?php echo esc_html($pc->notes); ?>
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
                                <form method="post" action="?" class="inline" data-ajax="true"
                                    data-confirm="Tem certeza que deseja restaurar este computador?">
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
                                <form method="post" action="?" class="inline ml-2" data-ajax="true"
                                    data-confirm="Tem certeza que deseja excluir PERMANENTEMENTE este computador? Todo o histórico e dados serão apagados. Esta ação não pode ser desfeita.">
                                    <?php wp_nonce_field('ccs_action_nonce'); ?>
                                    <input type="hidden" name="ccs_action" value="delete_permanent_computer">
                                    <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
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
                                <a href="?view=details&id=<?php echo $pc->id; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Gerenciar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($computers)): ?>
                    <tr class="no-results-row">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Nenhum computador encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50 text-xs text-slate-500 flex justify-between items-center">
    </div>
</div>

<script>
// Salvar filtros atuais no sessionStorage para preservar ao voltar da página de detalhes
(function() {
    const currentParams = window.location.search;
    if (currentParams) {
        sessionStorage.setItem('ccs_list_filters', currentParams);
    } else {
        // Se não há filtros, limpar o sessionStorage
        sessionStorage.removeItem('ccs_list_filters');
    }
})();
</script>
