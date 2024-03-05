<?php

namespace Verseles\Progressable\Tests;

use Orchestra\Testbench\TestCase;
use Verseles\Progressable\Facades\FullProgress;
use Verseles\Progressable\Progressable;

class ProgressableTest extends TestCase
{
  use Progressable;

  protected function getPackageProviders($app)
  {
    return ['Verseles\Progressable\ProgressableServiceProvider'];
  }

  public function testSetUniqueName()
  {
    $this->setUniqueName('test');
    $this->assertEquals('test', $this->uniqueName);
  }

  public function testUpdateProgress()
  {
    $this->setUniqueName('test');
    $this->updateProgress(50);
    $this->assertEquals(50, $this->getProgress());
  }

  public function testProgressBounds()
  {
    $this->setUniqueName('test');
    $this->updateProgress(-10);
    $this->assertEquals(0, $this->getProgress());

    $this->updateProgress(120);
    $this->assertEquals(100, $this->getProgress());
  }

  public function testOverallProgress()
  {
    $uniqueName = 'test';

    $fullProgress = FullProgress::make($uniqueName);

    $this->setUniqueName($uniqueName);
    $this->updateProgress(25);

    $obj2 = new class {
      use Progressable;
    };
    $obj2->setUniqueName($uniqueName);
    $obj2->updateProgress(75);

    $this->assertEquals(50, $fullProgress->getProgress());
  }
}
