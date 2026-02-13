<?php

class ComputerControlSystem
{
    public const VERSION = '1.9.4';

    private const MODULE_COMPUTERS = 'computers';
    private const MODULE_CELLPHONES = 'cellphones';

    private $db_version = '1.4.2';
    private $table_inventory;
    private $table_history;
    private $table_computer_inventory;
    private $table_computer_history;
    private $table_cellphone_inventory;
    private $table_cellphone_history;
    private $current_module = self::MODULE_COMPUTERS;
    private $module_config = [];
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
        'add_cellphone',
        'update_cellphone',
        'add_cellphone_checkup',
        'upload_cellphone_photo',
        'trash_cellphone',
        'restore_cellphone',
        'delete_cellphone_history',
        'delete_permanent_cellphone',
    ];

    public function __construct()
    {
        global $wpdb;
        $this->table_computer_inventory = $wpdb->prefix . 'computer_inventory';
        $this->table_computer_history = $wpdb->prefix . 'computer_history';
        $this->table_cellphone_inventory = $wpdb->prefix . 'cellphone_inventory';
        $this->table_cellphone_history = $wpdb->prefix . 'cellphone_history';
        $this->set_current_module(self::MODULE_COMPUTERS);

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

        $this->set_current_module($this->resolve_requested_module());

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

    private function get_module_configs()
    {
        return [
            self::MODULE_COMPUTERS => [
                'key' => self::MODULE_COMPUTERS,
                'inventory_table' => $this->table_computer_inventory,
                'history_table' => $this->table_computer_history,
                'history_foreign_key' => 'computer_id',
                'id_field' => 'computer_id',
                'identifier_field' => 'hostname',
                'identifier_required' => true,
                'table_preferences_meta_key' => 'ccs_report_table_preferences',
                'report_primary_column' => 'hostname',
                'add_action' => 'add_computer',
                'update_action' => 'update_computer',
                'checkup_action' => 'add_checkup',
                'upload_photo_action' => 'upload_photo',
                'trash_action' => 'trash_computer',
                'restore_action' => 'restore_computer',
                'delete_history_action' => 'delete_history',
                'delete_permanent_action' => 'delete_permanent_computer',
                'quick_action' => 'quick_windows_update',
                'trash_filters_storage_key' => 'ccs_trash_filters_computers',
                'report_filters_storage_key' => 'ccs_reports_filters_computers',
                'title' => 'Controle de Computadores',
                'subtitle' => 'Gerenciamento de Inventario',
                'plural_label' => 'Computadores',
                'singular_label' => 'Computador',
                'new_label' => 'Novo Computador',
                'report_title' => 'Relatorios de PCs',
                'report_search_placeholder' => 'Busca global (hostname, usuario, local...)',
                'list_search_placeholder' => 'Filtrar computadores...',
                'copy_title' => 'FICHA DO COMPUTADOR',
            ],
            self::MODULE_CELLPHONES => [
                'key' => self::MODULE_CELLPHONES,
                'inventory_table' => $this->table_cellphone_inventory,
                'history_table' => $this->table_cellphone_history,
                'history_foreign_key' => 'cellphone_id',
                'id_field' => 'cellphone_id',
                'identifier_field' => 'asset_code',
                'identifier_required' => false,
                'table_preferences_meta_key' => 'ccs_report_table_preferences_cellphones',
                'report_primary_column' => 'asset_code',
                'add_action' => 'add_cellphone',
                'update_action' => 'update_cellphone',
                'checkup_action' => 'add_cellphone_checkup',
                'upload_photo_action' => 'upload_cellphone_photo',
                'trash_action' => 'trash_cellphone',
                'restore_action' => 'restore_cellphone',
                'delete_history_action' => 'delete_cellphone_history',
                'delete_permanent_action' => 'delete_permanent_cellphone',
                'quick_action' => null,
                'trash_filters_storage_key' => 'ccs_trash_filters_cellphones',
                'report_filters_storage_key' => 'ccs_reports_filters_cellphones',
                'title' => 'Controle de Celulares',
                'subtitle' => 'Gerenciamento de Inventario',
                'plural_label' => 'Celulares',
                'singular_label' => 'Celular',
                'new_label' => 'Novo Celular',
                'report_title' => 'Relatorios de Celulares',
                'report_search_placeholder' => 'Busca global (ID CEL, numero, usuario, marca/modelo...)',
                'list_search_placeholder' => 'Filtrar celulares...',
                'copy_title' => 'FICHA DO CELULAR',
            ],
        ];
    }

    private function set_current_module($module)
    {
        $configs = $this->get_module_configs();
        if (!isset($configs[$module])) {
            $module = self::MODULE_COMPUTERS;
        }

        $this->current_module = $module;
        $this->module_config = $configs[$module];
        $this->table_inventory = $this->module_config['inventory_table'];
        $this->table_history = $this->module_config['history_table'];
    }

    private function resolve_requested_module()
    {
        $module = isset($_REQUEST['module']) ? sanitize_key((string) $_REQUEST['module']) : self::MODULE_COMPUTERS;
        if (!in_array($module, [self::MODULE_COMPUTERS, self::MODULE_CELLPHONES], true)) {
            $module = self::MODULE_COMPUTERS;
        }

        return $module;
    }

    private function get_module_from_action($action)
    {
        $configs = $this->get_module_configs();

        foreach ($configs as $module => $config) {
            $action_keys = [
                'add_action',
                'update_action',
                'checkup_action',
                'upload_photo_action',
                'trash_action',
                'restore_action',
                'delete_history_action',
                'delete_permanent_action',
                'quick_action',
            ];

            foreach ($action_keys as $action_key) {
                if (!empty($config[$action_key]) && $config[$action_key] === $action) {
                    return $module;
                }
            }
        }

        return null;
    }

    private function is_computer_module()
    {
        return $this->current_module === self::MODULE_COMPUTERS;
    }

    private function get_status_labels()
    {
        return [
            'active' => 'Em Uso',
            'backup' => 'Backup',
            'maintenance' => 'Manutencao',
            'retired' => 'Aposentado',
        ];
    }

    private function get_message_map()
    {
        $entity = $this->module_config['singular_label'];
        $messages = [
            'created' => $entity . ' cadastrado com sucesso!',
            'updated' => 'Dados atualizados com sucesso!',
            'checkup_added' => 'Checkup registrado!',
            'photo_uploaded' => 'Foto atualizada com sucesso!',
            'trashed' => $entity . ' movido para a lixeira!',
            'restored' => $entity . ' restaurado com sucesso!',
            'history_deleted' => 'Item de historico excluido com sucesso!',
            'permanently_deleted' => $entity . ' excluido permanentemente.',
        ];

        if ($this->is_computer_module()) {
            $messages['windows_updated'] = 'Atualizacao do Windows registrada!';
        }

        return $messages;
    }

    private function build_url($params = [])
    {
        $query = ['module' => $this->current_module];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[$key] = $value;
        }

        return '?' . http_build_query($query);
    }

    private function build_module_url($module, $params = [])
    {
        $query = ['module' => $module];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[$key] = $value;
        }

        return '?' . http_build_query($query);
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
            if ($action === 'quick_windows_update' && !$this->is_computer_module()) {
                $this->deny_request('Acao desconhecida ou nao permitida para este modulo.', $is_ajax, 403);
            }

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

    private function table_exists($table_name)
    {
        global $wpdb;
        $like = $wpdb->esc_like((string) $table_name);
        $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
        return $result === $table_name;
    }

    private function index_exists($table_name, $index_name)
    {
        global $wpdb;
        $table_sql = esc_sql((string) $table_name);
        $index_name = sanitize_text_field((string) $index_name);
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW INDEX FROM {$table_sql} WHERE Key_name = %s LIMIT 1",
                $index_name
            )
        );

        return !empty($result);
    }

    private function generate_cellphone_asset_code($id)
    {
        $numeric_id = max(0, intval($id));
        return 'CEL-' . str_pad((string) $numeric_id, 3, '0', STR_PAD_LEFT);
    }

    private function sync_cellphone_asset_code_for_id($id)
    {
        global $wpdb;
        $id = intval($id);
        if ($id <= 0) {
            return '';
        }

        $asset_code = $this->generate_cellphone_asset_code($id);
        $wpdb->update(
            $this->table_cellphone_inventory,
            ['asset_code' => $asset_code],
            ['id' => $id]
        );

        return $asset_code;
    }

    private function backfill_cellphone_asset_codes()
    {
        global $wpdb;
        $ids = $wpdb->get_col("SELECT id FROM {$this->table_cellphone_inventory} ORDER BY id ASC");
        if (empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $this->sync_cellphone_asset_code_for_id($id);
        }
    }

    private function maybe_migrate()
    {
        global $wpdb;
        if ($this->table_exists($this->table_computer_inventory)) {
            $row = $wpdb->get_row("SELECT * FROM {$this->table_computer_inventory} LIMIT 1", ARRAY_A);
            if ($row && isset($row['user_email']) && !isset($row['user_name'])) {
                $wpdb->query("ALTER TABLE {$this->table_computer_inventory} CHANGE user_email user_name varchar(100) DEFAULT ''");
            }

            $row = $wpdb->get_row("SELECT * FROM {$this->table_computer_inventory} LIMIT 1", ARRAY_A);
            if ($row && !isset($row['photo_url'])) {
                $wpdb->query("ALTER TABLE {$this->table_computer_inventory} ADD photo_url varchar(255) DEFAULT '' AFTER notes");
            }

            $row = $wpdb->get_row("SELECT * FROM {$this->table_computer_inventory} LIMIT 1", ARRAY_A);
            if ($row && !isset($row['deleted'])) {
                $wpdb->query("ALTER TABLE {$this->table_computer_inventory} ADD deleted tinyint(1) NOT NULL DEFAULT 0 AFTER status");
            }

            $row = $wpdb->get_row("SELECT * FROM {$this->table_computer_inventory} LIMIT 1", ARRAY_A);
            if ($row && isset($row['last_windows_update'])) {
                $wpdb->query("ALTER TABLE {$this->table_computer_inventory} DROP COLUMN last_windows_update");
            }

            $row = $wpdb->get_row("SELECT * FROM {$this->table_computer_inventory} LIMIT 1", ARRAY_A);
            if ($row && !isset($row['property'])) {
                $wpdb->query("ALTER TABLE {$this->table_computer_inventory} ADD property varchar(20) DEFAULT '' AFTER location");
            }
        }

        if ($this->table_exists($this->table_computer_history)) {
            $row_hist = $wpdb->get_row("SELECT * FROM {$this->table_computer_history} LIMIT 1", ARRAY_A);
            if ($row_hist && !isset($row_hist['photos'])) {
                $wpdb->query("ALTER TABLE {$this->table_computer_history} ADD photos text AFTER description");
            }
        }

        if ($this->table_exists($this->table_cellphone_inventory)) {
            $row_cell = $wpdb->get_row("SELECT * FROM {$this->table_cellphone_inventory} LIMIT 1", ARRAY_A);
            if ($row_cell && !isset($row_cell['asset_code'])) {
                $wpdb->query("ALTER TABLE {$this->table_cellphone_inventory} ADD asset_code varchar(20) DEFAULT '' AFTER id");
            }

            $row_cell = $wpdb->get_row("SELECT * FROM {$this->table_cellphone_inventory} LIMIT 1", ARRAY_A);
            if ($row_cell && !isset($row_cell['brand_model'])) {
                $wpdb->query("ALTER TABLE {$this->table_cellphone_inventory} ADD brand_model varchar(150) DEFAULT '' AFTER user_name");
            }

            $row_cell = $wpdb->get_row("SELECT * FROM {$this->table_cellphone_inventory} LIMIT 1", ARRAY_A);
            if ($row_cell && !isset($row_cell['property'])) {
                $wpdb->query("ALTER TABLE {$this->table_cellphone_inventory} ADD property varchar(20) DEFAULT '' AFTER department");
            }

            $row_cell = $wpdb->get_row("SELECT * FROM {$this->table_cellphone_inventory} LIMIT 1", ARRAY_A);
            if ($row_cell && isset($row_cell['asset_code'])) {
                $this->backfill_cellphone_asset_codes();

                if (!$this->index_exists($this->table_cellphone_inventory, 'asset_code')) {
                    $wpdb->query("ALTER TABLE {$this->table_cellphone_inventory} ADD UNIQUE KEY asset_code (asset_code)");
                }
            }
        }
    }

    private function install_db()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_computer_inventory = "CREATE TABLE {$this->table_computer_inventory} (
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

        $sql_computer_history = "CREATE TABLE {$this->table_computer_history} (
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

        $sql_cellphone_inventory = "CREATE TABLE {$this->table_cellphone_inventory} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            asset_code varchar(20) DEFAULT '',
            phone_number varchar(30) DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'active',
            deleted tinyint(1) NOT NULL DEFAULT 0,
            user_name varchar(100) DEFAULT '',
            brand_model varchar(150) DEFAULT '',
            department varchar(100) DEFAULT '',
            property varchar(20) DEFAULT '',
            notes text,
            photo_url varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY asset_code (asset_code),
            KEY phone_number (phone_number)
        ) $charset_collate;";

        $sql_cellphone_history = "CREATE TABLE {$this->table_cellphone_history} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            cellphone_id mediumint(9) NOT NULL,
            event_type varchar(50) NOT NULL,
            description text NOT NULL,
            photos text,
            user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY cellphone_id (cellphone_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_computer_inventory);
        dbDelta($sql_computer_history);
        dbDelta($sql_cellphone_inventory);
        dbDelta($sql_cellphone_history);

        update_option('ccs_db_version', $this->db_version);
    }

    private function handle_form_submissions()
    {
        if (!isset($_POST['ccs_action'])) {
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ccs_action_nonce')) {
            if ($this->is_ajax()) {
                wp_send_json_error(['message' => 'Erro de seguranca: Nonce invalido ou expirado.']);
            }
            wp_die('Erro de seguranca: Nonce invalido ou expirado.');
        }

        $current_user_id = get_current_user_id();
        $is_ajax = $this->is_ajax();
        $action = sanitize_key((string) $_POST['ccs_action']);
        $request_module = $this->resolve_requested_module();
        $request_has_module = isset($_REQUEST['module']);

        $action_module = $this->get_module_from_action($action);
        if ($action_module !== null) {
            if ($request_has_module && $request_module !== $action_module) {
                $this->deny_request('Acao desconhecida ou nao permitida para este modulo.', $is_ajax, 403);
            }
            $this->set_current_module($action_module);
        } else {
            $this->set_current_module($request_module);
        }

        $this->ensure_action_is_allowed($action, $is_ajax);

        $result = ['success' => false, 'message' => 'Acao desconhecida.'];

        switch ($action) {
            case 'add_computer':
            case 'add_cellphone':
                $result = $this->process_add_inventory_item($current_user_id);
                break;
            case 'update_computer':
            case 'update_cellphone':
                $result = $this->process_update_inventory_item($current_user_id);
                break;
            case 'add_checkup':
            case 'add_cellphone_checkup':
                $result = $this->process_add_checkup($current_user_id);
                break;
            case 'upload_photo':
            case 'upload_cellphone_photo':
                $result = $this->process_upload_photo($current_user_id);
                break;
            case 'trash_computer':
            case 'trash_cellphone':
                $result = $this->process_trash_item($current_user_id);
                break;
            case 'restore_computer':
            case 'restore_cellphone':
                $result = $this->process_restore_item($current_user_id);
                break;
            case 'quick_windows_update':
                $result = $this->process_quick_windows_update($current_user_id);
                break;
            case 'delete_history':
            case 'delete_cellphone_history':
                $result = $this->process_delete_history($current_user_id);
                break;
            case 'delete_permanent_computer':
            case 'delete_permanent_cellphone':
                $result = $this->process_delete_permanent_item($current_user_id);
                break;
            case 'save_table_preferences':
                $result = $this->process_save_table_preferences($current_user_id);
                break;
        }

        if ($is_ajax) {
            if (!empty($result['success'])) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } else {
            if (!empty($result['success'])) {
                $this->redirect($result['redirect_url']);
            } else {
                if (isset($result['form_error'])) {
                    $this->form_error = $result['form_error'];
                    $this->form_data = $result['form_data'] ?? $_POST;
                    $_GET['view'] = $result['view'] ?? $_GET['view'];
                    if (isset($result['id'])) {
                        $_GET['id'] = $result['id'];
                    }
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

    private function get_post_item_id()
    {
        $module_id_field = $this->module_config['id_field'] ?? 'computer_id';
        if (isset($_POST[$module_id_field])) {
            return intval($_POST[$module_id_field]);
        }

        if (isset($_POST['computer_id'])) {
            return intval($_POST['computer_id']);
        }

        if (isset($_POST['cellphone_id'])) {
            return intval($_POST['cellphone_id']);
        }

        return 0;
    }

    private function build_form_error($message, $view, $id = 0, $form_data = null)
    {
        $result = [
            'success' => false,
            'message' => $message,
            'form_error' => $message,
            'form_data' => is_array($form_data) ? $form_data : $_POST,
            'view' => $view,
        ];

        if ($id > 0) {
            $result['id'] = $id;
        }

        return $result;
    }

    private function build_inventory_payload_from_post()
    {
        if ($this->is_computer_module()) {
            return [
                'type' => sanitize_text_field($_POST['type'] ?? 'desktop'),
                'hostname' => strtoupper(sanitize_text_field($_POST['hostname'] ?? '')),
                'status' => sanitize_text_field($_POST['status'] ?? 'active'),
                'user_name' => sanitize_text_field($_POST['user_name'] ?? ''),
                'location' => sanitize_text_field($_POST['location'] ?? ''),
                'property' => $this->sanitize_property($_POST['property'] ?? ''),
                'specs' => sanitize_textarea_field($_POST['specs'] ?? ''),
                'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            ];
        }

        return [
            'phone_number' => $this->format_phone_number($_POST['phone_number'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'user_name' => sanitize_text_field($_POST['user_name'] ?? ''),
            'brand_model' => sanitize_text_field($_POST['brand_model'] ?? ''),
            'department' => $this->sanitize_department($_POST['department'] ?? ''),
            'property' => $this->sanitize_property($_POST['property'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        ];
    }

    private function validate_unique_identifier_on_create($payload)
    {
        global $wpdb;

        if ($this->is_computer_module()) {
            $hostname = $payload['hostname'];
            if ($hostname === '') {
                return $this->build_form_error('Hostname e obrigatorio.', 'add', 0, array_merge($_POST, ['hostname' => $hostname]));
            }

            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_inventory} WHERE hostname = %s AND deleted = 0", $hostname));
            if (intval($exists) > 0) {
                return $this->build_form_error(
                    "O hostname '{$hostname}' ja esta em uso por outro computador.",
                    'add',
                    0,
                    array_merge($_POST, ['hostname' => $hostname])
                );
            }

            return null;
        }

        $phone_number = trim((string) $payload['phone_number']);
        if (!$this->is_valid_phone_number($phone_number)) {
            return $this->build_form_error(
                'Numero do celular invalido. Use o padrao (99) 99999-9999.',
                'add',
                0,
                array_merge($_POST, ['phone_number' => $phone_number])
            );
        }

        $normalized_phone = $this->normalize_phone_number($phone_number);

        if ($normalized_phone !== '' && !$this->is_phone_number_unique($normalized_phone, 0)) {
            return $this->build_form_error(
                "O numero '{$phone_number}' ja esta em uso por outro celular.",
                'add',
                0,
                array_merge($_POST, ['phone_number' => $phone_number])
            );
        }

        return null;
    }

    private function validate_unique_identifier_on_update($payload, $id)
    {
        global $wpdb;

        if ($this->is_computer_module()) {
            $hostname = $payload['hostname'];
            if ($hostname === '') {
                return $this->build_form_error('Hostname e obrigatorio.', 'edit', $id, array_merge($_POST, ['hostname' => $hostname]));
            }

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_inventory} WHERE hostname = %s AND id != %d AND deleted = 0",
                $hostname,
                $id
            ));

            if (intval($exists) > 0) {
                return $this->build_form_error(
                    "O hostname '{$hostname}' ja esta em uso por outro computador.",
                    'edit',
                    $id,
                    array_merge($_POST, ['hostname' => $hostname])
                );
            }

            return null;
        }

        $phone_number = trim((string) $payload['phone_number']);
        if (!$this->is_valid_phone_number($phone_number)) {
            return $this->build_form_error(
                'Numero do celular invalido. Use o padrao (99) 99999-9999.',
                'edit',
                $id,
                array_merge($_POST, ['phone_number' => $phone_number])
            );
        }

        $normalized_phone = $this->normalize_phone_number($phone_number);

        if ($normalized_phone !== '' && !$this->is_phone_number_unique($normalized_phone, $id)) {
            return $this->build_form_error(
                "O numero '{$phone_number}' ja esta em uso por outro celular.",
                'edit',
                $id,
                array_merge($_POST, ['phone_number' => $phone_number])
            );
        }

        return null;
    }

    private function process_add_inventory_item($current_user_id)
    {
        global $wpdb;

        $photo_url = '';
        $photos_json = null;

        if (!empty($_FILES['photo']['name'])) {
            $single_photo_files = [
                'name' => [$_FILES['photo']['name']],
                'type' => [$_FILES['photo']['type']],
                'tmp_name' => [$_FILES['photo']['tmp_name']],
                'error' => [$_FILES['photo']['error']],
                'size' => [$_FILES['photo']['size']],
            ];
            $uploaded_photos = $this->handle_file_uploads($single_photo_files);
            if (!empty($uploaded_photos)) {
                $photo_url = $uploaded_photos[0];
                $photos_json = json_encode($uploaded_photos);
            }
        }

        $payload = $this->build_inventory_payload_from_post();
        $validation_error = $this->validate_unique_identifier_on_create($payload);
        if ($validation_error !== null) {
            return $validation_error;
        }

        $payload['photo_url'] = $photo_url;
        $wpdb->insert($this->table_inventory, $payload);
        $item_id = intval($wpdb->insert_id);

        if ($item_id <= 0) {
            return [
                'success' => false,
                'message' => 'Erro ao cadastrar registro.',
            ];
        }

        if (!$this->is_computer_module()) {
            $this->sync_cellphone_asset_code_for_id($item_id);
        }

        $this->log_history($item_id, 'create', $this->module_config['singular_label'] . ' cadastrado', $current_user_id, $photos_json);

        return [
            'success' => true,
            'message' => $this->module_config['singular_label'] . ' cadastrado com sucesso!',
            'redirect_url' => $this->build_url(['message' => 'created']),
            'data' => ['id' => $item_id],
        ];
    }

    private function process_update_inventory_item($current_user_id)
    {
        global $wpdb;
        $id = $this->get_post_item_id();
        $old_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id), ARRAY_A);

        if (!$old_data) {
            return [
                'success' => false,
                'message' => $this->module_config['singular_label'] . ' nao encontrado.',
            ];
        }

        $new_data = $this->build_inventory_payload_from_post();
        $validation_error = $this->validate_unique_identifier_on_update($new_data, $id);
        if ($validation_error !== null) {
            return $validation_error;
        }

        $wpdb->update($this->table_inventory, $new_data, ['id' => $id]);

        $changes = [];
        foreach ($new_data as $key => $value) {
            $old_value = isset($old_data[$key]) ? (string) $old_data[$key] : '';
            if ((string) $old_value !== (string) $value) {
                $changes[] = "{$key} alterado de '{$old_value}' para '{$value}'";
            }
        }

        if (!empty($changes)) {
            $this->log_history($id, 'update', implode('; ', $changes), $current_user_id);
        }

        return [
            'success' => true,
            'message' => $this->module_config['singular_label'] . ' atualizado com sucesso!',
            'redirect_url' => $this->build_url(['view' => 'details', 'id' => $id, 'message' => 'updated']),
            'data' => ['id' => $id],
        ];
    }

    private function process_add_checkup($current_user_id)
    {
        $id = $this->get_post_item_id();
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        if ($description === '') {
            return [
                'success' => false,
                'message' => 'Descricao obrigatoria.',
            ];
        }

        $history_id = $this->log_history($id, 'checkup', $description, $current_user_id);

        return [
            'success' => true,
            'message' => 'Checkup adicionado com sucesso.',
            'redirect_url' => $this->build_url(['view' => 'details', 'id' => $id, 'message' => 'checkup_added']),
            'data' => [
                'history_html' => $this->get_history_item_html($id, $description, 'checkup', $current_user_id, $history_id)
            ],
        ];
    }

    private function get_uploaded_photos_from_request()
    {
        $file_candidates = ['computer_photos', 'cellphone_photos', 'asset_photos'];
        foreach ($file_candidates as $file_key) {
            if (isset($_FILES[$file_key]) && !empty($_FILES[$file_key]['name'])) {
                return $this->handle_file_uploads($_FILES[$file_key]);
            }
        }

        return [];
    }

    private function process_upload_photo($current_user_id)
    {
        global $wpdb;
        $id = $this->get_post_item_id();
        $uploaded_photos = $this->get_uploaded_photos_from_request();

        if (empty($uploaded_photos)) {
            return ['success' => false, 'message' => 'Erro ao enviar imagens.'];
        }

        $photo_url = $uploaded_photos[0];
        $photos_json = json_encode($uploaded_photos);

        $wpdb->update($this->table_inventory, ['photo_url' => $photo_url], ['id' => $id]);
        $this->log_history($id, 'update', 'Novas fotos adicionadas', $current_user_id, $photos_json);

        return [
            'success' => true,
            'message' => 'Fotos enviadas com sucesso!',
            'redirect_url' => $this->build_url(['view' => 'details', 'id' => $id, 'message' => 'photo_uploaded']),
            'data' => ['photo_url' => $photo_url],
        ];
    }

    private function process_trash_item($current_user_id)
    {
        global $wpdb;
        $id = $this->get_post_item_id();
        $wpdb->update($this->table_inventory, ['deleted' => 1], ['id' => $id]);
        $this->log_history($id, 'trash', 'Movido para a lixeira', $current_user_id);

        return [
            'success' => true,
            'message' => $this->module_config['singular_label'] . ' movido para a lixeira.',
            'redirect_url' => $this->build_url(['view' => 'list', 'message' => 'trashed']),
        ];
    }

    private function process_restore_item($current_user_id)
    {
        global $wpdb;
        $id = $this->get_post_item_id();
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id), ARRAY_A);
        if (!$item) {
            return [
                'success' => false,
                'message' => $this->module_config['singular_label'] . ' nao encontrado.',
            ];
        }

        if (!$this->is_computer_module()) {
            $phone_number = trim((string) ($item['phone_number'] ?? ''));
            $normalized_phone = $this->normalize_phone_number($phone_number);
            if ($normalized_phone !== '' && !$this->is_phone_number_unique($normalized_phone, $id)) {
                return [
                    'success' => false,
                    'message' => "Nao foi possivel restaurar: o numero '{$phone_number}' ja esta em uso por outro celular ativo.",
                ];
            }
        }

        $wpdb->update($this->table_inventory, ['deleted' => 0], ['id' => $id]);
        $this->log_history($id, 'restore', 'Restaurado da lixeira', $current_user_id);

        return [
            'success' => true,
            'message' => $this->module_config['singular_label'] . ' restaurado.',
            'redirect_url' => $this->build_url(['view' => 'details', 'id' => $id, 'message' => 'restored']),
        ];
    }

    private function process_quick_windows_update($current_user_id)
    {
        if (!$this->is_computer_module()) {
            return [
                'success' => false,
                'message' => 'Acao nao disponivel para este modulo.',
            ];
        }

        $id = $this->get_post_item_id();
        $description = 'Windows Atualizado';
        $history_id = $this->log_history($id, 'maintenance', $description, $current_user_id);

        return [
            'success' => true,
            'message' => 'Atualizacao do Windows registrada no historico.',
            'redirect_url' => $this->build_url(['view' => 'details', 'id' => $id, 'message' => 'windows_updated']),
            'data' => [
                'history_html' => $this->get_history_item_html($id, $description, 'maintenance', $current_user_id, $history_id)
            ],
        ];
    }

    private function process_delete_history($current_user_id)
    {
        global $wpdb;
        $history_id = intval($_POST['history_id'] ?? 0);
        $item_id = $this->get_post_item_id();
        $fk = $this->module_config['history_foreign_key'];

        $history_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_history} WHERE id = %d AND {$fk} = %d",
            $history_id,
            $item_id
        ));

        if (!$history_item) {
            return [
                'success' => false,
                'message' => 'Item de historico nao encontrado.',
            ];
        }

        $wpdb->delete($this->table_history, ['id' => $history_id]);

        return [
            'success' => true,
            'message' => 'Item de historico excluido com sucesso.',
            'redirect_url' => $this->build_url(['view' => 'details', 'id' => $item_id, 'message' => 'history_deleted']),
            'data' => ['deleted_id' => $history_id],
        ];
    }

    private function process_delete_permanent_item($current_user_id)
    {
        global $wpdb;
        $id = $this->get_post_item_id();
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id));

        if (!$item) {
            return [
                'success' => false,
                'message' => $this->module_config['singular_label'] . ' nao encontrado.',
            ];
        }

        $fk = $this->module_config['history_foreign_key'];
        $wpdb->delete($this->table_history, [$fk => $id]);
        $wpdb->delete($this->table_inventory, ['id' => $id]);

        return [
            'success' => true,
            'message' => $this->module_config['singular_label'] . ' excluido permanentemente.',
            'redirect_url' => $this->build_url(['view' => 'trash', 'message' => 'permanently_deleted']),
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

        update_user_meta($current_user_id, $this->module_config['table_preferences_meta_key'], $sanitized_preferences);

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
        if ($value === 'Meralife') {
            $value = 'Metalife';
        }

        $allowed = ['Metalife', 'Selbetti'];
        return in_array($value, $allowed, true) ? $value : '';
    }

    private function sanitize_department($value)
    {
        $value = sanitize_text_field((string) $value);
        $normalized = strtoupper(remove_accents($value));
        $allowed = ['COMERCIAL-RN', 'FABRICA-RN'];
        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return $value;
    }

    private function normalize_phone_number($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) > 11 && substr($digits, 0, 2) === '55') {
            $without_country_code = substr($digits, 2);
            if (in_array(strlen($without_country_code), [10, 11], true)) {
                $digits = $without_country_code;
            }
        }

        return $digits;
    }

    private function is_valid_phone_number($value)
    {
        $normalized = $this->normalize_phone_number($value);
        if ($normalized === '') {
            return true;
        }

        return in_array(strlen($normalized), [10, 11], true);
    }

    private function format_phone_number($value)
    {
        $normalized = $this->normalize_phone_number($value);
        if ($normalized === '') {
            return '';
        }

        if (strlen($normalized) === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($normalized, 0, 2),
                substr($normalized, 2, 5),
                substr($normalized, 7, 4)
            );
        }

        if (strlen($normalized) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($normalized, 0, 2),
                substr($normalized, 2, 4),
                substr($normalized, 6, 4)
            );
        }

        return sanitize_text_field((string) $value);
    }

    private function is_phone_number_unique($normalized_phone, $exclude_id = 0)
    {
        if ($normalized_phone === '') {
            return true;
        }

        global $wpdb;
        if ($exclude_id > 0) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT phone_number FROM {$this->table_inventory} WHERE deleted = 0 AND id != %d",
                    $exclude_id
                )
            );
        } else {
            $rows = $wpdb->get_results("SELECT phone_number FROM {$this->table_inventory} WHERE deleted = 0");
        }

        foreach ($rows as $row) {
            $existing = $this->normalize_phone_number($row->phone_number ?? '');
            if ($existing !== '' && $existing === $normalized_phone) {
                return false;
            }
        }

        return true;
    }

    private function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    private function log_history($item_id, $type, $description, $user_id, $photos_json = null)
    {
        global $wpdb;
        $fk = $this->module_config['history_foreign_key'];
        $wpdb->insert($this->table_history, [
            $fk => $item_id,
            'event_type' => $type,
            'description' => $description,
            'photos' => $photos_json,
            'user_id' => $user_id,
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
        $current_module = $this->current_module;
        $module_config = $this->module_config;
        $message_map = $this->get_message_map();
        $module_switch_urls = [
            self::MODULE_COMPUTERS => $this->build_module_url(self::MODULE_COMPUTERS, ['view' => 'list']),
            self::MODULE_CELLPHONES => $this->build_module_url(self::MODULE_CELLPHONES, ['view' => 'list']),
        ];

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
        $where_add = '';
        $filter = $_GET['filter'] ?? '';

        if ($filter === 'no_photos') {
            $history_fk = $this->module_config['history_foreign_key'];
            $where_add = " AND (photo_url IS NULL OR photo_url = '')
                AND id NOT IN (
                    SELECT DISTINCT {$history_fk}
                    FROM {$this->table_history}
                    WHERE photos IS NOT NULL AND photos != '' AND photos != 'null'
                )";
        }

        if ($this->is_computer_module()) {
            $type_desktop = isset($_GET['type_desktop']) && $_GET['type_desktop'] === '1';
            $type_notebook = isset($_GET['type_notebook']) && $_GET['type_notebook'] === '1';

            if ($type_desktop && !$type_notebook) {
                $where_add .= " AND type = 'desktop'";
            } elseif ($type_notebook && !$type_desktop) {
                $where_add .= " AND type = 'notebook'";
            }

            $loc_conditions = [];
            $locations_map = [
                'loc_fabrica' => 'Fabrica',
                'loc_centro' => 'Centro',
                'loc_perdido' => 'Perdido',
                'loc_manutencao' => ['Manutencao', 'MANUTENCAO'],
            ];

            foreach ($locations_map as $param => $db_value) {
                if (isset($_GET[$param]) && $_GET[$param] === '1') {
                    if (is_array($db_value)) {
                        $escaped_values = array_map('esc_sql', $db_value);
                        $quoted_values = "'" . implode("','", $escaped_values) . "'";
                        $loc_conditions[] = "location IN ({$quoted_values})";
                    } else {
                        $loc_conditions[] = "location = '" . esc_sql($db_value) . "'";
                    }
                }
            }

            if (isset($_GET['loc_sem_local']) && $_GET['loc_sem_local'] === '1') {
                $loc_conditions[] = "(location IS NULL OR location = '')";
            }

            if (!empty($loc_conditions)) {
                $where_add .= " AND (" . implode(' OR ', $loc_conditions) . ")";
            }
        } else {
            $department_conditions = [];
            $departments_map = [
                'dept_comercial_rn' => 'COMERCIAL-RN',
                'dept_fabrica_rn' => 'FABRICA-RN',
            ];

            foreach ($departments_map as $param => $db_value) {
                if (isset($_GET[$param]) && $_GET[$param] === '1') {
                    $department_conditions[] = "department = '" . esc_sql($db_value) . "'";
                }
            }

            if (isset($_GET['dept_outro']) && $_GET['dept_outro'] === '1') {
                $department_conditions[] = "(department IS NOT NULL AND department != '' AND department NOT IN ('COMERCIAL-RN', 'FABRICA-RN'))";
            }

            if (isset($_GET['dept_sem']) && $_GET['dept_sem'] === '1') {
                $department_conditions[] = "(department IS NULL OR department = '')";
            }

            if (!empty($department_conditions)) {
                $where_add .= " AND (" . implode(' OR ', $department_conditions) . ")";
            }
        }

        $status_conditions = [];
        $status_map = [
            'status_active' => 'active',
            'status_backup' => 'backup',
            'status_maintenance' => 'maintenance',
            'status_retired' => 'retired',
        ];

        foreach ($status_map as $param => $db_value) {
            if (isset($_GET[$param]) && $_GET[$param] === '1') {
                $status_conditions[] = "status = '" . esc_sql($db_value) . "'";
            }
        }

        if (!empty($status_conditions)) {
            $where_add .= " AND (" . implode(' OR ', $status_conditions) . ")";
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_inventory} WHERE deleted = %d {$where_add} ORDER BY updated_at DESC",
                $deleted_val
            )
        );

        $history_fk = $this->module_config['history_foreign_key'];
        $history_data = $wpdb->get_results("
            SELECT {$history_fk} AS item_id, GROUP_CONCAT(description SEPARATOR ' ') as full_history
            FROM {$this->table_history}
            GROUP BY {$history_fk}
        ", OBJECT_K);

        foreach ($rows as $row) {
            if (!$this->is_computer_module()) {
                $row->phone_number = $this->format_phone_number($row->phone_number ?? '');
            }
            $row->search_meta = isset($history_data[$row->id]) ? strip_tags($history_data[$row->id]->full_history) : '';
        }

        $computers = $rows;
        $can_edit = $this->user_can_edit();
        $current_module = $this->current_module;
        $module_config = $this->module_config;
        $status_labels = $this->get_status_labels();

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

        if (!$this->is_computer_module()) {
            foreach ($report_rows as $report_row) {
                $report_row->phone_number = $this->format_phone_number($report_row->phone_number ?? '');
            }
        }

        $report_photos_map = [];
        $history_fk = $this->module_config['history_foreign_key'];

        if (!empty($report_rows)) {
            $report_ids = [];
            foreach ($report_rows as $row) {
                $item_id = intval($row->id ?? 0);
                if ($item_id > 0) {
                    $report_ids[] = $item_id;
                }
            }

            if (!empty($report_ids)) {
                $ids_sql = implode(',', array_map('intval', array_unique($report_ids)));

                $history_photo_rows = $wpdb->get_results("
                    SELECT {$history_fk} AS item_id, photos
                    FROM {$this->table_history}
                    WHERE photos IS NOT NULL AND photos != '' AND photos != 'null'
                      AND {$history_fk} IN ($ids_sql)
                    ORDER BY created_at ASC
                ");

                foreach ($history_photo_rows as $history_row) {
                    $item_id = intval($history_row->item_id);
                    if ($item_id <= 0) {
                        continue;
                    }

                    $decoded_photos = json_decode($history_row->photos, true);
                    if (!is_array($decoded_photos)) {
                        continue;
                    }

                    if (!isset($report_photos_map[$item_id])) {
                        $report_photos_map[$item_id] = [];
                    }

                    foreach ($decoded_photos as $photo_url) {
                        $photo_url = esc_url_raw(trim((string) $photo_url));
                        if ($photo_url === '') {
                            continue;
                        }

                        if (!in_array($photo_url, $report_photos_map[$item_id], true)) {
                            $report_photos_map[$item_id][] = $photo_url;
                        }
                    }
                }
            }

            foreach ($report_rows as $row) {
                $item_id = intval($row->id ?? 0);
                if ($item_id <= 0) {
                    continue;
                }

                $primary_photo = esc_url_raw(trim((string) ($row->photo_url ?? '')));
                if ($primary_photo === '') {
                    continue;
                }

                if (!isset($report_photos_map[$item_id])) {
                    $report_photos_map[$item_id] = [];
                }

                if (!in_array($primary_photo, $report_photos_map[$item_id], true)) {
                    array_unshift($report_photos_map[$item_id], $primary_photo);
                }
            }
        }

        $current_user_id = get_current_user_id();
        $table_preferences = get_user_meta($current_user_id, $this->module_config['table_preferences_meta_key'], true);
        if (!is_array($table_preferences)) {
            $table_preferences = [];
        }

        $can_edit = $this->user_can_edit();
        $can_save_table_preferences = $this->user_can_save_table_preferences();
        $current_module = $this->current_module;
        $module_config = $this->module_config;

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
            if ($this->is_computer_module()) {
                $columns = ['id', 'type', 'hostname', 'status', 'deleted', 'user_name', 'location', 'property', 'specs', 'notes', 'photo_url', 'created_at', 'updated_at'];
            } else {
                $columns = ['id', 'asset_code', 'phone_number', 'status', 'deleted', 'user_name', 'brand_model', 'department', 'property', 'notes', 'photo_url', 'created_at', 'updated_at'];
            }
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
            if ($pc && !$this->is_computer_module()) {
                $pc->phone_number = $this->format_phone_number($pc->phone_number ?? '');
            }
        }
        $is_edit = !empty($pc);

        $error_message = $this->form_error;
        $form_data = $this->form_data;
        $current_module = $this->current_module;
        $module_config = $this->module_config;
        $status_labels = $this->get_status_labels();

        require __DIR__ . '/../templates/view-form.php';
    }

    private function render_details($id)
    {
        global $wpdb;
        $can_edit = $this->user_can_edit();
        $pc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_inventory} WHERE id = %d", $id));
        if (!$pc) {
            echo "<div class='text-red-500'>{$this->module_config['singular_label']} nao encontrado.</div>";
            return;
        }
        if (!$this->is_computer_module()) {
            $pc->phone_number = $this->format_phone_number($pc->phone_number ?? '');
        }
        $history_fk = $this->module_config['history_foreign_key'];
        $history = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_history} WHERE {$history_fk} = %d ORDER BY created_at DESC", $id));
        $current_module = $this->current_module;
        $module_config = $this->module_config;
        $status_labels = $this->get_status_labels();

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
    private function get_history_item_html($item_id, $description, $event_type, $user_id, $history_id = 0)
    {
        $u = get_userdata($user_id);
        $display_name = $u ? $u->display_name : 'Sistema';
        $time = date('d/m H:i', current_time('timestamp'));
        $delete_action = $this->module_config['delete_history_action'];
        $id_field = $this->module_config['id_field'];

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
                                <input type="hidden" name="ccs_action" value="<?php echo esc_attr($delete_action); ?>">
                                <input type="hidden" name="<?php echo esc_attr($id_field); ?>"
                                    value="<?php echo intval($item_id); ?>">
                                <input type="hidden" name="history_id" value="<?php echo intval($history_id); ?>">
                                <input type="hidden" name="module" value="<?php echo esc_attr($this->current_module); ?>">
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
