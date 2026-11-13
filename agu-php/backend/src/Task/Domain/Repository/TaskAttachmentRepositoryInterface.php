<?php

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\TaskAttachment;

interface TaskAttachmentRepositoryInterface
{
    /**
     * Persiste os metadados de um anexo. Após a chamada, o anexo tem id.
     */
    public function add(TaskAttachment $attachment): void;
}
