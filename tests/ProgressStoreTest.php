<?php

namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Contracts\ProgressStore;
use Verseles\Progressable\Progressable;
use Verseles\Progressable\Stores\CacheProgressStore;

class ProgressStoreTest extends TestCase {
    public function test_stable_local_keys_update_independently_without_replacing_each_other(): void {
        $store = new InMemoryProgressStore;
        $uniqueName = uniqid('routine-run:', true);

        $moduleA = $this->makeProgressable($store);
        $moduleA->setOverallUniqueName($uniqueName);
        $moduleA->setLocalKey('module:A');
        $moduleA->setStatusMessage('uploading');
        $moduleA->setMetadata(['module' => 'A']);
        $moduleA->setLocalProgress(30);

        $moduleB = $this->makeProgressable($store);
        $moduleB->setOverallUniqueName($uniqueName);
        $moduleB->setLocalKey('module:B');
        $moduleB->setStatusMessage('compressing');
        $moduleB->setMetadata(['module' => 'B']);
        $moduleB->setLocalProgress(80);

        $progressData = $moduleA->getOverallProgressData();

        $this->assertCount(2, $progressData);
        $this->assertEquals(30, $progressData['module:A']['progress']);
        $this->assertEquals('uploading', $progressData['module:A']['message']);
        $this->assertEquals(['module' => 'A'], $progressData['module:A']['metadata']);
        $this->assertEquals(80, $progressData['module:B']['progress']);
        $this->assertEquals('compressing', $progressData['module:B']['message']);
        $this->assertEquals(['module' => 'B'], $progressData['module:B']['metadata']);
        $this->assertEquals(55, $moduleA->getOverallProgress());
        $this->assertFalse($moduleA->isOverallComplete());

        $moduleA->setLocalProgress(100);
        $moduleB->setLocalProgress(100);

        $this->assertTrue($moduleA->isOverallComplete());
    }

    public function test_remove_local_and_reset_overall_use_progress_store_operations(): void {
        $store = new InMemoryProgressStore;
        $uniqueName = uniqid('routine-run:', true);

        $moduleA = $this->makeProgressable($store);
        $moduleA->setOverallUniqueName($uniqueName);
        $moduleA->setLocalKey('module:A');
        $moduleA->setLocalProgress(40);

        $moduleB = $this->makeProgressable($store);
        $moduleB->setOverallUniqueName($uniqueName);
        $moduleB->setLocalKey('module:B');
        $moduleB->setLocalProgress(80);

        $moduleA->removeLocalFromOverall();

        $progressData = $moduleB->getOverallProgressData();

        $this->assertArrayNotHasKey('module:A', $progressData);
        $this->assertArrayHasKey('module:B', $progressData);
        $this->assertEquals(80, $moduleB->getOverallProgress());

        $moduleB->resetOverallProgress();

        $this->assertSame([], $moduleA->getOverallProgressData());
        $this->assertEquals(0, $moduleA->getOverallProgress());
    }

    public function test_cache_progress_store_preserves_sibling_entries(): void {
        $store = new CacheProgressStore;
        $overallKey = 'progressable_store_test_'.uniqid('', true);

        $store->putLocal($overallKey, 'module:A', ['progress' => 30], 1);
        $store->putLocal($overallKey, 'module:B', ['progress' => 80], 1);

        $progressData = $store->getAll($overallKey);

        $this->assertEquals(30, $progressData['module:A']['progress']);
        $this->assertEquals(80, $progressData['module:B']['progress']);

        $store->removeLocal($overallKey, 'module:A', 1);

        $progressData = $store->getAll($overallKey);

        $this->assertArrayNotHasKey('module:A', $progressData);
        $this->assertArrayHasKey('module:B', $progressData);

        $store->resetOverall($overallKey);

        $this->assertSame([], $store->getAll($overallKey));
    }

    private function makeProgressable(ProgressStore $store): object {
        return new class($store) {
            use Progressable;

            public function __construct(private ProgressStore $store) {}

            protected function getProgressStore(): ProgressStore {
                return $this->store;
            }
        };
    }
}

class InMemoryProgressStore implements ProgressStore {
    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    public array $items = [];

    public function putLocal(string $overallKey, string $localKey, array $data, int $ttl): void {
        $this->items[$overallKey][$localKey] = $data;
    }

    public function getAll(string $overallKey): array {
        return $this->items[$overallKey] ?? [];
    }

    public function renameLocal(string $overallKey, string $currentLocalKey, string $newLocalKey, int $ttl): void {
        if (! isset($this->items[$overallKey][$currentLocalKey])) {
            return;
        }

        if (! isset($this->items[$overallKey][$newLocalKey])) {
            $this->items[$overallKey][$newLocalKey] = $this->items[$overallKey][$currentLocalKey];
        }

        unset($this->items[$overallKey][$currentLocalKey]);
    }

    public function removeLocal(string $overallKey, string $localKey, int $ttl): void {
        unset($this->items[$overallKey][$localKey]);
    }

    public function resetOverall(string $overallKey): void {
        unset($this->items[$overallKey]);
    }
}
