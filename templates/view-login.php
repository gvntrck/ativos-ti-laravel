<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle de Computadores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php $manifest_version = file_exists(__DIR__ . '/../manifest.json') ? filemtime(__DIR__ . '/../manifest.json') : time(); ?>
    <style type="text/tailwindcss">
        <?php
        $css_path = __DIR__ . '/../assets/css/tailwind-custom.css';
        if (file_exists($css_path)) {
            include $css_path;
        }
        ?>
    </style>
    <!-- PWA Configuration -->
    <link rel="manifest" href="manifest.json?v=<?php echo $manifest_version; ?>">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <script>
        if ('serviceWorker' in navigator) {
            let isRefreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (isRefreshing) return;
                isRefreshing = true;
                window.location.reload();
            });

            window.addEventListener('load', () => {
                navigator.serviceWorker.register('service-worker.js', { updateViaCache: 'none' })
                    .then(registration => {
                        registration.update();

                        if (registration.waiting) {
                            registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                        }

                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            if (!newWorker) return;

                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                                }
                            });
                        });
                    })
                    .catch(err => console.log('SW registration failed: ', err));
            });
        }
    </script>
</head>

<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center py-12 sm:px-6 lg:px-8 font-[Inter]">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-slate-900">
            Acessar Sistema
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Controle de Computadores
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-lg sm:rounded-lg sm:px-10 border border-slate-200">
            <?php if (isset($_GET['login_error'])): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm text-center">
                    <?php
                    if (isset($_GET['error_message']) && !empty($_GET['error_message'])) {
                        echo htmlspecialchars(urldecode($_GET['error_message']));
                    } else {
                        echo 'Credenciais inválidas. Tente novamente.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="index.php" method="POST">
                <input type="hidden" name="ccs_action" value="login">

                <div>
                    <label for="log" class="block text-sm font-medium text-slate-700">Usuário ou Email</label>
                    <div class="mt-1">
                        <input id="log" name="log" type="text" autocomplete="username" required
                            class="block w-full appearance-none rounded-md border border-slate-300 px-3 py-2 placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="pwd" class="block text-sm font-medium text-slate-700">Senha</label>
                    <div class="mt-1">
                        <input id="pwd" name="pwd" type="password" autocomplete="current-password" required
                            class="block w-full appearance-none rounded-md border border-slate-300 px-3 py-2 placeholder-slate-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="rememberme" name="rememberme" type="checkbox" value="forever"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="rememberme" class="ml-2 block text-sm text-slate-900">Lembrar-me</label>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
                        Entrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
