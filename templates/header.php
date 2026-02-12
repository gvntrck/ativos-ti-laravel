<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Computadores</title>
    <?php
    // Shim for Elementor frontend config to prevent JS errors
    ?>
    <script>
        if (!window.elementorFrontendConfig) {
            window.elementorFrontendConfig = {
                environmentMode: {
                    edit: false,
                    wpPreview: false
                },
                is_rtl: false,
                breakpoints: {
                    xs: 0,
                    sm: 480,
                    md: 768,
                    lg: 1025,
                    xl: 1440,
                    xxl: 1600
                },
                responsive: {
                    breakpoints: {
                        mobile: {
                            label: "Mobile",
                            value: 767,
                            default_value: 767,
                            direction: "max",
                            is_enabled: true
                        },
                        mobile_extra: {
                            label: "Mobile Extra",
                            value: 880,
                            default_value: 880,
                            direction: "max",
                            is_enabled: false
                        },
                        tablet: {
                            label: "Tablet",
                            value: 1024,
                            default_value: 1024,
                            direction: "max",
                            is_enabled: true
                        },
                        tablet_extra: {
                            label: "Tablet Extra",
                            value: 1200,
                            default_value: 1200,
                            direction: "max",
                            is_enabled: false
                        },
                        laptop: {
                            label: "Laptop",
                            value: 1366,
                            default_value: 1366,
                            direction: "max",
                            is_enabled: false
                        },
                        widescreen: {
                            label: "Widescreen",
                            value: 2400,
                            default_value: 2400,
                            direction: "min",
                            is_enabled: false
                        }
                    }
                },
                version: '3.35.0',
                is_static: false,
                experimentalFeatures: [],
                urls: {
                    assets: "/wp-content/plugins/elementor/assets/"
                },
                settings: {
                    page: [],
                    editorPreferences: []
                },
                kit: {
                    active_breakpoints: ["viewport_mobile", "viewport_tablet"],
                    global_image_lightbox: "yes",
                    lightbox_enable_counter: "yes",
                    lightbox_enable_fullscreen: "yes",
                    lightbox_enable_zoom: "yes",
                    lightbox_enable_share: "yes",
                    lightbox_title_src: "title",
                    lightbox_description_src: "description"
                },
                post: {
                    id: 0,
                    title: "Shim",
                    excerpt: ""
                }
            };
        }
    </script>
    <?php
    // Load necessary WP scripts/styles if wanted
    wp_head();
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        <?php include __DIR__ . '/../assets/css/tailwind-custom.css'; ?>
    </style>
    <?php
    $script_version = file_exists(__DIR__ . '/../assets/js/script.js') ? filemtime(__DIR__ . '/../assets/js/script.js') : time();
    $ajax_handler_version = file_exists(__DIR__ . '/../assets/js/ajax-handler.js') ? filemtime(__DIR__ . '/../assets/js/ajax-handler.js') : time();
    $lightbox_version = file_exists(__DIR__ . '/../assets/js/lightbox.js') ? filemtime(__DIR__ . '/../assets/js/lightbox.js') : time();
    $manifest_version = file_exists(__DIR__ . '/../manifest.json') ? filemtime(__DIR__ . '/../manifest.json') : time();
    $service_worker_version = file_exists(__DIR__ . '/../service-worker.js') ? filemtime(__DIR__ . '/../service-worker.js') : time();
    ?>
    <script src="assets/js/script.js?v=<?php echo $script_version; ?>"></script>
    <script src="assets/js/ajax-handler.js?v=<?php echo $ajax_handler_version; ?>"></script>
    <script src="assets/js/lightbox.js?v=<?php echo $lightbox_version; ?>"></script>
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
                navigator.serviceWorker.register('service-worker.js?v=<?php echo $service_worker_version; ?>', { updateViaCache: 'none' })
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

<body class="wp-core-ui">
    <?php
    $current_user = wp_get_current_user();
    $user_first_name = trim((string) get_user_meta($current_user->ID, 'first_name', true));
    if ($user_first_name === '') {
        $user_first_name = $current_user->display_name ?: $current_user->user_login;
    }
    $logout_url = wp_logout_url(home_url('/'));
    $can_edit = isset($can_edit) ? (bool) $can_edit : false;
    $is_read_only = isset($is_read_only) ? (bool) $is_read_only : false;
    ?>

    <div class="ccs-topbar">
        <div class="max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-center justify-end gap-3 text-xs">
            <span class="text-slate-500">Usuario: <strong class="text-slate-700"><?php echo esc_html($user_first_name); ?></strong></span>
            <a href="<?php echo esc_url($logout_url); ?>" class="text-slate-600 hover:text-slate-900 hover:underline">Sair</a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-0 pb-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Controle de Computadores</h1>
                <p class="text-slate-500 mt-1">Gerenciamento de Inventário</p>
            </div>
            <div class="flex space-x-3 w-full sm:w-auto">
                <?php if ($view !== 'list'): ?>
                    <a href="?" id="backToListBtn" class="btn btn-secondary">Voltar para Lista</a>
                    <script>
                        // Restaurar contexto ao clicar em Voltar para Lista
                        (function () {
                            const backBtn = document.getElementById('backToListBtn');
                            if (backBtn) {
                                const currentParams = new URLSearchParams(window.location.search);
                                const returnTo = (currentParams.get('return_to') || '').toLowerCase();

                                if (returnTo === 'trash') {
                                    const savedTrashFilters = sessionStorage.getItem('ccs_trash_filters');
                                    if (savedTrashFilters) {
                                        try {
                                            const params = new URLSearchParams(savedTrashFilters.replace(/^\?/, ''));
                                            const allowedKeys = new Set([
                                                'view',
                                                'filter',
                                                'type_desktop',
                                                'type_notebook',
                                                'status_active',
                                                'status_backup',
                                                'status_maintenance',
                                                'status_retired',
                                                'loc_fabrica',
                                                'loc_centro',
                                                'loc_perdido',
                                                'loc_manutencao',
                                                'loc_sem_local'
                                            ]);

                                            const sanitized = new URLSearchParams();
                                            params.forEach((value, key) => {
                                                if (allowedKeys.has(key)) {
                                                    sanitized.set(key, value);
                                                }
                                            });

                                            sanitized.set('view', 'trash');
                                            backBtn.href = '?' + sanitized.toString();
                                            return;
                                        } catch (error) {
                                            backBtn.href = '?view=trash';
                                            return;
                                        }
                                    }

                                    backBtn.href = '?view=trash';
                                    return;
                                }

                                if (returnTo === 'reports') {
                                    backBtn.href = '?view=reports';
                                    return;
                                }

                                backBtn.href = '?view=list';
                            }
                        })();
                    </script>
                <?php endif; ?>
                <?php if ($view === 'list'): ?>
                    <a href="?view=trash" class="btn btn-secondary">Lixeira</a>
                <?php endif; ?>
                <?php if ($can_edit && $view !== 'add'): ?>
                    <a href="?view=add" class="btn btn-primary"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>Novo Computador</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php
        if ($is_read_only) {
            echo "<div class='mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg shadow-sm'>Modo somente visualizacao: voce pode consultar os dados, mas nao pode alterar inventario ou historico.</div>";
        }

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
