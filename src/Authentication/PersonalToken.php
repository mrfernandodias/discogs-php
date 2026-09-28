<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Authentication;

use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

final readonly class PersonalToken implements RequestAuthenticatorInterface
{
    private string $token;

    public function __construct(#[SensitiveParameter] string $token)
    {
        $token = trim($token);

        if (!$token) {
            throw new \InvalidArgumentException('The Discogs personal token cannot be empty.');
        }

        $this->token = $token;
    }

    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Discogs token=' . $this->token);
    }
}
