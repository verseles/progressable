<?php

namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Exceptions\UniqueNameAlreadySetException;
use Verseles\Progressable\Exceptions\UniqueNameNotSetException;
use Verseles\Progressable\Progressable;

class ProgressableTest extends TestCase {
    use Progressable;

    /**
     * Unique test identifier to isolate tests.
     */
    private string $testId;

    protected function setUp(): void {
        parent::setUp();

        // Generate unique ID for each test to ensure isolation
        $this->testId = uniqid('test_', true);

        // Reset trait properties to default state
        $this->progress = 0;
        $this->customSaveData = null;
        $this->customGetData = null;
        $this->customTTL = null;
        $this->localKey = null;
        $this->customPrefixStorageKey = null;

        // Unset overallUniqueName to simulate fresh state
        unset($this->overallUniqueName);
    }

    public function test_set_overall_unique_name(): void {
        $uniqueName = 'test_unique_'.$this->testId;
        $this->setOverallUniqueName($uniqueName);
        $this->assertEquals($uniqueName, $this->getOverallUniqueName());
    }

    public function test_update_local_progress(): void {
        $this->setOverallUniqueName('test_progress_'.$this->testId);
        $this->setLocalProgress(50);
        $this->assertEquals(50, $this->getLocalProgress());
    }

    public function test_update_local_progress_bounds(): void {
        $this->setOverallUniqueName('test_bounds_'.$this->testId);
        $this->setLocalProgress(-10);
        $this->assertEquals(0, $this->getLocalProgress());
        $this->setLocalProgress(120);
        $this->assertEquals(100, $this->getLocalProgress());
    }

    public function test_get_overall_progress(): void {
        $uniqueName = 'test_overall_'.$this->testId;
        $this->setOverallUniqueName($uniqueName);
        $this->setLocalProgress(25);

        $obj2 = new class {
            use Progressable;
        };
        $obj2->setOverallUniqueName($uniqueName);
        $obj2->setLocalProgress(75);

        $this->assertEquals(50, $this->getOverallProgress());
    }

    public function test_get_overall_progress_data(): void {
        $this->setOverallUniqueName('test_data_'.$this->testId);
        $this->setLocalProgress(50);

        $progressData = $this->getOverallProgressData();
        $this->assertArrayHasKey($this->getLocalKey(), $progressData);
        $this->assertEquals(50, $progressData[$this->getLocalKey()]['progress']);
    }

    public function test_update_local_progress_without_unique_name(): void {
        $this->expectException(UniqueNameNotSetException::class);
        $this->setLocalProgress(50);
    }

    public function test_reset_local_progress(): void {
        $this->setOverallUniqueName('test_reset_local_'.$this->testId);
        $this->setLocalProgress(50);
        $this->resetLocalProgress();
        $this->assertEquals(0, $this->getLocalProgress());
    }

    public function test_reset_overall_progress(): void {
        $uniqueName = 'test_reset_overall_'.$this->testId;
        $this->setOverallUniqueName($uniqueName);
        $this->setLocalProgress(50);

        $obj2 = new class {
            use Progressable;
        };
        $obj2->setOverallUniqueName($uniqueName);
        $obj2->setLocalProgress(75);

        $this->assertNotEquals(0, $this->getOverallProgress());
        $this->resetOverallProgress();
        $this->assertEquals(0, $this->getOverallProgress());
    }

    public function test_set_local_key(): void {
        $this->setOverallUniqueName('test_local_key_'.$this->testId);
        $this->setLocalKey('my_custom_key');
        $progressData = $this->getOverallProgressData();
        $this->assertArrayHasKey('my_custom_key', $progressData);
    }

    public function test_set_local_key_preserves_progress_data(): void {
        $this->setOverallUniqueName('test_local_key_rename_'.$this->testId);
        $this->setLocalKey('original_key');
        $this->setLocalProgress(75);

        // Verify original key has the progress
        $progressData = $this->getOverallProgressData();
        $this->assertArrayHasKey('original_key', $progressData);
        $this->assertEquals(75, $progressData['original_key']['progress']);

        // Rename the key
        $this->setLocalKey('renamed_key');

        // Verify data was preserved under new key and old key is removed
        $progressData = $this->getOverallProgressData();
        $this->assertArrayHasKey('renamed_key', $progressData);
        $this->assertArrayNotHasKey('original_key', $progressData);
        $this->assertEquals(75, $progressData['renamed_key']['progress']);
    }

    public function test_set_prefix_storage_key(): void {
        $this->setPrefixStorageKey('custom_prefix');
        $this->setOverallUniqueName('test_prefix_'.$this->testId);
        $this->assertEquals('custom_prefix_test_prefix_'.$this->testId, $this->getStorageKeyName());
    }

    public function test_set_prefix_storage_key_after_unique_name_throws_exception(): void {
        $this->setOverallUniqueName('test_prefix_exception_'.$this->testId);
        $this->expectException(UniqueNameAlreadySetException::class);
        $this->setPrefixStorageKey('custom_prefix');
    }

    public function test_set_ttl(): void {
        $this->setOverallUniqueName('test_ttl_'.$this->testId);
        $this->setLocalProgress(50);
        $this->setTTL(60); // 1 hour

        $ttl = $this->getTTL();
        $this->assertEquals(60, $ttl);
    }

    public function test_custom_save_and_get_data(): void {
        $storage = [];

        $saveCallback = function ($key, $data, $ttl) use (&$storage) {
            $storage[$key] = $data;
        };

        $getCallback = function ($key) use (&$storage) {
            return $storage[$key] ?? [];
        };

        $this->setCustomSaveData($saveCallback);
        $this->setCustomGetData($getCallback);

        $this->setOverallUniqueName('custom_test_'.$this->testId);
        $this->setLocalProgress(50);

        $progressData = $this->getOverallProgressData();
        $this->assertArrayHasKey($this->getLocalKey(), $progressData);
        $this->assertEquals(50, $progressData[$this->getLocalKey()]['progress']);
    }

    public function test_make_sure_local_is_part_of_the_calc(): void {
        $uniqueName = 'test_auto_register_'.$this->testId;

        // When setting unique name, instance should be automatically registered
        $this->setOverallUniqueName($uniqueName);

        $progressData = $this->getOverallProgressData();
        $this->assertArrayHasKey($this->getLocalKey(), $progressData);
        $this->assertEquals(0, $progressData[$this->getLocalKey()]['progress']);
    }

    public function test_get_overall_progress_with_empty_data(): void {
        $this->setOverallUniqueName('test_empty_'.$this->testId);
        $this->resetOverallProgress();

        // Should return 0 when no progress data exists
        $this->assertEquals(0, $this->getOverallProgress());
    }

    public function test_progress_precision(): void {
        $this->setOverallUniqueName('test_precision_'.$this->testId);
        $this->setLocalProgress(33.3333);

        $this->assertEquals(33.33, $this->getLocalProgress(2));
        $this->assertEquals(33.333, $this->getLocalProgress(3));
        $this->assertEquals(33, $this->getLocalProgress(0));
    }
}
