<?php
$head_module_config = isset($module_config) && is_array($module_config) ? $module_config : [];
$html_title = !empty($head_module_config['title']) ? $head_module_config['title'] : 'Controle de Computadores';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($html_title); ?></title>
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
    <script src="assets/js/script.js?v=<?php echo $script_version; ?>" defer></script>
    <script src="assets/js/ajax-handler.js?v=<?php echo $ajax_handler_version; ?>" defer></script>
    <script src="assets/js/lightbox.js?v=<?php echo $lightbox_version; ?>" defer></script>
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
    $current_module = isset($current_module) ? (string) $current_module : 'computers';
    $module_config = isset($module_config) && is_array($module_config) ? $module_config : [];
    $message_map = isset($message_map) && is_array($message_map) ? $message_map : [];
    $module_switch_urls = isset($module_switch_urls) && is_array($module_switch_urls) ? $module_switch_urls : [];
    $module_param = 'module=' . urlencode($current_module);
    $module_list_url = '?' . $module_param . '&view=list';
    $module_trash_url = '?' . $module_param . '&view=trash';
    $module_add_url = '?' . $module_param . '&view=add';
    $trash_filters_storage_key = !empty($module_config['trash_filters_storage_key']) ? $module_config['trash_filters_storage_key'] : 'ccs_trash_filters';
    $module_title = !empty($module_config['title']) ? $module_config['title'] : 'Controle';
    $module_subtitle = !empty($module_config['subtitle']) ? $module_config['subtitle'] : 'Gerenciamento';
    $module_new_label = !empty($module_config['new_label']) ? $module_config['new_label'] : 'Novo Registro';
    $module_is_computers = $current_module === 'computers';
    ?>

    <script>
        window.ccsCurrentModule = <?php echo wp_json_encode($current_module); ?>;
    </script>

    <div class="ccs-topbar">
        <div class="max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-center justify-end gap-3 text-xs">
            <span class="text-slate-500">Usuario: <strong
                    class="text-slate-700"><?php echo esc_html($user_first_name); ?></strong></span>
            <a href="<?php echo esc_url($logout_url); ?>"
                class="text-slate-600 hover:text-slate-900 hover:underline">Sair</a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-0 pb-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    <?php echo esc_html($module_title); ?></h1>
                <p class="text-slate-500 mt-1"><?php echo esc_html($module_subtitle); ?></p>
                <div class="mt-3 inline-flex rounded-lg border border-slate-200 bg-white p-1 gap-1">
                    <a href="<?php echo esc_url($module_switch_urls['computers'] ?? '?module=computers&view=list'); ?>"
                        class="px-3 py-1.5 text-sm rounded-md transition-colors <?php echo $module_is_computers ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">
                        Computadores
                    </a>
                    <a href="<?php echo esc_url($module_switch_urls['cellphones'] ?? '?module=cellphones&view=list'); ?>"
                        class="px-3 py-1.5 text-sm rounded-md transition-colors <?php echo !$module_is_computers ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">
                        Celulares
                    </a>
                </div>
            </div>
            <div class="flex space-x-3 w-full sm:w-auto">
                <a href="relatorios/index.php"
                    class="hidden lg:inline-flex items-center px-4 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l4 4a1 1 0 01.586 1.414V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Relatórios
                </a>
                <?php if ($view !== 'list'): ?>
                    <a href="<?php echo esc_url($module_list_url); ?>" id="backToListBtn" class="btn btn-secondary">Voltar
                        para Lista</a>
                    <script>
                        (function () {
                            const backBtn = document.getElementById('backToListBtn');
                            if (!backBtn) return;

                            const currentParams = new URLSearchParams(window.location.search);
                            const returnTo = (currentParams.get('return_to') || '').toLowerCase();
                            const module = (currentParams.get('module') || '<?php echo esc_js($current_module); ?>').toLowerCase();
                            const storageKey = <?php echo wp_json_encode($trash_filters_storage_key); ?>;

                            if (returnTo === 'trash') {
                                const savedTrashFilters = sessionStorage.getItem(storageKey);
                                if (savedTrashFilters) {
                                    try {
                                        const params = new URLSearchParams(savedTrashFilters.replace(/^\?/, ''));
                                        const allowedKeys = new Set([
                                            'module',
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
                                            'loc_sem_local',
                                            'dept_comercial_rn',
                                            'dept_fabrica_rn',
                                            'dept_outro',
                                            'dept_sem'
                                        ]);

                                        const sanitized = new URLSearchParams();
                                        params.forEach((value, key) => {
                                            if (allowedKeys.has(key)) {
                                                sanitized.set(key, value);
                                            }
                                        });

                                        sanitized.set('view', 'trash');
                                        sanitized.set('module', module);
                                        backBtn.href = '?' + sanitized.toString();
                                        return;
                                    } catch (error) {
                                        backBtn.href = '?module=' + encodeURIComponent(module) + '&view=trash';
                                        return;
                                    }
                                }

                                backBtn.href = '?module=' + encodeURIComponent(module) + '&view=trash';
                                return;
                            }

                            if (returnTo === 'reports') {
                                backBtn.href = '?module=' + encodeURIComponent(module) + '&view=reports';
                                return;
                            }

                            backBtn.href = '?module=' + encodeURIComponent(module) + '&view=list';
                        })();
                    </script>
                <?php endif; ?>
                <?php if ($view === 'list'): ?>
                    <a href="<?php echo esc_url($module_trash_url); ?>" class="btn btn-secondary">Lixeira</a>
                <?php endif; ?>
                <?php if ($can_edit && $view !== 'add'): ?>
                    <a href="<?php echo esc_url($module_add_url); ?>" class="btn btn-primary"><svg class="w-4 h-4 mr-2"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg><?php echo esc_html($module_new_label); ?></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php
        if ($is_read_only) {
            echo "<div class='mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg shadow-sm'>Modo somente visualizacao: voce pode consultar os dados, mas nao pode alterar inventario ou historico.</div>";
        }

        if (isset($_GET['message'])) {
            $msg_key = sanitize_key((string) $_GET['message']);
            $msg = $message_map[$msg_key] ?? '';
            if ($msg) {
                echo "<div class='mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center shadow-sm'><svg class='w-5 h-5 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'></path></svg> $msg</div>";
            }
        }
        ?>