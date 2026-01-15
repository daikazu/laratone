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

    /**
     * Pre-calculate and persist color values when saving.
     *
     * When enabled, if a color is saved with only a hex value, the RGB, CMYK,
     * and LAB values will be automatically calculated and stored in the database.
     *
     * When disabled (default), values are calculated on-the-fly when accessed
     * but not persisted to the database.
     *
     * Benefits of enabling:
     * - Faster subsequent reads (no calculation needed)
     * - Values are queryable in the database
     * - Consistent values even if white_point config changes later
     *
     * Benefits of disabling (default):
     * - Smaller database storage
     * - White point changes affect all colors immediately
     * - Explicit values (like official Solid Coated LAB) always take precedence
     */
    'pre_calculate_colors' => false,
];
