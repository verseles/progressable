<?php
/**
 * Progressable/FullProgress.php
 */

namespace Verseles\Progressable;

use Illuminate\Support\Facades\Cache;

class FullProgress
{
  /**
   * The unique name of the overall progress.
   *
   * @var string
   */
  protected string $uniqueName;

  /**
   * The default cache time-to-live in minutes.
   *
   * @var int
   */
  protected int $ttl = 60;

  /**
   * Create a new instance of FullProgress.
   *
   * @param string $uniqueName
   */
  public function __construct(string $uniqueName)
  {
    $this->uniqueName = $uniqueName;
  }

  /**
   * Get the overall progress for the unique name.
   *
   * @return int
   */
  public function getProgress(): int
  {
    $progressData = Cache::get($this->getCacheKey(), []);

    $totalProgress = array_sum(array_column($progressData, 'progress'));
    $totalCount = count($progressData);

    if ($totalCount === 0) {
      return 0;
    }

    return round($totalProgress / $totalCount);
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
   * Get a singleton instance of FullProgress.
   *
   * @param string $uniqueName
   * @return static
   */
  public static function make(string $uniqueName): static
  {
    return app()->make(static::class, ['uniqueName' => $uniqueName]);
  }
}
