<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    |
    | max_lines: Jumlah maksimal record log yang disimpan. Record tertua
    |            otomatis dihapus setiap kali insert melewati batas ini.
    |
    */

    'max_lines' => (int) env('ACTIVITY_LOG_MAX_LINES', 500),

    'enabled' => (bool) env('ACTIVITY_LOG_ENABLED', true),

];
