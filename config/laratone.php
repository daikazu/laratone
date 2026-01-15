<?php

return [

    /**
     * The prefix for the tables
     */
    'table_prefix' => 'laratone_',

    /**
     * Cache time in seconds for color books and colors
     */
    'cache_time' => 3600,

    /**
     * Reference white point (illuminant) for LAB color space calculations.
     *
     * When RGB/CMYK/LAB values are not provided, they are auto-calculated
     * from the hex value. LAB calculations require a reference white point.
     *
     * Common options:
     * - 'D50' : Print/graphic arts (warm white, ~5000K)
     * - 'D55' : Mid-morning/afternoon daylight (~5500K)
     * - 'D65' : Standard daylight, most common (default, ~6500K)
     * - 'D75' : North sky daylight (cool white, ~7500K)
     */
    'white_point' => 'D65',
];
