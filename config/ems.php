<?php

return [
    /*
    |--------------------------------------------------------------------------
    | URL prefix of each role ("panel")
    |--------------------------------------------------------------------------
    | Every signed-in page lives under the user's own role:
    |   /employee/..., /manager/..., /hr/..., /admin/...
    | route('tasks.index') fills the prefix in automatically (App\Http\Middleware\SetPanelPrefix)
    | and opening another role's prefix is a 404.
    */
    'panels' => [
        'employee' => 'employee',
        'manager' => 'manager',
        'hr' => 'hr',
        'super_admin' => 'admin',
    ],

    /*
    | Interface languages (switcher in the top bar). Arabic is right-to-left.
    | Translations: the `translations` table (keys are the English texts), edited on /admin/translations.
    */
    /*
    | HR scope. false (default): HR works inside their own department only (the project's isolation rule).
    | true: HR sees ALL employees and tasks of every department, exactly as the brief words it.
    | Set in .env:  EMS_HR_SEES_ALL_DEPARTMENTS=true
    */
    'hr_sees_all_departments' => env('EMS_HR_SEES_ALL_DEPARTMENTS', false),

    'locales' => [
        'en' => ['name' => 'English', 'short' => 'EN', 'dir' => 'ltr'],
        'ar' => ['name' => 'العربية', 'short' => 'AR', 'dir' => 'rtl'],
    ],

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
