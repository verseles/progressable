<?php

namespace Verseles\Progressable\Contracts;

interface ProgressStore {
    /**
     * Replace the data for one local key without dropping sibling local keys.
     *
     * @param  array<string, mixed>  $data
     */
    public function putLocal(string $overallKey, string $localKey, array $data, int $ttl): void;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAll(string $overallKey): array;

    /**
     * Move one local entry, preserving an existing target entry when present.
     */
    public function renameLocal(string $overallKey, string $currentLocalKey, string $newLocalKey, int $ttl): void;

    /**
     * Remove one local entry. Missing keys are ignored.
     */
    public function removeLocal(string $overallKey, string $localKey, int $ttl): void;

    /**
     * Remove every local entry for the overall key.
     */
    public function resetOverall(string $overallKey): void;
}
