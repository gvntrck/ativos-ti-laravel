<?php
// --- Configuration ---
// Path to wp-load.php assuming: public_html/sistemas/computadores/index.php
// Leads to: public_html/wp-load.php
$wp_load_path = __DIR__ . '/../../wp-load.php';

// --- Bootstrap WordPress ---
if (!file_exists($wp_load_path)) {
    die("Erro: Não foi possível encontrar o WordPress em: " . $wp_load_path);
}
require_once $wp_load_path;

class ComputerControlSystem
{
    private $db_version = '1.0.7';
    private $table_inventory;
    private $table_history;

    public function __construct()
    {
        global $wpdb;
        $this->table_inventory = $wpdb->prefix . 'computer_inventory';
        $this->table_history = $wpdb->prefix . 'computer_history';
    }

    public function run()
    {
        // Enforce Authentication
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        // Enforce Permissions (Admin only)
        if (!current_user_can('manage_options')) {
            wp_die("Acesso negado. Apenas administradores podem acessar este sistema.");
        }

        // Install/Update DB if needed
        $this->check_installation();

        // Handle Form Submissions
        $this->handle_form_submissions();

        // Render Page
        $this->render_page();
    }

    private function check_installation()
    {
        if (get_option('ccs_db_version') != $this->db_version) {
            $this->maybe_migrate();
            $this->install_db();
        }
    }

    private function maybe_migrate()
    {
        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM {$this->table_inventory} LIMIT 1", ARRAY_A);
        if ($row && isset($row['user_email']) && !isset($row['user_name'])) {
            $wpdb->query("ALTER TABLE {$this->table_inventory} CHANGE user_email user_name varchar(100) DEFAULT ''");
        }

        $row = $wpdb->get_row("SELECT * FROM {$this->table_inventory} LIMIT 1", ARRAY_A);
        if ($row && !isset($row['photo_url'])) {
            $wpdb->query("ALTER TABLE {$this->table_inventory} ADD photo_url varchar(255) DEFAULT '' AFTER notes");
        }

        $row_hist = $wpdb->get_row("SELECT * FROM {$this->table_history} LIMIT 1", ARRAY_A);
        if ($row_hist && !isset($row_hist['photos'])) {
            $wpdb->query("ALTER TABLE {$this->table_history} ADD photos text AFTER description");
        }

        $row = $wpdb->get_row("SELECT * FROM {$this->table_inventory} LIMIT 1", ARRAY_A);
        if ($row && !isset($row['deleted'])) {
            $wpdb->query("ALTER TABLE {$this->table_inventory} ADD deleted tinyint(1) NOT NULL DEFAULT 0 AFTER status");
        }
    }

    private function install_db()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_inventory = "CREATE TABLE {$this->table_inventory} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type enum('desktop','notebook') NOT NULL DEFAULT 'desktop',
            hostname varchar(100) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            deleted tinyint(1) NOT NULL DEFAULT 0,
            user_name varchar(100) DEFAULT '',
            location varchar(255) DEFAULT '',
            specs text,
            notes text,
            photo_url varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY hostname (hostname)
        ) $charset_collate;";

        $sql_history = "CREATE TABLE {$this->table_history} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            computer_id mediumint(9) NOT NULL,
            event_type varchar(50) NOT NULL,
            description text NOT NULL,
            photos text,
            user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY computer_id (computer_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_inventory);
        dbDelta($sql_history);

        update_option('ccs_db_version', $this->db_version);
    }

    private function handle_form_submissions()
    {
        if (!isset($_POST['ccs_action'])) {
            return;
        }

        // Verify Nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ccs_action_nonce')) {
            wp_die('Erro de segurança: Nonce inválido ou expirado.');
        }

        global $wpdb;
        $current_user_id = get_current_user_id();

        // Setup redirect URL (preserve other params if needed, but clean action)
        $redirect_url = '?';

        if ($_POST['ccs_action'] === 'add_computer') {
            $hostname = strtoupper(sanitize_text_field($_POST['hostname']));

            // Handle Photos (Single Camera Shot)
            $photo_url = '';
            $photos_json = null;

            if (!empty($_FILES['photo']['name'])) {
                $uploaded_photos = $this->handle_file_uploads(['name' => [$_FILES['photo']['name']], 'type' => [$_FILES['photo']['type']], 'tmp_name' => [$_FILES['photo']['tmp_name']], 'error' => [$_FILES['photo']['error']], 'size' => [$_FILES['photo']['size']]]);
                if (!empty($uploaded_photos)) {
                    $photo_url = $uploaded_photos[0];
                    $photos_json = json_encode($uploaded_photos);
                }
            }

            $wpdb->insert($this->table_inventory, [
                'type' => sanitize_text_field($_POST['type']),
                'hostname' => $hostname,
                'status' => sanitize_text_field($_POST['status']),
                'user_name' => sanitize_text_field($_POST['user_name']),
                'location' => sanitize_text_field($_POST['location']),
                'specs' => sanitize_textarea_field($_POST['specs']),
                'notes' => sanitize_textarea_field($_POST['notes']),
                'photo_url' => $photo_url
            ]);

            $computer_id = $wpdb->insert_id;
            $this->log_history($computer_id, 'create', 'Computador cadastrado', $current_user_id, $photos_json);

            $this->redirect('?message=created');
        }

        if ($_POST['ccs_action'] === 'update_computer') {
            $id = intval($_POST['computer_id']);
            $old_data = $wpdb->get_row("SELECT * FROM {$this->table_inventory} WHERE id = $id", ARRAY_A);

            $new_data = [
                'type' => sanitize_text_field($_POST['type']),
                'hostname' => strtoupper(sanitize_text_field($_POST['hostname'])),
                'status' => sanitize_text_field($_POST['status']),
                'user_name' => sanitize_text_field($_POST['user_name']),
                'location' => sanitize_text_field($_POST['location']),
                'specs' => sanitize_textarea_field($_POST['specs']),
                'notes' => sanitize_textarea_field($_POST['notes']),
            ];

            $wpdb->update($this->table_inventory, $new_data, ['id' => $id]);

            $changes = [];
            foreach ($new_data as $key => $value) {
                if ($old_data[$key] != $value) {
                    $changes[] = "$key alterado de '{$old_data[$key]}' para '$value'";
                }
            }

            if (!empty($changes)) {
                $this->log_history($id, 'update', implode('; ', $changes), $current_user_id);
            }

            $this->redirect('?view=details&id=' . $id . '&message=updated');
        }

        if ($_POST['ccs_action'] === 'add_checkup') {
            $id = intval($_POST['computer_id']);
            $description = sanitize_textarea_field($_POST['description']);
            $this->log_history($id, 'checkup', $description, $current_user_id);

            $this->redirect('?view=details&id=' . $id . '&message=checkup_added');
        }

        if ($_POST['ccs_action'] === 'upload_photo') {
            $id = intval($_POST['computer_id']);

            $uploaded_photos = $this->handle_file_uploads($_FILES['computer_photos']);

            if (!empty($uploaded_photos)) {
                $photo_url = $uploaded_photos[0]; // Set the first one as main
                $photos_json = json_encode($uploaded_photos);

                $wpdb->update($this->table_inventory, ['photo_url' => $photo_url], ['id' => $id]);
                $this->log_history($id, 'update', 'Novas fotos adicionadas', $current_user_id, $photos_json);
                $this->redirect('?view=details&id=' . $id . '&message=photo_uploaded');
            } else {
                wp_die("Erro ao enviar imagens.");
            }
        }

        if ($_POST['ccs_action'] === 'trash_computer') {
            $id = intval($_POST['computer_id']);
            $wpdb->update($this->table_inventory, ['deleted' => 1], ['id' => $id]);
            $this->log_history($id, 'trash', 'Movido para a lixeira', $current_user_id);
            $this->redirect('?message=trashed');
        }

        if ($_POST['ccs_action'] === 'restore_computer') {
            $id = intval($_POST['computer_id']);
            $wpdb->update($this->table_inventory, ['deleted' => 0], ['id' => $id]);
            $this->log_history($id, 'restore', 'Restaurado da lixeira', $current_user_id);
            $this->redirect('?view=details&id=' . $id . '&message=restored');
        }
    }

    private function handle_file_uploads($files)
    {
        if (empty($files) || empty($files['name'][0])) {
            return [];
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploaded_urls = [];
        $files_formatted = [];
        $file_count = count($files['name']);
        $file_keys = array_keys($files);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $files_formatted[$i][$key] = $files[$key][$i];
            }
        }

        foreach ($files_formatted as $file) {
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $uploaded_urls[] = $movefile['url'];
            }
        }

        return $uploaded_urls;
    }

    private function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    private function log_history($computer_id, $type, $description, $user_id, $photos_json = null)
    {
        global $wpdb;
        $wpdb->insert($this->table_history, [
            'computer_id' => $computer_id,
            'event_type' => $type,
            'description' => $description,
            'photos' => $photos_json,
            'user_id' => $user_id
        ]);
    }

    private function render_page()
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        }
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Controle de Computadores</title>
            <?php
            // Load necessary WP scripts/styles if wanted, or keeps it clean. 
            // Let's load basics for admin bar if logged in (user might like it, or clean).
            // User requested "use os recurso do wp". Let's instantiate wp_head just in case.
            wp_head();
            ?>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style type="text/tailwindcss">
                body {
                                                                                                                    background-color: #f8fafc;
                                                                                                                    color: #1e293b;
                                                                                                                    font-family: 'Inter', sans-serif;
                                                                                                                }

                                                                                                                /* Admin Bar Fix */
                                                                                                                html {
                                                                                                                    margin-top: 32px !important;
                                                                                                                }

                                                                                                                @media screen and (max-width: 782px) {
                                                                                                                    html {
                                                                                                                        margin-top: 46px !important;
                                                                                                                    }
                                                                                                                }

                                                                                                                .ccs-glass {
                                                                                                                    background: rgba(255, 255, 255, 0.95);
                                                                                                                    backdrop-filter: blur(10px);
                                                                                                                    border: 1px solid rgba(229, 231, 235, 0.8);
                                                                                                                }

                                                                                                                .btn {
                                                                                                                    @apply inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all;
                                                                                                                }

                                                                                                                .btn-primary {
                                                                                                                    @apply text-white bg-indigo-600 hover:text-white focus:ring-indigo-500;
                                                                                                                }

                                                                                                                .btn-secondary {
                                                                                                                    @apply text-slate-700 bg-white border-slate-300 hover:bg-slate-50 focus:ring-indigo-500;
                                                                                                                }
                                                                                                            </style>
            <script>
                function filterTable() {
                    const input = document.getElementById("searchInput");
                    const filter = input.value.toLowerCase();
                    const rows = document.querySelectorAll("#computerTableBody tr");

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? "" : "none";
                    });
                }
            </script>
        </head>

        <body class="wp-core-ui">
            <?php $this->render_content(); ?>
            <?php wp_footer(); ?>
        </body>

        </html>
        <?php
    }

    private function render_content()
    {
        $view = $_GET['view'] ?? 'list';

        echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">';

        // Header
        echo '<div class="flex justify-between items-center mb-8">';
        echo '<div><h1 class="text-3xl font-bold text-slate-900 tracking-tight">Controle de Computadores</h1><p class="text-slate-500 mt-1">Gerenciamento de Inventário</p></div>';
        echo '<div class="flex space-x-3">';
        if ($view !== 'list') {
            echo '<a href="?" class="btn btn-secondary">Voltar para Lista</a>';
        }
        if ($view !== 'add') {
            echo '<a href="?view=add" class="btn btn-primary"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>Novo Computador</a>';
        }
        echo '</div>';
        echo '</div>';

        // Messages
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

        // Router
        if ($view === 'list') {
            $this->render_list_view();
        } elseif ($view === 'add') {
            $this->render_form();
        } elseif ($view === 'details') {
            $this->render_details($_GET['id']);
        } elseif ($view === 'edit') {
            $this->render_form($_GET['id']);
        } elseif ($view === 'trash') {
            $this->render_list_view(true);
        }

        echo '</div>'; // End container
    }

    private function render_list_view($show_trash = false)
    {
        global $wpdb;
        $deleted_val = $show_trash ? 1 : 0;
        $computers = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE deleted = %d ORDER BY updated_at DESC", $deleted_val));
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div
                class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <input type="text" id="searchInput" onkeyup="filterTable()"
                    class="block w-full sm:w-64 px-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Filtrar computadores...">

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
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2"><span
                                        class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>"><?php echo $status_label; ?></span>
                                </td>
                                <td class="px-4 py-2 font-medium text-slate-900">
                                    <a href="?view=details&id=<?php echo $pc->id; ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <?php echo esc_html(strtoupper($pc->hostname)); ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-slate-600 capitalize"><?php echo $pc->type; ?></td>
                                <td class="px-4 py-2 text-slate-600">
                                    <div class="font-medium text-slate-900"><?php echo esc_html($pc->user_name ?: '-'); ?></div>
                                    <div class="text-xs text-slate-400"><?php echo esc_html($pc->location); ?></div>
                                </td>
                                <td class="px-4 py-2">
                                    <?php if ($show_trash): ?>
                                        <form method="post" action="?" class="inline"
                                            onsubmit="return confirm('Tem certeza que deseja restaurar este computador?');">
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
                                    <?php else: ?>
                                        <a href="?view=details&id=<?php echo $pc->id; ?>"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium text-xs">Gerenciar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($computers)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">Nenhum computador encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private function render_form($id = null)
    {
        global $wpdb;
        $pc = null;
        if ($id) {
            $pc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id));
        }
        $is_edit = !empty($pc);
        ?>
        <div class="max-w-2xl mx-auto">
            <form method="post" action="?" enctype="multipart/form-data"
                class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                <?php wp_nonce_field('ccs_action_nonce'); ?>
                <input type="hidden" name="ccs_action" value="<?php echo $is_edit ? 'update_computer' : 'add_computer'; ?>">
                <?php if ($is_edit): ?><input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>"><?php endif; ?>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Hostname <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="hostname"
                            value="<?php echo $is_edit ? esc_attr(strtoupper($pc->hostname)) : ''; ?>" required
                            class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm uppercase">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
                        <select name="type"
                            class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                            <option value="desktop" <?php selected($is_edit ? $pc->type : '', 'desktop'); ?>>Desktop</option>
                            <option value="notebook" <?php selected($is_edit ? $pc->type : '', 'notebook'); ?>>Notebook</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                        <select name="status"
                            class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                            <option value="active" <?php selected($is_edit ? $pc->status : '', 'active'); ?>>Em Uso</option>
                            <option value="backup" <?php selected($is_edit ? $pc->status : '', 'backup'); ?>>Backup</option>
                            <option value="maintenance" <?php selected($is_edit ? $pc->status : '', 'maintenance'); ?>>Em
                                Manutenção</option>
                            <option value="retired" <?php selected($is_edit ? $pc->status : '', 'retired'); ?>>Aposentado
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nome do Usuário</label>
                        <input type="text" name="user_name" value="<?php echo $is_edit ? esc_attr($pc->user_name) : ''; ?>"
                            class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Localização</label>
                    <input type="text" name="location" value="<?php echo $is_edit ? esc_attr($pc->location) : ''; ?>"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        placeholder="Ex: Financeiro, TI">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Especificações</label>
                    <textarea name="specs" rows="3"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo $is_edit ? esc_textarea($pc->specs) : ''; ?></textarea>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Anotações</label>
                    <textarea name="notes" rows="2"
                        class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"><?php echo $is_edit ? esc_textarea($pc->notes) : ''; ?></textarea>
                </div>

                <?php if (!$is_edit): ?>
                    <div class="mb-8 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Foto Inicial (Câmera)</label>
                        <input type="file" name="photo" accept="image/*" capture="environment"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-slate-500">Tire uma foto do computador para o cadastro.</p>
                    </div>
                <?php endif; ?>

                <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                    <a href="?" class="btn btn-secondary">Cancelar</a>
                    <button type="submit"
                        class="btn btn-primary"><?php echo $is_edit ? 'Salvar Alterações' : 'Cadastrar'; ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_details($id)
    {
        global $wpdb;
        $pc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id));
        if (!$pc) {
            echo "<div class='text-red-500'>Computador não encontrado.</div>";
            return;
        }
        $history = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_history} WHERE computer_id = %d ORDER BY created_at DESC", $id));
        ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Card -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900"><?php echo esc_html(strtoupper($pc->hostname)); ?>
                            </h2>
                            <span class="text-sm text-slate-500 capitalize"><?php echo $pc->type; ?></span>
                        </div>
                        <a href="?view=edit&id=<?php echo $pc->id; ?>"
                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Editar Informações</a>
                    </div>

                    <div class="mb-6 flex justify-end">
                        <form method="post" action="?"
                            onsubmit="return confirm('Tem certeza que deseja enviar este computador para a lixeira? Ele não será excluído permanentemente, mas sairá da lista principal.');">
                            <?php wp_nonce_field('ccs_action_nonce'); ?>
                            <input type="hidden" name="ccs_action" value="trash_computer">
                            <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
                            <button type="submit"
                                class="text-red-500 hover:text-red-700 text-xs font-medium flex items-center border border-red-200 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                                Mover para Lixeira
                            </button>
                        </form>
                    </div>

                    <div class="grid grid-cols-2 gap-6 text-sm">
                        <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Status</span>
                            <span class="font-medium"><?php echo ucfirst($pc->status); ?></span>
                        </div>
                        <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Usuário</span>
                            <span class="font-medium"><?php echo $pc->user_name ?: '-'; ?></span>
                        </div>
                        <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Local</span>
                            <span class="font-medium"><?php echo $pc->location ?: '-'; ?></span>
                        </div>
                        <div><span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold">Atualizado
                                em</span> <span
                                class="font-medium"><?php echo date('d/m/Y H:i', strtotime($pc->updated_at)); ?></span></div>
                    </div>
                    <?php if ($pc->specs): ?>
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <span
                                class="block text-slate-400 text-xs uppercase tracking-wider font-semibold mb-2">Especificações</span>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-lg"><?php echo nl2br(esc_html($pc->specs)); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($pc->notes): ?>
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <span class="block text-slate-400 text-xs uppercase tracking-wider font-semibold mb-2">Anotações</span>
                            <p class="text-slate-700 bg-amber-50 border border-amber-100 p-3 rounded-lg text-sm">
                                <?php echo nl2br(esc_html($pc->notes)); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- History -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-6">Histórico</h3>
                    <div
                        class="space-y-6 relative before:absolute before:inset-0 before:ml-2.5 before:w-0.5 before:bg-slate-200">
                        <?php foreach ($history as $h):
                            $u = get_userdata($h->user_id);
                            ?>
                            <div class="relative flex gap-4">
                                <div class="absolute -left-1 w-2.5 h-2.5 rounded-full bg-indigo-500 ring-4 ring-white mt-1.5 ml-1">
                                </div>
                                <div class="ml-6 flex-1">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <span class="font-semibold text-slate-900 capitalize"><?php echo $h->event_type; ?></span>
                                        <span
                                            class="text-xs text-slate-400"><?php echo date('d/m H:i', strtotime($h->created_at)); ?>
                                            - <?php echo $u ? $u->display_name : 'Sistema'; ?></span>
                                    </div>
                                    <p class="text-slate-600 text-sm"><?php echo esc_html($h->description); ?></p>

                                    <?php
                                    $photos = !empty($h->photos) ? json_decode($h->photos, true) : [];
                                    if (!empty($photos)):
                                        ?>
                                        <div class="flex gap-2 mt-2 overflow-x-auto pb-2">
                                            <?php foreach ($photos as $photo_url): ?>
                                                <a href="<?php echo esc_url($photo_url); ?>" target="_blank" class="block flex-shrink-0">
                                                    <img src="<?php echo esc_url($photo_url); ?>"
                                                        class="h-16 w-16 object-cover rounded-lg border border-slate-200 hover:opacity-75 transition-opacity">
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($history)): ?>
                            <p class="ml-6 text-slate-400 italic">Sem histórico registrado.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Sidebar / Novo Evento -->
            <div class="lg:col-start-3 lg:row-start-1 lg:row-span-2">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 lg:sticky lg:top-8">
                    <h3 class="font-bold text-slate-900 mb-4">Novo Evento / Checkup</h3>
                    <form method="post" action="?">
                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                        <input type="hidden" name="ccs_action" value="add_checkup">
                        <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">
                        <div class="mb-4">
                            <textarea name="description" rows="4"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm p-3"
                                placeholder="Descreva a manutenção, checkup ou movimentação..." required></textarea>
                        </div>
                        <button type="submit" class="w-full btn btn-primary">Registrar</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 mt-6 lg:sticky lg:top-80">
                    <h3 class="font-bold text-slate-900 mb-4">Fotos do Equipamento</h3>
                    <form method="post" action="?" enctype="multipart/form-data">
                        <?php wp_nonce_field('ccs_action_nonce'); ?>
                        <input type="hidden" name="ccs_action" value="upload_photo">
                        <input type="hidden" name="computer_id" value="<?php echo $pc->id; ?>">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Adicionar Foto</label>
                            <input type="file" name="computer_photos[]" multiple accept="image/*" capture="environment"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        <button type="submit" class="w-full btn btn-secondary">Enviar Foto</button>
                    </form>
                </div>
            </div>
        </div>
        </div>
        <?php
    }
}

// Instantiate and Run directly
$app = new ComputerControlSystem();
$app->run();
