<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class HashPassword extends Command
{
    protected $signature = 'ems:hash-password {password? : Leave empty to be asked securely}';

    protected $description = 'Print a bcrypt hash, e.g. for EMS_MASTER_PASSWORD_HASH in .env';

    public function handle(): int
    {
        $password = $this->argument('password') ?? $this->secret('Password');

        if (blank($password)) {
            $this->components->error('Password cannot be empty.');

            return self::FAILURE;
        }

        // Single quotes keep the "$" characters of the hash literal inside .env
        $this->line("EMS_MASTER_PASSWORD_HASH='".Hash::make($password)."'");

        return self::SUCCESS;
    }
}
