<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DeepLTestBase extends TestCase
{
    protected $authKey;
    protected $serverUrl;
    protected $isMockServer;

    protected $sessionNoResponse;
    protected $session429Count;
    protected $sessionInitCharacterLimit;
    protected $sessionInitDocumentLimit;
    protected $sessionInitTeamDocumentLimit;
    protected $sessionDocFailure;
    protected $sessionDocQueueTime;
    protected $sessionDocTranslateTime;
    protected $sessionExpectProxy;

    protected const EXAMPLE_TEXT = [
        'bg' => 'протонен лъч',
        'cs' => 'protonový paprsek',
        'da' => 'protonstråle',
        'de' => 'Protonenstrahl',
        'el' => 'δέσμη πρωτονίων',
        'en' => 'proton beam',
        'en-US' => 'proton beam',
        'en-GB' => 'proton beam',
        'es' => 'haz de protones',
        'et' => 'prootonikiirgus',
        'fi' => 'protonisäde',
        'fr' => 'faisceau de protons',
        'hu' => 'protonnyaláb',
        'id' => 'berkas proton',
        'it' => 'fascio di protoni',
        'ja' => '陽子ビーム',
        'lt' => 'protonų spindulys',
        'lv' => 'protonu staru kūlis',
        'nl' => 'protonenbundel',
        'pl' => 'wiązka protonów',
        'pt' => 'feixe de prótons',
        'pt-BR' => 'feixe de prótons',
        'pt-PT' => 'feixe de prótons',
        'ro' => 'fascicul de protoni',
        'ru' => 'протонный луч',
        'sk' => 'protónový lúč',
        'sl' => 'protonski žarek',
        'sv' => 'protonstråle',
        'tr' => 'proton ışını',
        'zh' => '质子束',
    ];

    public function __construct(?string $name = null, array $data = array(), $dataName = '')
    {
        $this->serverUrl = getenv('DEEPL_SERVER_URL');
        $this->isMockServer = getenv('DEEPL_MOCK_SERVER_PORT') !== false;

        if ($this->isMockServer) {
            $this->authKey = 'mock_server';
            if ($this->serverUrl === false) {
                throw new \Exception('DEEPL_SERVER_URL environment variable must be set if using a mock server');
            }
        } else {
            $this->authKey = getenv('DEEPL_AUTH_KEY');
            if ($this->authKey === false) {
                throw new \Exception('DEEPL_AUTH_KEY environment variable must be set unless using a mock server');
            }
        }

        parent::__construct($name, $data, $dataName);
    }

    protected function needsMockServer()
    {
        if (!$this->isMockServer) {
            self::markTestSkipped('Test requires mock server');
        }
    }

    protected function needsRealServer()
    {
        if ($this->isMockServer) {
            self::markTestSkipped('Test requires real server');
        }
    }

    private function makeSessionName(): string
    {
        return $this->getName() . '/' . Uuid::uuid4();
    }

    private function sessionHeaders(): array
    {
        $result = array();
        if ($this->sessionNoResponse !== null) {
            $result['mock-server-session-no-response-count'] = strval($this->sessionNoResponse);
        }
        if ($this->session429Count !== null) {
            $result['mock-server-session-429-count'] = strval($this->session429Count);
        }
        if ($this->sessionInitCharacterLimit !== null) {
            $result['mock-server-session-init-character-limit'] = strval($this->sessionInitCharacterLimit);
        }
        if ($this->sessionInitDocumentLimit !== null) {
            $result['mock-server-session-init-document-limit'] = strval($this->sessionInitDocumentLimit);
        }
        if ($this->sessionInitTeamDocumentLimit !== null) {
            $result['mock-server-session-init-team-document-limit'] = strval($this->sessionInitTeamDocumentLimit);
        }
        if ($this->sessionDocFailure !== null) {
            $result['mock-server-session-doc-failure'] = strval($this->sessionDocFailure);
        }
        if ($this->sessionDocQueueTime !== null) {
            $result['mock-server-session-doc-queue-time'] = strval($this->sessionDocQueueTime);
        }
        if ($this->sessionDocTranslateTime !== null) {
            $result['mock-server-session-doc-translate-time'] = strval($this->sessionDocTranslateTime);
        }
        if ($this->sessionExpectProxy !== null) {
            $result['mock-server-session-expect-proxy'] = $this->sessionExpectProxy ? '1' : '0';
        }

        if (count($result) > 0) {
            $result['mock-server-session'] = $this->makeSessionName();
        }

        return $result;
    }

    public function makeTranslator(array $options = []): Translator
    {
        $mergedOptions = array_replace(
            [TranslatorOptions::SERVER_URL => $this->serverUrl,
                TranslatorOptions::HEADERS => $this->sessionHeaders()],
            $options ?? []
        );

        return new Translator($this->authKey, $mergedOptions);
    }

    public function makeTranslatorWithRandomAuthKey(): Translator
    {
        $mergedOptions = array_replace(
            [TranslatorOptions::SERVER_URL => $this->serverUrl,
                TranslatorOptions::HEADERS => $this->sessionHeaders()],
            $options ?? []
        );
        $authKey = Uuid::uuid4();

        return new Translator($authKey, $mergedOptions);
    }
}
