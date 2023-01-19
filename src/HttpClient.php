<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use Psr\Log\LoggerInterface;

/**
 * Internal class implementing HTTP requests.
 * @private
 */
class HttpClient
{
    private $serverUrl;
    private $headers;
    private $maxRetries;
    private $minTimeout;
    private $logger;
    private $proxy;

    /**
     * @var resource cURL handle.
     */
    private $curlHandle;

    public const OPTION_FILE = 'file';
    public const OPTION_HEADERS = 'headers';
    public const OPTION_PARAMS = 'params';
    public const OPTION_OUTFILE = 'outfile';

    public function __construct(
        string           $serverUrl,
        array            $headers,
        float            $timeout,
        int              $maxRetries,
        ?LoggerInterface $logger,
        ?string $proxy
    ) {
        $this->serverUrl = $serverUrl;
        $this->maxRetries = $maxRetries;
        $this->minTimeout = $timeout;
        $this->headers = $headers;
        $this->logger = $logger;
        $this->proxy = $proxy;
        $this->curlHandle = \curl_init();
    }

    public function __destruct()
    {
        \curl_close($this->curlHandle);
    }

    /**
     * Makes API request retrying if necessary, and returns (as Promise) response.
     * @param string $method HTTP method, for example 'GET'.
     * @param string $url Path to endpoint, excluding base server URL.
     * @param array|null $options Array of options, possible arguments are given by OPTIONS_ constants.
     * @return array Status code and content.
     * @throws DeepLException
     */
    public function sendRequestWithBackoff(string $method, string $url, ?array $options = []): array
    {
        $url = $this->serverUrl . $url;
        $headers = array_replace(
            $this->headers,
            $options[self::OPTION_HEADERS] ?? []
        );
        $file = $options[self::OPTION_FILE] ?? null;
        $params = $options[self::OPTION_PARAMS] ?? [];
        $this->logInfo("Request to DeepL API $method $url");
        $this->logDebug('Request details: ' . json_encode($params));
        $backoff = new BackoffTimer();
        $response = null;
        $exception = null;
        while ($backoff->getNumRetries() <= $this->maxRetries) {
            $outFile = isset($options[self::OPTION_OUTFILE]) ? fopen($options[self::OPTION_OUTFILE], 'w') : null;
            $timeout = max($this->minTimeout, $backoff->getTimeUntilDeadline());
            $response = null;
            $exception = null;
            try {
                $response = $this->sendRequest($method, $url, $timeout, $headers, $params, $file, $outFile);
            } catch (ConnectionException $e) {
                $exception = $e;
            }

            if ($outFile) {
                fclose($outFile);
            }

            if (!$this->shouldRetry($response, $exception) || $backoff->getNumRetries() + 1 >= $this->maxRetries) {
                break;
            }

            if ($exception !== null) {
                $this->logDebug("Encountered a retryable-error: {$exception->getMessage()}");
            }

            $this->logInfo('Starting retry ' . ($backoff->getNumRetries() + 1) .
                " for request $method $url after sleeping for {$backoff->getTimeUntilDeadline()} seconds.");
            $backoff->sleepUntilDeadline();
        }

        if ($exception !== null) {
            throw $exception;
        } else {
            list($statusCode, $content) = $response;
            $this->logInfo("DeepL API response $method $url $statusCode");
            $this->logDebug("Response details: $content");
            return $response;
        }
    }

    /**
     * @param string $method HTTP method to use.
     * @param string $url Absolute URL to query.
     * @param float $timeout Time to wait before triggering timeout, in seconds.
     * @param array $headers Array of headers to include in request.
     * @param array $params Array of parameters to include in body.
     * @param string|null $filePath If not null, path to file to upload with request.
     * @param resource|null $outFile If not null, file to write output to.
     * @return array Array where the first element is the HTTP status code and the second element is the response body.
     * @throws ConnectionException
     */
    private function sendRequest(
        string $method,
        string $url,
        float $timeout,
        array $headers,
        array $params,
        ?string $filePath,
        $outFile
    ): array {
        $curlOptions = [];
        $curlOptions[\CURLOPT_HEADER] = false;

        switch ($method) {
            case "POST":
                $curlOptions[\CURLOPT_POST] = true;
                break;
            case "GET":
                $curlOptions[\CURLOPT_HTTPGET] = true;
                break;
            default:
                $curlOptions[\CURLOPT_CUSTOMREQUEST] = $method;
                break;
        }

        $curlOptions[\CURLOPT_URL] = $url;
        $curlOptions[\CURLOPT_CONNECTTIMEOUT] = $timeout;
        $curlOptions[\CURLOPT_TIMEOUT_MS] = $timeout * 1000;

        if ($this->proxy !== null) {
            $curlOptions[\CURLOPT_PROXY] = $this->proxy;
        }

        // Convert headers from an associative array to an array of "key: value" elements
        $curlOptions[\CURLOPT_HTTPHEADER] = \array_map(function (string $key, string $value): string {
            return "$key: $value";
        }, array_keys($headers), array_values($headers));

        if ($filePath !== null) {
            // If a file is to be uploaded, add it to the list of body parameters
            $params['file'] = \curl_file_create($filePath);
            $curlOptions[\CURLOPT_POSTFIELDS] = $params;
        } elseif (count($params) > 0) {
            // If there are repeated parameters, passing the parameters directly to cURL will index the repeated
            // parameters which is not what we need, so instead we encode the parameters without indexes.
            // This case only occurs if no file is uploaded.
            $curlOptions[\CURLOPT_POSTFIELDS] = $this->urlEncodeWithRepeatedParams($params);
        }

        if ($outFile) {
            // Stream response content to specified file
            $curlOptions[\CURLOPT_FILE] = $outFile;
        } else {
            // Return response content as function result
            $curlOptions[\CURLOPT_RETURNTRANSFER] = true;
        }

        \curl_reset($this->curlHandle);

        // The next 3 curl calls are unqualified so that we can mock them, see
        // https://github.com/php-mock/php-mock-phpunit#restrictions
        curl_setopt_array($this->curlHandle, $curlOptions);

        $result = curl_exec($this->curlHandle);
        if ($result !== false) {
            $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
            return [$statusCode, $result];
        } else {
            $errorMessage = \curl_error($this->curlHandle);
            $errorCode = \curl_errno($this->curlHandle);
            switch ($errorCode) {
                case \CURLE_UNSUPPORTED_PROTOCOL:
                case \CURLE_URL_MALFORMAT:
                case \CURLE_URL_MALFORMAT_USER:
                    $shouldRetry = false;
                    $errorMessage = "Invalid server URL. $errorMessage";
                    break;
                case \CURLE_OPERATION_TIMEOUTED:
                case \CURLE_COULDNT_CONNECT:
                case \CURLE_GOT_NOTHING:
                    $shouldRetry = true;
                    break;
                default:
                    $shouldRetry = false;
                    break;
            }
            throw new ConnectionException($errorMessage, $errorCode, null, $shouldRetry);
        }
    }

    private function shouldRetry(?array $response, ?ConnectionException $exception): bool
    {
        if ($exception !== null) {
            return $exception->shouldRetry;
        }
        list($statusCode, ) = $response;

        // Retry on Too-Many-Requests error and internal errors
        return $statusCode === 429 || $statusCode >= 500;
    }

    public function logDebug(string $message): void
    {
        if ($this->logger) {
            $this->logger->debug($message);
        }
    }

    public function logInfo(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }

    private static function urlEncodeWithRepeatedParams(?array $params): string
    {
        $params = $params ?? [];
        $fields = [];
        foreach ($params as $key => $value) {
            $name = \urlencode($key);
            if (is_array($value)) {
                $fields[] = implode(
                    '&',
                    array_map(
                        function (string $textElement) use ($name): string {
                            return $name . '=' . \urlencode($textElement);
                        },
                        $value
                    )
                );
            } elseif (is_null($value)) {
                // Parameters with null value are skipped
            } else {
                $fields[] = $name . '=' . \urlencode($value);
            }
        }

        return implode("&", $fields);
    }
}
