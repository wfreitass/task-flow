<?php

namespace App\Task\Application\Service;

use App\Shared\Application\DTO\EmailMessage;
use App\Shared\Application\Port\EmailSenderInterface;
use App\Shared\Application\Port\EmailTemplateRendererInterface;
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
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly UserContactInterface $contacts,
        private readonly UserDirectoryInterface $directory,
        private readonly EmailSenderInterface $emailSender,
        private readonly EmailTemplateRendererInterface $templates,
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

    /**
     * Os textos, o layout e a formatação (rótulos de prioridade e status, data, escape) estão no modelo
     * `task_assigned` (templates/email/). Aqui só entram os dados, sem HTML.
     */
    private function buildMessage(string $assigneeName, string $assigneeEmail, string $assignerName, Task $task): EmailMessage
    {
        $email = $this->templates->render('task_assigned', [
            'assignee' => $assigneeName,
            'assigner' => $assignerName,
            'task' => [
                'id' => $task->id(),
                'title' => $task->title(),
                'description' => $task->description(),
                'priority' => $task->priority()->value,
                'status' => $task->status()->value,
                'dueDate' => $task->dueDate(),
            ],
        ]);

        return new EmailMessage($assigneeEmail, $assigneeName, $email->subject, $email->text, $email->html);
    }
}
