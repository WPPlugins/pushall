<div class="wrap">
    <h1><?php _e('PushAll integration settings', 'pushall'); ?> <small>(<a href="options-general.php?page=pushall&a=logs"><?php _e('watch logs', 'pushall') ?></a>)</small></h1>

    <form method="POST" action="options.php">
        <?php
        settings_fields('pushall_settings_section');
        do_settings_sections('pushall');

        submit_button();
        ?>
    </form>
</div>