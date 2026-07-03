<?php

namespace Verseles\Progressable\Tests;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Verseles\Progressable\Progressable;

class WithoutLaravelTest extends TestCase {
    public function test_can_use_progressable_without_laravel(): void {
        // Ensure no Laravel instance is bound, simulating usage purely outside Laravel.
        Container::setInstance(null);

        $storage = [];
        $saveCallback = function ($key, $data, $ttl) use (&$storage) {
            $storage[$key] = $data;
        };

        $getCallback = function ($key) use (&$storage) {
            return $storage[$key] ?? [];
        };

        $obj = new class {
            use Progressable;
        };

        $obj->setCustomSaveData($saveCallback)
            ->setCustomGetData($getCallback)
            ->setOverallUniqueName('my-task-without-laravel')
            ->resetOverallProgress()
            ->setLocalProgress(25);

        $this->assertEquals(25, $obj->getLocalProgress());
        $this->assertEquals(25, $obj->getOverallProgress());
        $this->assertEquals('progressable_my-task-without-laravel', array_key_first($storage));
    }
}
