<?php

namespace App\Shared\Presentation\Http;

use App\Shared\Presentation\Http\Exception\MalformedRequestException;
use App\Shared\Presentation\Http\Exception\PayloadTooLargeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lê o corpo como JSON ou, quando a requisição é multipart/form-data (envio de arquivos), como formulário.
 * Os arquivos não vêm aqui: ficam em `$request->files`.
 */
final class RequestPayloadDecoder
{
    public function __construct(private readonly JsonPayloadDecoder $json)
    {
    }

    /**
     * @return array<mixed>
     *
     * @throws MalformedRequestException
     * @throws PayloadTooLargeException
     */
    public function decode(Request $request): array
    {
        if (!\in_array($request->getContentTypeFormat(), ['form'], true)) {
            return $this->json->decode($request);
        }

        // Acima de post_max_size o PHP descarta o corpo inteiro: não chegam nem os campos nem os arquivos.
        if (0 === $request->request->count() && 0 === $request->files->count() && (int) $request->server->get('CONTENT_LENGTH', 0) > 0) {
            throw new PayloadTooLargeException();
        }

        return $request->request->all();
    }
}
