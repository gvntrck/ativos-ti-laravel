<?php

class ComputerControlSystem
{
    public const VERSION = '1.8.8';

    private $db_version = '1.2.0';
    private $table_inventory;
    private $table_history;
    private $form_error = '';
    private $form_data = [];

    public function __construct()
    {
        global $wpdb;
        $this->table_inventory = $wpdb->prefix . 'computer_inventory';
        $this->table_history = $wpdb->prefix . 'computer_history';

        // Set MySQL session time zone to GMT-3
        $wpdb->query("SET time_zone = '-03:00'");
    }

    public function run()
    {
        // Enforce Authentication
        // Handle Login Submission
        if (isset($_POST['ccs_action']) && $_POST['ccs_action'] === 'login') {
            $this->handle_login();
        }

        // Enforce Authentication
        if (!is_user_logged_in()) {
            $this->render_login_page();
            exit;
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

        $row = $wpdb->get_row("SELECT * FROM {$this->table_inventory} LIMIT 1", ARRAY_A);
        if ($row && isset($row['last_windows_update'])) {
            $wpdb->query("ALTER TABLE {$this->table_inventory} DROP COLUMN last_windows_update");
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
            if ($this->is_ajax()) {
                wp_send_json_error(['message' => 'Erro de segurança: Nonce inválido ou expirado.']);
            }
            wp_die('Erro de segurança: Nonce inválido ou expirado.');
        }

        global $wpdb;
        $current_user_id = get_current_user_id();
        $is_ajax = $this->is_ajax();
        $action = $_POST['ccs_action'];

        $result = ['success' => false, 'message' => 'Ação desconhecida.'];

        switch ($action) {
            case 'add_computer':
                $result = $this->process_add_computer($current_user_id);
                break;
            case 'update_computer':
                $result = $this->process_update_computer($current_user_id);
                break;
            case 'add_checkup':
                $result = $this->process_add_checkup($current_user_id);
                break;
            case 'upload_photo':
                $result = $this->process_upload_photo($current_user_id);
                break;
            case 'trash_computer':
                $result = $this->process_trash_computer($current_user_id);
                break;
            case 'restore_computer':
                $result = $this->process_restore_computer($current_user_id);
                break;
            case 'quick_windows_update':
                $result = $this->process_quick_windows_update($current_user_id);
                break;
            case 'delete_history':
                $result = $this->process_delete_history($current_user_id);
                break;
            case 'delete_permanent_computer':
                $result = $this->process_delete_permanent_computer($current_user_id);
                break;
        }

        if ($is_ajax) {
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } else {
            if ($result['success']) {
                $this->redirect($result['redirect_url']);
            } else {
                // Handle form errors in non-ajax mode (populate form_error and form_data)
                // This logic is slightly simplistic for "stay on page" errors like invalid hostname
                // Ideally process methods should set class state if they fail? 
                // For now let's rely on what we have. Most errors were redirecting or setting state.

                if (isset($result['form_error'])) {
                    $this->form_error = $result['form_error'];
                    $this->form_data = $result['form_data'] ?? $_POST;
                    $_GET['view'] = $result['view'] ?? $_GET['view'];
                    if (isset($result['id']))
                        $_GET['id'] = $result['id'];
                } else {
                    wp_die($result['message']);
                }
            }
        }
    }

    private function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_POST['ajax']) && $_POST['ajax'] === '1');
    }

    private function process_add_computer($current_user_id)
    {
        global $wpdb;
        $hostname = strtoupper(sanitize_text_field($_POST['hostname']));

        // Handle Photos
        $photo_url = '';
        $photos_json = null;

        if (!empty($_FILES['photo']['name'])) {
            $uploaded_photos = $this->handle_file_uploads(['name' => [$_FILES['photo']['name']], 'type' => [$_FILES['photo']['type']], 'tmp_name' => [$_FILES['photo']['tmp_name']], 'error' => [$_FILES['photo']['error']], 'size' => [$_FILES['photo']['size']]]);
            if (!empty($uploaded_photos)) {
                $photo_url = $uploaded_photos[0];
                $photos_json = json_encode($uploaded_photos);
            }
        }

        // Validation
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_inventory} WHERE hostname = %s AND deleted = 0", $hostname));
        if ($exists > 0) {
            return [
                'success' => false,
                'message' => "O hostname '$hostname' já está em uso por outro computador.",
                'form_error' => "O hostname '$hostname' já está em uso por outro computador.",
                'form_data' => array_merge($_POST, ['hostname' => $hostname]),
                'view' => 'add'
            ];
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

        return [
            'success' => true,
            'message' => 'Computador cadastrado com sucesso!',
            'redirect_url' => '?message=created',
            'data' => ['id' => $computer_id]
        ];
    }

    private function process_update_computer($current_user_id)
    {
        global $wpdb;
        $id = intval($_POST['computer_id']);
        $old_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id), ARRAY_A);

        if (!$old_data) {
            return [
                'success' => false,
                'message' => 'Computador não encontrado.',
            ];
        }

        $new_data = [
            'type' => sanitize_text_field($_POST['type']),
            'hostname' => strtoupper(sanitize_text_field($_POST['hostname'])),
            'status' => sanitize_text_field($_POST['status']),
            'user_name' => sanitize_text_field($_POST['user_name']),
            'location' => sanitize_text_field($_POST['location']),
            'specs' => sanitize_textarea_field($_POST['specs']),
            'notes' => sanitize_textarea_field($_POST['notes']),
        ];

        // Validation
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_inventory} WHERE hostname = %s AND id != %d AND deleted = 0",
            $new_data['hostname'],
            $id
        ));

        if ($exists > 0) {
            return [
                'success' => false,
                'message' => "O hostname '{$new_data['hostname']}' já está em uso por outro computador.",
                'form_error' => "O hostname '{$new_data['hostname']}' já está em uso por outro computador.",
                'form_data' => array_merge($_POST, ['hostname' => $new_data['hostname']]),
                'view' => 'edit',
                'id' => $id
            ];
        }

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

        return [
            'success' => true,
            'message' => 'Computador atualizado com sucesso!',
            'redirect_url' => '?view=details&id=' . $id . '&message=updated',
            'data' => ['id' => $id]
        ];
    }

    private function process_add_checkup($current_user_id)
    {
        $id = intval($_POST['computer_id']);
        $description = sanitize_textarea_field($_POST['description']);
        $history_id = $this->log_history($id, 'checkup', $description, $current_user_id);

        return [
            'success' => true,
            'message' => 'Checkup adicionado com sucesso.',
            'redirect_url' => '?view=details&id=' . $id . '&message=checkup_added',
            'data' => [
                'history_html' => $this->get_history_item_html($id, $description, 'checkup', $current_user_id, $history_id)
            ]
        ];
    }

    private function process_upload_photo($current_user_id)
    {
        global $wpdb;
        $id = intval($_POST['computer_id']);
        $uploaded_photos = $this->handle_file_uploads($_FILES['computer_photos']);

        if (!empty($uploaded_photos)) {
            $photo_url = $uploaded_photos[0];
            $photos_json = json_encode($uploaded_photos);

            $wpdb->update($this->table_inventory, ['photo_url' => $photo_url], ['id' => $id]);
            $this->log_history($id, 'update', 'Novas fotos adicionadas', $current_user_id, $photos_json);

            return [
                'success' => true,
                'message' => 'Fotos enviadas com sucesso!',
                'redirect_url' => '?view=details&id=' . $id . '&message=photo_uploaded',
                'data' => ['photo_url' => $photo_url]
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao enviar imagens.'];
        }
    }

    private function process_trash_computer($current_user_id)
    {
        global $wpdb;
        $id = intval($_POST['computer_id']);
        $wpdb->update($this->table_inventory, ['deleted' => 1], ['id' => $id]);
        $this->log_history($id, 'trash', 'Movido para a lixeira', $current_user_id);

        return [
            'success' => true,
            'message' => 'Computador movido para a lixeira.',
            'redirect_url' => '?message=trashed'
        ];
    }

    private function process_restore_computer($current_user_id)
    {
        global $wpdb;
        $id = intval($_POST['computer_id']);
        $wpdb->update($this->table_inventory, ['deleted' => 0], ['id' => $id]);
        $this->log_history($id, 'restore', 'Restaurado da lixeira', $current_user_id);

        return [
            'success' => true,
            'message' => 'Computador restaurado.',
            'redirect_url' => '?view=details&id=' . $id . '&message=restored'
        ];
    }

    private function process_quick_windows_update($current_user_id)
    {
        $id = intval($_POST['computer_id']);
        $description = 'Windows Atualizado';
        $history_id = $this->log_history($id, 'maintenance', $description, $current_user_id);

        return [
            'success' => true,
            'message' => 'Atualizacao do Windows registrada no historico.',
            'redirect_url' => '?view=details&id=' . $id . '&message=windows_updated',
            'data' => [
                'history_html' => $this->get_history_item_html($id, $description, 'maintenance', $current_user_id, $history_id)
            ]
        ];
    }

    private function process_delete_history($current_user_id)
    {
        global $wpdb;
        $history_id = intval($_POST['history_id']);
        $computer_id = intval($_POST['computer_id']);

        // Verificar se o item existe
        $history_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_history} WHERE id = %d AND computer_id = %d",
            $history_id,
            $computer_id
        ));

        if (!$history_item) {
            return [
                'success' => false,
                'message' => 'Item de histórico não encontrado.'
            ];
        }

        // Excluir o item do histórico
        $wpdb->delete($this->table_history, ['id' => $history_id]);

        return [
            'success' => true,
            'message' => 'Item de histórico excluído com sucesso.',
            'redirect_url' => '?view=details&id=' . $computer_id . '&message=history_deleted',
            'data' => ['deleted_id' => $history_id]
        ];
    }

    private function process_delete_permanent_computer($current_user_id)
    {
        global $wpdb;
        $id = intval($_POST['computer_id']);

        // Check if computer exists
        $computer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id));

        if (!$computer) {
            return [
                'success' => false,
                'message' => 'Computador não encontrado.'
            ];
        }

        // Delete history first
        $wpdb->delete($this->table_history, ['computer_id' => $id]);

        // Delete inventory record
        $wpdb->delete($this->table_inventory, ['id' => $id]);

        return [
            'success' => true,
            'message' => 'Computador excluído permanentemente.',
            'redirect_url' => '?view=trash&message=permanently_deleted'
        ];
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

        return intval($wpdb->insert_id);
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
        } elseif ($view === 'reports') {
            $this->render_reports_view();
        }
    }

    private function render_list_view($show_trash = false)
    {
        global $wpdb;
        $deleted_val = $show_trash ? 1 : 0;

        $where_add = "";
        $filter = $_GET['filter'] ?? '';

        if ($filter === 'no_photos') {
            // Sem Fotos: sem photo_url E sem fotos no histórico
            $where_add = " AND (photo_url IS NULL OR photo_url = '') 
                AND id NOT IN (
                    SELECT DISTINCT computer_id 
                    FROM {$this->table_history} 
                    WHERE photos IS NOT NULL AND photos != '' AND photos != 'null'
                )";
        }

        // Type Filters
        $type_desktop = isset($_GET['type_desktop']) && $_GET['type_desktop'] === '1';
        $type_notebook = isset($_GET['type_notebook']) && $_GET['type_notebook'] === '1';

        if ($type_desktop && !$type_notebook) {
            $where_add .= " AND type = 'desktop'";
        } elseif ($type_notebook && !$type_desktop) {
            $where_add .= " AND type = 'notebook'";
        }
        // If both are checked or neither is checked, we show all (no filter needed)

        // Location Filters
        $loc_conditions = [];
        $locations_map = [
            'loc_fabrica' => 'Fabrica',
            'loc_centro' => 'Centro',
            'loc_perdido' => 'Perdido',
            'loc_manutencao' => 'Manutenção'
        ];

        foreach ($locations_map as $param => $db_value) {
            if (isset($_GET[$param]) && $_GET[$param] === '1') {
                $loc_conditions[] = "location = '" . esc_sql($db_value) . "'";
            }
        }

        // Filtro para computadores sem local definido
        if (isset($_GET['loc_sem_local']) && $_GET['loc_sem_local'] === '1') {
            $loc_conditions[] = "(location IS NULL OR location = '')";
        }

        if (!empty($loc_conditions)) {
            $where_add .= " AND (" . implode(' OR ', $loc_conditions) . ")";
        }

        // Status Filters
        $status_conditions = [];
        $status_map = [
            'status_active' => 'active',
            'status_backup' => 'backup',
            'status_maintenance' => 'maintenance',
            'status_retired' => 'retired'
        ];

        foreach ($status_map as $param => $db_value) {
            if (isset($_GET[$param]) && $_GET[$param] === '1') {
                $status_conditions[] = "status = '" . esc_sql($db_value) . "'";
            }
        }

        if (!empty($status_conditions)) {
            $where_add .= " AND (" . implode(' OR ', $status_conditions) . ")";
        }

        $computers = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE deleted = %d $where_add ORDER BY updated_at DESC", $deleted_val));

        // Buscar histórico concatenado para pesquisa (inclui hostnames antigos, mudanças de usuário, etc)
        // Usamos OBJECT_K para indexar o array pelo computer_id para acesso r\u00e1pido
        $history_data = $wpdb->get_results("
            SELECT computer_id, GROUP_CONCAT(description SEPARATOR ' ') as full_history 
            FROM {$this->table_history} 
            GROUP BY computer_id
        ", OBJECT_K);

        foreach ($computers as $pc) {
            // Anexa o hist\u00f3rico ao objeto do computador
            // Removemos tags HTML se houver e normalizamos
            $pc->search_meta = isset($history_data[$pc->id]) ? strip_tags($history_data[$pc->id]->full_history) : '';
        }

        require __DIR__ . '/../templates/view-list.php';
    }

    private function render_reports_view()
    {
        global $wpdb;

        $columns_info = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_inventory}", ARRAY_A);
        $report_columns = [];

        if (!empty($columns_info)) {
            foreach ($columns_info as $column_info) {
                if (!empty($column_info['Field'])) {
                    $report_columns[] = $column_info['Field'];
                }
            }
        }

        if (empty($report_columns)) {
            $report_columns = ['id', 'type', 'hostname', 'status', 'deleted', 'user_name', 'location', 'specs', 'notes', 'photo_url', 'created_at', 'updated_at'];
        }

        $report_rows = $wpdb->get_results("SELECT * FROM {$this->table_inventory} ORDER BY updated_at DESC");
        $report_photos_map = [];

        $history_photo_rows = $wpdb->get_results("
            SELECT computer_id, photos
            FROM {$this->table_history}
            WHERE photos IS NOT NULL AND photos != '' AND photos != 'null'
            ORDER BY created_at ASC
        ");

        foreach ($history_photo_rows as $history_row) {
            $computer_id = intval($history_row->computer_id);
            if ($computer_id <= 0) {
                continue;
            }

            $decoded_photos = json_decode($history_row->photos, true);
            if (!is_array($decoded_photos)) {
                continue;
            }

            if (!isset($report_photos_map[$computer_id])) {
                $report_photos_map[$computer_id] = [];
            }

            foreach ($decoded_photos as $photo_url) {
                $photo_url = esc_url_raw(trim((string) $photo_url));
                if ($photo_url === '') {
                    continue;
                }

                if (!in_array($photo_url, $report_photos_map[$computer_id], true)) {
                    $report_photos_map[$computer_id][] = $photo_url;
                }
            }
        }

        foreach ($report_rows as $row) {
            $computer_id = intval($row->id ?? 0);
            if ($computer_id <= 0) {
                continue;
            }

            $primary_photo = esc_url_raw(trim((string) ($row->photo_url ?? '')));
            if ($primary_photo === '') {
                continue;
            }

            if (!isset($report_photos_map[$computer_id])) {
                $report_photos_map[$computer_id] = [];
            }

            if (!in_array($primary_photo, $report_photos_map[$computer_id], true)) {
                array_unshift($report_photos_map[$computer_id], $primary_photo);
            }
        }

        require __DIR__ . '/../templates/view-reports.php';
    }

    private function render_form($id = null)
    {
        global $wpdb;
        $pc = null;
        if ($id) {
            $pc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id));
        }
        $is_edit = !empty($pc);

        // Pass error/data to view
        $error_message = $this->form_error;
        $form_data = $this->form_data;

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

    private function handle_login()
    {
        $creds = array();
        $creds['user_login'] = $_POST['log'];
        $creds['user_password'] = $_POST['pwd'];
        $creds['remember'] = isset($_POST['rememberme']);

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            $error_code = $user->get_error_code();
            // Map WP errors to user friendly messages if needed, or just pass generic
            $error_msg = $user->get_error_message();
            // Sanitize message for URL
            $this->redirect('index.php?login_error=1&error_message=' . urlencode(strip_tags($error_msg)));
        } else {
            $this->redirect('index.php');
        }
    }

    private function render_login_page()
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        }
        require __DIR__ . '/../templates/view-login.php';
    }
    private function get_history_item_html($computer_id, $description, $event_type, $user_id, $history_id = 0)
    {
        $u = get_userdata($user_id);
        $display_name = $u ? $u->display_name : 'Sistema';
        $time = date('d/m H:i', current_time('timestamp'));

        // Simulating the structure from view-details.php
        ob_start();
        ?>
        <div class="relative flex gap-4 min-w-0 history-item-new fade-in">
            <div class="absolute -left-1 w-2.5 h-2.5 rounded-full bg-indigo-500 ring-4 ring-white mt-1.5 ml-1">
            </div>
            <div class="ml-6 flex-1 min-w-0">
                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:items-baseline mb-1 min-w-0">
                    <span class="font-semibold text-slate-900 capitalize">
                        <?php echo esc_html($event_type); ?>
                    </span>
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-slate-400 break-words whitespace-normal">
                            <?php echo $time; ?>
                            -
                            <?php echo esc_html($display_name); ?>
                        </span>
                        <?php if (!empty($history_id)): ?>
                            <form method="post" action="?" data-ajax="true" class="inline"
                                data-confirm="Tem certeza que deseja excluir este item do historico?">
                                <?php wp_nonce_field('ccs_action_nonce'); ?>
                                <input type="hidden" name="ccs_action" value="delete_history">
                                <input type="hidden" name="computer_id" value="<?php echo intval($computer_id); ?>">
                                <input type="hidden" name="history_id" value="<?php echo intval($history_id); ?>">
                                <button type="submit"
                                    class="text-slate-400 hover:text-red-500 p-1 rounded transition-colors"
                                    title="Excluir item do historico">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-slate-600 text-sm break-words whitespace-normal">
                    <?php echo esc_html($description); ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
