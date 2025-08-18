<?php
<?php
// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data["conditional_logic"] ?? null;
$conditional_attr = "";
if ($conditional_logic) {
    $conditional_attr = " data-hp-conditional-logic="" . esc_attr(json_encode($conditional_logic)) . """;
}
?>
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'map';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? ['lat' => 0, 'lng' => 0, 'address' => ''];
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$map_options = $field_data['map_options'] ?? [];

$lat = $value['lat'] ?? 0;
$lng = $value['lng'] ?? 0;
$address = $value['address'] ?? '';

// Default map options
$zoom = $map_options['zoom'] ?? 15;
$map_type = $map_options['type'] ?? 'roadmap';
$api_key = $map_options['api_key'] ?? '';
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <div class="hyperpress-map-field">
            <input type="text" 
                   id="<?php echo esc_attr($name); ?>_address" 
                   name="<?php echo esc_attr($name_attr); ?>[address]" 
                   value="<?php echo esc_attr($address); ?>" 
                   placeholder="<?php _e('Search for an address...', 'hyperpress'); ?>" 
                   class="regular-text hyperpress-map-search">
            
            <button type="button" class="button hyperpress-geocode-button" data-field="<?php echo esc_attr($name); ?>">
                <?php _e('Search', 'hyperpress'); ?>
            </button>
            
            <div class="hyperpress-map-canvas" 
                 data-field="<?php echo esc_attr($name); ?>" 
                 data-lat="<?php echo esc_attr($lat); ?>" 
                 data-lng="<?php echo esc_attr($lng); ?>" 
                 data-zoom="<?php echo esc_attr($zoom); ?>" 
                 style="height: 300px; margin-top: 10px; border: 1px solid #ccc;">
            </div>
            
            <input type="hidden" 
                   id="<?php echo esc_attr($name); ?>_lat" 
                   name="<?php echo esc_attr($name_attr); ?>[lat]" 
                   value="<?php echo esc_attr($lat); ?>">
            
            <input type="hidden" 
                   id="<?php echo esc_attr($name); ?>_lng" 
                   name="<?php echo esc_attr($name_attr); ?>[lng]" 
                   value="<?php echo esc_attr($lng); ?>">
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>
