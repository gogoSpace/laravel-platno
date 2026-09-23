<?php

declare(strict_types=1);

namespace Platno\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class MakePluginCommand extends Command
{
    protected $signature = 'platno:make-plugin {name : PHP class name, for example Callout} {--type= : Portable block type, for example example.callout}';

    protected $description = 'Create a PHP plugin and escaped Blade view without overwriting host files';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $type = (string) ($this->option('type') ?: 'custom.'.Str::kebab($name));
        if (preg_match('/\A[A-Z][A-Za-z0-9]*\z/', $name) !== 1 || preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/', $type) !== 1 || in_array(strtolower($name), ['class', 'trait', 'interface', 'enum', 'match', 'readonly', 'parent', 'self', 'static'], true)) {
            $this->error('Use an ordinary PascalCase class name and a lowercase plugin type.');

            return self::FAILURE;
        }
        $namespace = rtrim($this->laravel->getNamespace(), '\\').'\\Platno';
        $view = 'platno-custom.'.Str::kebab($name);
        $classPath = app_path('Platno/'.$name.'.php');
        $viewPath = resource_path('views/platno-custom/'.Str::kebab($name).'.blade.php');
        if (file_exists($classPath) || file_exists($viewPath)) {
            $this->error('A destination already exists. No files were changed.');

            return self::FAILURE;
        }
        $label = Str::headline($name);
        $source = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        final class {$name} extends \\Platno\\Plugins\\Plugin
        {
            public function type(): string
            {
                return '{$type}';
            }

            public function label(): string
            {
                return '{$label}';
            }

            public function fields(): array
            {
                return ['text' => ['type' => 'textarea', 'label' => 'Text', 'max' => 50000]];
            }

            public function view(): string
            {
                return '{$view}';
            }
        }

        PHP;
        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (\ParseError) {
            $this->error('Choose a class name that is not reserved by PHP.');

            return self::FAILURE;
        }
        $created = [];
        try {
            foreach ([$classPath => $source, $viewPath => "<aside>{{ \$data['text'] }}</aside>\n"] as $path => $contents) {
                if (! is_dir(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }
                $handle = fopen($path, 'x');
                if ($handle === false) {
                    throw new RuntimeException('A destination was created concurrently.');
                }
                $created[] = $path;
                try {
                    if (fwrite($handle, $contents) !== strlen($contents)) {
                        throw new RuntimeException('The complete file could not be written.');
                    }
                } finally {
                    fclose($handle);
                }
            }
        } catch (Throwable $exception) {
            foreach ($created as $path) {
                unlink($path);
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Plugin created. Add this class to the plugins list in config/platno.php:');
        $this->line('\\'.$namespace.'\\'.$name.'::class,');
        $this->line('Then run php artisan config:cache if your configuration is cached.');

        return self::SUCCESS;
    }
}
