<?php

namespace Src\Media\Domain\Enums;

enum MediaStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Generated = 'generated';
    case Uploading = 'uploading';
    case QueuedForCompaction = 'queued_for_compaction';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }

    public function isRetryable(): bool
    {
        return $this === self::Failed;
    }

    public function isReprocessable(): bool
    {
        return in_array($this, [self::Failed, self::Generated, self::Completed]);
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, self::allowedTransitions()[$this->value] ?? []);
    }

    /**
     * @return array<string, MediaStatus[]>
     */
    private static function allowedTransitions(): array
    {
        return [
            self::Pending->value => [self::Generating, self::Failed],
            self::Generating->value => [self::Generated, self::Failed],
            self::Generated->value => [self::Uploading, self::QueuedForCompaction, self::Failed],
            self::Uploading->value => [self::QueuedForCompaction, self::Failed],
            self::QueuedForCompaction->value => [self::Processing, self::Failed],
            self::Processing->value => [self::Completed, self::Failed],
            self::Completed->value => [self::QueuedForCompaction],
            self::Failed->value => [self::Pending, self::QueuedForCompaction],
        ];
    }
}
