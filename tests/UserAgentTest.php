<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

class UserAgentTest extends DeepLTestBase
{
    private $curlExecMock;
    private $curlGetInfoMock;
    private $curlOptionsMock;
        

    protected function setUp(): void
    {
        $this->curlExecMock = $this->getFunctionMock(__NAMESPACE__, "curl_exec");
        $this->curlGetInfoMock = $this->getFunctionMock(__NAMESPACE__, "curl_getinfo");
        $this->curlGetInfoMock->expects($this->once())->willReturn(200);
        $this->curlExecMock->expects($this->once())->willReturn(
            '{"character_count": 180118,"character_limit": 1250000}'
        );
        $this->curlOptionsMock = $this->getFunctionMock(__NAMESPACE__, "curl_setopt_array");
    }

    protected function tearDown(): void
    {
        $this->curlExecMock = null;
        $this->curlGetInfoMock = null;
        $this->curlOptionsMock = null;
    }

    public function testDefaultUserAgentHeader()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertStringContainsString('deepl-php/', $userAgentHeader);
            $this->assertStringContainsString('(', $userAgentHeader);
            $this->assertStringContainsString(' php/', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator();
        $translator->getUsage();
    }

    public function testOptInUserAgentHeader()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertStringContainsString('deepl-php/', $userAgentHeader);
            $this->assertStringContainsString('(', $userAgentHeader);
            $this->assertStringContainsString(' php/', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator(['send_platform_info' => true]);
        $translator->getUsage();
    }

    public function testOptOutUserAgentHeader()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertStringContainsString('deepl-php/', $userAgentHeader);
            $this->assertStringNotContainsString('(', $userAgentHeader);
            $this->assertStringNotContainsString(' php/', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator(['send_platform_info' => false]);
        $translator->getUsage();
    }

    public function testCustomUserAgentHeader()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertEquals('my-custom-php-client', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator(['headers' => ['User-Agent' => 'my-custom-php-client']]);
        $translator->getUsage();
    }
    public function testDefaultUserAgentHeaderWithAppInfo()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertStringContainsString('deepl-php/', $userAgentHeader);
            $this->assertStringContainsString('(', $userAgentHeader);
            $this->assertStringContainsString(' php/', $userAgentHeader);
            $this->assertStringContainsString('my-custom-php-chat-client/1.2.3', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator(['app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3')]);
        $translator->getUsage();
    }

    public function testOptInUserAgentHeaderWithAppInfo()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertStringContainsString('deepl-php/', $userAgentHeader);
            $this->assertStringContainsString('(', $userAgentHeader);
            $this->assertStringContainsString(' php/', $userAgentHeader);
            $this->assertStringContainsString('my-custom-php-chat-client/1.2.3', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator([
            'send_platform_info' => true,
            'app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3')]);
        $translator->getUsage();
    }

    public function testOptOutUserAgentHeaderWithAppInfo()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertStringContainsString('deepl-php/', $userAgentHeader);
            $this->assertStringNotContainsString('(', $userAgentHeader);
            $this->assertStringNotContainsString(' php/', $userAgentHeader);
            $this->assertStringContainsString('my-custom-php-chat-client/1.2.3', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator([
            'send_platform_info' => false,
            'app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3')]);
        $translator->getUsage();
    }

    public function testCustomUserAgentHeaderWithAppInfo()
    {
        $this->curlOptionsMock->expects($this->once())->willReturnCallback(function ($handle, $curlOptions) {
            $userAgentHeader = $this->getUserAgentHeaderFromCurlOptions($curlOptions);
            $this->assertEquals('my-custom-php-client', $userAgentHeader);
            $return_var = 1;
        });
        $translator = $this->makeTranslator([
            'headers' => ['User-Agent' => 'my-custom-php-client'],
            'app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3')]);
        $translator->getUsage();
    }
    

    private function getUserAgentHeaderFromCurlOptions($curlOptions): string
    {
        $headers = $curlOptions[\CURLOPT_HTTPHEADER];
        $foundOnce = false;
        $result = '';
        foreach ($headers as $header) {
            $searchResult = \strpos($header, 'User-Agent');
            if ($searchResult !== false) {
                if ($foundOnce) {
                    $this->assertTrue(false, 'Found multiple User-Agent headers in the curl call.');
                }
                $foundOnce = true;
                // header is 'User-Agent: XYZ', so index of colon + 2 for XYZ
                $result = \substr($header, \strpos($header, ':') + 2);
            }
        }
        return $result;
    }
}
