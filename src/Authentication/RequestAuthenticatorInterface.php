<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Authentication;

use Psr\Http\Message\RequestInterface;

interface RequestAuthenticatorInterface
{
    public function authenticate(RequestInterface $request): RequestInterface;
}
