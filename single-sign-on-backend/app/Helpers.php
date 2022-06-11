<?php

declare(strict_types = 1);

if (! function_exists('base64_url_encode')) {
    /**
     * Base64 URL 編碼
     *
     * @param string $data 原始字串
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    function base64_url_encode(string $data): string
    {
        $encoded = base64_encode($data);

        if ($encoded === false) {
            throw new InvalidArgumentException('Base64 編碼失敗');
        }

        $encoded = str_replace('+', '-', $encoded);
        $encoded = str_replace('/', '_', $encoded);
        $encoded = rtrim($encoded, '=');

        return $encoded;
    }
}

if (! function_exists('base64_url_decode')) {
    /**
     * Base64 URL 解碼
     *
     * @param string $url 原始字串
     * @param bool $strict
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    function base64_url_decode(string $url, bool $strict = false): string
    {
        $decoded = str_replace('-', '+', $url);
        $decoded = str_replace('+', '/', $decoded);
        $decoded = base64_decode($decoded, $strict);

        if ($decoded === false) {
            throw new InvalidArgumentException('Base64 解碼失敗');
        }

        return $decoded;
    }
}
