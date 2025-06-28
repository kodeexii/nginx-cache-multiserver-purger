<?php
// includes/core-functions.php
if (!defined('ABSPATH')) exit;

// Fungsi ini trigger purge untuk URL tunggal dari kod lain (cth: auto purge on save)
function ncmp_initiate_purge($uri_path) {
    ncmp_send_purge_requests($uri_path, false);
}

// Fungsi utama yang menghantar request purge ke semua server
function ncmp_send_purge_requests($uri_path = null, $purge_all = false) {
    $options = get_option('ncmp_settings');
    $server_ips_raw = $options['ncmp_server_ips'] ?? '';
    $secret_key = $options['ncmp_secret_key'] ?? '';

    if (empty($secret_key) || empty($server_ips_raw)) {
        $error_msg = empty($secret_key) ? 'Secret Key' : 'Senarai IP Server';
        error_log("Nginx Cache MultiServer Purger: {$error_msg} tidak ditetapkan. Purge dibatalkan.");
        return;
    }

    $server_ips = array_filter(array_map('trim', explode("\n", $server_ips_raw)));
    if (empty($server_ips)) { error_log("Nginx Cache MultiServer Purger: Senarai IP Server kosong."); return; }

    $endpoint_path = $purge_all ? 'ncmp-purger/v1/purge-all' : 'ncmp-purger/v1/purge';
    $api_body = $purge_all ? '' : json_encode(['uri_path' => $uri_path]);
    $api_headers = ['X-Purge-Key' => $secret_key, 'Content-Type' => 'application/json'];
    
    $targets = [];
    foreach ($server_ips as $ip) {
        $targets[] = "http://{$ip}/wp-json/{$endpoint_path}";
    }

    foreach ($targets as $url) {
        wp_remote_post($url, [
            'headers' => $api_headers, 'body' => $api_body, 'method' => 'POST', 'timeout' => 15, 'blocking' => false,
        ]);
    }
}
