<?php

namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Exceptions\UniqueNameNotSetException;
use Verseles\Progressable\Progressable;

class ProgressableTest extends TestCase
{
  use Progressable;

  public function testSetOverallUniqueName()
  {
    $this->setOverallUniqueName('test');
    $this->assertEquals('test', $this->getOverallUniqueName());
  }

  public function testUpdateLocalProgress()
  {
    $this->setOverallUniqueName('test');
    $this->setLocalProgress(50);
    $this->assertEquals(50, $this->getLocalProgress());
  }

  public function testUpdateLocalProgressBounds()
  {
    $this->setOverallUniqueName('test');
    $this->setLocalProgress(-10);
    $this->assertEquals(0, $this->getLocalProgress());
    $this->setLocalProgress(120);
    $this->assertEquals(100, $this->getLocalProgress());
  }

  public function testGetOverallProgress()
  {
    $uniqueName = 'test';
    $this->setOverallUniqueName($uniqueName);
    $this->setLocalProgress(25);

    $obj2 = new class {
      use Progressable;
    };
    $obj2->setOverallUniqueName($uniqueName);
    $obj2->setLocalProgress(75);

    $this->assertEquals(50, $this->getOverallProgress());
  }

  public function testGetOverallProgressData()
  {
    $this->setOverallUniqueName('test');
    $this->setLocalProgress(50);

    $progressData = $this->getOverallProgressData();
    $this->assertArrayHasKey($this->getLocalKey(), $progressData);
    $this->assertEquals(50, $progressData[$this->getLocalKey()]['progress']);
  }

  public function testUpdateLocalProgressWithoutUniqueName()
  {
    $this->overallUniqueName = '';
    $this->expectException(UniqueNameNotSetException::class);
    $this->setLocalProgress(50);
  }

  public function testResetLocalProgress()
  {
    $this->setOverallUniqueName('test');
    $this->setLocalProgress(50);
    $this->resetLocalProgress();
    $this->assertEquals(0, $this->getLocalProgress());
  }

  public function testResetOverallProgress()
  {
    $this->setOverallUniqueName('test');
    $this->setLocalProgress(50);

    $obj2 = new class {
      use Progressable;
    };
    $obj2->setOverallUniqueName('test');
    $obj2->setLocalProgress(75);

    $this->assertNotEquals(0, $this->getOverallProgress());
    $this->resetOverallProgress();
    $this->assertEquals(0, $this->getOverallProgress());
  }

  public function testSetLocalKey()
  {
    $this->setOverallUniqueName('test');
    $this->setLocalKey('my_custom_key');
    $progressData = $this->getOverallProgressData();
    $this->assertArrayHasKey('my_custom_key', $progressData);
  }

  public function testSetPrefixStorageKey()
  {
    $this->setPrefixStorageKey('custom_prefix');
    $this->setOverallUniqueName('test');
    $this->assertEquals('custom_prefix_test', $this->getStorageKeyName());

  }

  public function testSetTTL()
  {
    $this->setOverallUniqueName('test');
    $this->setLocalProgress(50);
    $this->setTTL(60); // 1 heure

    $ttl = $this->getTTL();
    $this->assertEquals(60, $ttl);
  }

  public function testCustomSaveAndGetData()
  {
    $storage = [];

    $saveCallback = function ($key, $data, $ttl) use (&$storage) {
      $storage[$key] = $data;
    };

    $getCallback = function ($key) use (&$storage) {
      return $storage[$key] ?? [];
    };

    $this->setCustomSaveData($saveCallback);
    $this->setCustomGetData($getCallback);

    $this->setOverallUniqueName('custom_test');
    $this->setLocalProgress(50);

    $progressData = $this->getOverallProgressData();
    $this->assertArrayHasKey($this->getLocalKey(), $progressData);
    $this->assertEquals(50, $progressData[$this->getLocalKey()]['progress']);
  }
}
