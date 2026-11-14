<?php

namespace App\User\Infrastructure\Security;

use App\Shared\Application\DTO\AccessIdentity;
use App\Shared\Application\Port\AccessTokenVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * Valida o JWT da API (assinatura e validade, pelo LexikJWTAuthenticationBundle) fora de uma requisição HTTP.
 * Também confere que o usuário do token ainda existe e está ativo, como a API faz a cada requisição.
 *
 * Roda dentro do servidor de WebSocket, um processo que fica dias no ar: depois de cada consulta ao banco a
 * conexão é fechada e o EntityManager, limpo, para não usar uma conexão que o MySQL já derrubou por inatividade
 * nem dados velhos em memória. A conexão reabre sozinha na próxima consulta.
 */
final class LexikAccessTokenVerifier implements AccessTokenVerifierInterface
{
    public function __construct(
        private readonly JWTEncoderInterface $encoder,
        private readonly UserProvider $users,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function verify(string $token): ?AccessIdentity
    {
        try {
            $payload = $this->encoder->decode($token);
        } catch (JWTDecodeFailureException) {
            return null;
        }
        if (!is_array($payload) || !is_string($payload['email'] ?? null) || !is_int($payload['exp'] ?? null)) {
            return null;
        }

        try {
            $user = $this->users->loadUserByIdentifier($payload['email']);
        } catch (UserNotFoundException) {
            return null;
        } finally {
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
        }

        return new AccessIdentity($user->id(), (new \DateTimeImmutable())->setTimestamp($payload['exp']));
    }
}
