<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modelo de persistência da tabela `task_comments`.
 */
#[ORM\Entity]
#[ORM\Table(name: 'task_comments')]
class TaskCommentEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TaskEntity::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
    public TaskEntity $task;

    #[ORM\Column(name: 'author_id', type: Types::INTEGER, options: ['unsigned' => true])]
    public int $authorId;

    #[ORM\Column(type: Types::TEXT, length: 65535)]
    public string $content;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null;
}
