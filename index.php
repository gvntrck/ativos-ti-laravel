<?php
// Set timezone for GMT-3
date_default_timezone_set('America/Sao_Paulo');

// --- Configuration ---
// Path to wp-load.php assuming: public_html/sistemas/computadores/index.php
// Leads to: public_html/wp-load.php
$wp_load_path = __DIR__ . '/../../wp-load.php';

// --- Bootstrap WordPress --- 
if (!file_exists($wp_load_path)) {
    die("Erro: NÃ£o foi possÃ­vel encontrar o WordPress em: " . $wp_load_path);
}
require_once $wp_load_path;

// --- Load Logic ---d
require_once __DIR__ . '/src/ComputerControlSystem.php';

// --- Run App ---
$app = new ComputerControlSystem();
$app->run();
