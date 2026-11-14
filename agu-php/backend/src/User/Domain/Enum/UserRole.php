<?php

namespace App\User\Domain\Enum;

use App\Shared\Domain\Permission;

/**
 * Perfil (papel) do usuário. Cada usuário tem exatamente um.
 *
 * A MATRIZ DE PERMISSÕES fica aqui, em permissions(): para mudar o que um perfil pode fazer, mude só esta lista.
 */
enum UserRole: string
{
    /** Pode tudo, inclusive excluir/cancelar tarefas e gerenciar usuários. */
    case Admin = 'admin';
    /** Executa o trabalho: cria, edita (o que é seu), muda status e comenta. Não exclui nem cancela. */
    case Executor = 'executor';

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Executor => [Permission::TaskCreate],
        };
    }

    public function can(Permission $permission): bool
    {
        return \in_array($permission, $this->permissions(), true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
