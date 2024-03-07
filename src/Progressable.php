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
  protected string $overallUniqueName;

  /**
   * The progress value for this instance.
   *
   * @var float
   */
  protected float $progress = 0;

  /**
   * The callback function for saving cache data.
   *
   * @var callable|null
   */
  protected $customSaveData = null;

  /**
   * The callback function for retrieving cache data.
   *
   * @var callable|null
   */
  protected $customGetData = null;

  /**
   * The default cache time-to-live in minutes.
   *
   * @var int
   */
  protected int $defaultTTL = 1140;

  /**
   * If you want to override the default cache time-to-live in minutes,
   *
   * @var null|int
   */
  protected $customTTL = null;

  protected string $defaultPrefixStorageKey = "progressable";

  protected $customPrefixStorageKey = null;

  /**
   * Set the callback function for saving cache data.
   *
   * @param callable $callback
   * @return $this
   */
  public function setCustomSaveData(callable $callback): static
  {
    $this->customSaveData = $callback;
    return $this;
  }

  /**
   * Set the callback function for retrieving cache data.
   *
   * @param callable $callback
   * @return $this
   */
  public function setCustomGetData(callable $callback): static
  {
    $this->customGetData = $callback;
    return $this;
  }

  /**
   * Get the overall progress for the unique name.
   *
   * @param int $precision The precision of the overall progress
   * @return float
   */
  public function getOverallProgress(int $precision = 2): float
  {
    $progressData = $this->getOverallProgressData();

    $totalProgress = array_sum(array_column($progressData, "progress"));
    $totalCount    = count($progressData);

    if ($totalCount === 0) {
      return 0;
    }

    return round($totalProgress / $totalCount, $precision, PHP_ROUND_HALF_ODD);
  }

  /**
   * Get the overall progress data from the storage.
   *
   * @return array
   */
  public function getOverallProgressData(): array
  {
    if ($this->customGetData !== null) {
      return call_user_func($this->customGetData, $this->getStorageKeyName());
    }

    return Cache::get($this->getStorageKeyName(), []);
  }

  /**
   * Get the cache key for the unique name.
   *
   * @return string
   */
  protected function getStorageKeyName(): string
  {
    return $this->getPrefixStorageKey() . $this->getOverallUniqueName();
  }

  /**
   * Retrieve the prefix storage key for the PHP function.
   *
   * @return string
   */
  protected function getPrefixStorageKey(): string
  {
    return $this->customPrefixStorageKey ?? config("progressable.prefix", $this->defaultPrefixStorageKey);
  }

  /**
   * Get the overall unique name.
   *
   * @return string the overall unique name
   * @throws UniqueNameNotSetException If the overall unique name is not set
   */
  public function getOverallUniqueName()
  {
    if (!isset($this->overallUniqueName) || empty($this->overallUniqueName)) {
      throw new UniqueNameNotSetException();
    }

    return $this->overallUniqueName;
  }

  /**
   * Set the unique name of the overall progress.
   *
   * @param string $overallUniqueName
   * @return $this
   */
  public function setOverallUniqueName(string $overallUniqueName): static
  {
    $this->overallUniqueName = $overallUniqueName;

    if ($this->getLocalProgress(0) == 0) {
      // This make sure that the class who called this method will be part of the overall progress calculation
      $this->resetLocalProgress();
    }

    return $this;
  }

  /**
   * Get the progress value for this instance
   *
   * @param int $precision The precision of the local progress
   * @return float
   */
  public function getLocalProgress(int $precision = 2): float
  {
    return round($this->progress, $precision, PHP_ROUND_HALF_ODD);
  }

  /**
   * @return static
   * @throws UniqueNameNotSetException
   */
  public function resetLocalProgress(): static
  {
    return $this->setLocalProgress(0);
  }

  /**
   * Update the progress value for this instance.
   *
   * @param float $progress
   * @return $this
   * @throws UniqueNameNotSetException
   */
  public function setLocalProgress(float $progress): static
  {
    $this->progress = max(0, min(100, $progress));

    return $this->updateLocalProgressData($this->progress);
  }

  /**
   * Update the progress data in storage.
   *
   * @return static
   */
  protected function updateLocalProgressData(float $progress): static
  {
    $progressData = $this->getOverallProgressData();

    $progressData[$this->getLocalKey()] = [
      "progress" => $progress,
    ];

    return $this->saveOverallProgressData($progressData);
  }

  /**
   * Get the cache identifier for this instance.
   *
   * @return string
   */
  protected function getLocalKey(): string
  {
    return get_class($this) . "@" . spl_object_hash($this);
  }

  /**
   * Save the overall progress data to the storage.
   *
   * @param array $progressData
   * @return static
   */
  protected function saveOverallProgressData(array $progressData): static
  {
    if ($this->customSaveData !== null) {
      call_user_func(
        $this->customSaveData,
        $this->getStorageKeyName(),
        $progressData,
        $this->getTTL()
      );
    } else {
      Cache::put($this->getStorageKeyName(), $progressData, $this->getTTL());
    }

    return $this;
  }

  /**
   * Get the storage time-to-live in minutes.
   *
   * @return int
   */
  public function getTTL(): int
  {
    return $this->customTTL ?? config("progressable.ttl", $this->defaultTTL);
  }

  /**
   * Reset the overall progress.
   *
   * @return static
   */
  public function resetOverallProgress(): static
  {
    return $this->saveOverallProgressData([]);
  }

  /**
   * Set the prefix storage key.
   *
   * @param string $prefixStorageKey The prefix storage key to set.
   * @return static
   */
  public function setPrefixStorageKey(string $prefixStorageKey): static
  {
    $this->customPrefixStorageKey = $prefixStorageKey;

    return $this;
  }

  /**
   * Set the time-to-live for the object.
   *
   * @param int $defaultTTL The time-to-live value to set
   * @return static
   */
  public function setTTL(int $TTL): static
  {
    $this->customTTL = $TTL;
    return $this;
  }
}
