<?php

namespace Verseles\Progressable\Tests;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Progressable;
use Mockery;

class LockingTest extends TestCase
{
    use Progressable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->overallUniqueName = 'test_locking';
        // Reset trait state
        $this->isLocked = false;
        $this->customSaveData = null;
        $this->customGetData = null;
        $this->progress = 0;
        $this->metadata = [];
        $this->statusMessage = null;
    }

    public function test_update_local_progress_uses_lock()
    {
        $lockMock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $lockMock->shouldReceive('block')
            ->once()
            ->with(5, Mockery::type('callable'))
            ->andReturnUsing(function ($seconds, $callback) {
                return $callback();
            });

        Cache::shouldReceive('lock')
            ->once()
            ->with('progressable_test_locking_lock', 5)
            ->andReturn($lockMock);

        Cache::shouldReceive('get')
            ->once()
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->once();

        $this->setLocalProgress(50);
    }

    public function test_nested_locks_are_avoided()
    {
        $lockMock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $lockMock->shouldReceive('block')
            ->once()
            ->with(5, Mockery::type('callable'))
            ->andReturnUsing(function ($seconds, $callback) {
                return $callback();
            });

        // The key for lock will be based on overallUniqueName
        Cache::shouldReceive('lock')
            ->once()
            ->with('progressable_test_locking_lock', 5)
            ->andReturn($lockMock);

        Cache::shouldReceive('get')
            ->atLeast()->once()
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->atLeast()->once();

        $this->setLocalKey('new_key');
    }
}
