<?php

// Copyright 2025 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use JsonException;

class DeepLClient extends Translator
{

    public function __construct(string $authKey, array $options = [])
    {
        parent::__construct($authKey, $options);
    }

    /**
     * Rephrases the given text using DeepL's API.
     * @param string|string[] $texts A single string or array of strings to rephrase
     * @param string|null $targetLang Optional target language code
     * @param array $options Rephrase options to apply. See \DeepL\DeepLClientOptions.
     * @return RephraseTextResult|RephraseTextResult[] A RephraseTextResult or array of
     * RephraseTextResult objects containing rephrased texts
     * @throws DeepLException
     * @see \DeepL\DeepLClientOptions
     */
    public function rephraseText($texts, ?string $targetLang = null, array $options = [])
    {
        $params = $this->buildRephraseBodyParams(
            $targetLang,
            $options[RephraseTextOptions::WRITING_STYLE] ?? null,
            $options[RephraseTextOptions::TONE] ?? null
        );
        $this->validateAndAppendTexts($params, $texts);

        $response = $this->client->sendRequestWithBackoff(
            'POST',
            '/v2/write/rephrase',
            [HttpClientWrapper::OPTION_PARAMS => $params]
        );
        $this->checkStatusCode($response);
        list(, $content) = $response;

        try {
            $json = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidContentException($exception);
        }

        $improvements = isset($json['improvements']) && is_array($json['improvements']) ? $json['improvements'] : [];
        $output = [];
        foreach ($improvements as $improvement) {
            $text = $improvement['text'] ?? '';
            $detectedSourceLanguage = $improvement['detected_source_language'] ?? '';
            $targetLanguage = $improvement['target_language'] ?? '';
            $output[] = new RephraseTextResult($text, $detectedSourceLanguage, $targetLanguage);
        }

        return is_array($texts) ? $output : $output[0];
    }

    /**
     * Validates and prepares HTTP parameters for rephrase requests.
     * @param string|string[] $texts Text(s) to rephrase
     * @param string|null $targetLang Target language code, or null to use default
     * @param string|null $style Writing style option, or null if not specified
     * @param string|null $tone Tone option, or null if not specified
     * @return array Associative array of HTTP parameters
     * @throws DeepLException
     */
    public function buildRephraseBodyParams(
        ?string $targetLang = null,
        ?string $style = null,
        ?string $tone = null
    ): array {
        if ($targetLang !== null) {
            $targetLang = LanguageCode::standardizeLanguageCode($targetLang);
            if ($targetLang === 'en') {
                throw new DeepLException('targetLang="en" is deprecated, please use "en-GB" or "en-US" instead.');
            } elseif ($targetLang === 'pt') {
                throw new DeepLException('targetLang="pt" is deprecated, please use "pt-PT" or "pt-BR" instead.');
            }
            $params['target_lang'] = $targetLang;
        }

        if ($style !== null) {
            $params['writing_style'] = $style;
        }

        if ($tone !== null) {
            $params['tone'] = $tone;
        }

        return $params;
    }
}
