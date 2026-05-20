<?php

return [

    /*
    |--------------------------------------------------------------------------
    | mysqldump binary path
    |--------------------------------------------------------------------------
    |
    | Optional absolute path to mysqldump when it is not on PHP's PATH (common
    | with PHP-FPM). When unset or not executable, backups fall back to a
    | PHP-based SQL exporter.
    |
    */

    'mysqldump_path' => env('MYSQLDUMP_PATH'),

];
