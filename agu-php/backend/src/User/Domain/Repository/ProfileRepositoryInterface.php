<?php

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\Profile;
use App\User\Domain\Exception\ProfileCodeTakenException;
use App\User\Domain\ValueObject\RoleDefinition;

interface ProfileRepositoryInterface
{
    public function findById(int $id): ?Profile;

    public function findByCode(string $code): ?Profile;

    /**
     * Todos os profiles, com as roles de cada um, por ordem de nome.
     *
     * @return list<Profile>
     */
    public function findAll(): array;

    /**
     * O profile de um usuário ATIVO; null se o usuário não existe ou está inativo.
     */
    public function findByActiveUserId(int $userId): ?Profile;

    /**
     * O catálogo de roles (tabela `roles`).
     *
     * @return list<RoleDefinition>
     */
    public function roleCatalog(): array;

    /**
     * @throws ProfileCodeTakenException se o código já existir (inclusive em corrida entre requisições)
     */
    public function add(Profile $profile): void;

    /**
     * Grava as alterações de um profile que já existe (dados e conjunto de roles).
     */
    public function save(Profile $profile): void;

    public function remove(Profile $profile): void;

    /**
     * Quantos usuários (ativos ou não) têm o profile.
     */
    public function countUsers(int $profileId): int;
}
