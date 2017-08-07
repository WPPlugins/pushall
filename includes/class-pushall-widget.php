<?php

/**
 * Class that controls PushAll subscription widget.
 *
 * @author Oleg Tsvetkov
 * @copyright Copyright (c) 2016, Oleg Tsvetkov
 * @package PushAll
 */
class PushAll_Widget extends WP_Widget
{
    /**
     * This function is for "widgets_init" action
     */
    public static function init()
    {
        register_widget("PushAll_Widget");
    }

    /**
     * PushAll_Widget constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'pushall_widget',
            __('PushAll', 'pushall'),
            array(
                'description' => __('PushAll subscription widget', 'pushall')
            ));
    }

    /**
     * @inheritdoc
     */
    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        if ($args['type'] == 'middle') {
            echo '<iframe frameborder="0" src="https://pushall.ru/widget.php?subid=' . get_option('pushall_chanel_id') . '&type=middle" width="420" height="110" scrolling="no" style="overflow: hidden;"></iframe>';
        } else {
            echo '<iframe frameborder="0" src="https://pushall.ru/widget.php?subid=' . get_option('pushall_chanel_id') . '" width="320" height="120" scrolling="no" style="overflow: hidden;"></iframe>';
        }

        echo $args['after_widget'];
    }

    /**
     * @inheritdoc
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();

        if (!empty($new_instance['type'])) {
            if ($new_instance['type'] === 'normal' || $new_instance['type'] === 'middle') {
                $instance['type'] = $new_instance['type'];
            } else {
                $instance['type'] = 'normal';
            }
        }

        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function form($instance)
    {
        $type = !empty($instance['type']) ? $instance['type'] : 'normal';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('type')); ?>"><?php esc_html_e('Type:', 'pushall'); ?></label>

            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('type')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('type')); ?>">
                <option value="normal" <?php echo($type === 'normal' ? 'selected' : '') ?>><?php _e('Normal (320px width)', 'pushall') ?></option>
                <option value="middle" <?php echo($type === 'middle' ? 'selected' : '') ?>><?php _e('Medium (420px width)', 'pushall') ?></option>
            </select>
        </p>
        <?php
    }
}