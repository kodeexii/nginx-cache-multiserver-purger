<?php
/**
 * Plugin Name:       Nginx Cache MultiServer Purger
 * Plugin URI:        https://github.com/kodeexii/nginx-cache-multiserver-purger
 * Description:       A custom plugin to purge Nginx FastCGI cache on multiple load-balanced servers via REST API.
 * Version:           1.4.0
 * Author:            Al-Hadee Mohd Roslan & Mat Gem
 * Author URI:        https://hadeeroslan.my
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       nginx-cache-multiserver-purger
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('NCMP_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Muat naik semua komponen penting plugin kita
require_once NCMP_PLUGIN_PATH . 'includes/settings-page.php';
require_once NCMP_PLUGIN_PATH . 'includes/api-endpoints.php';
require_once NCMP_PLUGIN_PATH . 'includes/core-functions.php';

// === KOD UNTUK AUTO-UPDATE DARI GITHUB ===
require_once NCMP_PLUGIN_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
try {
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/kodeexii/nginx-cache-multiserver-purger/', // WAJIB: Gantikan dengan URL GitHub repo sebenar   
        __FILE__,
        'nginx-cache-multiserver-purger'
    );
    $myUpdateChecker->setBranch('main');
    // $myUpdateChecker->setAuthentication('your-github-personal-access-token'); // Untuk private repo
} catch (Exception $e) {
    error_log('Gagal memuatkan Pustaka Plugin Update Checker: ' . $e->getMessage());
}
// === AKHIR KOD AUTO-UPDATE ===

/**
 * Fungsi utama yang dicetuskan bila post disimpan.
 */
function ncmp_trigger_purge_on_save($post_id, $post) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || $post->post_status !== 'publish') {
        return;
    }
    $uri_path = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);
    ncmp_initiate_purge($uri_path);
}
add_action('save_post', 'ncmp_trigger_purge_on_save', 10, 2);
