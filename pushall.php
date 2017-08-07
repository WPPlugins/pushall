<?php
/**
 * The PushAll plugin file

 * @author Oleg Tsvetkov
 * @link https://pushall.ru
 * @copyright Copyright (c) 2016, Oleg Tsvetkov
 * @package PushAll
 *
 * @wordpress-plugin
 * Plugin Name:       PushAll
 * Plugin URI:        https://wordpress.org/plugins/pushall/
 * Description:       Send Push notifications from Wordpress via PushAll service
 * Version:           1.1.1
 * Author:            PushAll
 * Author URI:        https://pushall.ru
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pushall
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define("PUSHALL_VERSION", "1.1.1");
define("PUSHALL_DB_VERSION", "1");
define("PUSHALL_PATH", plugin_dir_path(__FILE__));
define("PUSHALL_URL", plugin_dir_url(__FILE__));

define("PUSHALL_STATUS_SUCCESS", '0');
define("PUSHALL_STATUS_ERROR", '1');
define("PUSHALL_STATUS_ERROR_CURL", '2');

require_once PUSHALL_PATH . 'includes/class-pushall.php';

function pushall_plugins_loaded()
{
    load_plugin_textdomain('pushall', FALSE, 'pushall/languages');
}

add_action('plugins_loaded', 'pushall_plugins_loaded');

if (defined('DOING_CRON')) {
    add_action('init', array('PushAll', 'init'));

    function pushall_publish_future_post($post_id)
    {
        if (!PushAll::isActive()) {
            return;
        }

        $post = get_post($post_id);
        if ($post->post_type !== 'post') {
            return;
        }

        if (get_post_meta($post->ID, '_pushall_should_send_push', true) != '0') {
            PushAll::send_push_for_post($post);
        }
    }

    add_action('publish_future_post', 'pushall_publish_future_post');
} else {
    if (is_admin()) {
        require_once PUSHALL_PATH . 'includes/class-pushall-admin.php';

        add_action('admin_init', array('PushAll_Admin', 'admin_init'));
        add_action('admin_menu', array('PushAll_Admin', 'admin_menu'));
        add_action('admin_enqueue_scripts', array('PushAll_Admin', 'admin_enqueue_scripts'));
    }

    if (PushAll::isActive()) {
        require_once PUSHALL_PATH . 'includes/class-pushall-widget.php';

        add_action('widgets_init', array('PushAll_Widget', 'init'));
    }
}

function pushall_install()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "pushall_log";

    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $charset_collate = $wpdb->get_charset_collate();

        $sql = <<<SQL
CREATE TABLE $table_name (
    `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `post_id` bigint(20) UNSIGNED NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT '0',
    `message` VARCHAR(256) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `pushall_log_status` (`status`),
  KEY `pushall_log_status_date` (`status`, `date`)
) $charset_collate;
SQL;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option("pushall_db_version", PUSHALL_DB_VERSION);
    }
}

register_activation_hook(__FILE__, 'pushall_install');