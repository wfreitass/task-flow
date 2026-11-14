<?php

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use App\User\Domain\Exception\UsernameAlreadyTakenException;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByUsername(string $username): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @param list<int> $ids
     *
     * @return list<User> os que existem (ativos ou não), em qualquer ordem
     */
    public function findByIds(array $ids): array;

    /**
     * Usuários **ativos** cujo nome de usuário contém o texto, ou cujo e-mail é exatamente ele, por ordem de nome.
     *
     * @return list<User>
     */
    public function searchActive(string $query, int $limit): array;

    /** @return list<User> */
    public function findActive(): array;

    /**
     * Persiste um novo usuário.
     *
     * @throws UsernameAlreadyTakenException se o nome de usuário já existir (inclusive em corrida entre requisições)
     * @throws EmailAlreadyRegisteredException se o e-mail já existir
     */
    public function add(User $user): void;

    /**
     * Grava as alterações de um usuário que já existe.
     */
    public function save(User $user): void;

    /**
     * Quantos admins ativos existem. Para usar DENTRO de uma transação: trava as linhas deles até o commit, para
     * que dois rebaixamentos simultâneos não deixem o sistema sem nenhum admin.
     */
    public function countActiveAdminsForUpdate(): int;
}
