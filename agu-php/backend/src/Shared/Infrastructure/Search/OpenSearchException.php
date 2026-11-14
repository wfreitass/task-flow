<?php

namespace App\Shared\Infrastructure\Search;

/**
 * Falha ao falar com o OpenSearch (fora do ar, tempo esgotado ou resposta de erro).
 */
final class OpenSearchException extends \RuntimeException
{
}
