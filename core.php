<?php
/*
Plugin Name: Interaction & Debug Logger
Plugin URI: https://github.com/volkansah/interaction-debug-logger
Description: Real-time logging of user interactions and debugging information with an expandable console. Easily monitor and analyze site activity directly from your WordPress dashboard or anywhere on your site.
Version: 2.5
Author: Volkan Kücükbudak
Author URI: https://aicodecraft.io
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: interaction-debug-logger
Domain Path: /languages
Tags: debug, logger, interactions, real-time, monitoring, WordPress, development, admin-tools
*/




// Funktion zum Loggen der Anfragen
function log_wordpress_requests() {
    if (get_option('adv_debug_logger_enabled', '0') === '1') {
        $log_file = WP_CONTENT_DIR . '/debug-log.txt';
        $current_time = current_time('mysql');
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_method = $_SERVER['REQUEST_METHOD'];
        $user_ip = $_SERVER['REMOTE_ADDR'];

        if (strpos($request_uri, 'admin-ajax.php') === false) {
            $log_message = "$current_time | $request_method | $request_uri | $user_ip\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
        }
    }
}
add_action('init', 'log_wordpress_requests');

// Funktion zum Hinzufügen des Menüeintrags unter Tools
function adv_debug_logger_add_admin_menu() {
    add_management_page(
        'Interaction & Debug Logger', 
        'Interaction Logger', 
        'manage_options', 
        'adv-debug-logger', 
        'adv_debug_logger_page'
    );
}
add_action('admin_menu', 'adv_debug_logger_add_admin_menu');

// Funktion zum Registrieren der Einstellungen
function adv_debug_logger_settings_init() {
    register_setting('adv_debug_logger', 'adv_debug_logger_enabled');
    register_setting('adv_debug_logger', 'adv_debug_logger_refresh_interval', [
        'default' => 1000,
    ]);
}
add_action('admin_init', 'adv_debug_logger_settings_init');

// Funktion zur Darstellung der Plugin-Seite
function adv_debug_logger_page() {
    ?>
    <div class="wrap">
        <h1>Advanced Interaction & Debug Logger</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('adv_debug_logger');
            do_settings_sections('adv_debug_logger');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Interaction Logging</th>
                    <td><input type="checkbox" name="adv_debug_logger_enabled" value="1" <?php checked('1', get_option('adv_debug_logger_enabled')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Refresh Interval (milliseconds)</th>
                    <td><input type="number" name="adv_debug_logger_refresh_interval" value="<?php echo esc_attr(get_option('adv_debug_logger_refresh_interval', '1000')); ?>" min="100" step="100" /></td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <h2>Interaction Log</h2>
        <button id="clean-log" class="button button-secondary">Clean Log</button>
        <div id="debug-log-content" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var refreshInterval = <?php echo intval(get_option('adv_debug_logger_refresh_interval', '1000')); ?>;

        function refreshLogContent() {
            $.ajax({
                url: ajaxurl,
                data: { action: 'refresh_debug_log' },
                success: function(response) {
                    $('#debug-log-content').html(response.split('\n').reverse().join('\n'));
                }
            });
        }

        setInterval(refreshLogContent, refreshInterval);

        $('#clean-log').click(function() {
            if (confirm('Are you sure you want to clean the log?')) {
                $.ajax({
                    url: ajaxurl,
                    data: { action: 'clean_debug_log' },
                    success: function() {
                        refreshLogContent();
                    }
                });
            }
        });
    });
    </script>
    <?php
}
add_action('admin_menu', 'adv_debug_logger_add_admin_menu');

// AJAX-Handler für das Aktualisieren des Log-Inhalts auf der Optionsseite
function adv_debug_logger_refresh_log() {
    $log_file = WP_CONTENT_DIR . '/debug-log.txt';
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        // Die Log-Einträge in HTML umbrüche anpassen
        echo nl2br(esc_html($log_content));
    } else {
        echo '<p>No log file found.</p>';
    }
    wp_die();
}
add_action('wp_ajax_refresh_debug_log', 'adv_debug_logger_refresh_log');

// AJAX-Handler für das Säubern des Logs
function adv_debug_logger_clean_log() {
    $log_file = WP_CONTENT_DIR . '/debug-log.txt';
    file_put_contents($log_file, '');
    wp_die();
}
add_action('wp_ajax_clean_debug_log', 'adv_debug_logger_clean_log');

// Funktion zum Hinzufügen der Debug-Konsole im Footer
function adv_debug_logger_add_console() {
    if (current_user_can('manage_options') && get_option('adv_debug_logger_enabled', '0') === '1') {
        $refresh_interval = intval(get_option('adv_debug_logger_refresh_interval', '1000'));
        ?>
        <div id="debug-console" style="position:fixed; bottom:0; left:0; right:0; height:30px; background:#f1f1f1; border-top:1px solid #ccc; overflow:hidden; transition:height 0.3s; z-index: 9999;">
            <div style="padding:5px; cursor:pointer; background:#e1e1e1; text-align:center;" onclick="toggleConsole()">Interaction Console (Click to expand)</div>
            <div id="debug-console-content" style="padding:10px; height:calc(100% - 30px); overflow:auto; display:none;">
                <button id="clear-log-console" style="font-size: 10px; margin-bottom: 5px; position:sticky; top:0; background:#e1e1e1; padding:5px; border:1px solid #ccc;">Clear Log</button>
                <div id="log-content" style="max-height: 250px; overflow-y: auto;"></div>
            </div>
        </div>
        <script>
        var refreshInterval = <?php echo $refresh_interval; ?>;

        function toggleConsole() {
            var console = document.getElementById('debug-console');
            var content = document.getElementById('debug-console-content');
            if (console.style.height === '30px') {
                console.style.height = '300px';
                content.style.display = 'block';
            } else {
                console.style.height = '30px';
                content.style.display = 'none';
            }
        }

        function refreshConsoleContent() {
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: { action: 'refresh_debug_log' },
                success: function(response) {
                    jQuery('#log-content').html(response.split('\n').reverse().join('\n'));
                }
            });
        }

        setInterval(refreshConsoleContent, refreshInterval);

        jQuery(document).ready(function($) {
            $('#clear-log-console').click(function() {
                if (confirm('Are you sure you want to clear the log?')) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        data: { action: 'clean_debug_log' },
                        success: function() {
                            refreshConsoleContent();
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'adv_debug_logger_add_console');
add_action('admin_footer', 'adv_debug_logger_add_console');

// Funktion zum Hinzufügen von Admin-Skripten
function adv_debug_logger_enqueue_admin_scripts($hook) {
    if ('tools_page_adv-debug-logger' !== $hook) {
        return;
    }
    wp_enqueue_script('jquery');
}
add_action('admin_enqueue_scripts', 'adv_debug_logger_enqueue_admin_scripts');
