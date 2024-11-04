<?php

namespace App\Domains\Feedback\Entities;

use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;

/**
 * フィードバックエンティティ
 */
class Feedback
{
    private const MAX_CONTENT_LENGTH = 1000;

    public function __construct(
        public readonly FeedbackIdentifier $identifier,
        public readonly FeedbackType $type,
        public readonly FeedbackStatus $status,
        public readonly string $content,
        public readonly \DateTimeInterface $createdAt,
        public readonly \DateTimeInterface $updatedAt,
    ) {

        if (static::MAX_CONTENT_LENGTH < mb_strlen($content)) {
            throw new \InvalidArgumentException(\sprintf(
                'Content must be less than or equal to %d characters.',
                static::MAX_CONTENT_LENGTH
            ));
        }
    }

    public function identifier(): FeedbackIdentifier
    {
        return $this->identifier;
    }

    public function type(): FeedbackType
    {
        return $this->type;
    }

    public function status(): FeedbackStatus
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function createdAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function equals(Feedback $other): bool
    {
        if (!$this->identifier->equals($other->identifier)) {
            return false;
        }

        if ($this->type !== $other->type) {
            return false;
        }

        if ($this->status !== $other->status) {
            return false;
        }

        if ($this->content !== $other->content) {
            return false;
        }

        if ($this->createdAt->toAtomString() !== $other->createdAt->toAtomString()) {
            return false;
        }

        if ($this->updatedAt->toAtomString() !== $other->updatedAt->toAtomString()) {
            return false;
        }

        return true;
    }
}
