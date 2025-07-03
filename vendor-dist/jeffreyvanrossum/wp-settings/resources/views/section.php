<h2><?php echo $section->title; ?></h2>

<?php if ($section->description) { ?>
    <div><?php echo $section->description; ?></div>
<?php } ?>

<table class="form-table">
    <tbody>
    <?php foreach ($section->options as $option) { ?>
        <?php echo $option->render(); ?>
    <?php } ?>
    </tbody>
</table>