<h2 class="nav-tab-wrapper">
    <?php foreach($settings->tabs as $tab) { ?>
        <a href="<?php echo $settings->get_url(); ?>&tab=<?php echo $tab->slug; ?>" class="nav-tab <?php echo $tab->slug == $settings->get_active_tab()->slug ? 'nav-tab-active' : null; ?>"><?php echo $tab->title; ?></a>
    <?php } ?>
</h2>
