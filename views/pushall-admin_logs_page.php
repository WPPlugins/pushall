<?php
/**
 * @var PushAll_Logs_List_Table $logsListTable
 */
?>
<div class="wrap">

    <h1><?php _e('PushAll logs', 'pushall'); ?> <small>(<a href="options-general.php?page=pushall"><?php _e('back to settings', 'pushall') ?></a>)</small></h1>

    <form id="pushall-logs" method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="a" value="logs" />

        <?php $logsListTable->display() ?>
    </form>

</div>