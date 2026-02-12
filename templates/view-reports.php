<?php
$column_labels = [];
$column_filter_meta = [];
$column_widths = [];

if (in_array('property', $report_columns, true)) {
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
            default => $value
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

foreach ($report_columns as $column) {
    $column_labels[$column] = ucwords(str_replace('_', ' ', $column));

    $unique_values_map = [];
    $has_empty_values = false;

    foreach ($report_rows as $row) {
        $raw_value = isset($row->$column) ? (string) $row->$column : '';
        $normalized = trim($raw_value);

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

    $use_select_filter = !$is_date_column
        && !$is_long_text_column
        && !$is_url_column
        && count($unique_values) <= 15;

    $column_filter_meta[$column] = [
        'values' => $unique_values,
        'has_empty' => $has_empty_values,
        'is_date' => $is_date_column,
        'use_select' => $use_select_filter,
    ];

    $column_widths[$column] = match ($column) {
        'id' => 90,
        'hostname' => 180,
        'specs', 'notes' => 320,
        'photo_url' => 220,
        'created_at', 'updated_at' => 170,
        default => 160,
    };
}
?>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/60">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Relatorios de PCs</h2>
                <p class="text-sm text-slate-500">Tabela tipo planilha com filtros por coluna.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                <input id="reportGlobalSearch" type="text"
                    class="block w-full sm:w-80 px-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Busca global (hostname, usuario, local...)">
                <button type="button" id="clearReportFilters" class="btn btn-secondary whitespace-nowrap">Limpar filtros</button>
            </div>
        </div>
        <div class="mt-3 text-sm text-slate-500">
            Linhas visiveis:
            <strong id="reportVisibleCount" class="text-slate-700"><?php echo count($report_rows); ?></strong>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-max min-w-full table-fixed text-left border-collapse text-sm">
            <colgroup>
                <?php foreach ($report_columns as $column): ?>
                    <col style="width: <?php echo intval($column_widths[$column] ?? 160); ?>px;">
                <?php endforeach; ?>
            </colgroup>
            <thead class="bg-slate-50">
                <tr class="border-b border-slate-200">
                    <?php foreach ($report_columns as $column): ?>
                        <th class="px-3 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                            <?php echo esc_html($column_labels[$column]); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
                <tr class="border-b border-slate-200 bg-white">
                    <?php foreach ($report_columns as $column): ?>
                        <th class="px-2 py-2">
                            <?php $meta = $column_filter_meta[$column]; ?>
                            <?php if ($meta['is_date']): ?>
                                <input type="date"
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
                                <input type="text"
                                    data-report-filter="<?php echo esc_attr($column); ?>"
                                    data-report-filter-type="text"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:outline-none focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Filtrar...">
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="reportsTableBody" class="divide-y divide-slate-100">
                <?php foreach ($report_rows as $row): ?>
                    <?php
                    $search_terms = [];
                    $row_attributes = [];

                    foreach ($report_columns as $column) {
                        $raw_value = isset($row->$column) ? (string) $row->$column : '';
                        $normalized_value = strtolower(trim($raw_value));
                        $row_attributes[] = 'data-col-' . esc_attr($column) . '="' . esc_attr($normalized_value) . '"';
                        $search_terms[] = $normalized_value;
                    }

                    $row_search = trim(implode(' ', $search_terms));
                    $row_id = isset($row->id) ? intval($row->id) : 0;
                    $row_photos = [];

                    if ($row_id > 0 && isset($report_photos_map[$row_id]) && is_array($report_photos_map[$row_id])) {
                        $row_photos = array_values($report_photos_map[$row_id]);
                    }
                    ?>
                    <tr class="report-row hover:bg-slate-50"
                        data-report-search="<?php echo esc_attr($row_search); ?>" <?php echo implode(' ', $row_attributes); ?>>
                        <?php foreach ($report_columns as $column): ?>
                            <?php
                            $raw_value = isset($row->$column) ? (string) $row->$column : '';
                            $formatted_value = $format_report_value($column, $raw_value);
                            $is_long_text = in_array($column, ['specs', 'notes'], true);
                            $display_value = $is_long_text ? trim($formatted_value) : $formatted_value;
                            ?>
                            <td class="px-3 py-2 align-top">
                                <?php if ($column === 'hostname' && $row_id > 0): ?>
                                    <a href="?view=details&id=<?php echo $row_id; ?>"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        <?php echo esc_html(strtoupper($raw_value)); ?>
                                    </a>
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
                                    <button type="button"
                                        data-report-photo-url="<?php echo esc_url($trigger_photo); ?>"
                                        data-report-photos="<?php echo esc_attr($photos_json); ?>"
                                        data-report-photo-index="<?php echo esc_attr((string) $start_index); ?>"
                                        class="text-indigo-600 hover:text-indigo-900 underline break-all block text-left">
                                        <?php echo count($row_photos) > 1 ? 'Fotos (' . count($row_photos) . ')' : 'Foto'; ?>
                                    </button>
                                <?php elseif ($column === 'photo_url'): ?>
                                    <span class="text-slate-500">-</span>
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
                    <td colspan="<?php echo count($report_columns); ?>" class="px-4 py-8 text-center text-slate-400">
                        Nenhum registro encontrado para os filtros atuais.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
