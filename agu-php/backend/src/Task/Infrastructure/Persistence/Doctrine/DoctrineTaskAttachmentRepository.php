<?php

namespace App\Task\Infrastructure\Persistence\Doctrine;

use App\Task\Domain\Entity\TaskAttachment;
use App\Task\Domain\Repository\TaskAttachmentRepositoryInterface;
use App\Task\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Task\Infrastructure\Persistence\Doctrine\Mapper\TaskAttachmentMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTaskAttachmentRepository implements TaskAttachmentRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskAttachmentMapper $mapper,
    ) {
    }

    public function add(TaskAttachment $attachment): void
    {
        $task = $this->entityManager->getReference(TaskEntity::class, $attachment->taskId());
        $entity = $this->mapper->toEntity($attachment, $task);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->mapper->syncGeneratedId($attachment, $entity);
    }
}
