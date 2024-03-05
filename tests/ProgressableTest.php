<?php

namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Exceptions\UniqueNameNotSetException;
use Verseles\Progressable\Progressable;

class ProgressableTest extends TestCase
{
  use Progressable;

  protected function getPackageProviders($app)
  {
    return ['Verseles\Progressable\ProgressableServiceProvider'];
  }

  public function testSetOverallUniqueName()
  {
    $this->setOverallUniqueName('test');
    $this->assertEquals('test', $this->getOverallUniqueName());
  }

  public function testUpdateLocalProgress()
  {
    $this->setOverallUniqueName('test');
    $this->updateLocalProgress(50);
    $this->assertEquals(50, $this->getLocalProgress());
  }

  public function testUpdateLocalProgressBounds()
  {
    $this->setOverallUniqueName('test');
    $this->updateLocalProgress(-10);
    $this->assertEquals(0, $this->getLocalProgress());
    $this->updateLocalProgress(120);
    $this->assertEquals(100, $this->getLocalProgress());
  }

  public function testGetOverallProgress()
  {
    $uniqueName = 'test';
    $this->setOverallUniqueName($uniqueName);
    $this->updateLocalProgress(25);

    $obj2 = new class {
      use Progressable;
    };
    $obj2->setOverallUniqueName($uniqueName);
    $obj2->updateLocalProgress(75);

    $this->assertEquals(50, $this->getOverallProgress());
  }

  public function testGetOverallProgressData()
  {
    $this->setOverallUniqueName('test');
    $this->updateLocalProgress(50);

    $progressData = $this->getOverallProgressData();
    $this->assertArrayHasKey($this->getLocalKey(), $progressData);
    $this->assertEquals(50, $progressData[$this->getLocalKey()]['progress']);
  }

  public function testUpdateLocalProgressWithoutUniqueName()
  {
    $this->expectException(UniqueNameNotSetException::class);
    $this->updateLocalProgress(50);
  }

  public function testSetCustomSaveData()
  {
    $saveCallback = function ($key, $data, $ttl) {
      // Custom save logic
    };

    $this->setCustomSaveData($saveCallback);
    $this->assertSame($saveCallback, $this->customSaveData);
  }

  public function testSetCustomGetData()
  {
    $getCallback = function ($key) {
      // Custom get logic
    };

    $this->setCustomGetData($getCallback);
    $this->assertSame($getCallback, $this->customGetData);
  }

  public function testSetTTL()
  {
    $this->setTTL(60);
    $this->assertEquals(60, $this->getTTL());
  }

  public function testSetPrefixStorageKey()
  {
    $this->setPrefixStorageKey('custom_prefix');
    $this->assertEquals('custom_prefix', $this->getPrefixStorageKey());
  }

  public function testProgressWithoutLaravel()
  {
    $overallUniqueName = 'test-without-laravel';
    $my_super_storage = [];

    $saveCallback = function ($key, $data, $ttl) use (&$my_super_storage) {
      $my_super_storage[$key] = $data;
    };

    $getCallback = function ($key) use (&$my_super_storage) {
      return $my_super_storage[$key] ?? [];
    };

    $obj1 = new class {
      use Progressable;
    };
    $obj1
      ->setCustomSaveData($saveCallback)
      ->setCustomGetData($getCallback)
      ->setOverallUniqueName($overallUniqueName)
      ->updateLocalProgress(25);

    $obj2 = new class {
      use Progressable;
    };
    $obj2
      ->setCustomSaveData($saveCallback)
      ->setCustomGetData($getCallback)
      ->setOverallUniqueName($overallUniqueName)
      ->updateLocalProgress(75);

    $this->assertEquals(25, $obj1->getLocalProgress());
    $this->assertEquals(75, $obj2->getLocalProgress());
    $this->assertEquals(50, $obj1->getOverallProgress());
    $this->assertEquals(50, $obj2->getOverallProgress());
  }
}
