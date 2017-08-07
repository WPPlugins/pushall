<?php

/**
 * This is a generic class for PushAll plugin.
 *
 * @author Oleg Tsvetkov
 * @copyright Copyright (c) 2016, Oleg Tsvetkov
 * @package PushAll
 */
class PushAll
{
    public static function init()
    {
        if (static::isActive()) {
            add_filter('strip_push_message', 'wp_trim_excerpt');
            add_filter('strip_push_message', 'strip_shortcodes');
            add_filter('strip_push_message', 'wp_strip_all_tags');
            add_filter('strip_push_message', array('PushAll', 'clean_message'));
        }
    }

    public static function clean_message($text = '')
    {
        return html_entity_decode(preg_replace("/(\s|&nbsp;){2,}/", ' ', $text));
    }

    /**
     * Returns true if PushAll Channel ID and Channel key is set, false otherwise.
     *
     * @return bool
     */
    public static function isActive()
    {
        return get_option('pushall_chanel_id', '') !== '' && get_option('pushall_chanel_key', '') !== '';
    }

    /**
     * Log Push Notification status
     *
     * @param int $post_id Post ID
     * @param int $status Status
     * @param string $message Extra information
     */
    public static function log($post_id, $status, $message)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "pushall_log";

        $wpdb->insert(
            $table_name,
            array(
                'date' => current_time('mysql'),
                'post_id' => $post_id,
                'status' => $status,
                'message' => $message,
            )
        );
    }

    /**
     * Perform PushAll send push request
     *
     * @param string $title Push notification title
     * @param string $message Push notification message
     * @param string $url Url to open on Push notification
     * @param string|null $image Image url for Push notification
     *
     * @return array
     */
    public static function send_push($title, $message, $url, $image = null)
    {
        $options = array(
            'type' => 'broadcast',
            'id' => get_option('pushall_chanel_id'),
            'key' => get_option('pushall_chanel_key'),
            'title' => $title,
            'text' => $message,
            'url' => $url,
            'encode' => get_bloginfo('charset'),
            'background' => 1
        );

        if ($image !== null) {
            $options['icon'] = $image;
        }

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "https://pushall.ru/api.php",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $options,
            CURLOPT_RETURNTRANSFER => true
        ));

        $curl_response = curl_exec($ch);
        if (curl_errno($ch)) {
            // Some curl error
            $result = array(
                'status' => PUSHALL_STATUS_ERROR_CURL,
                'data' => curl_error($ch)
            );
        } else if ($curl_response === null) {
            $result = array(
                'status' => PUSHALL_STATUS_ERROR_CURL,
                'data' => 'Possibly curl_exec is disabled by PHP settings. Please, check your PHP error logs.'
            );
        } else {
            /*
             * Possible results:
             *
             * {"error":"wrong key"} - id
             * {"error":"wrong key"} - key
             * {"error":"no key or id"}
             * {"error":"duplicate in 10min"}
             * {"error":"not so fast"}
             * {"success":1,"unfilt":1,"all":1,"lid":12345}
             */
            $data = json_decode($curl_response, true);
            if (isset($data['error'])) {
                // PushAll service returned an error
                $result = array(
                    'status' => PUSHALL_STATUS_ERROR,
                    'data' => $data['error']
                );
            } else {
                // PushAll successfully received our data
                unset($data['success']);
                unset($data['status']);
                $result = array(
                    'status' => PUSHALL_STATUS_SUCCESS,
                    'data' => json_encode($data)
                );
            }
        }

        curl_close($ch);

        return $result;
    }


    /**
     * Resend Push Notification based on PushAll plugin log record
     *
     * @param $log_id
     */
    public static function resend_push($log_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "pushall_log";
        $record = $wpdb->get_row($wpdb->prepare("SELECT `post_id` FROM {$table_name} WHERE ID = %d", array($log_id)));

        $post = get_post($record->post_id);

        static::send_push_for_post($post, true);
    }


    /**
     * Send Push notification for WordPress Post
     *
     * @param WP_Post|int $post WordPress Post object or it's ID
     * @param bool $force_send Should ignore _pushall_should_send_push post option or not. Default false.
     * @param string|null $custom_title Custom push title. Default null.
     * @param string|null $custom_message Custom push title. Default null.
     */
    public static function send_push_for_post($post, $force_send = false, $custom_title = null, $custom_message = null)
    {
        if (!($post instanceof WP_Post)) {
            $post = get_post($post);
        }

        if (!$force_send) {
            $should_send_push = get_post_meta($post->ID, '_pushall_should_send_push', true);
            if ($should_send_push === '0') {
                return;
            }
        }

        if ($custom_title !== null) {
            $title = $custom_title;
        } else {
            $title = get_post_meta($post->ID, '_pushall_custom_title', true);
            if (empty($title)) {
                $title = $post->post_title;
            }
        }

        if ($custom_message !== null) {
            $message = $custom_message;
        } else {
            $message = get_post_meta($post->ID, '_pushall_custom_message', true);
            if (empty($message)) {
                $message = empty($post->post_excerpt) ? $post->post_content : $post->post_excerpt;
            }
        }

        $result = static::send_push(sanitize_text_field($title), apply_filters('strip_push_message', $message), get_permalink($post->ID));

        static::log($post->ID, $result['status'], $result['data']);
    }
}