<?php

namespace LucaSabato\LaravelQaSetup\Commands;

use Illuminate\Console\Command;

class SetupQaEnvironment extends Command
{
    protected $signature = 'setup:qa';
    protected $description = 'Install and configure QA tools for Laravel + Vue + Inertia stack';

    public function handle(): int
    {
        $this->info('Starting QA environment setup using Laravel Sail.');

        if (!file_exists(base_path('vendor/bin/sail'))) {
            $this->error('Laravel Sail is not installed. Run: composer require laravel/sail --dev && php artisan sail:install');
            return self::FAILURE;
        }

        $packageManager = file_exists(base_path('pnpm-lock.yaml')) ? 'pnpm' : 'npm';
        $this->info("Detected package manager: {$packageManager}");

        $inertiaInstalled = false;
        $packageJsonPath = base_path('package.json');
        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            $deps = $packageJson['dependencies'] ?? [];
            $devDeps = $packageJson['devDependencies'] ?? [];
            if (
                array_key_exists('@inertiajs/inertia', $deps) ||
                array_key_exists('@inertiajs/inertia', $devDeps)
            ) {
                $inertiaInstalled = true;
                $this->info('Inertia.js detected in package.json dependencies.');
            } else {
                $this->info('Inertia.js not detected. Skipping Vue frontend tools installation.');
            }
        } else {
            $this->warn('package.json not found, skipping Inertia check.');
        }

        $this->info('Installing Composer dev dependencies...');
        $this->runCommand('composer require --dev laravel/pint nunomaduro/larastan phpunit/phpunit enlightn/laravel-insights');

        $this->info("Installing frontend dev dependencies using {$packageManager}...");

        $frontendBasePackages = [
            'eslint',
            'eslint-plugin-vue',
            'prettier',
            'typescript',
            'vitest',
            'jsdom',
            'vue-tsc',
            '@vue/test-utils',
            '@vitejs/plugin-vue',
        ];

        if (!$inertiaInstalled) {
            $frontendBasePackages = array_filter($frontendBasePackages, fn($pkg) => !in_array($pkg, ['eslint-plugin-vue', 'vue-tsc', '@vue/test-utils', '@vitejs/plugin-vue']));
        }

        $packagesString = implode(' ', $frontendBasePackages);
        $this->runCommand("{$packageManager} install --save-dev {$packagesString}");

        $this->info('Updating composer.json scripts...');
        $this->updateJsonScripts(base_path('composer.json'), [
            'format' => 'vendor/bin/pint',
            'lint' => '@format',
            'analyse' => 'vendor/bin/phpstan analyse',
            'test' => 'php artisan test',
            'coverage' => 'phpunit --coverage-text --colors=always',
            'check-all' => [
                '@format',
                '@analyse',
                '@test',
                'npm run check-format',
                'npm run lint',
                'npm run type-check',
                'npm run test',
            ],
        ]);

        $this->info('Updating package.json scripts...');
        $this->updateJsonScripts(base_path('package.json'), [
            'lint' => 'eslint resources/js',
            'format' => 'prettier --write "resources/js/**/*.{js,ts,vue}"',
            'check-format' => 'prettier --check "resources/js/**/*.{js,ts,vue}"',
            'type-check' => 'vue-tsc --noEmit',
            'test' => 'vitest run',
            'test:watch' => 'vitest',
        ]);

        $stubsPath = __DIR__ . '/../../stubs/';

        $this->createFileIfMissing('.eslintrc.cjs', file_get_contents($stubsPath . 'eslintrc.cjs.stub'));
        $this->createFileIfMissing('.prettierrc', file_get_contents($stubsPath . 'prettierrc.stub'));

        if (!file_exists(base_path('tsconfig.json'))) {
            $this->info('Generating tsconfig.json...');
            $this->runCommand('npx tsc --init --rootDir resources/js --outDir resources/js/dist --allowJs --esModuleInterop --module ESNext --target ESNext --moduleResolution Node --skipLibCheck');
        }

        $this->createFileIfMissing('vite.config.ts', file_get_contents($stubsPath . 'vite.config.ts.stub'));
        $this->createFileIfMissing('vitest.config.ts', file_get_contents($stubsPath . 'vitest.config.ts.stub'));
        $this->createFileIfMissing('phpstan.neon', file_get_contents($stubsPath . 'phpstan.neon.stub'));

        if (!file_exists(base_path('phpunit.xml')) && !file_exists(base_path('phpunit.xml.dist'))) {
            $this->createFileIfMissing('phpunit.xml', file_get_contents($stubsPath . 'phpunit.xml.stub'));
        }

        $this->info('Running initial frontend build...');
        $this->runCommand("{$packageManager} run build");

        $this->info('QA environment is fully configured.');
        $this->line('Run "composer check-all" to verify full quality checks.');
        $this->line("Run \"{$packageManager} run test\" to run frontend tests.");

        return self::SUCCESS;
    }

    protected function createFileIfMissing(string $filename, string $contents): void
    {
        $fullPath = base_path($filename);

        if (!file_exists($fullPath)) {
            file_put_contents($fullPath, $contents);
            $this->info("Created: {$filename}");
        }
    }

    protected function updateJsonScripts(string $filePath, array $scriptsToAdd): void
    {
        if (!file_exists($filePath)) {
            $this->warn("File {$filePath} does not exist, skipping.");
            return;
        }

        $json = json_decode(file_get_contents($filePath), true);

        if (!isset($json['scripts']) || !is_array($json['scripts'])) {
            $json['scripts'] = [];
        }

        $json['scripts'] = array_merge($json['scripts'], $scriptsToAdd);

        file_put_contents($filePath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function runCommand(string $command): void
    {
        $prefix = env('SAIL') ? '' : './vendor/bin/sail ';
        shell_exec($prefix . $command);
    }
}
