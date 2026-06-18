<?php

// Copyright 2026 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 HTTP client used in tests to capture outgoing requests and return a canned
 * response, without sending traffic to a real server. Use with
 * TranslatorOptions::HTTP_CLIENT to assert on the requests the SDK produces.
 */
class CapturingHttpClient implements ClientInterface
{
    /** @var RequestInterface[] */
    private $requests = [];
    /** @var int */
    private $responseStatus;
    /** @var string */
    private $responseBody;
    /** @var array */
    private $responseHeaders;
    /** @var \Psr\Http\Message\ResponseFactoryInterface */
    private $responseFactory;
    /** @var \Psr\Http\Message\StreamFactoryInterface */
    private $streamFactory;

    public function __construct(
        string $responseBody = '{}',
        int $responseStatus = 200,
        array $responseHeaders = ['Content-Type' => 'application/json']
    ) {
        $this->responseBody = $responseBody;
        $this->responseStatus = $responseStatus;
        $this->responseHeaders = $responseHeaders;
        $this->responseFactory = Psr17FactoryDiscovery::findResponseFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Sets the canned response returned on subsequent calls to sendRequest().
     */
    public function setResponse(
        string $body,
        int $status = 200,
        array $headers = ['Content-Type' => 'application/json']
    ): void {
        $this->responseBody = $body;
        $this->responseStatus = $status;
        $this->responseHeaders = $headers;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $response = $this->responseFactory->createResponse($this->responseStatus);
        foreach ($this->responseHeaders as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response->withBody($this->streamFactory->createStream($this->responseBody));
    }

    /**
     * @return RequestInterface[] All requests received so far, in order.
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getRequestCount(): int
    {
        return count($this->requests);
    }

    public function getLastRequest(): RequestInterface
    {
        if (count($this->requests) === 0) {
            throw new \RuntimeException('No requests have been captured yet');
        }
        return $this->requests[count($this->requests) - 1];
    }

    public function getLastRequestBody(): string
    {
        return (string) $this->getLastRequest()->getBody();
    }

    public function getLastRequestMethod(): string
    {
        return $this->getLastRequest()->getMethod();
    }

    public function getLastRequestPath(): string
    {
        return $this->getLastRequest()->getUri()->getPath();
    }

    public function getLastRequestQuery(): string
    {
        return $this->getLastRequest()->getUri()->getQuery();
    }

    /**
     * Decodes the most recent request's body according to its Content-Type header.
     * Returns an associative array for application/x-www-form-urlencoded (via parse_str)
     * and for application/json (via json_decode). Returns an empty array for other or
     * empty bodies.
     */
    public function decodeBody(): array
    {
        $request = $this->getLastRequest();
        $body = (string) $request->getBody();
        if ($body === '') {
            return [];
        }
        $contentType = $request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($body, true);
            return is_array($decoded) ? $decoded : [];
        }
        if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $result = [];
            parse_str($body, $result);
            return $result;
        }
        return [];
    }
}
