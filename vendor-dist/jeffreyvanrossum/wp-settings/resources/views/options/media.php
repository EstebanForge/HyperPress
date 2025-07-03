<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo $option->get_id_attribute(); ?>" class="<?php echo $option->get_label_class_attribute(); ?>"><?php echo $option->get_label(); ?></label>
    </th>
    <td class="forminp forminp-text wps-media-wrapper"
        data-media-library="<?php echo esc_attr(json_encode($option->get_media_library_config())); ?>">
        <div>
            <?php if($preview = $option->get_preview_url()) { ?>
                <div class="wps-media-preview" style="display: flex;">
                    <img src="<?php echo $preview; ?>" />
                </div>
            <?php } else { ?>
                <div class="wps-media-preview"></div>
            <?php } ?>

            <input name="<?php echo esc_attr($option->get_name_attribute()); ?>" id="<?php echo $option->get_id_attribute(); ?>" type="hidden" value="<?php echo $option->get_value_attribute(); ?>" class="wps-media-target <?php echo $option->get_input_class_attribute(); ?>">

            <button class="wps-media-open button"><?php echo $option->get_arg('button_open_text', _e('Select')); ?></button>
            <button class="wps-media-clear button" style="<?php echo empty($option->get_value_attribute()) ? 'display: none;' : ''; ?>"><?php echo $option->get_arg('button_clear_text', _e('Clear')); ?></button>
        </div>

        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>

        <?php if($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>
