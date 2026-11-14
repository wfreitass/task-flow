<?php

namespace App\Shared\Domain;

/**
 * Ações que dependem do perfil (papel) do usuário. É o vocabulário comum entre os contextos: quem decide
 * quais perfis têm cada permissão é UserRole::permissions(), em um único lugar.
 *
 * Isto é só a camada de PERFIL. As regras de posse continuam valendo por cima (ex.: só quem criou a tarefa a edita
 * ou exclui; Task::assertManagedBy).
 */
enum Permission: string
{
    case TaskCreate = 'task.create';
    /** Excluir a tarefa (exclusão lógica). */
    case TaskDelete = 'task.delete';
    /** Cancelar a tarefa: mudar o status para `canceled`. */
    case TaskCancel = 'task.cancel';
    /** Gerenciar usuários (hoje, mudar o perfil de alguém). */
    case UserManage = 'user.manage';
}
