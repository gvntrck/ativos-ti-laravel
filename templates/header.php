<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Computadores</title>
    <?php
    // Load necessary WP scripts/styles if wanted
    wp_head();
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        <?php include __DIR__ . '/../assets/css/tailwind-custom.css'; ?>
    </style>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/ajax-handler.js"></script>
    <!-- PWA Configuration -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('service-worker.js')
                    .then(registration => console.log('SW registered'))
                    .catch(err => console.log('SW registration failed: ', err));
            });
        }
    </script>
</head>

<body class="wp-core-ui">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-0 pb-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Controle de Computadores</h1>
                <p class="text-slate-500 mt-1">Gerenciamento de Inventário</p>
            </div>
            <div class="flex space-x-3 w-full sm:w-auto">
                <?php if ($view !== 'list'): ?>
                    <a href="?" class="btn btn-secondary">Voltar para Lista</a>
                <?php endif; ?>
                <?php if ($view !== 'add'): ?>
                    <a href="?view=add" class="btn btn-primary"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>Novo Computador</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php
        if (isset($_GET['message'])) {
            $msg = '';
            $type = 'success';
            switch ($_GET['message']) {
                case 'created':
                    $msg = 'Computador cadastrado com sucesso!';
                    break;
                case 'updated':
                    $msg = 'Dados atualizados com sucesso!';
                    break;
                case 'checkup_added':
                    $msg = 'Checkup registrado!';
                    break;
                case 'photo_uploaded':
                    $msg = 'Foto atualizada com sucesso!';
                    break;
                case 'trashed':
                    $msg = 'Computador movido para a lixeira!';
                    break;
                case 'restored':
                    $msg = 'Computador restaurado com sucesso!';
                    break;
            }
            if ($msg) {
                echo "<div class='mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center shadow-sm'><svg class='w-5 h-5 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'></path></svg> $msg</div>";
            }
        }
        ?>