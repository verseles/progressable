<?php
namespace Verseles\Progressable;

use Illuminate\Support\Facades\Cache;
use Verseles\Progressable\Exceptions\UniqueNameNotSetException;

trait Progressable
{
  /**
   * The unique name of the overall progress.
   *
   * @var string
   */
  protected string $uniqueName;

  /**
   * The progress value for this instance.
   *
   * @var int
   */
  protected int $progress = 0;

  /**
   * Set the unique name of the overall progress.
   *
   * @param string $uniqueName
   * @return void
   */
  public function setUniqueName(string $uniqueName): void
  {
    $this->uniqueName = $uniqueName;
  }

  /**
   * Update the progress value for this instance.
   *
   * @param int $progress
   * @return void
   * @throws UniqueNameNotSetException
   */
  public function updateProgress(int $progress): void
  {
    if (empty($this->uniqueName)) {
      throw new UniqueNameNotSetException();
    }

    $this->progress = max(0, min(100, $progress));
    $this->updateProgressData();
  }

  /**
   * Get the progress value for this instance.
   *
   * @return int
   */
  public function getProgress(): int
  {
    return $this->progress;
  }

  /**
   * Update the progress data in the cache.
   *
   * @return void
   */
  protected function updateProgressData(): void
  {
    $progressData = Cache::get($this->getCacheKey(), []);

    $progressData[$this->getCacheIdentifier()] = [
      'progress' => $this->progress,
    ];

    Cache::put($this->getCacheKey(), $progressData, $this->getTtl());
  }

  /**
   * Get the cache key for the unique name.
   *
   * @return string
   */
  protected function getCacheKey(): string
  {
    return 'vlp_' . $this->uniqueName;
  }

  /**
   * Get the cache identifier for this instance.
   *
   * @return string
   */
  protected function getCacheIdentifier(): string
  {
    return get_class($this) . '@' . spl_object_hash($this);
  }

  /**
   * Get the cache time-to-live in minutes.
   *
   * @return int
   */
  protected function getTtl(): int
  {
    return config('progressable.ttl', 1140);
  }
}
