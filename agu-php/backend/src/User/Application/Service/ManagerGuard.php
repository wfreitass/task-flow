<?php

namespace App\User\Application\Service;

use App\Shared\Domain\Role;
use App\User\Domain\Exception\LastAdminException;
use App\User\Domain\Repository\UserRepositoryInterface;

/**
 * Impede que o sistema fique sem nenhum usuário ATIVO com a role `user.manage` (sem ele, ninguém mais vincula
 * perfis). Vale para qualquer mudança que possa tirar essa role de alguém: trocar o profile de um usuário, tirar a
 * role de um profile ou desativar o usuário.
 *
 * Deve rodar DENTRO de uma transação: a primeira contagem trava as linhas de quem tem a role, então duas mudanças
 * simultâneas não passam juntas. Se a mudança deixar zero, a exceção desfaz a transação.
 */
final class ManagerGuard
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * @template T
     *
     * @param callable(): T $change a mudança, já gravada pelo próprio callable
     *
     * @return T
     *
     * @throws LastAdminException
     */
    public function protect(callable $change): mixed
    {
        $before = $this->users->countActiveWithRoleForUpdate(Role::UserManage);

        $result = $change();

        // Se já não havia ninguém antes, esta mudança não é a culpada: não a bloqueia.
        if ($before > 0 && 0 === $this->users->countActiveWithRoleForUpdate(Role::UserManage)) {
            throw new LastAdminException();
        }

        return $result;
    }
}
