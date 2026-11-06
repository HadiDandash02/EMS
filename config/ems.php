<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Master password (support / "log in as" access)
    |--------------------------------------------------------------------------
    | A BCRYPT HASH (never the plain password) of the super admin password.
    | When set, entering the super admin's password together with ANY user's email
    | signs you in as that user. Every such login is written to the log.
    | Leave it empty to switch the feature off completely.
    |
    | Generate a hash:  php artisan ems:hash-password
    */
    'master_password_hash' => env('EMS_MASTER_PASSWORD_HASH'),

    /*
    | Passwords used by `php artisan db:seed` for the initial accounts.
    | If one is empty the seeder generates a random strong password and prints it once.
    */
    'seed_passwords' => [
        'hadi' => env('EMS_PASSWORD_HADI'),
        'ahmad' => env('EMS_PASSWORD_AHMAD'),
        'abbas' => env('EMS_PASSWORD_ABBAS'),
    ],
];
