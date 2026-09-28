<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Tests\Support;

use LogicException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RecordingHttpClient implements ClientInterface
{
    private ?RequestInterface $lastRequest = null;

    public function __construct(private readonly ResponseInterface $response) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return $this->response;
    }

    public function lastRequest(): RequestInterface
    {
        return $this->lastRequest ?? throw new LogicException('No HTTP request has been sent.');
    }
}
