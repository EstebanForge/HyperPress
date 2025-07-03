<form method="post" action="<?php 
echo $settings->get_full_url();
?>">
    <?php 
\HMApi\Jeffreyvr\WPSettings\view('section-menu', compact('settings'));
?>

    <?php 
foreach ($settings->get_active_tab()->get_active_sections() as $section) {
    ?>
        <?php 
    \HMApi\Jeffreyvr\WPSettings\view('section', compact('section'));
    ?>
    <?php 
}
?>

    <?php 
wp_nonce_field('wp_settings_save_' . $settings->option_name, 'wp_settings_save');
?>

    <?php 
submit_button();
?>
</form>
