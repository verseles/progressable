<?php

namespace Verseles\Progressable;

use Illuminate\Support\Facades\Cache;
use Verseles\Progressable\Exceptions\UniqueNameAlreadySetException;
use Verseles\Progressable\Exceptions\UniqueNameNotSetException;

trait Progressable {
    /**
     * The unique name of the overall progress.
     */
    protected string $overallUniqueName;

    /**
     * The progress value for this instance.
     */
    protected float $progress = 0;

    /**
     * The callback function for saving cache data.
     *
     * @var callable|null
     */
    protected mixed $customSaveData = null;

    /**
     * The callback function for retrieving cache data.
     *
     * @var callable|null
     */
    protected mixed $customGetData = null;

    /**
     * The default cache time-to-live in minutes.
     */
    protected int $defaultTTL = 1140;

    /**
     * If you want to override the default cache time-to-live in minutes,
     */
    protected ?int $customTTL = null;

    /**
     * The local key identifier for this instance.
     */
    protected ?string $localKey = null;

    /**
     * The default prefix for storage keys.
     */
    protected string $defaultPrefixStorageKey = 'progressable';

    /**
     * Custom prefix for storage keys.
     */
    protected ?string $customPrefixStorageKey = null;

    /**
     * Set the callback function for saving cache data.
     *
     * @return $this
     */
    public function setCustomSaveData(callable $callback): static {
        $this->customSaveData = $callback;

        return $this;
    }

    /**
     * Set the callback function for retrieving cache data.
     *
     * @return $this
     */
    public function setCustomGetData(callable $callback): static {
        $this->customGetData = $callback;

        return $this;
    }

    /**
     * Get the overall progress for the unique name.
     *
     * @param  int  $precision  The precision of the overall progress
     */
    public function getOverallProgress(int $precision = 2): float {
        $progressData = $this->getOverallProgressData();

        $totalProgress = array_sum(array_column($progressData, 'progress'));
        $totalCount = count($progressData);

        if ($totalCount === 0) {
            return 0;
        }

        return round($totalProgress / $totalCount, $precision, PHP_ROUND_HALF_ODD);
    }

    /**
     * Get the overall progress data from the storage.
     */
    public function getOverallProgressData(): array {
        if ($this->customGetData !== null) {
            return call_user_func($this->customGetData, $this->getStorageKeyName());
        }

        return Cache::get($this->getStorageKeyName(), []);
    }

    /**
     * Get the cache key for the unique name.
     */
    protected function getStorageKeyName(): string {
        return $this->getPrefixStorageKey().'_'.$this->getOverallUniqueName();
    }

    /**
     * Retrieve the prefix storage key for the PHP function.
     */
    protected function getPrefixStorageKey(): string {
        return $this->customPrefixStorageKey ?? config('progressable.prefix', $this->defaultPrefixStorageKey);
    }

    /**
     * Get the overall unique name.
     *
     * @return string The overall unique name
     *
     * @throws UniqueNameNotSetException If the overall unique name is not set
     */
    public function getOverallUniqueName(): string {
        if (! isset($this->overallUniqueName) || empty($this->overallUniqueName)) {
            throw new UniqueNameNotSetException;
        }

        return $this->overallUniqueName;
    }

    /**
     * Set the unique name of the overall progress.
     *
     * @return $this
     */
    public function setOverallUniqueName(string $overallUniqueName): static {
        $this->overallUniqueName = $overallUniqueName;

        $this->makeSureLocalIsPartOfTheCalc();

        return $this;
    }

    /**
     * @throws UniqueNameNotSetException
     */
    public function makeSureLocalIsPartOfTheCalc(): void {
        if ($this->getLocalProgress(0) == 0) {
            // This make sure that the class who called this method will be part of the overall progress calculation
            $this->resetLocalProgress();
        }
    }

    /**
     * Get the progress value for this instance
     *
     * @param  int  $precision  The precision of the local progress
     */
    public function getLocalProgress(int $precision = 2): float {
        return round($this->progress, $precision, PHP_ROUND_HALF_ODD);
    }

    /**
     * @throws UniqueNameNotSetException
     */
    public function resetLocalProgress(): static {
        return $this->setLocalProgress(0);
    }

    /**
     * Update the progress value for this instance.
     *
     * @return $this
     *
     * @throws UniqueNameNotSetException
     */
    public function setLocalProgress(float $progress): static {
        $this->progress = max(0, min(100, $progress));

        return $this->updateLocalProgressData($this->progress);
    }

    /**
     * Update the progress data in storage.
     */
    protected function updateLocalProgressData(float $progress): static {
        $progressData = $this->getOverallProgressData();

        $progressData[$this->getLocalKey()] = [
            'progress' => $progress,
        ];

        return $this->saveOverallProgressData($progressData);
    }

    /**
     * Get the cache identifier for this instance.
     */
    public function getLocalKey(): string {
        return $this->localKey ?? get_class($this).'@'.spl_object_hash($this);
    }

    /**
     * Set the local key
     *
     * @param  string  $name  The new local key name
     */
    public function setLocalKey(string $name): static {
        $currentKey = $this->getLocalKey();
        $overallProgressData = $this->getOverallProgressData();

        if (isset($overallProgressData[$currentKey])) {
            // Rename the local key preserving the data
            $overallProgressData[$name] = $overallProgressData[$currentKey];
            unset($overallProgressData[$currentKey]);
            $this->saveOverallProgressData($overallProgressData);
        }

        $this->localKey = $name;

        $this->makeSureLocalIsPartOfTheCalc();

        return $this;
    }

    /**
     * Save the overall progress data to the storage.
     */
    protected function saveOverallProgressData(array $progressData): static {
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
     */
    public function getTTL(): int {
        return $this->customTTL ?? config('progressable.ttl', $this->defaultTTL);
    }

    /**
     * Reset the overall progress.
     */
    public function resetOverallProgress(): static {
        return $this->saveOverallProgressData([]);
    }

    /**
     * Set the prefix storage key.
     *
     * @param  string  $prefixStorageKey  The prefix storage key to set.
     *
     * @throw UniqueNameAlreadySetException If the unique name has already been set
     */
    public function setPrefixStorageKey(string $prefixStorageKey): static {
        if (isset($this->overallUniqueName)) {
            throw new UniqueNameAlreadySetException;
        }

        $this->customPrefixStorageKey = $prefixStorageKey;

        return $this;
    }

    /**
     * Set the time-to-live for the object.
     *
     * @param  int  $TTL  The time-to-live value to set in minutes
     */
    public function setTTL(int $TTL): static {
        $this->customTTL = $TTL;

        return $this;
    }
}
