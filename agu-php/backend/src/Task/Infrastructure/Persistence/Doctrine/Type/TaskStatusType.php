<?php

namespace App\Task\Infrastructure\Persistence\Doctrine\Type;

use App\Task\Domain\Enum\TaskStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

/**
 * Converte o enum de domínio TaskStatus no id do catálogo `task_statuses`.
 * Os ids abaixo são fixados pela migration Version20261111000000; mudanças no catálogo exigem nova migration.
 */
final class TaskStatusType extends Type
{
    public const NAME = 'task_status';

    private const IDS = [
        'backlog' => 1,
        'todo' => 2,
        'in_progress' => 3,
        'blocked' => 4,
        'done' => 5,
        'canceled' => 6,
    ];

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'SMALLINT UNSIGNED';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?TaskStatus
    {
        if (null === $value) {
            return null;
        }

        $code = array_search((int) $value, self::IDS, true);
        if (false === $code) {
            throw ConversionException::conversionFailed((string) $value, self::NAME);
        }

        return TaskStatus::from($code);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof TaskStatus) {
            throw ConversionException::conversionFailedInvalidType($value, self::NAME, ['null', TaskStatus::class]);
        }

        return self::IDS[$value->value];
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
