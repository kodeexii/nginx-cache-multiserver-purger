<?php
// includes/settings-page.php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'ncmp_add_admin_menu');
add_action('admin_init', 'ncmp_settings_init');

function ncmp_add_admin_menu() {
    add_options_page('Nginx Cache MultiServer Purger', 'MultiServer Purger', 'manage_options', 'nginx_cache_multiserver_purger', 'ncmp_options_page_html');
}

function ncmp_settings_init() {
    register_setting('ncmp_settings_group', 'ncmp_settings');
    add_settings_section('ncmp_settings_section', 'Konfigurasi Penyegerak Cache', null, 'ncmp_settings_group');
    add_settings_field('ncmp_server_ips', 'Senarai IP Web Server', 'ncmp_server_ips_render', 'ncmp_settings_group', 'ncmp_settings_section');
    add_settings_field('ncmp_secret_key', 'Secret Key', 'ncmp_secret_key_render', 'ncmp_settings_group', 'ncmp_settings_section');
    add_settings_field('ncmp_cache_path', 'Path Direktori Cache Nginx', 'ncmp_cache_path_render', 'ncmp_settings_group', 'ncmp_settings_section');
    
    add_settings_section('ncmp_manual_purge_section', 'Manual Cache Purging', null, 'ncmp_settings_group');
    add_settings_field('ncmp_purge_url_field', 'Purge URL Spesifik', 'ncmp_purge_url_render', 'ncmp_settings_group', 'ncmp_manual_purge_section');
    add_settings_field('ncmp_purge_all_field', 'Purge Semua Cache', 'ncmp_purge_all_render', 'ncmp_settings_group', 'ncmp_manual_purge_section');
}

function ncmp_server_ips_render() {
    $options = get_option('ncmp_settings');
    $ips = $options['ncmp_server_ips'] ?? '';
    echo "<textarea id='ncmp_server_ips' name='ncmp_settings[ncmp_server_ips]' rows='5' class='large-text code' placeholder='10.0.0.1\n10.0.0.2\n10.0.0.3'>" . esc_textarea($ips) . "</textarea>";
    echo "<p class='description'>Masukkan semua IP web server dalam cluster, satu IP setiap baris.</p>";
}

function ncmp_secret_key_render() {
    $options = get_option('ncmp_settings');
    echo "<input type='text' id='ncmp-secret-key-field' class='regular-text' name='ncmp_settings[ncmp_secret_key]' value='" . esc_attr($options['ncmp_secret_key'] ?? '') . "' placeholder='Klik Generate untuk kunci baru'>";
    echo "<button type='button' id='ncmp-generate-key-btn' class='button button-secondary'>Generate</button>";
    echo "<p class='description'>Kunci rahsia untuk melindungi REST API endpoint.</p>";
}

function ncmp_cache_path_render() {
    $options = get_option('ncmp_settings');
    echo "<input type='text' class='regular-text' name='ncmp_settings[ncmp_cache_path]' value='" . esc_attr($options['ncmp_cache_path'] ?? '/var/cache/nginx/fastcgi') . "'>";
    echo "<p class='description'>Path penuh ke direktori cache Nginx anda.</p>";
}

function ncmp_purge_url_render() {
    echo "<input type='text' id='ncmp-url-to-purge' class='regular-text' placeholder='/path/ke/halaman/anda/'>";
    echo "<button type='button' id='ncmp-purge-single-btn' class='button button-secondary'>Purge URL</button>";
    echo "<p class='description'>Masukkan path URL (cth: <code>/hubungi-kami/</code>). Biarkan kosong untuk laman utama.</p>";
}

function ncmp_purge_all_render() {
    echo "<button type='button' id='ncmp-purge-all-btn' class='button button-danger'>Purge SEMUA Cache Sekarang</button>";
    echo "<p class='description' style='color: #d63638;'><strong>Amaran:</strong> Tindakan ini akan memadam keseluruhan direktori cache.</p>";
}

function ncmp_options_page_html() {
    ?>
    <div class="wrap" id="ncmp-purger-app">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div id="ncmp-notice" style="display:none; margin-left: 0;"></div>
        <form id="ncmp-settings-form" action='options.php' method='post'>
            <?php
            settings_fields('ncmp_settings_group');
            do_settings_sections('ncmp_settings_group');
            ?>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const settingsForm = document.getElementById('ncmp-settings-form');
        const noticeEl = document.getElementById('ncmp-notice');
        
        // Letakkan butang simpan di luar form tetapan sebenar.
        const submitButton = document.createElement('p');
        submitButton.className = 'submit';
        submitButton.innerHTML = '<input type="submit" name="submit" id="submit" class="button button-primary" value="Simpan Tetapan">';
        settingsForm.appendChild(submitButton);

        function showNotice(message, type = 'success') {
            noticeEl.innerHTML = '<p>' + message + '</p>';
            noticeEl.className = 'notice notice-' + type + ' is-dismissible';
            noticeEl.style.display = 'block';
        }

        document.getElementById('ncmp-generate-key-btn').addEventListener('click', function() {
            const randomBytes = new Uint8Array(32); window.crypto.getRandomValues(randomBytes);
            document.getElementById('ncmp-secret-key-field').value = Array.from(randomBytes).map(byte => ('0' + byte.toString(16)).slice(-2)).join('');
        });

        const singlePurgeBtn = document.getElementById('ncmp-purge-single-btn');
        if(singlePurgeBtn) {
            singlePurgeBtn.addEventListener('click', function() {
                const secretKey = document.querySelector('[name="ncmp_settings[ncmp_secret_key]"]').value;
                this.textContent = 'Memproses...'; this.disabled = true;
                let uri = document.getElementById('ncmp-url-to-purge').value.trim() || '/';
                fetch("<?php echo esc_js(get_rest_url(null, 'ncmp-purger/v1/purge')); ?>", {
                    method: 'POST', headers: {'X-Purge-Key': secretKey, 'Content-Type': 'application/json'}, body: JSON.stringify({uri_path: uri})
                }).then(res => res.json()).then(data => { showNotice(data.message || 'Selesai!', data.status || 'success'); this.textContent = 'Purge URL'; this.disabled = false; });
            });
        }
        
        const purgeAllBtn = document.getElementById('ncmp-purge-all-btn');
        if(purgeAllBtn) {
            purgeAllBtn.addEventListener('click', function() {
                if (!confirm('Anda pasti nak padam SEMUA fail cache? Tindakan ini tidak boleh diundur.')) return;
                const secretKey = document.querySelector('[name="ncmp_settings[ncmp_secret_key]"]').value;
                this.textContent = 'MEMADAM...'; this.disabled = true;
                fetch("<?php echo esc_js(get_rest_url(null, 'ncmp-purger/v1/purge-all')); ?>", {
                    method: 'POST', headers: {'X-Purge-Key': secretKey}
                }).then(res => res.json()).then(data => { showNotice(data.message, data.status || 'success'); this.textContent = 'Purge SEMUA Cache Sekarang'; this.disabled = false; });
            });
        }
    });
    </script>
    <?php
}
