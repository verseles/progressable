<?php

namespace Verseles\Progressable\Stores;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;
use Verseles\Progressable\Contracts\ProgressStore;

class CacheProgressStore implements ProgressStore {
    public function __construct(
        protected int $lockSeconds = 10,
        protected int $lockWaitSeconds = 5,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function putLocal(string $overallKey, string $localKey, array $data, int $ttl): void {
        $this->mutate($overallKey, $ttl, function (array $progressData) use ($localKey, $data) {
            $progressData[$localKey] = $data;

            return $progressData;
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAll(string $overallKey): array {
        $progressData = Cache::get($overallKey, []);

        return is_array($progressData) ? $progressData : [];
    }

    public function renameLocal(string $overallKey, string $currentLocalKey, string $newLocalKey, int $ttl): void {
        $this->mutate($overallKey, $ttl, function (array $progressData) use ($currentLocalKey, $newLocalKey) {
            if (! isset($progressData[$currentLocalKey])) {
                return $progressData;
            }

            if (! isset($progressData[$newLocalKey])) {
                $progressData[$newLocalKey] = $progressData[$currentLocalKey];
            }

            unset($progressData[$currentLocalKey]);

            return $progressData;
        });
    }

    public function removeLocal(string $overallKey, string $localKey, int $ttl): void {
        $this->mutate($overallKey, $ttl, function (array $progressData) use ($localKey) {
            unset($progressData[$localKey]);

            return $progressData;
        });
    }

    public function resetOverall(string $overallKey): void {
        if (! $this->supportsLocks()) {
            Cache::forget($overallKey);

            return;
        }

        Cache::lock($this->lockKey($overallKey), $this->lockSeconds)->block(
            $this->lockWaitSeconds,
            fn () => Cache::forget($overallKey)
        );
    }

    /**
     * @param  callable(array<string, array<string, mixed>>): array<string, array<string, mixed>>  $callback
     */
    protected function mutate(string $overallKey, int $ttl, callable $callback): void {
        if (! $this->supportsLocks()) {
            Cache::put($overallKey, $callback($this->getAll($overallKey)), $this->ttlInSeconds($ttl));

            return;
        }

        Cache::lock($this->lockKey($overallKey), $this->lockSeconds)->block(
            $this->lockWaitSeconds,
            function () use ($overallKey, $ttl, $callback) {
                Cache::put($overallKey, $callback($this->getAll($overallKey)), $this->ttlInSeconds($ttl));
            }
        );
    }

    protected function supportsLocks(): bool {
        return Cache::getStore() instanceof LockProvider;
    }

    protected function lockKey(string $overallKey): string {
        return 'progressable:lock:'.sha1($overallKey);
    }

    protected function ttlInSeconds(int $ttl): int {
        return max(0, $ttl) * 60;
    }
}
