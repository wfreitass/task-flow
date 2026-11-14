<?php

namespace App\Task\Application\Service;

use App\Shared\Application\DTO\RealtimeMessage;
use App\Shared\Application\Port\RealtimePublisherInterface;
use App\Task\Domain\Event\TaskStatusChanged;
use App\Task\Domain\Repository\TaskRepositoryInterface;

/**
 * Avisa em tempo real todos que enxergam a tarefa que o status mudou.
 *
 * Idempotente: o id da mensagem é o do evento, então o servidor de tempo real descarta a repetição (a fila entrega
 * "ao menos uma vez"), e a mensagem traz o estado novo, não um passo a executar.
 */
final class BroadcastTaskStatusChangedService
{
    public const TYPE = 'task.status_changed';

    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly RealtimePublisherInterface $realtime,
    ) {
    }

    /**
     * @return bool false se ninguém mais enxerga a tarefa (nada foi enviado)
     */
    public function execute(TaskStatusChanged $event): bool
    {
        // O evento é uma foto de um momento anterior. Quem não enxerga mais a tarefa (excluída ou reatribuída
        // enquanto a mensagem esperava na fila) não deve ser avisado dela.
        $recipients = [];
        foreach (array_unique($event->audience) as $userId) {
            if (null !== $this->tasks->findAccessibleById($event->taskId, $userId)) {
                $recipients[] = $userId;
            }
        }
        if ([] === $recipients) {
            return false;
        }

        $this->realtime->publish(new RealtimeMessage(
            id: $event->eventId,
            type: self::TYPE,
            occurredAt: $event->occurredAt,
            recipientIds: $recipients,
            data: [
                'taskId' => $event->taskId,
                'status' => $event->status,
                'previousStatus' => $event->previousStatus,
                'completedAt' => $event->completedAt,
                'changedBy' => $event->changedBy,
            ],
        ));

        return true;
    }
}
