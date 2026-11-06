<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;

class CreateDatabase extends Command
{
    protected $signature = 'ems:db-create';

    protected $description = 'Create the MySQL database configured in .env if it does not exist yet';

    public function handle(): int
    {
        $config = config('database.connections.'.config('database.default'));

        if (($config['driver'] ?? null) !== 'mysql') {
            $this->components->info('Default connection is not MySQL - nothing to create.');

            return self::SUCCESS;
        }

        $name = $config['database'];

        if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $this->components->error('DB_DATABASE may only contain letters, numbers and underscores.');

            return self::FAILURE;
        }

        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            $this->components->error('Could not connect to MySQL: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Database [{$name}] is ready.");

        return self::SUCCESS;
    }
}
