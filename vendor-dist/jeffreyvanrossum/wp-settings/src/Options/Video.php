<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

class Video extends Media
{
    public function get_media_library_config()
    {
        return wp_parse_args($this->get_arg('media_library', []), [
            'title' => 'Select Video',
            'button' => [
                'text' => 'Select Video',
            ],
            'library' => [
                'type' => 'video',
            ],
            'multiple' => false,
        ]);
    }
}
