<?php
/*
Plugin Name: My Debug Logger
Description: Logs all WordPress requests with an option page, auto-refresh, and log cleaner
Version: 1.2
Author: Your Name
*/

// Funktion zum Loggen der Anfragen
function log_wordpress_requests() {
    if (get_option('my_debug_logger_enabled', '0') === '1') {
        $log_file = WP_CONTENT_DIR . '/debug-log.txt';
        $current_time = current_time('mysql');
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_method = $_SERVER['REQUEST_METHOD'];
        $user_ip = $_SERVER['REMOTE_ADDR'];

        $log_message = "$current_time | $request_method | $request_uri | $user_ip\n";

        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

add_action('init', 'log_wordpress_requests');

// Funktion zum Hinzufügen des Menüeintrags unter Tools
function my_debug_logger_add_admin_menu() {
    add_management_page('My Debug Logger', 'My Debug Logger', 'manage_options', 'my-debug-logger', 'my_debug_logger_page');
}

add_action('admin_menu', 'my_debug_logger_add_admin_menu');

// Funktion zum Registrieren der Einstellungen
function my_debug_logger_settings_init() {
    register_setting('my_debug_logger', 'my_debug_logger_enabled');
    register_setting('my_debug_logger', 'my_debug_logger_refresh_interval');
}

add_action('admin_init', 'my_debug_logger_settings_init');

// Funktion zur Darstellung der Plugin-Seite
function my_debug_logger_page() {
    // Handle log cleaning
    if (isset($_POST['clean_log'])) {
        $log_file = WP_CONTENT_DIR . '/debug-log.txt';
        file_put_contents($log_file, '');
        echo '<div class="updated"><p>Log file cleaned.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>My Debug Logger</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('my_debug_logger');
            do_settings_sections('my_debug_logger');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Debug Logging</th>
                    <td><input type="checkbox" name="my_debug_logger_enabled" value="1" <?php checked('1', get_option('my_debug_logger_enabled')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Auto-refresh Interval (seconds)</th>
                    <td><input type="number" name="my_debug_logger_refresh_interval" value="<?php echo esc_attr(get_option('my_debug_logger_refresh_interval', '30')); ?>" min="0" /></td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <h2>Debug Log</h2>
        <form method="post">
            <input type="submit" name="clean_log" value="Clean Log" class="button button-secondary" onclick="return confirm('Are you sure you want to clean the log?');" />
        </form>
        <div id="debug-log-content">
            <?php echo my_debug_logger_get_log_content(); ?>
        </div>
    </div>

    <script>
<script>
    function refreshLogContent() {
        jQuery.get(ajaxurl, {
            action: 'refresh_debug_log'
        }, function(response) {
            jQuery('#debug-log-content').html(response);
        });
    }

    jQuery(document).ready(function($) {
        var refreshInterval = <?php echo intval(get_option('my_debug_logger_refresh_interval', '30')) * 1000; ?>;
        if (refreshInterval > 0) {
            setInterval(refreshLogContent, refreshInterval);
        }
    });
    </script>
    <?php
}

// Funktion zum Abrufen des Log-Inhalts
function my_debug_logger_get_log_content() {
    $log_file = WP_CONTENT_DIR . '/debug-log.txt';
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        return '<pre>' . esc_html($log_content) . '</pre>';
    } else {
        return '<p>No log file found.</p>';
    }
}

// AJAX-Handler für das Aktualisieren des Log-Inhalts
function my_debug_logger_refresh_log() {
    echo my_debug_logger_get_log_content();
    wp_die();
}
add_action('wp_ajax_refresh_debug_log', 'my_debug_logger_refresh_log');

// Funktion zum Hinzufügen von Admin-Skripten
function my_debug_logger_enqueue_admin_scripts($hook) {
    if ('tools_page_my-debug-logger' !== $hook) {
        return;
    }
    wp_enqueue_script('jquery');
}
add_action('admin_enqueue_scripts', 'my_debug_logger_enqueue_admin_scripts');
