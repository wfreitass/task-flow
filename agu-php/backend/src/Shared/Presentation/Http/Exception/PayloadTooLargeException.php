<?php

namespace App\Shared\Presentation\Http\Exception;

/**
 * O corpo da requisição passou do limite do PHP (post_max_size) e foi descartado por inteiro.
 */
final class PayloadTooLargeException extends \RuntimeException
{
}
