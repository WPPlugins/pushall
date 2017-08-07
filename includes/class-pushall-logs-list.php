<?php

/**
 * Class PushAll_Logs_List_Table
 *
 * Extends PushAll_WP_List_Table, which is a copy of WP_List_Table from WordPress 4.3.1
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_List_Table
 */
class PushAll_Logs_List_Table extends PushAll_WP_List_Table
{
    /**
     * @var string
     */
    protected $table_name;

    /**
     * @var int
     */
    protected $per_page = 20;

    function __construct()
    {
        global $wpdb;

        parent::__construct(array(
            'singular'  => 'log',
            'plural'    => 'logs',
            'ajax'      => false
        ));

        $this->table_name = $wpdb->prefix . "pushall_log";
    }

    /**
     * @return bool
     */
    public function ajax_user_can()
    {
        return current_user_can('manage_options');
    }

    /**
     * @inheritdoc
     */
    function prepare_items()
    {
        global $wpdb;

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $this->process_bulk_action();

        $current_page = $this->get_pagenum();
        $start_item = ($current_page - 1) * $this->per_page;

        $data = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY `ID` DESC LIMIT $start_item, {$this->per_page}");

        $total_items = count($data);

        $this->items = $data;

        $total_pages = ceil($total_items / $this->per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $this->per_page,
            'total_pages' => $total_pages
       ));
    }

    /**
     * @return array
     */
    function get_bulk_actions()
    {
        return array(
            'delete'  => __('Delete Permanently')
        );
    }

    /**
     *
     */
    function process_bulk_action()
    {
        if ('delete' === $this->current_action()) {
            global $wpdb;

            $records_ids = array_map('intval', $_REQUEST['records']);

            foreach ((array) $records_ids as $record_id) {
                $wpdb->delete($this->table_name, array(
                    'ID' => $record_id
               ), array('%s'));
            }
        }
    }

    /**
     * @return array
     */
    protected function get_table_classes()
    {
        return array('widefat', 'fixed', 'striped', 'pushall'); // pages/posts
    }

    /**
     * @return array
     */
    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'date' => __('Date'),
            'post_title' => __('Post title'),
            'status' => __('Push status', 'pushall')
       );
    }

    /**
     * @return array
     */
    protected function get_sortable_columns()
    {
        return array(
            'status' => 'status',
            'date' => array('date', true)
       );
    }

    /**
     * @param object $item
     */
    protected function column_cb($item)
    {
        ?>
        <label class="screen-reader-text" for="cb-select-<?php echo $item->ID; ?>"><?php
            printf(__('Select %s'), $item->ID);
            ?></label>
        <input id="cb-select-<?php echo $item->ID; ?>" type="checkbox" name="records[]" value="<?php echo $item->ID; ?>" />
        <div class="locked-indicator"></div>
        <?php
    }

    /**
     * @param object $item
     * @param string $column_name
     * @return string
     */
    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'post_id':
            case 'status':
            case 'message':
                return $item->{$column_name};
            default:
                return '';
        }
    }

    /**
     * @param $item
     * @return bool|int|string
     */
    protected function column_date($item)
    {
        return mysql2date(__('Y/m/d g:i:s a'), $item->date);
    }

    /**
     * @param $item
     * @return string
     */
    protected function column_post_title($item)
    {
        $post = get_post($item->post_id);
        if ($post === null) {
            return sprintf(__('Unable to find post (ID: %s)', 'pushall'), $item->post_id);
        }

        return sprintf('<a href="%s" title="%s">%s</a>', get_edit_post_link($post->post_id), esc_attr__('Edit this item'), $post->post_title);
    }

    /**
     * @param $item
     * @return string
     */
    protected function column_status($item)
    {
        if ($item->status == PUSHALL_STATUS_SUCCESS) {
            $data = json_decode($item->message, true);
            $status_text = sprintf(__('Success (Subscribers: %d, Unfiltered: %d)', 'pushall'), $data['all'], $data['unfilt']);
        } else if ($item->status == PUSHALL_STATUS_ERROR_CURL) {
            $status_text = sprintf(__('cURL error: %s', 'pushall'), $item->message);
        } else {
            switch($item->message) {
                case 'wrong key':
                    $error_reason = __('Bad PushAll credentials', 'pushall');
                    break;
                case 'no key or id':
                    $error_reason = __('Channel ID or/and Channel Key is missing', 'pushall');
                    break;
                case 'duplicate in 10min':
                    $error_reason = __('Same Push Notification was already send in the last 10 minutes', 'pushall');
                    break;
                case 'not so fast':
                    $error_reason = __('Too many Push Notifications was sent in the small amount of time', 'pushall');
                    break;
                default:
                    $error_reason = $item->message;
                    break;
            }

            $status_text = sprintf(__('Error: %s', 'pushall'), $error_reason);
        }

        return $status_text;
    }

    protected function get_primary_column_name() {
        return 'post_title';
    }

    /**
     * @param object $item
     * @param string $column_name
     * @param string $primary
     * @return string
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        if ($column_name != $primary) {
            return '';
        }

        $actions = array(
            'edit' => sprintf('<a href="%s" title="%s">%s</a>', get_edit_post_link($item->post_id), esc_attr__('Edit this item'), __('Edit')),
            'view' => sprintf('<a href="%s" title="%s">%s</a>', get_permalink($item->post_id), esc_attr__('View this item', 'pushall'), __('View'))
        );

        if ($item->status != PUSHALL_STATUS_SUCCESS) {
            $actions['resend'] = sprintf('<a href="?page=pushall&a=resend&record=%s&noheader=true" title="%s">%s</a>', $item->ID, __('Resend Push notification', 'pushall'), __('Resend push', 'pushall'));
        }

        return $this->row_actions($actions);
    }
}