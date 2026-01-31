<?php

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

        $view = $_GET['view'] ?? 'list';

        require_once __DIR__ . '/../templates/header.php';
        $this->render_content($view);
        require_once __DIR__ . '/../templates/footer.php';
    }

    private function render_content($view)
    {
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
    }

    private function render_list_view($show_trash = false)
    {
        global $wpdb;
        $deleted_val = $show_trash ? 1 : 0;
        $computers = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE deleted = %d ORDER BY updated_at DESC", $deleted_val));

        require __DIR__ . '/../templates/view-list.php';
    }

    private function render_form($id = null)
    {
        global $wpdb;
        $pc = null;
        if ($id) {
            $pc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id));
        }
        $is_edit = !empty($pc);

        require __DIR__ . '/../templates/view-form.php';
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

        require __DIR__ . '/../templates/view-details.php';
    }
}
