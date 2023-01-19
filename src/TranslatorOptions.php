<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Options that can be specified when constructing a Translator.
 * @see Translator::__construct
 */
class TranslatorOptions
{
    /**
     * Base URL of DeepL API, can be overridden for example for testing purposes. By default, the correct DeepL API URL
     * is selected based on the user account type (free or paid).
     * @see DEFAULT_SERVER_URL
     * @see DEFAULT_SERVER_URL_FREE
     */
    public const SERVER_URL = 'server_url';

    /**
     * HTTP headers attached to every HTTP request. By default, no extra headers are used. Note that during Translator
     * initialization headers for Authorization and User-Agent are added, unless they are overridden in this option.
     */
    public const HEADERS = 'headers';

    /**
     * Connection timeout used for each HTTP request retry, as a float in seconds.
     * @see DEFAULT_TIMEOUT
     */
    public const TIMEOUT = 'timeout';

    /**
     * The maximum number of failed attempts that Translator will retry, per request. Note: only errors due to
     * transient conditions are retried.
     * @see DEFAULT_MAX_RETRIES
     */
    public const MAX_RETRIES = 'max_retries';

    /**
     * Proxy server URL, for example 'https://user:pass@10.10.1.10:3128'.
     */
    public const PROXY = 'proxy';

    /**
     * The PSR-3 compatible logger to log messages to.
     * @see LoggerInterface
     */
    public const LOGGER = 'logger';

    /** The default server URL used for DeepL API Pro accounts (if SERVER_URL is unspecified). */
    public const DEFAULT_SERVER_URL = 'https://api.deepl.com';

    /** The default server URL used for DeepL API Free accounts (if SERVER_URL is unspecified). */
    public const DEFAULT_SERVER_URL_FREE = 'https://api-free.deepl.com';

    /** The default timeout (if TIMEOUT is unspecified) is 10 seconds. */
    public const DEFAULT_TIMEOUT = 10.0;

    /** The default maximum number of request retries (if MAX_RETRIES is unspecified) is 5. */
    public const DEFAULT_MAX_RETRIES = 5;

    /**
     * Flag that determines if the library sends more detailed information about the platform it runs
     * on with each API call. This is overriden if the User-Agent header is set in the HEADERS field.
     * @see HEADERS
     */
    public const SEND_PLATFORM_INFO = 'send_platform_info';

    /** Name and version of the application that uses this client library. */
    public const APP_INFO = 'app_info';
}
