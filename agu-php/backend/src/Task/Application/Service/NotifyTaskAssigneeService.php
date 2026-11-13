<?php

namespace App\Task\Application\Service;

use App\Shared\Application\DTO\EmailMessage;
use App\Shared\Application\Port\EmailSenderInterface;
use App\Task\Application\Port\UserContactInterface;
use App\Task\Application\Port\UserDirectoryInterface;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Event\TaskAssigned;
use App\Task\Domain\Repository\TaskRepositoryInterface;

/**
 * Avisa por e-mail o usuário a quem uma tarefa foi atribuída. Roda quando o consumidor da fila trata o evento
 * TaskAssigned, ou seja, fora da requisição que fez a atribuição.
 */
final class NotifyTaskAssigneeService
{
    // As chaves são os códigos dos enums, em literal: `Enum::Caso->value` dentro de constante só existe no PHP 8.2+.
    private const PRIORITY_LABELS = [
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ];

    // Os mesmos nomes da tabela `task_statuses`.
    private const STATUS_LABELS = [
        'backlog' => 'Backlog',
        'todo' => 'A fazer',
        'in_progress' => 'Em andamento',
        'blocked' => 'Bloqueada',
        'done' => 'Concluída',
        'canceled' => 'Cancelada',
    ];

    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly UserContactInterface $contacts,
        private readonly UserDirectoryInterface $directory,
        private readonly EmailSenderInterface $emailSender,
    ) {
    }

    /**
     * Devolve true se enviou o e-mail e false se decidiu não enviar (sem erro): o evento é uma foto de um
     * momento anterior, e a atribuição pode ter mudado até aqui.
     */
    public function execute(TaskAssigned $event): bool
    {
        $assignee = $this->contacts->findActiveContactById($event->assigneeId);
        if (null === $assignee) {
            return false; // o usuário foi desativado ou removido
        }

        // A tarefa ainda existe, não foi excluída e continua atribuída a este usuário?
        $task = $this->tasks->findAccessibleById($event->taskId, $event->assigneeId);
        if (null === $task || !$task->isAssignedTo($event->assigneeId)) {
            return false;
        }

        $assigner = $this->directory->findByIds([$event->assignedById])[$event->assignedById] ?? null;

        $this->emailSender->send($this->buildMessage($assignee->username, $assignee->email, $assigner?->username ?? 'Alguém', $task));

        return true;
    }

    private function buildMessage(string $assigneeName, string $assigneeEmail, string $assignerName, Task $task): EmailMessage
    {
        $lines = [
            'Prioridade' => self::PRIORITY_LABELS[$task->priority()->value],
            'Status' => self::STATUS_LABELS[$task->status()->value],
        ];
        if (null !== $task->dueDate()) {
            $lines['Vencimento'] = $task->dueDate()->setTimezone(new \DateTimeZone('UTC'))->format('d/m/Y H:i').' (UTC)';
        }

        $text = sprintf("Olá, %s!\n\n%s atribuiu a tarefa \"%s\" a você.\n\n", $assigneeName, $assignerName, $task->title());
        foreach ($lines as $label => $value) {
            $text .= sprintf("%s: %s\n", $label, $value);
        }
        if (null !== $task->description()) {
            $text .= "\nDescrição:\n".$task->description()."\n";
        }

        $e = static fn (string $value): string => htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        $html = sprintf('<p>Olá, %s!</p><p><strong>%s</strong> atribuiu a tarefa <strong>%s</strong> a você.</p><ul>', $e($assigneeName), $e($assignerName), $e($task->title()));
        foreach ($lines as $label => $value) {
            $html .= sprintf('<li>%s: %s</li>', $e($label), $e($value));
        }
        $html .= '</ul>';
        if (null !== $task->description()) {
            $html .= '<p>'.nl2br($e($task->description())).'</p>';
        }

        return new EmailMessage(
            $assigneeEmail,
            $assigneeName,
            sprintf('Você foi designado(a) para a tarefa: %s', $task->title()),
            $text,
            $html,
        );
    }
}
