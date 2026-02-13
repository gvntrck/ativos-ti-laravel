<?php

// tabela principal



$current_module = isset($current_module) ? (string) $current_module : 'computers';
$module_config = isset($module_config) && is_array($module_config) ? $module_config : [];
$can_save_table_preferences = isset($can_save_table_preferences) ? (bool) $can_save_table_preferences : false;
$report_primary_column = !empty($module_config['report_primary_column']) ? (string) $module_config['report_primary_column'] : 'hostname';
$report_title = !empty($module_config['report_title']) ? (string) $module_config['report_title'] : 'Relatorios';
$report_search_placeholder = !empty($module_config['report_search_placeholder'])
    ? (string) $module_config['report_search_placeholder']
    : 'Busca global';
$module_param = 'module=' . urlencode($current_module);

$column_labels = [];
$column_filter_meta = [];
$column_widths = [];
$mobile_auto_fit_columns = ['asset_code', 'phone_number'];
$mobile_auto_fit_widths = [];
$report_origin_view = isset($_GET['view']) ? sanitize_text_field((string) $_GET['view']) : 'list';
if (!in_array($report_origin_view, ['list', 'reports'], true)) {
    $report_origin_view = 'list';
}

if ($current_module === 'computers' && in_array('property', $report_columns, true)) {
    $report_columns = array_values(array_filter($report_columns, static function ($column) {
        return $column !== 'property';
    }));

    $hostname_index = array_search('hostname', $report_columns, true);
    if ($hostname_index === false) {
        $report_columns[] = 'property';
    } else {
        array_splice($report_columns, $hostname_index + 1, 0, ['property']);
    }
}

$label_overrides = [
    'id' => 'ID',
    'asset_code' => 'ID Celular',
    'hostname' => 'Hostname',
    'phone_number' => 'Numero Celular',
    'brand_model' => 'Marca Modelo',
    'status' => 'Status',
    'deleted' => 'Excluido',
    'user_name' => 'Usuario',
    'location' => 'Localizacao',
    'property' => 'Propriedade',
    'department' => 'Departamento',
    'type' => 'Tipo',
    'specs' => 'Especificacoes',
    'notes' => 'Anotacoes',
    'photo_url' => 'Foto',
    'created_at' => 'Criado Em',
    'updated_at' => 'Atualizado Em',
];

$format_report_value = static function ($column, $value) {
    $value = (string) $value;

    if ($value === '') {
        return '-';
    }

    if ($column === 'deleted') {
        return $value === '1' ? 'Sim' : 'Nao';
    }

    if ($column === 'type') {
        return $value === 'desktop' ? 'Desktop' : ($value === 'notebook' ? 'Notebook' : $value);
    }

    if ($column === 'status') {
        return match ($value) {
            'active' => 'Em uso',
            'backup' => 'Backup',
            'maintenance' => 'Manutencao',
            'retired' => 'Aposentado',
            default => $value,
        };
    }

    if ($column === 'created_at' || $column === 'updated_at') {
        $timestamp = strtotime($value);
        if ($timestamp) {
            return date('d/m/Y H:i', $timestamp);
        }
    }

    return $value;
};

$get_text_length = static function ($value) {
    if (function_exists('mb_strlen')) {
        return mb_strlen((string) $value, 'UTF-8');
    }

    return strlen((string) $value);
};

foreach ($report_columns as $column) {
    $column_labels[$column] = $label_overrides[$column] ?? ucwords(str_replace('_', ' ', $column));

    $unique_values_map = [];
    $has_empty_values = false;
    $has_mobile_auto_fit = in_array($column, $mobile_auto_fit_columns, true);
    $auto_fit_max_length = $has_mobile_auto_fit ? $get_text_length($column_labels[$column]) : 0;

    foreach ($report_rows as $row) {
        $raw_value = isset($row->$column) ? (string) $row->$column : '';
        $normalized = trim($raw_value);

        if ($has_mobile_auto_fit) {
            $display_value = trim($format_report_value($column, $raw_value));
            if ($display_value === '') {
                $display_value = '-';
            }
            $auto_fit_max_length = max($auto_fit_max_length, $get_text_length($display_value));
        }

        if ($normalized === '') {
            $has_empty_values = true;
            continue;
        }

        $unique_values_map[$normalized] = true;
    }

    $unique_values = array_keys($unique_values_map);
    natcasesort($unique_values);
    $unique_values = array_values($unique_values);

    $is_date_column = in_array($column, ['created_at', 'updated_at'], true);
    $is_long_text_column = in_array($column, ['specs', 'notes'], true);
    $is_url_column = $column === 'photo_url';
    $force_text_columns = ['id', 'asset_code', 'user_name', 'brand_model', 'phone_number'];
    $force_text_filter = in_array($column, $force_text_columns, true);

    $use_select_filter = !$is_date_column
        && !$is_long_text_column
        && !$is_url_column
        && !$force_text_filter
        && count($unique_values) <= 15;

    $column_filter_meta[$column] = [
        'values' => $unique_values,
        'has_empty' => $has_empty_values,
        'is_date' => $is_date_column,
        'use_select' => $use_select_filter,
    ];

    if ($has_mobile_auto_fit) {
        $mobile_auto_fit_widths[$column] = max(74, min(172, (int) ceil(($auto_fit_max_length * 7.1) + 20)));
    }

    $column_widths[$column] = match ($column) {
        'id' => 90,
        'asset_code' => 140,
        'hostname', 'phone_number' => 190,
        'specs', 'notes' => 320,
        'photo_url' => 220,
        'created_at', 'updated_at' => 170,
        default => 160,
    };
}

$table_preferences = is_array($table_preferences ?? null) ? $table_preferences : [];

$srv_hidden_columns = [];
if (!empty($table_preferences['columns_visibility']) && is_array($table_preferences['columns_visibility'])) {
    foreach ($table_preferences['columns_visibility'] as $col => $visible) {
        if ($visible === false) {
            $srv_hidden_columns[$col] = true;
        }
    }
}

if (!empty($table_preferences['columns_order']) && is_array($table_preferences['columns_order'])) {
    $saved_order = $table_preferences['columns_order'];
    $reordered = [];
    foreach ($saved_order as $col) {
        if (in_array($col, $report_columns, true)) {
            $reordered[] = $col;
        }
    }
    foreach ($report_columns as $col) {
        if (!in_array($col, $reordered, true)) {
            $reordered[] = $col;
        }
    }
    $report_columns = $reordered;
}

$srv_density_compact = (!empty($table_preferences['density']) && $table_preferences['density'] === 'compact');
$srv_zebra = !empty($table_preferences['zebra']);
$srv_visible_count = count($report_columns) - count($srv_hidden_columns);

$table_preferences_config = [
    'columns' => array_values($report_columns),
    'labels' => $column_labels,
    'preferences' => $table_preferences,
    'nonce' => wp_create_nonce('ccs_action_nonce'),
    'save_url' => '?' . $module_param,
    'module' => $current_module,
];
?>

<style id="ccsPreloadHide">#reportsTableBody{visibility:hidden}</style>
<script>setTimeout(function(){var s=document.getElementById('ccsPreloadHide');if(s)s.remove()},5000)</script>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-3 py-2 border-b border-slate-100 bg-slate-50/60 lg:px-4 lg:py-4">
        <div class="flex items-center justify-between gap-2 lg:hidden">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-sm text-slate-500 whitespace-nowrap">
                    <strong id="reportVisibleCountMobile" class="text-slate-700"><?php echo count($report_rows); ?></strong> registros
                </span>
                <span id="reportActiveFiltersBadge" class="hidden inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-700 whitespace-nowrap">
                    <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span id="reportActiveFiltersCount">0</span>
                </span>
            </div>
            <button type="button" id="reportToolbarToggle"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 active:bg-slate-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                Filtros
                <svg id="reportToolbarChevron" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>

        <div id="reportToolbarPanel" class="hidden lg:block mt-2 lg:mt-0">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="hidden lg:block">
                    <h2 class="text-lg font-semibold text-slate-900"><?php echo esc_html($report_title); ?></h2>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <input id="reportGlobalSearch" type="search" autocomplete="one-time-code"
                        class="block w-full sm:w-80 px-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        placeholder="<?php echo esc_attr($report_search_placeholder); ?>">
                    <select id="reportAuditFilter"
                        class="block w-full sm:w-auto px-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white text-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Auditoria: Todos</option>
                        <option value="audited">Auditados</option>
                        <option value="pending">Nao auditados</option>
                    </select>
                    <button type="button" id="reportEditTableBtn"
                        class="btn btn-secondary whitespace-nowrap <?php echo $can_save_table_preferences ? '' : 'opacity-60 cursor-not-allowed'; ?>"
                        <?php echo $can_save_table_preferences ? '' : 'disabled title="Sem permissao para personalizar a tabela"'; ?>>Editar tabela</button>
                    <button type="button" id="clearReportFilters" class="btn btn-secondary whitespace-nowrap">Limpar filtros</button>
                </div>
            </div>
            <div class="mt-3 text-sm text-slate-500 hidden lg:block">
                Linhas visiveis:
                <strong id="reportVisibleCount" class="text-slate-700"><?php echo count($report_rows); ?></strong>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="reportsTable" class="w-max min-w-full table-fixed text-left border-collapse text-sm<?php echo $srv_density_compact ? ' report-density-compact' : ''; ?><?php echo $srv_zebra ? ' report-zebra-enabled' : ''; ?>">
            <colgroup>
                <?php foreach ($report_columns as $column): ?>
                    <?php
                    $col_style = 'width: ' . intval($column_widths[$column] ?? 160) . 'px;';
                    if (isset($mobile_auto_fit_widths[$column])) {
                        $col_style .= ' --mobile-auto-fit-width: ' . intval($mobile_auto_fit_widths[$column]) . 'px;';
                    }
                    if (isset($srv_hidden_columns[$column])) {
                        $col_style .= ' display: none;';
                    }
                    ?>
                    <col data-report-col="<?php echo esc_attr($column); ?>"
                        style="<?php echo esc_attr($col_style); ?>">
                <?php endforeach; ?>
            </colgroup>
            <thead class="bg-slate-50">
                <tr class="border-b border-slate-200">
                    <?php foreach ($report_columns as $column): ?>
                        <th data-report-header-cell="<?php echo esc_attr($column); ?>"
                            class="px-3 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wider transition-colors"<?php echo isset($srv_hidden_columns[$column]) ? ' style="display:none"' : ''; ?>>
                            <?php echo esc_html($column_labels[$column]); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
                <tr class="border-b border-slate-200 bg-white">
                    <?php foreach ($report_columns as $column): ?>
                        <th data-report-filter-cell="<?php echo esc_attr($column); ?>" class="px-2 py-2 transition-colors"<?php echo isset($srv_hidden_columns[$column]) ? ' style="display:none"' : ''; ?>>
                            <?php $meta = $column_filter_meta[$column]; ?>
                            <?php if ($column === 'photo_url'): ?>
                                <select data-report-filter="<?php echo esc_attr($column); ?>"
                                    data-report-filter-type="select"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                    <option value="">Todos</option>
                                    <option value="__NOT_EMPTY__">Com foto</option>
                                    <option value="__EMPTY__">Sem foto</option>
                                </select>
                            <?php elseif ($meta['is_date']): ?>
                                <input type="date" autocomplete="one-time-code"
                                    data-report-filter="<?php echo esc_attr($column); ?>"
                                    data-report-filter-type="date"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:outline-none focus:border-indigo-500 focus:ring-indigo-500">
                            <?php elseif ($meta['use_select']): ?>
                                <select data-report-filter="<?php echo esc_attr($column); ?>"
                                    data-report-filter-type="select"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                    <option value="">Todos</option>
                                    <?php if ($meta['has_empty']): ?>
                                        <option value="__EMPTY__">(vazio)</option>
                                    <?php endif; ?>
                                    <?php foreach ($meta['values'] as $value): ?>
                                        <option value="<?php echo esc_attr(strtolower($value)); ?>">
                                            <?php echo esc_html($format_report_value($column, $value)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <?php
                                $is_mobile_numeric_filter = in_array($column, ['asset_code', 'phone_number'], true);
                                $filter_input_mode = $is_mobile_numeric_filter ? 'numeric' : 'text';
                                $filter_placeholder = $is_mobile_numeric_filter ? 'Digite numeros...' : 'Filtrar...';
                                ?>
                                <input type="search" autocomplete="one-time-code"
                                    data-report-filter="<?php echo esc_attr($column); ?>"
                                    data-report-filter-type="text"
                                    inputmode="<?php echo esc_attr($filter_input_mode); ?>"
                                    enterkeyhint="search"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:outline-none focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="<?php echo esc_attr($filter_placeholder); ?>">
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="reportsTableBody" class="divide-y divide-slate-100">
                <?php foreach ($report_rows as $row): ?>
                    <?php
                    $row_id = isset($row->id) ? intval($row->id) : 0;
                    $row_photos = [];

                    if ($row_id > 0 && isset($report_photos_map[$row_id]) && is_array($report_photos_map[$row_id])) {
                        $row_photos = array_values($report_photos_map[$row_id]);
                    }

                    $search_terms = [];
                    $row_attributes = [];

                    foreach ($report_columns as $column) {
                        $raw_value = isset($row->$column) ? (string) $row->$column : '';
                        if ($column === 'photo_url') {
                            $normalized_value = !empty($row_photos) ? 'with_photo' : '';
                        } elseif ($column === 'phone_number') {
                            $digits_only = preg_replace('/\D+/', '', $raw_value);
                            $normalized_value = strtolower(trim($raw_value . ' ' . $digits_only));
                        } else {
                            $normalized_value = strtolower(trim($raw_value));
                        }
                        $row_attributes[] = 'data-col-' . esc_attr($column) . '="' . esc_attr($normalized_value) . '"';
                        $search_terms[] = $normalized_value;
                    }

                    $row_search = trim(implode(' ', $search_terms));
                    ?>
                    <?php $audit_status = !empty($row->last_audit_at) ? 'audited' : 'pending'; ?>
                    <tr class="report-row hover:bg-slate-50"
                        data-row-id="<?php echo $row_id; ?>"
                        data-audit-status="<?php echo esc_attr($audit_status); ?>"
                        data-report-search="<?php echo esc_attr($row_search); ?>" <?php echo implode(' ', $row_attributes); ?>>
                        <?php foreach ($report_columns as $column): ?>
                            <?php
                            $raw_value = isset($row->$column) ? (string) $row->$column : '';
                            $formatted_value = $format_report_value($column, $raw_value);
                            $is_long_text = in_array($column, ['specs', 'notes'], true);
                            $display_value = $is_long_text ? trim($formatted_value) : $formatted_value;
                            ?>
                            <td data-report-cell="<?php echo esc_attr($column); ?>" class="px-3 py-2 align-top"<?php echo isset($srv_hidden_columns[$column]) ? ' style="display:none"' : ''; ?>>
                                <?php if ($column === $report_primary_column && $row_id > 0): ?>
                                    <div class="flex items-center gap-1.5">
                                        <a href="?<?php echo esc_attr($module_param); ?>&view=details&id=<?php echo $row_id; ?>&return_to=<?php echo esc_attr($report_origin_view); ?>"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            <?php echo esc_html($current_module === 'computers' ? strtoupper($raw_value) : ($raw_value !== '' ? $raw_value : '-')); ?>
                                        </a>
                                        <?php if (!empty($row->last_audit_at)): ?>
                                            <span class="inline-flex items-center flex-shrink-0" title="Auditado em <?php echo esc_attr(date('d/m/Y H:i', strtotime($row->last_audit_at))); ?>">
                                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center flex-shrink-0" title="Auditoria pendente">
                                                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($column === 'photo_url' && !empty($row_photos)): ?>
                                    <?php
                                    $primary_photo = esc_url_raw(trim($raw_value));
                                    $fallback_photo = esc_url_raw((string) $row_photos[0]);
                                    $trigger_photo = $primary_photo !== '' ? $primary_photo : $fallback_photo;

                                    $start_index = 0;
                                    if ($primary_photo !== '') {
                                        $found_index = array_search($primary_photo, $row_photos, true);
                                        if ($found_index !== false) {
                                            $start_index = intval($found_index);
                                        }
                                    }

                                    $photos_json = wp_json_encode($row_photos);
                                    if ($photos_json === false) {
                                        $photos_json = '[]';
                                    }
                                    ?>
                                    <a href="<?php echo esc_url($trigger_photo); ?>"
                                        data-report-photo-url="<?php echo esc_url($trigger_photo); ?>"
                                        data-report-photos="<?php echo esc_attr($photos_json); ?>"
                                        data-report-photo-index="<?php echo esc_attr((string) $start_index); ?>"
                                        class="text-indigo-600 hover:text-indigo-900 underline break-all">
                                        <?php echo count($row_photos) > 1 ? 'Fotos (' . count($row_photos) . ')' : 'Foto'; ?>
                                    </a>
                                <?php elseif ($column === 'photo_url'): ?>
                                    <span class="text-slate-500">-</span>
                                <?php elseif ($column === 'user_name' && !empty($can_edit) && $row_id > 0): ?>
                                    <span class="ccs-inline-editable text-slate-700 block whitespace-nowrap overflow-hidden text-ellipsis cursor-pointer hover:bg-indigo-50 hover:text-indigo-700 rounded px-1 -mx-1 transition-colors"
                                        data-inline-editable="user_name"
                                        data-inline-raw="<?php echo esc_attr($raw_value); ?>"
                                        title="Clique para editar"><?php echo esc_html($display_value); ?></span>
                                <?php elseif ($column === 'notes' && !empty($can_edit) && $row_id > 0): ?>
                                    <span class="ccs-inline-editable whitespace-pre-wrap break-words text-xs text-slate-700 block w-full text-left cursor-pointer hover:bg-indigo-50 hover:text-indigo-700 rounded px-1 -mx-1 transition-colors"
                                        data-inline-editable="notes"
                                        data-inline-raw="<?php echo esc_attr($raw_value); ?>"
                                        title="Clique para editar"><?php echo esc_html($display_value); ?></span>
                                <?php elseif (in_array($column, ['phone_number', 'user_name'], true) && $row_id > 0 && $raw_value !== ''): ?>
                                    <a href="?<?php echo esc_attr($module_param); ?>&view=details&id=<?php echo $row_id; ?>&return_to=<?php echo esc_attr($report_origin_view); ?>"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium block whitespace-nowrap overflow-hidden text-ellipsis"
                                        title="<?php echo esc_attr($display_value); ?>">
                                        <?php echo esc_html($display_value); ?>
                                    </a>
                                <?php else: ?>
                                    <span
                                        class="<?php echo $is_long_text ? 'whitespace-pre-wrap break-words text-xs text-slate-700 block w-full text-left' : 'text-slate-700 block whitespace-nowrap overflow-hidden text-ellipsis'; ?>"
                                        title="<?php echo esc_attr($display_value); ?>"><?php echo esc_html($display_value); ?></span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <tr id="reportsNoResults" class="<?php echo empty($report_rows) ? '' : 'hidden'; ?>">
                    <td colspan="<?php echo $srv_visible_count; ?>" class="px-4 py-8 text-center text-slate-400">
                        Nenhum registro encontrado para os filtros atuais.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="reportTableSettingsModal" class="hidden fixed inset-0 z-[10040]">
    <div class="absolute inset-0 bg-slate-900/50" data-report-modal-close></div>
    <div class="relative mx-auto mt-10 w-[95%] max-w-2xl bg-white rounded-xl shadow-xl border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Personalizar tabela</h3>
                <p class="text-xs text-slate-500 mt-1">Escolha colunas, ordem e visualizacao.</p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600" data-report-modal-close>&times;</button>
        </div>
        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Densidade</label>
                    <select id="reportDensitySetting"
                        class="w-full px-2.5 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                        <option value="normal">Normal</option>
                        <option value="compact">Compacta</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" id="reportZebraSetting" class="h-4 w-4 text-indigo-600 border-slate-300 rounded">
                        Listras alternadas (zebra)
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Colunas e ordem</label>
                <div id="reportTableColumnsList" class="border border-slate-200 rounded-lg divide-y divide-slate-200"></div>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row gap-2 sm:justify-between">
            <button type="button" id="reportTableResetBtn" class="btn btn-secondary">Restaurar padrao</button>
            <div class="flex gap-2">
                <button type="button" class="btn btn-secondary" data-report-modal-close>Cancelar</button>
                <button type="button" id="reportTableSaveBtn" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.ccsCurrentModule = <?php echo wp_json_encode($current_module); ?>;
    window.ccsReportContext = <?php echo wp_json_encode($report_origin_view); ?>;
    window.ccsTablePreferencesConfig = <?php echo wp_json_encode($table_preferences_config); ?>;
    <?php if (!empty($can_edit)): ?>
    window.ccsInlineEditConfig = {
        action: <?php echo wp_json_encode($module_config['inline_edit_action'] ?? ''); ?>,
        idField: <?php echo wp_json_encode($module_config['id_field'] ?? 'computer_id'); ?>,
        nonce: <?php echo wp_json_encode(wp_create_nonce('ccs_action_nonce')); ?>,
        saveUrl: <?php echo wp_json_encode('?' . $module_param); ?>,
        module: <?php echo wp_json_encode($current_module); ?>
    };
    <?php endif; ?>
</script>

<style>
    #reportsTable.report-density-compact th,
    #reportsTable.report-density-compact td {
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
    }

    #reportsTable.report-zebra-enabled tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }

    .ccs-inline-editable {
        min-height: 1.5em;
    }

    .ccs-inline-editable:empty::after {
        content: '-';
        color: #94a3b8;
    }

    .ccs-inline-editing {
        padding: 0 !important;
    }

    .ccs-inline-input {
        width: 100%;
        border: 2px solid #6366f1;
        border-radius: 0.375rem;
        padding: 0.35rem 0.5rem;
        font-size: inherit;
        font-family: inherit;
        line-height: 1.4;
        background: #fff;
        color: #1e293b;
        outline: none;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        resize: vertical;
    }

    .ccs-inline-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    .ccs-inline-saving {
        opacity: 0.6;
        pointer-events: none;
    }

    @media (max-width: 767px) {
        #reportsTable {
            font-size: 0.8125rem;
        }

        #reportsTable th,
        #reportsTable td {
            padding-left: 0.45rem;
            padding-right: 0.45rem;
        }

        #reportsTable thead tr:first-child th {
            padding-top: 0.4rem;
            padding-bottom: 0.35rem;
            font-size: 0.64rem;
            letter-spacing: 0.02em;
        }

        #reportsTable thead tr:nth-child(2) th {
            padding-top: 0.3rem;
            padding-bottom: 0.35rem;
        }

        #reportsTable [data-report-filter] {
            min-height: 1.85rem;
            padding: 0.2rem 0.35rem;
            font-size: 0.69rem;
        }

        #reportsTable col[data-report-col="asset_code"],
        #reportsTable col[data-report-col="phone_number"] {
            width: var(--mobile-auto-fit-width, auto) !important;
        }
    }
</style>
