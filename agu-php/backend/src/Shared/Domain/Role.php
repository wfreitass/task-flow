<?php

namespace App\Shared\Domain;

/**
 * Uma ROLE é uma capacidade que um profile pode ter ("excluir tarefas", "gerenciar usuários"). Esta lista é o
 * catálogo das roles que o CÓDIGO sabe checar; a tabela `roles` guarda as mesmas, com nome e descrição, e quem tem
 * cada uma é decidido no banco: roles ligadas a profiles (`profile_roles`), e cada usuário tem um profile.
 *
 *   usuário ──▶ profile ──▶ roles
 *
 * Role nova: um caso aqui, um INSERT em `roles` (numa migration) e a chamada RoleCheckerInterface::assertHas() no
 * caso de uso. Sem a linha no banco, ninguém a tem (nega por padrão).
 *
 * Isto é só a camada de profile. As regras de posse continuam valendo por cima (ex.: só quem criou a tarefa a edita
 * ou exclui; Task::assertManagedBy).
 */
enum Role: string
{
    case TaskCreate = 'task.create';
    /** Excluir a tarefa (exclusão lógica). */
    case TaskDelete = 'task.delete';
    /** Cancelar a tarefa: mudar o status para `canceled`. */
    case TaskCancel = 'task.cancel';
    /** Vincular um profile a um usuário. */
    case UserManage = 'user.manage';
    /** Criar, editar e excluir profiles e escolher as roles de cada um. */
    case ProfileManage = 'profile.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
