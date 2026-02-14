<?php
/**
 * Sistema de Relatorios Standalone
 * carrega querys da pasta ../querys e exibe em tabela
 */

// Tenta carregar o WordPress para usar $wpdb
// Caminho ajustado conforme referencia do usuario: ../../../wp-load.php
$wp_load_path = __DIR__ . '/../../../wp-load.php';

if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    // Fallback: Tenta subir mais niveis caso a estrutura seja diferente
    $wp_load_path = __DIR__ . '/../../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('Erro: Nao foi possivel carregar o WordPress. Verifique o caminho do wp-load.php.');
    }
}

// Verifica se usuario esta logado (Qualquer role)
if (!is_user_logged_in()) {
    auth_redirect(); // Redireciona para tela de login do WordPress se nao estiver logado
    exit;
}

global $wpdb;

$querys_dir = __DIR__ . '/../querys';
$files = glob($querys_dir . '/*.sql');
$reports_by_category = [
    'Computadores' => [],
    'Celulares' => [],
    'Geral' => []
];

if ($files) {
    foreach ($files as $file) {
        $filename = basename($file);
        $content = file_get_contents($file);

        // Logica simples de categorizacao baseada no conteudo da query
        $category = 'Geral';
        if (stripos($content, 'wp_computer_inventory') !== false || stripos($content, 'computer') !== false) {
            $category = 'Computadores';
        } elseif (stripos($content, 'wp_cellphone_inventory') !== false || stripos($content, 'cellphone') !== false) {
            $category = 'Celulares';
        }

        $name = str_replace(['.sql', '_', '-'], ['', ' ', ' '], $filename);
        // Remove numeracao inicial se existir (ex: "1 distribuicao..." -> "Distribuicao...")
        $name = preg_replace('/^\d+\s*/', '', $name);
        $name = ucwords($name);

        $reports_by_category[$category][] = [
            'file' => $filename,
            'name' => $name,
            'path' => $file
        ];
    }
}

$current_report = isset($_GET['report']) ? $_GET['report'] : 'relatorio_celulares.sql';
$results = null;
$error = null;
$report_title = 'Selecione um relatorio';

if ($current_report) {
    $report_file = $querys_dir . '/' . basename($current_report);
    if (file_exists($report_file)) {
        $sql = file_get_contents($report_file);

        // Remove comentarios do SQL para exibir (opcional) ou execucao limpa
        // $wpdb->get_results executa a query diretamente

        // Pega o titulo baseado no nome do arquivo
        $report_title = str_replace(['.sql', '_', '-'], ['', ' ', ' '], basename($current_report));
        $report_title = preg_replace('/^\d+\s*/', '', $report_title);
        $report_title = ucwords($report_title);

        $results = $wpdb->get_results($sql, ARRAY_A);

        if ($wpdb->last_error) {
            $error = $wpdb->last_error;
        }
    } else {
        $error = 'Arquivo de relatorio nao encontrado.';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorios Ativos Metalife</title>

    <!-- Scripts e Estilos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.dataTables.min.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <style>
        :root {
            --ccs-primary: #1f2937;
            --ccs-secondary: #334155;
            --ccs-surface: #ffffff;
            --ccs-bg: #f5f5f5;
            --ccs-accent: #334155;
            --ccs-text-strong: #111111;
            --ccs-text: #6b7280;
            --ccs-text-soft: #9ca3af;
            --ccs-border: #d1d5db;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--ccs-bg);
            color: var(--ccs-text);
        }

        /* Ajustes para DataTables + Tailwind */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2rem;
            background-image: none;
            border: 1px solid var(--ccs-border);
            border-radius: 0.375rem;
            padding: 0.25rem 2rem 0.25rem 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter {
            text-align: left !important;
            float: none !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--ccs-border);
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            margin-left: 0;
            margin-right: 0.5rem;
            min-width: 250px;
        }

        /* Estilo Compacto ERP */
        table.dataTable {
            border-collapse: collapse !important;
        }

        table.dataTable tbody td {
            padding: 4px 8px !important;
            /* Mais compacto */
            font-size: 0.75rem !important;
            /* text-xs */
            vertical-align: middle;
            border-right: 1px solid var(--ccs-border);
            /* Linha vertical */
            border-bottom: 1px solid var(--ccs-border) !important;
            /* LINHA HORIZONTAL 1PX FORTE */
        }

        table.dataTable thead th {
            padding: 8px 8px !important;
            font-size: 0.75rem !important;
            background-color: var(--ccs-bg);
            border-bottom: 2px solid var(--ccs-border) !important;
            border-right: 1px solid var(--ccs-border);
            text-transform: uppercase;
            font-weight: 600;
            color: var(--ccs-text-strong);
            cursor: move;
            /* Indica reordenacao de colunas */
        }

        table.dataTable.no-footer {
            border-bottom: 1px solid #e5e7eb;
        }

        /* Hover na linha */
        /* Hover na linha */
        table.dataTable tbody tr:hover {
            background-color: #f3f4f6 !important;
            cursor: default;
        }

        /* Sidebar Link Active */
        .nav-link.active {
            background-color: var(--ccs-bg);
            color: var(--ccs-text-strong);
            font-weight: 600;
            border-right: 2px solid var(--ccs-primary);
        }

        .nav-link:hover:not(.active) {
            background-color: #f9fafb;
            color: #111827;
        }

        /* Botões do DataTables */
        .dt-button {
            background: white !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.25rem 0.75rem !important;
            font-size: 0.75rem !important;
            color: #374151 !important;
            margin-bottom: 0.5rem !important;
        }

        .dt-button:hover {
            background: #f3f4f6 !important;
        }
    </style>
</head>

<body class="h-full">
    <div class="min-h-full flex">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 flex w-64 flex-col">
            <div class="flex min-h-0 flex-1 flex-col border-r border-gray-200 bg-white shadow-lg z-10">
                <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
                    <div class="flex flex-col px-4 mb-5">
                        <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-3">
                            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Relatorios Ativos Metalife
                        </h1>
                        <a href="/sistemas/computadores/"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-slate-800 hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 w-full transition-all">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Voltar ao Painel
                        </a>
                    </div>
                    <nav class="mt-2 flex-1 space-y-1 bg-white px-2">
                        <?php foreach ($reports_by_category as $category => $reports): ?>
                            <?php if (empty($reports))
                                continue; ?>

                            <div class="px-3 mt-6 mb-2">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-1">
                                    <?php echo htmlspecialchars($category); ?>
                                </h3>
                            </div>

                            <?php foreach ($reports as $report): ?>
                                <?php
                                $active = $current_report === $report['file'];
                                $report_name_cleaned = preg_replace('/^\d+/', '', $report['name']); // Tira numero se tiver ficado
                                ?>
                                <a href="?report=<?php echo urlencode($report['file']); ?>"
                                    class="nav-link <?php echo $active ? 'active' : 'text-gray-600'; ?> group flex items-center px-2 py-1.5 text-xs font-medium rounded-md transition-colors duration-150">
                                    <span class="truncate"><?php echo htmlspecialchars($report_name_cleaned); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </nav>
                </div>

            </div>
        </div>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <main class="flex-1 py-6 bg-white">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="border-b border-gray-200 pb-4 mb-4 flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                                <?php echo htmlspecialchars($report_title); ?>
                            </h1>
                            <?php if ($current_report): ?>
                                <p class="mt-1 text-xs text-gray-500">Gerado em <?php echo date('d/m/Y H:i'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-500">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-bold text-red-800">Erro na execucao do relatorio</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p><?php echo htmlspecialchars($error); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($results): ?>
                        <div class="mt-4">
                            <div class="overflow-x-auto">
                                <table id="reportTable" class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <?php
                                            $columns = array_keys($results[0]);
                                            foreach ($columns as $column):
                                                // Limpa nome da coluna para ficar bonito no header
                                                $colName = str_replace('_', ' ', $column);
                                                // Se comecar com _ (ex: _prioridade), esconde do titulo visual mas mantem para debug se precisar
                                                $isHidden = substr($column, 0, 1) === '_';
                                                ?>
                                                <th scope="col"
                                                    class="text-xs font-semibold text-gray-700 uppercase tracking-wider <?php echo $isHidden ? 'hidden' : ''; ?>">
                                                    <?php echo htmlspecialchars($colName); ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($results as $row): ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <?php foreach ($row as $key => $value):
                                                    $isHidden = substr($key, 0, 1) === '_';
                                                    ?>
                                                    <td
                                                        class="<?php echo $isHidden ? 'hidden' : ''; ?> text-xs text-gray-700 whitespace-nowrap">
                                                        <?php
                                                        if (strpos($key, 'url') !== false && filter_var($value, FILTER_VALIDATE_URL)) {
                                                            echo '<a href="' . htmlspecialchars($value) . '" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg> Abrir</a>';
                                                        } elseif ($value === null) {
                                                            echo '<span class="text-gray-300">-</span>';
                                                        } else {
                                                            // Destaque condicional basico
                                                            if ($value === 'NUNCA AUDITADO')
                                                                echo '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">NUNCA</span>';
                                                            elseif (strpos($value, 'ATRASADO') !== false)
                                                                echo '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">' . htmlspecialchars($value) . '</span>';
                                                            elseif ($value === 'EM DIA')
                                                                echo '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">OK</span>';
                                                            else
                                                                echo htmlspecialchars($value);
                                                        }
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php elseif ($current_report && !$error): ?>
                        <div class="mt-12 text-center p-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum dado encontrado</h3>
                            <p class="mt-1 text-sm text-gray-500">A query executou com sucesso mas nao retornou linhas.</p>
                        </div>
                    <?php else: ?>
                        <!-- Tela de boas vindas -->
                        <div class="mt-12 text-center p-12 ">
                            <svg class="mx-auto h-16 w-16 text-indigo-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">Bem-vindo ao Sistema de Relatorios</h3>
                            <p class="mt-1 text-sm text-gray-500">Selecione um relatorio no menu lateral para comecar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Inicializacao do DataTables -->
    <script>
        $(document).ready(function () {
            var table = $('#reportTable').DataTable({
                dom: '<"flex flex-col gap-2 items-start" B f>rtip', /* B=Buttons, f=Filter (agora alinhados a esquerda em coluna) */
                buttons: [
                    'copy',
                    'csv',
                    'excel',
                    {
                        extend: 'pdfHtml5',
                        orientation: 'landscape', /* PDF Horizontal */
                        pageSize: 'A4'
                    },
                    'print'
                ],
                colReorder: true, /* Habilita reordenacao de colunas arrastando */
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]], /* Opcoes de quantidade */
                paging: true,
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                order: [] // Nao forcar ordenacao inicial, respeita a do SQL
            });
        });
    </script>
</body>

</html>