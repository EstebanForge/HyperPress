<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

class Image extends Media
{
    public function get_media_library_config()
    {
        return wp_parse_args($this->get_arg('media_library', []), [
            'title' => 'Select Image',
            'button' => [
                'text' => 'Select Image',
            ],
            'library' => [
                'type' => 'image',
            ],
            'multiple' => false,
        ]);
    }
}
