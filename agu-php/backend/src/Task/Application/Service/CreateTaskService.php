<?php

namespace App\Task\Application\Service;

use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\FileStorageInterface;
use App\Shared\Application\Port\MimeTypeDetectorInterface;
use App\Shared\Application\Port\TransactionRunnerInterface;
use App\Task\Application\DTO\AttachmentOutput;
use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Application\DTO\TaskOutput;
use App\Task\Application\Port\UserDirectoryInterface;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Entity\TaskAttachment;
use App\Task\Domain\Entity\TaskHistory;
use App\Task\Domain\Enum\TaskHistoryAction;
use App\Task\Domain\Exception\InvalidAssigneeException;
use App\Task\Domain\Repository\TaskAttachmentRepositoryInterface;
use App\Task\Domain\Repository\TaskHistoryRepositoryInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final class CreateTaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskHistoryRepositoryInterface $history,
        private readonly ClockInterface $clock,
        private readonly TransactionRunnerInterface $transactions,
        private readonly TaskAttachmentRepositoryInterface $attachments,
        private readonly FileStorageInterface $storage,
        private readonly MimeTypeDetectorInterface $mimeTypes,
        private readonly UserDirectoryInterface $directory,
        private readonly TaskOutputFactory $outputs,
    ) {
    }

    /**
     * Cria a tarefa, grava os arquivos enviados e registra o histórico.
     * O criador é sempre o usuário autenticado, nunca um valor vindo do corpo da requisição.
     *
     * Ordem, para nunca deixar lixo: (1) valida tudo que não depende de banco nem de disco; (2) grava os arquivos;
     * (3) grava tarefa, anexos e histórico numa transação; se ela falhar, os arquivos já gravados são apagados.
     */
    public function execute(CreateTaskInput $input, int $authenticatedUserId): TaskOutput
    {
        $now = $this->clock->now();

        $task = Task::create(
            $authenticatedUserId,
            $input->title,
            $input->description,
            $input->priority,
            $input->startDate,
            $input->dueDate,
            $input->assignedTo,
            $now,
        );

        // O responsável pode ser qualquer usuário ativo, não só quem cria. Confere antes de gravar qualquer arquivo.
        if (null !== $input->assignedTo && null === $this->directory->findActiveById($input->assignedTo)) {
            throw InvalidAssigneeException::notFound($input->assignedTo);
        }

        TaskAttachment::assertCount(\count($input->attachments));
        $files = [];
        foreach ($input->attachments as $file) {
            $mimeType = $this->mimeTypes->detect($file->tempPath);
            TaskAttachment::assertAcceptable($file->originalName, $mimeType, $file->size);
            $files[] = [$file, $mimeType];
        }

        $storedKeys = [];
        try {
            $stored = [];
            foreach ($files as [$file, $mimeType]) {
                $storedFile = $this->storage->store($file, $mimeType);
                $storedKeys[] = $storedFile->storageKey;
                $stored[] = [$file, $mimeType, $storedFile];
            }

            return $this->transactions->run(function () use ($task, $stored, $authenticatedUserId, $now): TaskOutput {
                $this->tasks->add($task);
                $this->history->add(TaskHistory::record(
                    $task,
                    $authenticatedUserId,
                    TaskHistoryAction::Created,
                    null,
                    $task->snapshot(),
                    $now,
                ));

                $outputs = [];
                foreach ($stored as [$file, $mimeType, $storedFile]) {
                    $attachment = TaskAttachment::create(
                        (int) $task->id(),
                        $authenticatedUserId,
                        $file->originalName,
                        $storedFile->storageKey,
                        $mimeType,
                        $file->size,
                        $storedFile->checksumSha256,
                        $now,
                    );
                    $this->attachments->add($attachment);
                    $this->history->add(TaskHistory::record(
                        $task,
                        $authenticatedUserId,
                        TaskHistoryAction::AttachmentAdded,
                        null,
                        ['attachment_id' => (string) $attachment->id(), 'name' => $attachment->originalName(), 'size' => (string) $attachment->sizeBytes()],
                        $now,
                    ));
                    $outputs[] = AttachmentOutput::fromAttachment($attachment);
                }

                return $this->outputs->one($task, $outputs);
            });
        } catch (\Throwable $exception) {
            foreach ($storedKeys as $storageKey) {
                $this->storage->delete($storageKey);
            }

            throw $exception;
        }
    }
}
