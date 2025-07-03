<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

use HMApi\Jeffreyvr\WPSettings\Enqueuer;

class Media extends OptionAbstract
{
    public $view = 'media';

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }

    public function get_preview_url()
    {
        $value = $this->get_value_attribute();

        if (empty($value)) {
            return '';
        }

        $attachment = wp_get_attachment_metadata($value);
        $fallback = '/wp-includes/images/media/document.png';

        if (! $attachment) {
            return $fallback;
        }

        if (isset($attachment['image_meta'])) {
            return wp_get_attachment_image_src($value, 'thumbnail')[0];
        }

        if (strpos($attachment['mime_type'], 'video') !== false) {
            return '/wp-includes/images/media/video.png';
        }

        if (strpos($attachment['mime_type'], 'audio') !== false) {
            return '/wp-includes/images/media/audio.png';
        }

        return $fallback;
    }

    public function get_media_library_config()
    {
        return wp_parse_args($this->get_arg('media_library', []), [
            'title' => 'Select Media',
            'button' => [
                'text' => 'Select Media',
            ],
            'multiple' => false,
        ]);
    }

    public function enqueue()
    {
        Enqueuer::add('wps-media', function () {
            wp_enqueue_media();
            ?>
            <style>
                .wps-media-wrapper > div {
                    display: flex;
                    gap: 15px;
                    align-items: start;
                }

                .wps-media-wrapper .wps-media-preview {
                    width: 100px;
                    background-color: #fff;
                    border-radius: 5px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 10px;
                    display: none;
                }

                .wps-media-wrapper .wps-media-preview img {
                    max-width: 100%;
                }
            </style>
            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    document.querySelectorAll('.wps-media-wrapper').forEach((el) => {
                        let trigger = el.querySelector('.wps-media-open');
                        let clear = el.querySelector('.wps-media-clear');
                        let target = el.querySelector('.wps-media-target');
                        let preview = el.querySelector('.wps-media-preview');
                        let media_library_config = JSON.parse(el.getAttribute('data-media-library'));

                        let media_library = wp.media(media_library_config);

                        clear.addEventListener('click', function (e) {
                            e.preventDefault();

                            target.value = '';
                            preview.innerHTML = '';
                            clear.style.display = 'none';
                            preview.style.display = 'none';
                        });

                        trigger.addEventListener('click', function (e) {
                            e.preventDefault();

                            media_library.open();
                        });

                        media_library.on('open', function() {
                            if(target.value === '') {
                                return;
                            }

                            let selection = media_library.state().get('selection');
                            let attachment = wp.media.attachment(target.value);

                            selection.add(attachment ? [attachment] : []);
                        });

                        media_library.on('select', function () {
                            let attachment = media_library.state().get('selection').first().toJSON();

                            target.value = attachment.id;

                            if (attachment.type === 'image') {
                                preview.innerHTML = '<img src="' + attachment.sizes.thumbnail.url + '">';
                            } else {
                                preview.innerHTML = '<img src="' + attachment.icon + '">';
                            }

                            preview.style.display = 'flex';
                            clear.style.display = 'inline-block';
                        });
                    });
                });
            </script>
            <?php
        });
    }
}
