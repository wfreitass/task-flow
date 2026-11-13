<?php

namespace App\Shared\Presentation\Http;

use App\Shared\Presentation\Http\Exception\MalformedRequestException;
use Symfony\Component\HttpFoundation\Request;

final class JsonPayloadDecoder
{
    /**
     * @return array<mixed>
     *
     * @throws MalformedRequestException se o corpo não for um JSON de objeto ou lista
     */
    public function decode(Request $request): array
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new MalformedRequestException();
        }

        if (!\is_array($payload)) {
            throw new MalformedRequestException();
        }

        return $payload;
    }
}
