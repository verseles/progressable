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
    $totalCount = count($progressData);

    if ($totalCount === 0) {
      return 0;
    }

    return round($totalProgress / $totalCount, $precision);
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
   * Update the progress value for this instance.
   *
   * @param float $progress
   * @return $this
   * @throws UniqueNameNotSetException
   */
  public function updateLocalProgress(float $progress): static
  {
    $this->progress = max(0, min(100, $progress));
    $this->updateProgressData();

    return $this;
  }

  /**
   * Update the progress data in storage.
   *
   * @return void
   */
  protected function updateProgressData(): void
  {
    $progressData = $this->getOverallProgressData();

    $progressData[$this->getLocalKey()] = [
      "progress" => $this->progress,
    ];

    $this->saveOverallProgressData($progressData);
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
   * @return void
   */
  public function saveOverallProgressData(array $progressData): void
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
   * Get the overall unique name.
   *
   * @throws UniqueNameNotSetException If the overall unique name is not set
   * @return string the overall unique name
   */
  public function getOverallUniqueName()
  {
    if (!isset($this->overallUniqueName)) {
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

  /**
   * Get the progress value for this instance.
   *
   * @param int $precision The precision of the local progress
   * @return float
   */
  public function getLocalProgress(int $precision = 2): float
  {
    return round($this->progress, $precision);
  }
}
