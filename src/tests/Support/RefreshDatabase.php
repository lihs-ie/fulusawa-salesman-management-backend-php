<?php

namespace Tests\Support;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase as Base;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

trait RefreshDatabase
{
    use Base;

    protected $connections = [null];

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function refreshInMemoryDatabase()
    {
        $config = $this->app[ConfigRepository::class];

        foreach (
            [
                $config->get('database.connections.testing') => 'default',
            ] as $database => $dir
        ) {
            $this->artisan('migrate', [
                '--database' => $database,
                '--path' => 'database/migrations/',
            ]);
        }
    }

    protected function refreshTestDatabase()
    {
        $config = $this->app[ConfigRepository::class];

        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
                '--database' => 'testing',
                '--path' => 'database/migrations/'
            ]);

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->connections = [$config->get('database.default')];

        $this->beginDatabaseTransaction();
    }

    protected function getDatabaseConnection(): string
    {
        $config = $this->app[ConfigRepository::class];

        return $config->get('database.default');
    }
}
