<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo $option->get_id_attribute(); ?>" class="<?php echo $option->get_label_class_attribute(); ?>"><?php echo $option->get_label(); ?></label>
    </th>
    <td class="forminp forminp-text">
        <textarea
            name="<?php echo esc_attr($option->get_name_attribute()); ?>"
            id="<?php echo $option->get_id_attribute(); ?>"
            class="<?php echo $option->get_input_class_attribute(); ?>"
            rows="<?php echo $option->get_arg('rows', 5); ?>"
            cols="<?php echo $option->get_arg('cols', 50); ?>"><?php echo $option->get_value_attribute(); ?></textarea>
        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>

        <?php if($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>
