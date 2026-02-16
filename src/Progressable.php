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
     * The total steps for the progress.
     */
    protected ?int $totalSteps = null;

    /**
     * The current step of the progress.
     */
    protected ?int $currentStep = null;

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
     * The default precision for progress values.
     */
    protected int $defaultPrecision = 2;

    /**
     * Custom precision for progress values.
     */
    protected ?int $customPrecision = null;

    /**
     * Metadata associated with this progress instance.
     */
    protected array $metadata = [];

    /**
     * Status message for this progress instance.
     */
    protected ?string $statusMessage = null;

    /**
     * Callback to be called when progress changes.
     *
     * @var callable|null
     */
    protected mixed $onProgressChange = null;

    /**
     * Callback to be called when progress completes.
     *
     * @var callable|null
     */
    protected mixed $onComplete = null;

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
     * @param  int|null  $precision  The precision of the overall progress (null uses configured default)
     */
    public function getOverallProgress(?int $precision = null): float {
        $precision = $precision ?? $this->getPrecision();
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
     * @param  int|null  $precision  The precision of the local progress (null uses configured default)
     */
    public function getLocalProgress(?int $precision = null): float {
        $precision = $precision ?? $this->getPrecision();

        return round($this->progress, $precision, PHP_ROUND_HALF_ODD);
    }

    /**
     * @throws UniqueNameNotSetException
     */
    public function resetLocalProgress(): static {
        return $this->setLocalProgress(0);
    }

    /**
     * Increment the local progress by a given amount.
     *
     * @param  float  $amount  The amount to increment (can be negative to decrement)
     *
     * @throws UniqueNameNotSetException
     */
    public function incrementLocalProgress(float $amount = 1): static {
        return $this->setLocalProgress($this->progress + $amount);
    }

    /**
     * Check if the local progress is complete (100%).
     */
    public function isComplete(): bool {
        return $this->progress >= 100;
    }

    /**
     * Check if the overall progress is complete (100%).
     */
    public function isOverallComplete(): bool {
        return $this->getOverallProgress(0) >= 100;
    }

    /**
     * Remove this instance from the overall progress calculation.
     *
     *
     * @throws UniqueNameNotSetException
     */
    public function removeLocalFromOverall(): static {
        $progressData = $this->getOverallProgressData();
        $localKey = $this->getLocalKey();

        if (isset($progressData[$localKey])) {
            unset($progressData[$localKey]);
            $this->saveOverallProgressData($progressData);
        }

        $this->progress = 0;

        return $this;
    }

    /**
     * Update the progress value for this instance.
     *
     * @return $this
     *
     * @throws UniqueNameNotSetException
     */
    public function setLocalProgress(float $progress): static {
        $oldProgress = $this->progress;
        $this->progress = max(0, min(100, $progress));

        if ($this->totalSteps !== null && $this->totalSteps > 0) {
            $this->currentStep = (int) round(($this->progress / 100) * $this->totalSteps);
        }

        $this->updateLocalProgressData($this->progress);

        // Fire progress change callback
        if ($this->onProgressChange !== null && $oldProgress !== $this->progress) {
            call_user_func($this->onProgressChange, $this->progress, $oldProgress, $this);
        }

        // Fire complete callback
        if ($this->onComplete !== null && $this->progress >= 100 && $oldProgress < 100) {
            call_user_func($this->onComplete, $this);
        }

        return $this;
    }

    /**
     * Update the progress data in storage.
     */
    protected function updateLocalProgressData(float $progress): static {
        $progressData = $this->getOverallProgressData();

        $localData = [
            'progress' => $progress,
        ];

        if ($this->statusMessage !== null) {
            $localData['message'] = $this->statusMessage;
        }

        if ($this->totalSteps !== null) {
            $localData['total_steps'] = $this->totalSteps;
        }

        if ($this->currentStep !== null) {
            $localData['current_step'] = $this->currentStep;
        }

        if (! empty($this->metadata)) {
            $localData['metadata'] = $this->metadata;
        }

        $progressData[$this->getLocalKey()] = $localData;

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

    /**
     * Get the precision for progress values.
     */
    public function getPrecision(): int {
        return $this->customPrecision ?? config('progressable.precision', $this->defaultPrecision);
    }

    /**
     * Set the precision for progress values.
     *
     * @param  int  $precision  The number of decimal places
     */
    public function setPrecision(int $precision): static {
        $this->customPrecision = max(0, $precision);

        return $this;
    }

    /**
     * Set the status message for this progress instance.
     */
    public function setStatusMessage(?string $message): static {
        $this->statusMessage = $message;

        // Update storage with new message if we have a unique name
        if (isset($this->overallUniqueName)) {
            $this->updateLocalProgressData($this->progress);
        }

        return $this;
    }

    /**
     * Merge metadata for this progress instance.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function mergeMetadata(array $metadata): static {
        $this->metadata = array_merge($this->metadata, $metadata);

        // Update storage with new metadata if we have a unique name
        if (isset($this->overallUniqueName)) {
            $this->updateLocalProgressData($this->progress);
        }

        return $this;
    }

    /**
     * Get the status message for this progress instance.
     */
    public function getStatusMessage(): ?string {
        return $this->statusMessage;
    }

    /**
     * Set metadata for this progress instance.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function setMetadata(array $metadata): static {
        $this->metadata = $metadata;

        // Update storage with new metadata if we have a unique name
        if (isset($this->overallUniqueName)) {
            $this->updateLocalProgressData($this->progress);
        }

        return $this;
    }

    /**
     * Get metadata for this progress instance.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array {
        return $this->metadata;
    }

    /**
     * Add or update a single metadata value.
     */
    public function addMetadata(string $key, mixed $value): static {
        $this->metadata[$key] = $value;

        // Update storage with new metadata if we have a unique name
        if (isset($this->overallUniqueName)) {
            $this->updateLocalProgressData($this->progress);
        }

        return $this;
    }

    /**
     * Get a single metadata value.
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set a callback to be called when progress changes.
     *
     * The callback receives: (float $newProgress, float $oldProgress, static $instance)
     */
    public function onProgressChange(callable $callback): static {
        $this->onProgressChange = $callback;

        return $this;
    }

    /**
     * Set a callback to be called when progress reaches 100%.
     *
     * The callback receives: (static $instance)
     */
    public function onComplete(callable $callback): static {
        $this->onComplete = $callback;

        return $this;
    }

    /**
     * Set the total number of steps.
     *
     * @param  int  $totalSteps  The total number of steps.
     * @return $this
     */
    public function setTotalSteps(int $totalSteps): static {
        $this->totalSteps = $totalSteps;

        return $this;
    }

    /**
     * Get the total number of steps.
     */
    public function getTotalSteps(): ?int {
        return $this->totalSteps;
    }

    /**
     * Set the current step.
     *
     * @param  int  $step  The current step.
     * @return $this
     */
    public function setStep(int $step): static {
        $this->currentStep = $step;

        if ($this->totalSteps !== null && $this->totalSteps > 0) {
            $progress = ($this->currentStep / $this->totalSteps) * 100;

            return $this->setLocalProgress($progress);
        }

        return $this->updateLocalProgressData($this->progress);
    }

    /**
     * Get the current step.
     */
    public function getStep(): ?int {
        return $this->currentStep;
    }

    /**
     * Increment the current step.
     *
     * @param  int  $amount  The amount to increment.
     * @return $this
     */
    public function incrementStep(int $amount = 1): static {
        $current = $this->currentStep ?? 0;

        return $this->setStep($current + $amount);
    }
}
