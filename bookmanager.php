<?php
/**
 * Plugin Name:     Book Manager
 * Plugin URI:      https://www.linkedin.com/in/alireza-ceon/
 * Plugin Prefix:   EP
 * Description:     VeronaLabs test task plugin.
 * Author:          AliReza B.
 * Author URI:      https://www.linkedin.com/in/alireza-ceon/
 * Text Domain:     bookmanager
 * Domain Path:     /languages
 * Version:         0.1.0
 */


namespace RabbitExamplePlugin;

use Rabbit\Application;
use Rabbit\Database\DatabaseServiceProvider;
use Rabbit\Logger\LoggerServiceProvider;
use Rabbit\Plugin;
use Rabbit\Redirects\AdminNotice;
use Rabbit\Templates\TemplatesServiceProvider;
use Rabbit\Utils\Singleton;
use Exception;
use League\Container\Container;
 
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require dirname(__FILE__) . '/vendor/autoload.php';
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