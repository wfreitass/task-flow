<?php

namespace App\Task\Domain\Enum;

/**
 * Ações do histórico. A tabela `task_history` aceita outras ações (CHECK da migration);
 * cada uma é adicionada aqui quando o caso de uso correspondente existir.
 */
enum TaskHistoryAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case Assigned = 'assigned';
    case Deleted = 'deleted';
    case AttachmentAdded = 'attachment_added';
    case CommentAdded = 'comment_added';
}
