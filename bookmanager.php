<?php
/*
Plugin Name: Book Manager
Description: VeronaLabs test task plugin.
Version: 1.0.0
Author: AliReza B.
Author URI: https://www.linkedin.com/in/alireza-ceon/
Text Domain: bookmanager
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Include the main plugin class.
require_once plugin_dir_path(__FILE__) . 'includes/public.php';

// Register activation hook.
register_activation_hook(__FILE__, 'bookmanager_activate');

// Instantiate the plugin.
$bookmanager = new BookManager();

// Run the plugin.
add_action('plugins_loaded', 'bookmanager_run');
function bookmanager_run()
{
    // Add your plugin logic here.
}

function bookmanager_activate()
{
    // Create the books_info table on plugin activation.
    global $wpdb;
    $table_name = $wpdb->prefix . 'books_info';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id bigint(20) UNSIGNED NOT NULL,
        isbn varchar(255) NOT NULL,
        PRIMARY KEY (ID)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
