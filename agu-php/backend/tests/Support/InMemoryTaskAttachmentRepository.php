<?php

namespace App\Tests\Support;

use App\Task\Domain\Entity\TaskAttachment;
use App\Task\Domain\Repository\TaskAttachmentRepositoryInterface;

final class InMemoryTaskAttachmentRepository implements TaskAttachmentRepositoryInterface
{
    /** @var list<TaskAttachment> */
    public array $attachments = [];

    public function add(TaskAttachment $attachment): void
    {
        $this->attachments[] = $attachment;
        (new \ReflectionProperty(TaskAttachment::class, 'id'))->setValue($attachment, \count($this->attachments));
    }
}
