<?php
/**
 * Sistema de Relatorios Standalone
 * carrega querys da pasta ../querys e exibe em tabela
 */

// Tenta carregar o WordPress para usar $wpdb
// Caminho relativo comum: plugin -> plugins -> wp-content -> raiz
$wp_load_path = __DIR__ . '/../../../../wp-load.php';

if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    // Tenta subir mais um nivel caso esteja em mu-plugins ou estrutura diferente
    $wp_load_path = __DIR__ . '/../../../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('Erro: Nao foi possivel carregar o WordPress. Verifique o caminho do wp-load.php.');
    }
}

// Verifica permissao (opcional, usuario pediu para nao focar agora, mas 'edit_pages' e razoavel)
if (!current_user_can('edit_pages')) {
    // wp_die('Acesso negado.'); 
    // Mantendo aberto conforme solicitado, mas deixando o codigo comentado
}

global $wpdb;

$querys_dir = __DIR__ . '/../querys';
$files = glob($querys_dir . '/*.sql');
$reports = [];

if ($files) {
    foreach ($files as $file) {
        $filename = basename($file);
        $name = str_replace(['.sql', '_', '-'], ['', ' ', ' '], $filename);
        $name = ucwords($name);
        $reports[] = [
            'file' => $filename,
            'name' => $name,
            'path' => $file
        ];
    }
}

$current_report = isset($_GET['report']) ? $_GET['report'] : null;
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
    <title>Sistema de Relatorios</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="h-full">
    <div class="min-h-full flex">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 flex w-64 flex-col">
            <div class="flex min-h-0 flex-1 flex-col border-r border-gray-200 bg-white">
                <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
                    <div class="flex flex-shrink-0 items-center px-4 mb-5">
                        <h1 class="text-xl font-bold text-indigo-600">Relatorios</h1>
                    </div>
                    <nav class="mt-2 flex-1 space-y-1 bg-white px-2">
                        <?php foreach ($reports as $report): ?>
                            <?php
                            $active = $current_report === $report['file'];
                            $classes = $active
                                ? 'bg-indigo-50 text-indigo-600 group flex items-center px-2 py-2 text-sm font-medium rounded-md'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md';
                            ?>
                            <a href="?report=<?php echo urlencode($report['file']); ?>" class="<?php echo $classes; ?>">
                                <svg class="<?php echo $active ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3 h-5 w-5 flex-shrink-0"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <?php echo htmlspecialchars($report['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
                <div class="flex flex-shrink-0 border-t border-gray-200 p-4">
                    <a href="/wp-admin" class="group block w-full flex-shrink-0">
                        <div class="flex items-center">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Voltar para o
                                    Painel</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <main class="flex-1 py-8">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-2xl font-semibold text-gray-900">
                                <?php echo htmlspecialchars($report_title); ?>
                            </h1>
                            <?php if ($current_report): ?>
                                <p class="mt-2 text-sm text-gray-700">Visualizando dados do relatorio.</p>
                            <?php else: ?>
                                <p class="mt-2 text-sm text-gray-700">Selecione um relatorio no menu lateral para visualizar
                                    os dados.</p>
                            <?php endif; ?>
                        </div>
                        <?php if ($results): ?>
                            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                                <button onclick="window.print()"
                                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                                    Imprimir / PDF
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($error): ?>
                        <div class="mt-6 rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Erro na execucao</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>
                                            <?php echo htmlspecialchars($error); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($results): ?>
                        <div class="mt-8 flex flex-col">
                            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <?php
                                                    // Pega as chaves do primeiro resultado para o cabecalho
                                                    $columns = array_keys($results[0]);
                                                    foreach ($columns as $column):
                                                        ?>
                                                        <th scope="col"
                                                            class="py-3.5 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 sm:pl-6">
                                                            <?php echo htmlspecialchars(str_replace('_', ' ', $column)); ?>
                                                        </th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                <?php foreach ($results as $row): ?>
                                                    <tr>
                                                        <?php foreach ($row as $key => $value): ?>
                                                            <td
                                                                class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                                                                <?php
                                                                // Formatacao basica
                                                                if (strpos($key, 'url') !== false && filter_var($value, FILTER_VALIDATE_URL)) {
                                                                    echo '<a href="' . htmlspecialchars($value) . '" target="_blank" class="text-indigo-600 hover:text-indigo-900">Link</a>';
                                                                } elseif ($value === null) {
                                                                    echo '<span class="text-gray-400">-</span>';
                                                                } else {
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
                                    <div class="mt-4 text-xs text-gray-500 text-right">
                                        Total de registros:
                                        <?php echo count($results); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($current_report && !$error): ?>
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-500">Nenhum resultado encontrado para este relatorio.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>