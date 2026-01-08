<?php

declare(strict_types=1);

namespace Pest\Browser\Enums;

/**
 * @internal
 */
enum Device: string
{
    // Generic
    case DESKTOP = 'desktop';
    case MOBILE = 'mobile';

    // Apple
    case MACBOOK_16 = 'macbook_16';
    case MACBOOK_14 = 'macbook_14';
    case MACBOOK_AIR = 'macbook_air';
    case IPHONE_15_PRO = 'iphone_15_pro';
    case IPHONE_15 = 'iphone_15';
    case IPHONE_14_PRO = 'iphone_14_pro';
    case IPHONE_SE = 'iphone_se';
    case IPAD_PRO = 'ipad_pro';
    case IPAD_MINI = 'ipad_mini';

    // Google
    case PIXEL_8 = 'pixel_8';
    case PIXEL_7 = 'pixel_7';
    case PIXEL_6A = 'pixel_6a';

    // Samsung
    case GALAXY_S24_ULTRA = 'galaxy_s24_ultra';
    case GALAXY_S23 = 'galaxy_s23';
    case GALAXY_S22 = 'galaxy_s22';
    case GALAXY_NOTE_20 = 'galaxy_note_20';
    case GALAXY_TAB_S8 = 'galaxy_tab_s8';

    // Microsoft
    case SURFACE_PRO_9 = 'surface_pro_9';
    case SURFACE_LAPTOP_5 = 'surface_laptop_5';

    // Other popular
    case ONEPLUS_11 = 'oneplus_11';
    case XIAOMI_13 = 'xiaomi_13';
    case HUAWEI_P50 = 'huawei_p50';

    /**
     * Returns the device context settings.
     *
     * @return array{
     *     viewport: array{width: int, height: int},
     *     deviceScaleFactor: float,
     *     isMobile: bool,
     *     hasTouch: bool,
     * }
     */
    public function context(): array
    {
        return match ($this) {
            // Generic
            self::DESKTOP => [
                'viewport' => ['width' => 1728, 'height' => 1117],
                'deviceScaleFactor' => 2,
                'isMobile' => false,
                'hasTouch' => false,
            ],
            self::MOBILE => [
                'viewport' => ['width' => 375, 'height' => 812],
                'deviceScaleFactor' => 2,
                'isMobile' => true,
                'hasTouch' => true,
            ],

            // Apple
            self::MACBOOK_16 => [
                'viewport' => ['width' => 1536, 'height' => 960],
                'deviceScaleFactor' => 2,
                'isMobile' => false,
                'hasTouch' => false,
            ],
            self::MACBOOK_14 => [
                'viewport' => ['width' => 1512, 'height' => 982],
                'deviceScaleFactor' => 2,
                'isMobile' => false,
                'hasTouch' => false,
            ],
            self::MACBOOK_AIR => [
                'viewport' => ['width' => 1440, 'height' => 900],
                'deviceScaleFactor' => 2,
                'isMobile' => false,
                'hasTouch' => false,
            ],
            self::IPHONE_15_PRO => [
                'viewport' => ['width' => 393, 'height' => 852],
                'deviceScaleFactor' => 3,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::IPHONE_15 => [
                'viewport' => ['width' => 390, 'height' => 844],
                'deviceScaleFactor' => 3,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::IPHONE_14_PRO => [
                'viewport' => ['width' => 393, 'height' => 852],
                'deviceScaleFactor' => 3,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::IPHONE_SE => [
                'viewport' => ['width' => 320, 'height' => 568],
                'deviceScaleFactor' => 2,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::IPAD_PRO => [
                'viewport' => ['width' => 1024, 'height' => 1366],
                'deviceScaleFactor' => 2,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::IPAD_MINI => [
                'viewport' => ['width' => 768, 'height' => 1024],
                'deviceScaleFactor' => 2,
                'isMobile' => true,
                'hasTouch' => true,
            ],

            // Google
            self::PIXEL_8 => [
                'viewport' => ['width' => 412, 'height' => 915],
                'deviceScaleFactor' => 2.625,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::PIXEL_7 => [
                'viewport' => ['width' => 412, 'height' => 915],
                'deviceScaleFactor' => 2.625,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::PIXEL_6A => [
                'viewport' => ['width' => 360, 'height' => 800],
                'deviceScaleFactor' => 2.5,
                'isMobile' => true,
                'hasTouch' => true,
            ],

            // Samsung
            self::GALAXY_S24_ULTRA => [
                'viewport' => ['width' => 412, 'height' => 915],
                'deviceScaleFactor' => 3,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::GALAXY_S23 => [
                'viewport' => ['width' => 412, 'height' => 915],
                'deviceScaleFactor' => 3,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::GALAXY_S22 => [
                'viewport' => ['width' => 360, 'height' => 800],
                'deviceScaleFactor' => 3,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::GALAXY_NOTE_20 => [
                'viewport' => ['width' => 412, 'height' => 915],
                'deviceScaleFactor' => 2.75,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::GALAXY_TAB_S8 => [
                'viewport' => ['width' => 1600, 'height' => 2560],
                'deviceScaleFactor' => 2.5,
                'isMobile' => true,
                'hasTouch' => true,
            ],

            // Microsoft
            self::SURFACE_PRO_9 => [
                'viewport' => ['width' => 1440, 'height' => 960],
                'deviceScaleFactor' => 2,
                'isMobile' => false,
                'hasTouch' => true,
            ],
            self::SURFACE_LAPTOP_5 => [
                'viewport' => ['width' => 2256, 'height' => 1504],
                'deviceScaleFactor' => 1.5,
                'isMobile' => false,
                'hasTouch' => false,
            ],

            // Others
            self::ONEPLUS_11 => [
                'viewport' => ['width' => 412, 'height' => 915],
                'deviceScaleFactor' => 2.75,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::XIAOMI_13 => [
                'viewport' => ['width' => 393, 'height' => 873],
                'deviceScaleFactor' => 3,
                'isMobile' => true,
                'hasTouch' => true,
            ],
            self::HUAWEI_P50 => [
                'viewport' => ['width' => 360, 'height' => 780],
                'deviceScaleFactor' => 2.75,
                'isMobile' => true,
                'hasTouch' => true,
            ],
        };
    }
}
