<?php

declare(strict_types=1);

namespace Platno\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use RuntimeException;

final class InstallCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'platno:install {--force : Allow installation in production}';

    protected $description = 'Install only Platno tables and missing configuration without overwriting host files';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $result = $this->call('migrate', [
            '--path' => realpath(__DIR__.'/../../database/migrations'),
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($result !== self::SUCCESS) {
            return $result;
        }

        $destination = config_path('platno.php');

        if (! file_exists($destination)) {
            $configuration = file_get_contents(__DIR__.'/../../config/platno.php');
            $handle = fopen($destination, 'x');

            if ($handle === false) {
                throw new RuntimeException('Could not create Platno configuration. Existing files were left intact.');
            }

            try {
                if (fwrite($handle, $configuration) !== strlen($configuration)) {
                    throw new RuntimeException('Could not write the complete Platno configuration.');
                }
            } finally {
                fclose($handle);
            }
        }

        $this->info('Platno is installed. Existing configuration and content were preserved.');
        $this->line('Mount Platno::editorRoutes() inside your host middleware group; mount publicRoutes() separately.');

        return self::SUCCESS;
    }
}
