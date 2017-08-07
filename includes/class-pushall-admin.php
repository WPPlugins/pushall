<?php

/**
 * Class for the staff, related to the admin interface.
 *
 * @author Oleg Tsvetkov
 * @copyright Copyright (c) 2016, Oleg Tsvetkov
 * @package PushAll
 */
class PushAll_Admin
{
    public static function admin_init()
    {
        if (PushAll::isActive()) {
            // If PushAll setting is not provided, then we don't need this hooks
            add_action('add_meta_boxes_post', array('PushAll_Admin', 'add_meta_boxes'));
            add_action('save_post', array('PushAll_Admin', 'save_post'));
            add_action('transition_post_status', array('PushAll_Admin', 'transition_post_status'), 10, 3);

            add_filter('strip_push_message', 'wp_trim_excerpt');
            add_filter('strip_push_message', 'strip_shortcodes');
            add_filter('strip_push_message', 'wp_strip_all_tags');
            add_filter('strip_push_message', array('PushAll', 'clean_message'));
        }

        add_settings_section('pushall_settings_section', 'PushAll', array('PushAll_Admin', 'section_options_callback'), 'pushall');
        add_settings_field('pushall_chanel_id', __('Channel ID', 'pushall'), array('PushAll_Admin', 'channel_id_setting_callback'), 'pushall', 'pushall_settings_section');
        add_settings_field('pushall_chanel_key', __('Channel Key', 'pushall'), array('PushAll_Admin', 'channel_key_setting_callback'), 'pushall', 'pushall_settings_section');

        register_setting('pushall_settings_section', 'pushall_chanel_id', array('PushAll_Admin', 'filter_channel_id'));
        register_setting('pushall_settings_section', 'pushall_chanel_key', array('PushAll_Admin', 'filter_channel_key'));
    }

    /*
     * Hooks and settings
     */
    public static function add_meta_boxes($post)
    {
        // todo: Add post type configuration
        add_meta_box('pushall', 'PushAll', array('PushAll_Admin', 'meta_box_callback'), 'post', 'advanced', 'high');
    }

    public static function meta_box_callback($post)
    {
        /** @var $post WP_Post */
        wp_nonce_field('pushall_save_meta_box_data', 'pushall_meta_box_nonce');

        $pushall_active = PushAll::isActive();
        $pushall_form_disabled = $post->post_status == 'publish' ? ' disabled="disabled"' : '';

        $should_send_push = get_post_meta($post->ID, '_pushall_should_send_push', true);
        if ($should_send_push == '0') {
            $should_send_push = false;
        } else {
            $should_send_push = true;
        }

        $custom_title = get_post_meta($post->ID, '_pushall_custom_title', true);
        $custom_message = get_post_meta($post->ID, '_pushall_custom_message', true);

        include(PUSHALL_PATH . 'views/pushall-post_meta_boxes.php');
    }

    public static function save_post($post_id)
    {
        // Check if our nonce is set.
        if (!isset($_POST['pushall_meta_box_nonce'])) {
            return;
        }

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['pushall_meta_box_nonce'], 'pushall_save_meta_box_data')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['_pushall_should_send_push'])) {
            // Default value of _pushall_should_send_push is 1 (send), so we can delete it to save some DB space.
            delete_post_meta($post_id, '_pushall_should_send_push');
        } else {
            update_post_meta($post_id, '_pushall_should_send_push', '0');
        }

        if (isset($_POST['_pushall_custom_title']) && !empty($_POST['_pushall_custom_title'])) {
            update_post_meta($post_id, '_pushall_custom_title', sanitize_text_field($_POST['_pushall_custom_title']));
        } else {
            // If _pushall_custom_title value is not provided then plugin will use an original post heading,
            // so we can delete it to save some DB space.
            delete_post_meta($post_id, '_pushall_custom_title');
        }

        if (isset($_POST['_pushall_custom_message']) && !empty($_POST['_pushall_custom_message'])) {
            update_post_meta($post_id, '_pushall_custom_message', wp_strip_all_tags($_POST['_pushall_custom_message']));
        } else {
            // If _pushall_custom_message value is not provided then plugin will use an original post heading,
            // so we can delete it to save some DB space.
            delete_post_meta($post_id, '_pushall_custom_message');
        }
    }

    public static function transition_post_status($new_status, $old_status, $post)
    {
        /** @var $post WP_Post */
        // todo: Add post type configuration
        if ($post->post_type !== 'post') {
            return;
        }

        if ($new_status != 'publish' || $old_status == 'publish' || $old_status == 'trash') {
            return;
        }

        if (isset($_POST['_pushall_should_send_push'])) {
            PushAll::send_push_for_post(
                $post,
                false,
                isset($_POST['_pushall_custom_title']) && !empty($_POST['_pushall_custom_title']) ? $_POST['_pushall_custom_title'] : null,
                isset($_POST['_pushall_custom_message']) && !empty($_POST['_pushall_custom_message']) ? $_POST['_pushall_custom_message'] : null
            );
        }
    }

    public static function section_options_callback()
    {
        _e('To obtain a PushAll credentials go to <a href="https://pushall.ru">PushAll.ru</a>', 'pushall');
    }

    public static function channel_id_setting_callback()
    {
        echo '<input type="text" id="pushall_chanel_id" name="pushall_chanel_id" value="' . get_option('pushall_chanel_id', '') . '" class="regular-text" />';
    }

    public static function channel_key_setting_callback()
    {
        echo '<input type="text" id="pushall_chanel_key" name="pushall_chanel_key" value="' . get_option('pushall_chanel_key', '') . '" class="regular-text" />';
    }

    public static function filter_channel_id($data)
    {
        // Remove everything except numbers, because Channel ID is a number.
        return preg_replace('/\D/', '', $data);
    }

    public static function filter_channel_key($data)
    {
        // Remove everything except numbers and letters, because Channel key can only have them.
        return preg_replace('/\W/', '', $data);
    }

    /*
     * Admin menu
     */
    public static function admin_menu()
    {
        add_options_page('PushAll', 'PushAll', 'manage_options', 'pushall', array('PushAll_Admin', 'admin_menu_settings'));
    }

    public static function admin_menu_settings()
    {
        $action = isset($_GET['a']) ? strval($_GET['a']) : 'settings';

        if ($action === 'logs') {
            // Logs page
            include(PUSHALL_PATH. 'includes/class-pushall-wp-list-table.php');
            include(PUSHALL_PATH. 'includes/class-pushall-logs-list.php');

            $logsListTable = new pushall_logs_List_Table();
            $logsListTable->prepare_items();

            include(PUSHALL_PATH . 'views/pushall-admin_logs_page.php');
        } else if ($action === 'resend' && isset($_GET['record']) && isset($_GET['noheader'])) {
            // Perform notification resend
            PushAll::resend_push(intval($_GET['record']));

            wp_safe_redirect(admin_url('options-general.php?page=pushall&a=logs'));
            // todo: Add notification
        } else {
            // Settings page
            include(PUSHALL_PATH . 'views/pushall-admin_menu_settings.php');
        }
    }

    /*
     * Styles
     */
    public static function admin_enqueue_scripts()
    {
        wp_enqueue_style('pushall', PUSHALL_URL . 'assets/css/pushall.css', '', PUSHALL_VERSION);
    }
}