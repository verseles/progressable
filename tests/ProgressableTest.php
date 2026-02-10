<?php

namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Exceptions\TotalStepsNotSetException;
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
        $this->customPrecision = null;
        $this->metadata = [];
        $this->statusMessage = null;
        $this->onProgressChange = null;
        $this->onComplete = null;

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

    public function test_increment_local_progress(): void {
        $this->setOverallUniqueName('test_increment_'.$this->testId);
        $this->setLocalProgress(10);

        $this->incrementLocalProgress(5);
        $this->assertEquals(15, $this->getLocalProgress());

        $this->incrementLocalProgress(10);
        $this->assertEquals(25, $this->getLocalProgress());
    }

    public function test_increment_local_progress_with_negative(): void {
        $this->setOverallUniqueName('test_decrement_'.$this->testId);
        $this->setLocalProgress(50);

        $this->incrementLocalProgress(-10);
        $this->assertEquals(40, $this->getLocalProgress());
    }

    public function test_increment_local_progress_respects_bounds(): void {
        $this->setOverallUniqueName('test_increment_bounds_'.$this->testId);
        $this->setLocalProgress(95);

        $this->incrementLocalProgress(10);
        $this->assertEquals(100, $this->getLocalProgress());

        $this->setLocalProgress(5);
        $this->incrementLocalProgress(-10);
        $this->assertEquals(0, $this->getLocalProgress());
    }

    public function test_is_complete(): void {
        $this->setOverallUniqueName('test_is_complete_'.$this->testId);

        $this->setLocalProgress(50);
        $this->assertFalse($this->isComplete());

        $this->setLocalProgress(99.99);
        $this->assertFalse($this->isComplete());

        $this->setLocalProgress(100);
        $this->assertTrue($this->isComplete());
    }

    public function test_is_overall_complete(): void {
        $uniqueName = 'test_is_overall_complete_'.$this->testId;
        $this->setOverallUniqueName($uniqueName);
        $this->setLocalProgress(100);

        $obj2 = new class {
            use Progressable;
        };
        $obj2->setOverallUniqueName($uniqueName);
        $obj2->setLocalProgress(50);

        $this->assertFalse($this->isOverallComplete());

        $obj2->setLocalProgress(100);
        $this->assertTrue($this->isOverallComplete());
    }

    public function test_remove_local_from_overall(): void {
        $uniqueName = 'test_remove_local_'.$this->testId;
        $this->setOverallUniqueName($uniqueName);
        $this->setLocalKey('instance_1');
        $this->setLocalProgress(50);

        $obj2 = new class {
            use Progressable;
        };
        $obj2->setOverallUniqueName($uniqueName);
        $obj2->setLocalKey('instance_2');
        $obj2->setLocalProgress(100);

        // Both instances contribute to overall progress
        $this->assertEquals(75, $this->getOverallProgress());

        // Remove first instance
        $this->removeLocalFromOverall();

        // Only second instance remains
        $progressData = $this->getOverallProgressData();
        $this->assertArrayNotHasKey('instance_1', $progressData);
        $this->assertArrayHasKey('instance_2', $progressData);
        $this->assertEquals(100, $this->getOverallProgress());

        // Local progress should be reset to 0
        $this->assertEquals(0, $this->progress);
    }

    public function test_set_precision(): void {
        $this->setOverallUniqueName('test_set_precision_'.$this->testId);
        $this->setLocalProgress(33.33333);

        // Default precision
        $this->assertEquals(33.33, $this->getLocalProgress());

        // Custom precision
        $this->setPrecision(4);
        $this->assertEquals(4, $this->getPrecision());
        $this->assertEquals(33.3333, $this->getLocalProgress());

        // Precision 0
        $this->setPrecision(0);
        $this->assertEquals(33, $this->getLocalProgress());
    }

    public function test_get_local_progress_uses_default_precision(): void {
        $this->setOverallUniqueName('test_default_precision_'.$this->testId);
        $this->setLocalProgress(33.33333);

        // Without parameter, should use default precision (2)
        $this->assertEquals(33.33, $this->getLocalProgress());

        // With explicit null, should also use default
        $this->assertEquals(33.33, $this->getLocalProgress(null));
    }

    public function test_get_overall_progress_uses_default_precision(): void {
        $this->setOverallUniqueName('test_overall_default_precision_'.$this->testId);
        $this->setLocalProgress(33.33333);

        // Without parameter, should use default precision (2)
        $this->assertEquals(33.33, $this->getOverallProgress());

        // With explicit null, should also use default
        $this->assertEquals(33.33, $this->getOverallProgress(null));
    }

    public function test_set_status_message(): void {
        $this->setOverallUniqueName('test_status_message_'.$this->testId);
        $this->setLocalProgress(50);

        $this->setStatusMessage('Processing files...');
        $this->assertEquals('Processing files...', $this->getStatusMessage());

        // Check it's stored in progress data
        $progressData = $this->getOverallProgressData();
        $this->assertEquals('Processing files...', $progressData[$this->getLocalKey()]['message']);
    }

    public function test_set_metadata(): void {
        $this->setOverallUniqueName('test_metadata_'.$this->testId);
        $this->setLocalProgress(50);

        $metadata = ['file' => 'test.txt', 'size' => 1024];
        $this->setMetadata($metadata);

        $this->assertEquals($metadata, $this->getMetadata());
        $this->assertEquals('test.txt', $this->getMetadataValue('file'));
        $this->assertEquals(1024, $this->getMetadataValue('size'));
        $this->assertNull($this->getMetadataValue('nonexistent'));
        $this->assertEquals('default', $this->getMetadataValue('nonexistent', 'default'));

        // Check it's stored in progress data
        $progressData = $this->getOverallProgressData();
        $this->assertEquals($metadata, $progressData[$this->getLocalKey()]['metadata']);
    }

    public function test_add_metadata(): void {
        $this->setOverallUniqueName('test_add_metadata_'.$this->testId);
        $this->setLocalProgress(50);

        $this->addMetadata('step', 1);
        $this->addMetadata('total', 10);

        $this->assertEquals(1, $this->getMetadataValue('step'));
        $this->assertEquals(10, $this->getMetadataValue('total'));

        // Update existing key
        $this->addMetadata('step', 2);
        $this->assertEquals(2, $this->getMetadataValue('step'));
    }

    public function test_on_progress_change_callback(): void {
        $this->setOverallUniqueName('test_callback_change_'.$this->testId);

        $callbackCalled = false;
        $capturedNew = null;
        $capturedOld = null;

        $this->onProgressChange(function ($new, $old, $instance) use (&$callbackCalled, &$capturedNew, &$capturedOld) {
            $callbackCalled = true;
            $capturedNew = $new;
            $capturedOld = $old;
        });

        $this->setLocalProgress(50);

        $this->assertTrue($callbackCalled);
        $this->assertEquals(50, $capturedNew);
        $this->assertEquals(0, $capturedOld);
    }

    public function test_on_progress_change_not_called_when_same_value(): void {
        $this->setOverallUniqueName('test_callback_same_'.$this->testId);
        $this->setLocalProgress(50);

        $callCount = 0;
        $this->onProgressChange(function () use (&$callCount) {
            $callCount++;
        });

        // Setting same value should not trigger callback
        $this->setLocalProgress(50);
        $this->assertEquals(0, $callCount);

        // Setting different value should trigger callback
        $this->setLocalProgress(51);
        $this->assertEquals(1, $callCount);
    }

    public function test_on_complete_callback(): void {
        $this->setOverallUniqueName('test_callback_complete_'.$this->testId);

        $completeCalled = false;
        $this->onComplete(function ($instance) use (&$completeCalled) {
            $completeCalled = true;
        });

        $this->setLocalProgress(50);
        $this->assertFalse($completeCalled);

        $this->setLocalProgress(100);
        $this->assertTrue($completeCalled);
    }

    public function test_on_complete_callback_only_fires_once(): void {
        $this->setOverallUniqueName('test_callback_complete_once_'.$this->testId);

        $callCount = 0;
        $this->onComplete(function () use (&$callCount) {
            $callCount++;
        });

        $this->setLocalProgress(100);
        $this->assertEquals(1, $callCount);

        // Setting to 100 again should not trigger (already at 100)
        $this->setLocalProgress(100);
        $this->assertEquals(1, $callCount);

        // Going down and back up should trigger again
        $this->setLocalProgress(50);
        $this->setLocalProgress(100);
        $this->assertEquals(2, $callCount);
    }

    public function test_metadata_and_message_stored_together(): void {
        $this->setOverallUniqueName('test_metadata_message_'.$this->testId);

        $this->setStatusMessage('Processing...');
        $this->setMetadata(['step' => 1]);
        $this->setLocalProgress(50);

        $progressData = $this->getOverallProgressData();
        $localData = $progressData[$this->getLocalKey()];

        $this->assertEquals(50, $localData['progress']);
        $this->assertEquals('Processing...', $localData['message']);
        $this->assertEquals(['step' => 1], $localData['metadata']);
    }

    public function test_merge_metadata(): void {
        $this->setOverallUniqueName('test_merge_metadata_'.$this->testId);
        $this->setMetadata(['key1' => 'value1']);

        $this->mergeMetadata(['key2' => 'value2', 'key1' => 'new_value1']);

        $this->assertEquals('new_value1', $this->getMetadataValue('key1'));
        $this->assertEquals('value2', $this->getMetadataValue('key2'));

        // Verify storage
        $progressData = $this->getOverallProgressData();
        $storedMetadata = $progressData[$this->getLocalKey()]['metadata'];
        $this->assertEquals('new_value1', $storedMetadata['key1']);
        $this->assertEquals('value2', $storedMetadata['key2']);
    }

    public function test_set_total_steps(): void {
        $this->setTotalSteps(10);
        $this->assertEquals(10, $this->getTotalSteps());
        $this->assertEquals(0, $this->getCurrentStep());
    }

    public function test_advance(): void {
        $this->setOverallUniqueName('test_advance_'.$this->testId);
        $this->setTotalSteps(10);

        $this->advance();
        $this->assertEquals(1, $this->getCurrentStep());
        $this->assertEquals(10, $this->getLocalProgress());

        $this->advance(2);
        $this->assertEquals(3, $this->getCurrentStep());
        $this->assertEquals(30, $this->getLocalProgress());
    }

    public function test_advance_without_total_steps_throws_exception(): void {
        $this->setOverallUniqueName('test_advance_exception_'.$this->testId);
        $this->expectException(TotalStepsNotSetException::class);
        $this->advance();
    }

    public function test_step_info_in_progress_data(): void {
        $this->setOverallUniqueName('test_step_info_'.$this->testId);
        $this->setTotalSteps(5);
        $this->advance(1);

        $progressData = $this->getOverallProgressData();
        $localData = $progressData[$this->getLocalKey()];

        $this->assertArrayHasKey('current_step', $localData);
        $this->assertArrayHasKey('total_steps', $localData);
        $this->assertEquals(1, $localData['current_step']);
        $this->assertEquals(5, $localData['total_steps']);
        $this->assertEquals(20, $localData['progress']);
    }
}
