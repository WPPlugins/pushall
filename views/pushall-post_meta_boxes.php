<?php
/**
 * @var bool $pushall_active
 * @var bool $should_send_push
 * @var string $pushall_form_disabled
 * @var string $custom_title
 * @var string $custom_message
 */
?>
<?php if ($pushall_active): ?>
<p>
    <label class="selectit" for="_pushall_should_send_push">
        <input type="checkbox" id="_pushall_should_send_push" name="_pushall_should_send_push" value="1"<?php echo ($should_send_push ? ' checked="checked"' : ''); ?><?php echo $pushall_form_disabled; ?> />
        <?php _e('Send push?', 'pushall'); ?>
    </label>
</p>
<p>
    <label for="_pushall_custom_title"><?php _e('Custom title for Push title:', 'pushall'); ?></label><br />
    <input type="text" id="_pushall_custom_title" name="_pushall_custom_title" placeholder="<?php _e('Enter the Push Notification title here, otherwise the post title will be used.', 'pushall'); ?>" value="<?php echo (empty($custom_title) ? '' : $custom_title); ?>" class="code"<?php echo $pushall_form_disabled; ?> />
</p>
<p>
    <label for="_pushall_custom_message"><?php _e('Custom title for Push message:', 'pushall'); ?></label><br />
    <textarea id="_pushall_custom_message" name="_pushall_custom_message" placeholder="<?php _e('Enter the Push Notification message here, otherwise the post body will be used.', 'pushall'); ?>" class="code"<?php echo $pushall_form_disabled; ?>><?php echo (empty($custom_message) ? '' : $custom_message); ?></textarea>
</p>
<?php else: ?>
<p>
    <?php printf(__('You need to <a href="%s" title="%s">configure PushAll module</a> to be able to send Push notifications about your post.', 'pushall'), admin_url('options-general.php?page=pushall'), __('PushAll integration settings', 'pushall')); ?>
</p>
<?php endif; ?>