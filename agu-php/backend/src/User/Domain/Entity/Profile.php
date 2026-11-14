<?php

namespace App\User\Domain\Entity;

use App\Shared\Domain\Role;
use App\User\Domain\Exception\InvalidProfileException;

/**
 * Profile (perfil): um conjunto de roles. O usuário tem um profile e, por ele, as roles.
 * Os profiles `admin` e `executor` vêm da migration e são de sistema: não podem ser excluídos, e o `code` de
 * nenhum profile muda depois de criado (é o que o código e os scripts usam para achá-lo).
 */
final class Profile
{
    /** Quem tem todas as roles. */
    public const ADMIN_CODE = 'admin';
    /** O profile de quem acabou de se cadastrar. */
    public const DEFAULT_CODE = 'executor';

    private ?int $id = null;

    /**
     * @param list<Role> $roles
     */
    private function __construct(
        private string $code,
        private string $name,
        private ?string $description,
        private bool $isSystem,
        private array $roles,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param list<Role> $roles
     */
    public static function create(string $code, string $name, ?string $description, array $roles, \DateTimeImmutable $now): self
    {
        $code = trim($code);
        if (1 !== preg_match('/^[a-z][a-z0-9_-]{1,49}$/', $code)) {
            throw InvalidProfileException::invalidCode();
        }

        return new self($code, self::normalizeName($name), self::normalizeDescription($description), false, self::unique($roles), $now, $now);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    /**
     * @return list<Role>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function has(Role $role): bool
    {
        return \in_array($role, $this->roles, true);
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeDetails(string $name, ?string $description, \DateTimeImmutable $now): void
    {
        $name = self::normalizeName($name);
        $description = self::normalizeDescription($description);

        if ($name !== $this->name || $description !== $this->description) {
            $this->name = $name;
            $this->description = $description;
            $this->updatedAt = $now;
        }
    }

    /**
     * Troca o conjunto de roles do profile.
     *
     * @param list<Role> $roles
     *
     * @return bool false se já eram exatamente estas
     */
    public function replaceRoles(array $roles, \DateTimeImmutable $now): bool
    {
        $roles = self::unique($roles);
        if ($roles === $this->roles) {
            return false;
        }

        $this->roles = $roles;
        $this->updatedAt = $now;

        return true;
    }

    /**
     * Sem repetição e na ordem do catálogo, para que dois conjuntos iguais sejam sempre idênticos.
     *
     * @param list<Role> $roles
     *
     * @return list<Role>
     */
    private static function unique(array $roles): array
    {
        return array_values(array_filter(Role::cases(), static fn (Role $role): bool => \in_array($role, $roles, true)));
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ('' === $name || mb_strlen($name) > 100) {
            throw InvalidProfileException::invalidName();
        }

        return $name;
    }

    private static function normalizeDescription(?string $description): ?string
    {
        $description = null === $description ? null : trim($description);
        if (null !== $description && mb_strlen($description) > 255) {
            throw InvalidProfileException::descriptionTooLong();
        }

        return '' === $description ? null : $description;
    }
}
