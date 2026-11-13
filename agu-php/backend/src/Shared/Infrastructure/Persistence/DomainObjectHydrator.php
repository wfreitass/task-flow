<?php

namespace App\Shared\Infrastructure\Persistence;

/**
 * Reconstrói objetos de domínio lidos do banco e atribui o id gerado por ele.
 *
 * As entidades de domínio têm construtor privado, que aplica as invariantes de criação.
 * Um objeto vindo do banco já passou por elas, então é recriado sem executar o construtor,
 * pela infraestrutura, e o domínio não precisa expor um método público que as contorne.
 */
final class DomainObjectHydrator
{
    /**
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $values propriedade => valor
     *
     * @return T
     */
    public function hydrate(string $class, array $values): object
    {
        $reflection = new \ReflectionClass($class);
        $object = $reflection->newInstanceWithoutConstructor();

        foreach ($values as $property => $value) {
            $reflection->getProperty($property)->setValue($object, $value);
        }

        return $object;
    }

    public function set(object $object, string $property, mixed $value): void
    {
        (new \ReflectionProperty($object, $property))->setValue($object, $value);
    }
}
