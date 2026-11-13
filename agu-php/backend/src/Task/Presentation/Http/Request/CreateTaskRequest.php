<?php

namespace App\Task\Presentation\Http\Request;

use App\Shared\Application\DTO\IncomingFile;
use App\Task\Application\DTO\CreateTaskInput;
use App\Task\Domain\Entity\TaskAttachment;
use App\Task\Domain\Enum\TaskPriority;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contrato HTTP de POST /api/tasks: JSON, ou multipart/form-data quando há arquivos. Os arquivos vão no campo
 * `attachments[]` (com colchetes, repetido para cada arquivo: o PHP só monta a lista assim; sem os colchetes o
 * último arquivo sobrescreve os anteriores). Valida o formato; regras de negócio e dos arquivos ficam no domínio.
 * Propriedades `mixed` permitem rejeitar tipos errados (ex.: número no lugar de texto) como erro de validação.
 */
final class CreateTaskRequest
{
    #[Assert\NotBlank(message: 'O título é obrigatório.')]
    #[Assert\Type(type: 'string', message: 'O título deve ser um texto.')]
    #[Assert\Length(max: 255, maxMessage: 'O título deve ter no máximo {{ limit }} caracteres.')]
    public mixed $title = null;

    #[Assert\Type(type: 'string', message: 'A descrição deve ser um texto.')]
    public mixed $description = null;

    #[Assert\Type(type: 'string', message: 'A prioridade deve ser um texto.')]
    #[Assert\Choice(callback: [TaskPriority::class, 'values'], message: 'Prioridade inválida. Valores aceitos: low, medium, high, urgent.')]
    public mixed $priority = null;

    #[Assert\Type(type: 'string', message: 'A data de início deve ser um texto.')]
    #[Assert\DateTime(format: \DATE_ATOM, message: 'A data de início deve estar no formato ISO 8601 (ex.: 2026-12-01T09:00:00-03:00).')]
    public mixed $startAt = null;

    #[Assert\Type(type: 'string', message: 'A data de vencimento deve ser um texto.')]
    #[Assert\DateTime(format: \DATE_ATOM, message: 'A data de vencimento deve estar no formato ISO 8601 (ex.: 2026-12-01T18:00:00-03:00).')]
    public mixed $dueAt = null;

    /** Id do usuário responsável pela tarefa (opcional); pode ser outro usuário que não quem a cria. */
    public mixed $assignedTo = null;

    /** @var list<mixed> o que chegou em `attachments[]`; cada item deve ser um arquivo válido */
    public array $attachments = [];

    /**
     * @param array<mixed> $payload campos do corpo (JSON ou formulário)
     * @param list<mixed>  $files    arquivos do campo `attachments`
     */
    public static function fromArray(array $payload, array $files = []): self
    {
        $request = new self();
        $request->title = $payload['title'] ?? null;
        $request->description = $payload['description'] ?? null;
        $request->priority = $payload['priority'] ?? null;
        $request->startAt = $payload['startAt'] ?? null;
        $request->dueAt = $payload['dueAt'] ?? null;
        $request->assignedTo = $payload['assignedTo'] ?? null;
        $request->attachments = $files;

        return $request;
    }

    #[Assert\Callback]
    public function validateAssignee(ExecutionContextInterface $context): void
    {
        if (null !== $this->assignedTo && !AssigneeId::isValid($this->assignedTo)) {
            $context->buildViolation('O responsável deve ser o id de um usuário (número inteiro positivo).')->atPath('assignedTo')->addViolation();
        }
    }

    /**
     * Confere só o que é do transporte: se cada item é um arquivo e se o PHP o recebeu inteiro.
     * Tamanho, tipo e quantidade são regras de negócio e ficam no domínio (TaskAttachment).
     */
    #[Assert\Callback]
    public function validateAttachments(ExecutionContextInterface $context): void
    {
        foreach ($this->attachments as $index => $file) {
            if (!$file instanceof UploadedFile) {
                $context->buildViolation('Envie cada anexo como arquivo, em multipart/form-data.')->atPath(sprintf('attachments[%d]', $index))->addViolation();

                continue;
            }
            if (!$file->isValid()) {
                $context->buildViolation(self::uploadErrorMessage($file))->atPath(sprintf('attachments[%d]', $index))->addViolation();
            }
        }
    }

    private static function uploadErrorMessage(UploadedFile $file): string
    {
        return match ($file->getError()) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => sprintf('O arquivo "%s" excede o tamanho máximo permitido de %d MB.', $file->getClientOriginalName(), intdiv(TaskAttachment::MAX_SIZE_BYTES, 1024 * 1024)),
            \UPLOAD_ERR_PARTIAL => sprintf('O envio do arquivo "%s" foi interrompido.', $file->getClientOriginalName()),
            default => sprintf('Não foi possível receber o arquivo "%s".', $file->getClientOriginalName()),
        };
    }

    /**
     * Só deve ser chamado depois de a validação passar.
     */
    public function toInput(): CreateTaskInput
    {
        return new CreateTaskInput(
            (string) $this->title,
            null === $this->description ? null : (string) $this->description,
            null === $this->priority ? TaskPriority::Medium : TaskPriority::from((string) $this->priority),
            null === $this->startAt ? null : new \DateTimeImmutable((string) $this->startAt),
            null === $this->dueAt ? null : new \DateTimeImmutable((string) $this->dueAt),
            AssigneeId::parse($this->assignedTo),
            array_map(
                static fn (UploadedFile $file): IncomingFile => new IncomingFile($file->getPathname(), $file->getClientOriginalName(), (int) $file->getSize()),
                array_values(array_filter($this->attachments, static fn (mixed $file): bool => $file instanceof UploadedFile)),
            ),
        );
    }
}
