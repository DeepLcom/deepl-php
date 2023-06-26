<?php

// Copyright 2023 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use \Psr\Http\Client\ClientInterface;

/**
 * Tests for the User-Agent header with a custom HTTP client (guzzle).
 */
class UserAgentTestCustom extends DeepLTestBase
{

    private $client;
    private $requestsHistory;

    protected function setUp(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"character_count": 180118,"character_limit": 1250000}'),
        ]);
        $this->requestsHistory = [];
        $history = Middleware::history($this->requestsHistory);
        
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $this->client = new \GuzzleHttp\Client(['handler' => $handlerStack]);
    }

    protected function tearDown(): void
    {
        $this->requestsHistory = null;
        $this->client = null;
    }

    public function testDefaultUserAgentHeader()
    {
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $this->client]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertStringContainsString('deepl-php/', $userAgentHeader);
        $this->assertStringContainsString('(', $userAgentHeader);
        $this->assertStringContainsString(' php/', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }

    public function testOptInUserAgentHeader()
    {
        $translator = $this->makeTranslator([
            'send_platform_info' => true,
            TranslatorOptions::HTTP_CLIENT => $this->client
        ]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertStringContainsString('deepl-php/', $userAgentHeader);
        $this->assertStringContainsString('(', $userAgentHeader);
        $this->assertStringContainsString(' php/', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }

    public function testOptOutUserAgentHeader()
    {
        $translator = $this->makeTranslator([
            'send_platform_info' => false,
            TranslatorOptions::HTTP_CLIENT => $this->client
        ]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertStringContainsString('deepl-php/', $userAgentHeader);
        $this->assertStringNotContainsString('(', $userAgentHeader);
        $this->assertStringNotContainsString(' php/', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }

    public function testCustomUserAgentHeader()
    {
        $translator = $this->makeTranslator([
            'headers' => ['User-Agent' => 'my-custom-php-client'],
            TranslatorOptions::HTTP_CLIENT => $this->client
        ]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertEquals('my-custom-php-client', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }

    public function testDefaultUserAgentHeaderWithAppInfo()
    {
        $translator = $this->makeTranslator([
            'app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3'),
            TranslatorOptions::HTTP_CLIENT => $this->client
        ]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertStringContainsString('deepl-php/', $userAgentHeader);
        $this->assertStringContainsString('(', $userAgentHeader);
        $this->assertStringContainsString(' php/', $userAgentHeader);
        $this->assertStringContainsString('my-custom-php-chat-client/1.2.3', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }

    public function testOptInUserAgentHeaderWithAppInfo()
    {
        $translator = $this->makeTranslator([
            'send_platform_info' => true,
            'app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3'),
            TranslatorOptions::HTTP_CLIENT => $this->client
        ]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertStringContainsString('deepl-php/', $userAgentHeader);
        $this->assertStringContainsString('(', $userAgentHeader);
        $this->assertStringContainsString(' php/', $userAgentHeader);
        $this->assertStringContainsString('my-custom-php-chat-client/1.2.3', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }

    public function testOptOutUserAgentHeaderWithAppInfo()
    {
        $translator = $this->makeTranslator([
            'send_platform_info' => false,
            'app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3'),
            TranslatorOptions::HTTP_CLIENT => $this->client
        ]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertStringContainsString('deepl-php/', $userAgentHeader);
        $this->assertStringNotContainsString('(', $userAgentHeader);
        $this->assertStringNotContainsString(' php/', $userAgentHeader);
        $this->assertStringContainsString('my-custom-php-chat-client/1.2.3', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }

    public function testCustomUserAgentHeaderWithAppInfo()
    {
        $translator = $this->makeTranslator([
            'headers' => ['User-Agent' => 'my-custom-php-client'],
            'app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3'),
            TranslatorOptions::HTTP_CLIENT => $this->client
        ]);
        $translator->getUsage();
        $userAgentHeader = $this->requestsHistory[0]["request"]->getHeaders()["User-Agent"][0];
        $this->assertEquals('my-custom-php-client', $userAgentHeader);
        $this->assertCount(1, $this->requestsHistory);
    }
}
