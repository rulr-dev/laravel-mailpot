<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailpot Storage Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Mailpot will store email messages and
    | statistics. By default, it uses the framework's storage directory.
    |
    */

    'storage_path' => env('MAILPOT_STORAGE_PATH', storage_path('framework/mailpot')),

];
