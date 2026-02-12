<?php

class ComputerControlSystem
{
    public const VERSION = '1.8.9';

    private $db_version = '1.3.0';
    private $table_inventory;
    private $table_history;
    private $form_error = '';
    private $form_data = [];
    private $permissions = [];
    private $user_access_level = 'none';
    private $edit_actions = [
        'add_computer',
        'update_computer',
        'add_checkup',
        'upload_photo',
        'trash_computer',
        'restore_computer',
        'quick_windows_update',
        'delete_history',
        'delete_permanent_computer',
    ];

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

        $this->initialize_permissions();

        if (!$this->user_can_view()) {
            $this->deny_request('Acesso negado. Sua role nao possui permissao para acessar este sistema.', false, 403);
        }

        $view = $this->get_requested_view();
        if (!$this->can_access_view($view)) {
            $this->deny_request('Acesso negado. Seu perfil possui somente permissao de visualizacao.', false, 403);
        }

        // Install/Update DB if needed
        $this->check_installation();

        // Handle Form Submissions
        $this->handle_form_submissions();

        // Render Page
        $render_view = $this->get_requested_view();
        if (!$this->can_access_view($render_view)) {
            $this->deny_request('Acesso negado. Seu perfil possui somente permissao de visualizacao.', false, 403);
        }
        $this->render_page($render_view);
    }

    private function initialize_permissions()
    {
        $this->permissions = $this->load_permissions_config();
        $this->user_access_level = $this->determine_user_access_level();
    }

    private function load_permissions_config()
    {
        $default = [
            'edit_roles' => ['administrator'],
            'view_roles' => [],
            'allow_viewers_table_preferences' => true,
        ];

        $config = [];
        $config_path = dirname(__DIR__) . '/config/permissions.php';

        if (file_exists($config_path)) {
            $loaded = require $config_path;
            if (is_array($loaded)) {
                $config = $loaded;
            }
        }

        $edit_roles = $this->sanitize_role_list($config['edit_roles'] ?? $default['edit_roles']);
        $view_roles = $this->sanitize_role_list($config['view_roles'] ?? $default['view_roles']);

        if (!in_array('administrator', $edit_roles, true)) {
            $edit_roles[] = 'administrator';
        }

        $allow_viewers_table_preferences = array_key_exists('allow_viewers_table_preferences', $config)
            ? (bool) $config['allow_viewers_table_preferences']
            : $default['allow_viewers_table_preferences'];

        return [
            'edit_roles' => array_values(array_unique($edit_roles)),
            'view_roles' => array_values(array_unique($view_roles)),
            'allow_viewers_table_preferences' => $allow_viewers_table_preferences,
        ];
    }

    private function sanitize_role_list($roles)
    {
        if (!is_array($roles)) {
            return [];
        }

        $sanitized = [];
        foreach ($roles as $role) {
            $role = sanitize_key((string) $role);
            if ($role !== '' && !in_array($role, $sanitized, true)) {
                $sanitized[] = $role;
            }
        }

        return $sanitized;
    }

    private function determine_user_access_level()
    {
        if (!is_user_logged_in()) {
            return 'none';
        }

        if (is_multisite() && is_super_admin(get_current_user_id())) {
            return 'edit';
        }

        $user_roles = $this->get_current_user_roles();

        if ($this->roles_match($user_roles, $this->permissions['edit_roles'] ?? [])) {
            return 'edit';
        }

        if ($this->roles_match($user_roles, $this->permissions['view_roles'] ?? [])) {
            return 'view';
        }

        return 'none';
    }

    private function get_current_user_roles()
    {
        $user = wp_get_current_user();
        if (!$user || !is_array($user->roles)) {
            return [];
        }

        return $this->sanitize_role_list($user->roles);
    }

    private function roles_match($user_roles, $allowed_roles)
    {
        if (empty($user_roles) || empty($allowed_roles)) {
            return false;
        }

        foreach ($user_roles as $role) {
            if (in_array($role, $allowed_roles, true)) {
                return true;
            }
        }

        return false;
    }

    private function user_can_view()
    {
        return $this->user_access_level === 'edit' || $this->user_access_level === 'view';
    }

    private function user_can_edit()
    {
        return $this->user_access_level === 'edit';
    }

    private function is_read_only_user()
    {
        return $this->user_access_level === 'view';
    }

    private function user_can_save_table_preferences()
    {
        if ($this->user_can_edit()) {
            return true;
        }

        return $this->is_read_only_user() && !empty($this->permissions['allow_viewers_table_preferences']);
    }

    private function get_requested_view()
    {
        $view = isset($_GET['view']) ? sanitize_key((string) $_GET['view']) : 'list';
        if ($view === '') {
            $view = 'list';
        }

        $allowed_views = ['list', 'add', 'details', 'edit', 'trash', 'reports'];
        if (!in_array($view, $allowed_views, true)) {
            $view = 'list';
        }

        return $view;
    }

    private function can_access_view($view)
    {
        $edit_only_views = ['add', 'edit'];
        if (in_array($view, $edit_only_views, true)) {
            return $this->user_can_edit();
        }

        return $this->user_can_view();
    }

    private function deny_request($message, $is_ajax = false, $status_code = 403)
    {
        if ($is_ajax) {
            wp_send_json_error(['message' => $message], $status_code);
        }

        wp_die($message, 'Acesso negado', ['response' => $status_code]);
    }

    private function ensure_action_is_allowed($action, $is_ajax)
    {
        if (in_array($action, $this->edit_actions, true)) {
            if (!$this->user_can_edit()) {
                $this->deny_request(
                    'Permissao insuficiente. Seu perfil esta em modo somente visualizacao.',
                    $is_ajax,
                    403
                );
            }

            return;
        }

        if ($action === 'save_table_preferences') {
            if (!$this->user_can_save_table_preferences()) {
                $this->deny_request(
                    'Permissao insuficiente para salvar personalizacao da tabela.',
                    $is_ajax,
                    403
                );
            }

            return;
        }

        $this->deny_request('Acao desconhecida ou nao permitida.', $is_ajax, 403);
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

        $row = $wpdb->get_row("SELECT * FROM {$this->table_inventory} LIMIT 1", ARRAY_A);
        if ($row && !isset($row['property'])) {
            $wpdb->query("ALTER TABLE {$this->table_inventory} ADD property varchar(20) DEFAULT '' AFTER location");
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
            property varchar(20) DEFAULT '',
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
        $action = sanitize_key((string) $_POST['ccs_action']);

        $this->ensure_action_is_allowed($action, $is_ajax);

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
            case 'save_table_preferences':
                $result = $this->process_save_table_preferences($current_user_id);
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
            'property' => $this->sanitize_property($_POST['property'] ?? ''),
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
            'property' => $this->sanitize_property($_POST['property'] ?? ''),
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


    private function process_save_table_preferences($current_user_id)
    {
        if ($current_user_id <= 0) {
            return [
                'success' => false,
                'message' => 'Usuario nao autenticado.'
            ];
        }

        $raw_json = isset($_POST['preferences_json']) ? wp_unslash($_POST['preferences_json']) : '';
        $decoded = json_decode($raw_json, true);

        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Dados de personalizacao invalidos.'
            ];
        }

        $allowed_columns = $this->get_inventory_columns();

        $incoming_order = isset($decoded['columns_order']) && is_array($decoded['columns_order']) ? $decoded['columns_order'] : [];
        $columns_order = [];
        foreach ($incoming_order as $column) {
            $column = sanitize_key($column);
            if (in_array($column, $allowed_columns, true) && !in_array($column, $columns_order, true)) {
                $columns_order[] = $column;
            }
        }
        foreach ($allowed_columns as $column) {
            if (!in_array($column, $columns_order, true)) {
                $columns_order[] = $column;
            }
        }

        $incoming_visibility = isset($decoded['columns_visibility']) && is_array($decoded['columns_visibility']) ? $decoded['columns_visibility'] : [];
        $columns_visibility = [];
        foreach ($allowed_columns as $column) {
            if (array_key_exists($column, $incoming_visibility)) {
                $columns_visibility[$column] = (bool) $incoming_visibility[$column];
            } else {
                $columns_visibility[$column] = true;
            }
        }

        $density = isset($decoded['density']) ? sanitize_text_field((string) $decoded['density']) : 'normal';
        if (!in_array($density, ['normal', 'compact'], true)) {
            $density = 'normal';
        }

        $zebra = !empty($decoded['zebra']);

        $sanitized_preferences = [
            'columns_order' => $columns_order,
            'columns_visibility' => $columns_visibility,
            'density' => $density,
            'zebra' => $zebra,
        ];

        update_user_meta($current_user_id, 'ccs_report_table_preferences', $sanitized_preferences);

        return [
            'success' => true,
            'message' => 'Personalizacao da tabela salva.'
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

    private function sanitize_property($value)
    {
        $value = sanitize_text_field((string) $value);
        $allowed = ['Metalife', 'Selbetti'];
        return in_array($value, $allowed, true) ? $value : '';
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

    private function render_page($view = null)
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        }

        if ($view === null) {
            $view = $this->get_requested_view();
        }

        $can_edit = $this->user_can_edit();
        $is_read_only = $this->is_read_only_user();

        require_once __DIR__ . '/../templates/header.php';
        $this->render_content($view);
        require_once __DIR__ . '/../templates/footer.php';
    }

    private function render_content($view)
    {
        if ($view === 'list') {
            $this->render_reports_view(false);
        } elseif ($view === 'add') {
            $this->render_form();
        } elseif ($view === 'details') {
            $this->render_details(isset($_GET['id']) ? intval($_GET['id']) : 0);
        } elseif ($view === 'edit') {
            $this->render_form(isset($_GET['id']) ? intval($_GET['id']) : 0);
        } elseif ($view === 'trash') {
            $this->render_list_view(true);
        } elseif ($view === 'reports') {
            $this->render_reports_view(null);
        } else {
            $this->render_reports_view(false);
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

        $can_edit = $this->user_can_edit();

        require __DIR__ . '/../templates/view-list.php';
    }

    private function render_reports_view($deleted_filter = null)
    {
        global $wpdb;

        $report_columns = $this->get_inventory_columns();

        if ($deleted_filter === null) {
            $report_rows = $wpdb->get_results("SELECT * FROM {$this->table_inventory} ORDER BY updated_at DESC");
        } else {
            $report_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_inventory} WHERE deleted = %d ORDER BY updated_at DESC",
                    $deleted_filter ? 1 : 0
                )
            );
        }

        $report_photos_map = [];

        if (!empty($report_rows)) {
            $report_ids = [];
            foreach ($report_rows as $row) {
                $computer_id = intval($row->id ?? 0);
                if ($computer_id > 0) {
                    $report_ids[] = $computer_id;
                }
            }

            if (!empty($report_ids)) {
                $ids_sql = implode(',', array_map('intval', array_unique($report_ids)));

                $history_photo_rows = $wpdb->get_results("
                    SELECT computer_id, photos
                    FROM {$this->table_history}
                    WHERE photos IS NOT NULL AND photos != '' AND photos != 'null'
                      AND computer_id IN ($ids_sql)
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
        }

        $current_user_id = get_current_user_id();
        $table_preferences = get_user_meta($current_user_id, 'ccs_report_table_preferences', true);
        if (!is_array($table_preferences)) {
            $table_preferences = [];
        }

        $can_edit = $this->user_can_edit();
        $can_save_table_preferences = $this->user_can_save_table_preferences();

        require __DIR__ . '/../templates/view-reports.php';
    }

    private function get_inventory_columns()
    {
        global $wpdb;

        $columns_info = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_inventory}", ARRAY_A);
        $columns = [];

        if (!empty($columns_info)) {
            foreach ($columns_info as $column_info) {
                if (!empty($column_info['Field'])) {
                    $columns[] = $column_info['Field'];
                }
            }
        }

        if (empty($columns)) {
            $columns = ['id', 'type', 'hostname', 'status', 'deleted', 'user_name', 'location', 'property', 'specs', 'notes', 'photo_url', 'created_at', 'updated_at'];
        }

        return $columns;
    }

    private function render_form($id = null)
    {
        global $wpdb;
        $can_edit = $this->user_can_edit();
        if (!$can_edit) {
            echo "<div class='mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg'>Permissao insuficiente. Este perfil esta em modo somente visualizacao.</div>";
            return;
        }

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
        $can_edit = $this->user_can_edit();
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
                        <?php if (!empty($history_id) && $this->user_can_edit()): ?>
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
