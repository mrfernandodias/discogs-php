<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Authentication;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

final readonly class ConsumerCredentials implements RequestAuthenticatorInterface
{
    private string $key;
    private string $secret;

    public function __construct(
        #[SensitiveParameter]
        string $key,
        #[SensitiveParameter]
        string $secret,
    ) {
        $key = trim($key);
        $secret = trim($secret);

        if ($key === '') {
            throw new InvalidArgumentException('The Discogs consumer key cannot be empty.');
        }

        if ($secret === '') {
            throw new InvalidArgumentException('The Discogs consumer secret cannot be empty.');
        }

        $this->key = $key;
        $this->secret = $secret;
    }

    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request->withHeader(
            'Authorization',
            "Discogs key={$this->key}, secret={$this->secret}",
        );
    }
}
