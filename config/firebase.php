<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Path ke fail service-account.json. Guna base_path supaya Laravel boleh
    | detect dengan betul walaupun relative path dalam .env.
    |
    */

    'credentials' => [
        'file' => base_path(env('FIREBASE_CREDENTIALS', 'storage/app/firebase/service-account.json')),
    ],
    'project_id' => env('FIREBASE_PROJECT_ID'),

];
