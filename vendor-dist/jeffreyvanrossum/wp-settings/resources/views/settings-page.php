<div class="wrap">
    <h1><?php echo $settings->title; ?></h1>

    <?php if ($flash = $settings->flash->has()) { ?>
    <div class="notice notice-<?php echo $flash['status']; ?> is-dismissible">
        <p><?php echo $flash['message']; ?></p>
    </div>
    <?php } ?>

    <?php if( $errors = $settings->errors->get_all() ) { ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'Some problems were detected.', 'textdomain' ); ?></p>
        </div>
    <?php } ?>

    <?php $settings->render_tab_menu(); ?>

    <?php $settings->render_active_sections(); ?>
</div>
