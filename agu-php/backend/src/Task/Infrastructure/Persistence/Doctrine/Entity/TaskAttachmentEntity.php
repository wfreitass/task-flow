<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modelo de persistência da tabela `task_attachments`: só metadados; o arquivo fica no armazenamento privado.
 */
#[ORM\Entity]
#[ORM\Table(name: 'task_attachments')]
class TaskAttachmentEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TaskEntity::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
    public TaskEntity $task;

    #[ORM\Column(name: 'uploaded_by', type: Types::INTEGER, options: ['unsigned' => true])]
    public int $uploadedBy;

    #[ORM\Column(name: 'original_name', type: Types::STRING, length: 255)]
    public string $originalName;

    #[ORM\Column(name: 'storage_key', type: Types::STRING, length: 512)]
    public string $storageKey;

    #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 150)]
    public string $mimeType;

    // A coluna é BIGINT UNSIGNED; o limite de 10 MB cabe folgado em int.
    #[ORM\Column(name: 'size_bytes', type: Types::INTEGER, options: ['unsigned' => true])]
    public int $sizeBytes;

    #[ORM\Column(name: 'checksum_sha256', type: Types::STRING, length: 64, nullable: true, options: ['fixed' => true])]
    public ?string $checksumSha256 = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null;
}
