<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Wrapper for the DeepL API for language translation.
 * Create an instance of Translator to use the DeepL API.
 */
final class Translator
{
    /**
     * Library version.
     */
    public const VERSION = '0.1.1';

    /**
     * Implements all HTTP requests and retries.
     */
    private $client;

    /**
     * Construct a Translator object wrapping the DeepL API using your authentication key.
     * This does not connect to the API, and returns immediately.
     * @param string $authKey Authentication key as specified in your account.
     * @param array $options Additional options controlling Translator behaviour.
     * @throws DeepLException
     * @see TranslatorOptions for a list of available request options.
     */
    public function __construct(string $authKey, array $options = [])
    {
        if ($authKey === '') {
            throw new DeepLException('authKey must be a non-empty string');
        }

        $serverUrl = $options[TranslatorOptions::SERVER_URL] ??
            (self::isAuthKeyFreeAccount($authKey) ? TranslatorOptions::DEFAULT_SERVER_URL_FREE
                : TranslatorOptions::DEFAULT_SERVER_URL);
        if (!is_string($serverUrl) || strlen($serverUrl) == 0) {
            throw new DeepLException('If specified, ' .
                TranslatorOptions::SERVER_URL . ' option must be a non-empty string.');
        } elseif (substr($serverUrl, -1) === "/") { // Remove trailing slash if present
            $serverUrl = substr($serverUrl, 0, strlen($serverUrl) - 1);
        }

        $headers = array_replace(
            [
                'Authorization' => "DeepL-Auth-Key $authKey",
                'User-Agent' => 'deepl-php/0.1.1',
            ],
            $options[TranslatorOptions::HEADERS] ?? []
        );

        $timeout = $options[TranslatorOptions::TIMEOUT] ?? TranslatorOptions::DEFAULT_TIMEOUT;

        $maxRetries = $options[TranslatorOptions::MAX_RETRIES] ?? TranslatorOptions::DEFAULT_MAX_RETRIES;

        $logger = $options[TranslatorOptions::LOGGER] ?? null;

        $this->client = new HttpClient($serverUrl, $headers, $timeout, $maxRetries, $logger);
    }

    /**
     * Queries character and document usage during the current billing period.
     * @return Usage
     * @throws DeepLException
     */
    public function getUsage(): Usage
    {
        $response = $this->client->sendRequestWithBackoff('POST', '/v2/usage');
        $this->checkStatusCode($response);
        list(, $content) = $response;
        return new Usage($content);
    }

    /**
     * Queries source languages supported by DeepL API.
     * @return Language[] Array of Language objects containing available source languages.
     * @throws DeepLException
     */
    public function getSourceLanguages(): array
    {
        return $this->getLanguages(false);
    }

    /**
     * Queries target languages supported by DeepL API.
     * @return Language[] Array of Language objects containing available target languages.
     * @throws DeepLException
     */
    public function getTargetLanguages(): array
    {
        return $this->getLanguages(true);
    }

    /**
     * Translates specified text string or array of text strings into the target language.
     * @param $texts string|string[] A single string or array of strings containing input texts to translate.
     * @param string|null $sourceLang Language code of input text language, or null to use auto-detection.
     * @param string $targetLang Language code of language to translate into.
     * @param array $options Translation options to apply. See \DeepL\TranslateTextOptions.
     * @return TextResult|TextResult[] A TextResult or array of TextResult objects containing translated texts.
     * @throws DeepLException
     * @see \DeepL\TranslateTextOptions
     */
    public function translateText($texts, ?string $sourceLang, string $targetLang, array $options = [])
    {
        $params = $this->buildBodyParams(
            $sourceLang,
            $targetLang,
            $options[TranslateTextOptions::FORMALITY] ?? null,
            $options[TranslateTextOptions::GLOSSARY] ?? null
        );
        $this->validateAndAppendTexts($params, $texts);
        $this->validateAndAppendTextOptions($params, $options);

        $response = $this->client->sendRequestWithBackoff(
            'POST',
            '/v2/translate',
            [HttpClient::OPTION_PARAMS => $params]
        );
        $this->checkStatusCode($response);

        list(, $content) = $response;
        $decoded = json_decode($content, true);
        $textResults = [];
        foreach ($decoded['translations'] as $textResult) {
            $textField = $textResult['text'];
            $detectedSourceLang = $textResult['detected_source_language'];
            $textResults[] = new TextResult($textField, $detectedSourceLang);
        }
        return is_array($texts) ? $textResults : $textResults[0];
    }

    /**
     * Queries source or target languages supported by DeepL API.
     * @param bool $target Query target languages if true, source languages otherwise.
     * @return Language[] Array of Language objects containing available languages.
     * @throws DeepLException
     */
    private function getLanguages(bool $target): array
    {
        $response = $this->client->sendRequestWithBackoff(
            'GET',
            '/v2/languages',
            [HttpClient::OPTION_PARAMS => ['type' => $target ? 'target' : null]]
        );
        $this->checkStatusCode($response);
        list(, $content) = $response;

        $decoded = json_decode($content, true);
        $result = [];
        foreach ($decoded as $lang) {
            $name = $lang['name'];
            $code = $lang['language'];
            $supportsFormality = array_key_exists('supports_formality', $lang) ?
                $lang['supports_formality'] : null;
            $result[] = new Language($name, $code, $supportsFormality);
        }
        return $result;
    }

    /**
     * Joins given TagList with commas to form a single comma-delimited string.
     * @param string[]|string $tagList List of tags to join.
     * @return string Tags combined into a comma-delimited string.
     */
    private function joinTagList($tagList): string
    {
        if (is_string($tagList)) {
            return $tagList;
        } else {
            return implode(',', $tagList);
        }
    }

    /**
     * Validates and prepares HTTP parameters for arguments common to text and document translation.
     * @param string|null $sourceLang Source language code, or null to use auto-detection.
     * @param string $targetLang Target language code.
     * @param string|null $formality Formality option, or null if not specified.
     * @param string|null $glossary Glossary ID, or null if not specified.
     * @return array Associative array of HTTP parameters.
     * @throws DeepLException
     */
    private function buildBodyParams(
        ?string $sourceLang,
        string $targetLang,
        ?string $formality,
        ?string $glossary
    ): array {
        $targetLang = LanguageCode::standardizeLanguageCode($targetLang);
        if (isset($sourceLang)) {
            $sourceLang = LanguageCode::standardizeLanguageCode($sourceLang);
        }

        if ($targetLang === 'en') {
            throw new DeepLException('targetLang="en" is deprecated, please use "en-GB" or "en-US" instead.');
        } elseif ($targetLang === 'pt') {
            throw new DeepLException('targetLang="pt" is deprecated, please use "pt-PT" or "pt-BR" instead.');
        }

        $params = ['target_lang' => $targetLang];
        if (isset($sourceLang)) {
            $params['source_lang'] = $sourceLang;
        }
        if (isset($formality)) {
            $formality_str = strtolower($formality);
            if ($formality_str !== 'default') {
                $params['formality'] = $formality_str;
            }
        }
        if (isset($glossary)) {
            $params['glossary_id'] = $glossary;
        }
        return $params;
    }

    /**
     * Validates and appends texts to HTTP request parameters.
     * @param array $params Parameters for HTTP request.
     * @param string|string[] $texts User-supplied texts to be checked.
     * @throws DeepLException
     */
    private function validateAndAppendTexts(array &$params, $texts)
    {
        if (is_array($texts)) {
            foreach ($texts as $text) {
                if (!is_string($text) || strlen($text) === 0) {
                    throw new DeepLException(
                        'texts parameter must be a non-empty string or array of non-empty strings',
                    );
                }
            }
        } else {
            if (!is_string($texts) || strlen($texts) === 0) {
                throw new DeepLException(
                    'texts parameter must be a non-empty string or array of non-empty strings',
                );
            }
        }
        $params['text'] = $texts;
    }

    /**
     * Validates and appends text options to HTTP request parameters.
     * @param array $params Parameters for HTTP request.
     * @param array|null $options Options for translate text request.
     * Note the formality and glossary options are handled separately, because these options overlap with document
     * translation.
     */
    private function validateAndAppendTextOptions(array &$params, ?array $options): void
    {
        if ($options === null) {
            return;
        }
        if (isset($options[TranslateTextOptions::SPLIT_SENTENCES])) {
            $split_sentences = strtolower($options[TranslateTextOptions::SPLIT_SENTENCES]);
            switch ($split_sentences) {
                case 'on':
                case 'default':
                    $params[TranslateTextOptions::SPLIT_SENTENCES] = '1';
                    break;
                case 'off':
                    $params[TranslateTextOptions::SPLIT_SENTENCES] = '0';
                    break;
                default:
                    $params[TranslateTextOptions::SPLIT_SENTENCES] = $split_sentences;
                    break;
            }
        }
        if ($options[TranslateTextOptions::PRESERVE_FORMATTING] ?? false) {
            $params[TranslateTextOptions::PRESERVE_FORMATTING] = '1';
        }
        if (isset($options[TranslateTextOptions::TAG_HANDLING])) {
            $params[TranslateTextOptions::TAG_HANDLING] = $options[TranslateTextOptions::TAG_HANDLING];
        }
        if (isset($options[TranslateTextOptions::OUTLINE_DETECTION]) &&
            $options[TranslateTextOptions::OUTLINE_DETECTION] === false) {
            $params[TranslateTextOptions::OUTLINE_DETECTION] = '0';
        }
        if (isset($options[TranslateTextOptions::NON_SPLITTING_TAGS])) {
            $params[TranslateTextOptions::NON_SPLITTING_TAGS] =
                $this->joinTagList($options[TranslateTextOptions::NON_SPLITTING_TAGS]);
        }
        if (isset($options[TranslateTextOptions::SPLITTING_TAGS])) {
            $params[TranslateTextOptions::SPLITTING_TAGS] =
                $this->joinTagList($options[TranslateTextOptions::SPLITTING_TAGS]);
        }
        if (isset($options[TranslateTextOptions::IGNORE_TAGS])) {
            $params[TranslateTextOptions::IGNORE_TAGS] =
                $this->joinTagList($options[TranslateTextOptions::IGNORE_TAGS]);
        }
    }

    /**
     * Checks the HTTP status code, and in case of failure, throws an exception with diagnostic information.
     * @throws DeepLException
     */
    private function checkStatusCode(array $response)
    {
        list($statusCode, $content) = $response;

        if (200 <= $statusCode && $statusCode < 400) {
            return;
        }

        $message = '';
        try {
            $json = json_decode($content, true);
            if (isset($json['message'])) {
                $message .= ", message: {$json['message']}";
            }
            if (isset($json['detail'])) {
                $message .= ", detail: {$json['detail']}";
            }
        } catch (\Exception $e) {
            // JSON parsing errors are ignored, and we fall back to the raw response
            $message = ", $content";
        }

        switch ($statusCode) {
            case 403:
                throw new AuthorizationException("Authorization failure, check authentication key$message");
            case 456:
                throw new QuotaExceededException("Quota for this billing period has been exceeded$message");
            case 404:
                throw new NotFoundException("Not found, check server_url$message");
            case 400:
                throw new DeepLException("Bad request$message");
            case 429:
                throw new TooManyRequestsException(
                    "Too many requests, DeepL servers are currently experiencing high load$message"
                );
            case 503:
                throw new DeepLException("Service unavailable$message");
            default:
                throw new DeepLException(
                    "Unexpected status code: $statusCode $message, content: $content."
                );
        }
    }

    /**
     * Returns true if the specified DeepL Authentication Key is associated with a free account,
     * otherwise false.
     * @param string authKey The authentication key to check.
     * @return bool True if the key is associated with a free account, otherwise false.
     */
    public static function isAuthKeyFreeAccount(string $authKey): bool
    {
        return substr($authKey, -3) === ':fx';
    }
}
