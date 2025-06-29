<?php
// includes/api-endpoints.php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', 'ncmp_register_rest_routes');

function ncmp_register_rest_routes() {
    register_rest_route('ncmp-purger/v1', '/purge', ['methods' => 'POST', 'callback' => 'ncmp_handle_purge_request', 'permission_callback' => 'ncmp_purge_permission_check']);
    register_rest_route('ncmp-purger/v1', '/purge-url', ['methods' => 'POST', 'callback' => 'ncmp_handle_purge_request', 'permission_callback' => 'ncmp_purge_permission_check']);
    register_rest_route('ncmp-purger/v1', '/purge-all', ['methods' => 'POST', 'callback' => 'ncmp_handle_purge_all_request', 'permission_callback' => 'ncmp_purge_permission_check']);
}

function ncmp_purge_permission_check($request) {
    $options = get_option('ncmp_settings');
    $secret_key = $options['ncmp_secret_key'] ?? '';
    $sent_key = $request->get_header('X-Purge-Key');
    return !empty($secret_key) && !empty($sent_key) && hash_equals($secret_key, $sent_key);
}

function ncmp_handle_purge_request($request) {
    $options = get_option('ncmp_settings');
    $cache_path = $options['ncmp_cache_path'];
    $uri_to_purge = $request->get_param('uri_path');
    if (empty($uri_to_purge)) return new WP_Error('no_uri', 'URI path is required.', ['status' => 400]);

    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    $scheme = wp_parse_url(home_url(), PHP_URL_SCHEME);
    $cache_key_string = "{$scheme}GET{$host}{$uri_to_purge}";
    $cache_hash = md5($cache_key_string);
    $cache_file_path = "{$cache_path}/" . substr($cache_hash, -1) . "/" . substr($cache_hash, -3, 2) . "/{$cache_hash}";

    if (file_exists($cache_file_path)) {
        if (unlink($cache_file_path)) {
            if (function_exists('opcache_invalidate')) opcache_invalidate($cache_file_path, true);
            return new WP_REST_Response(['status' => 'success', 'message' => "Cache dipadam untuk: {$uri_to_purge}"], 200);
        } else {
            return new WP_Error('delete_failed', 'Gagal memadam cache. Sila semak log error, file permissions & SELinux.', ['status' => 500]);
        }
    }
    return new WP_REST_Response(['status' => 'info', 'message' => "Cache tidak dijumpai untuk: {$uri_to_purge}"], 200);
}

function ncmp_handle_purge_all_request() {
    $options = get_option('ncmp_settings');
    $cache_path = $options['ncmp_cache_path'];
    if (empty($cache_path) || !is_dir($cache_path)) return new WP_Error('invalid_path', 'Path direktori cache tidak sah.', ['status' => 500]);
    try {
        $iterator = new RecursiveDirectoryIterator($cache_path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) { $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath()); }
        return new WP_REST_Response(['status' => 'success', 'message' => 'Semua cache telah berjaya dipadamkan!'], 200);
    } catch (Exception $e) {
        error_log('NCMP Purge All Error: ' . $e->getMessage());
        return new WP_Error('purge_all_failed', 'Gagal memadam semua cache. Sila semak file permissions, SELinux, dan log error server.', ['status' => 500]);
    }
}
