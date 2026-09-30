<?php

declare(strict_types=1);

namespace BMMatic\Support;

/**
 * The small HTTP client the review providers use. cURL when it is available, streams otherwise, so it also works on
 * hosts without the cURL extension. Tests and the end-to-end mock inject their own implementation of HttpClient.
 */
final class Http implements HttpClient
{
    public function __construct(private readonly int $timeout = 15)
    {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResponse
    {
        if (preg_match('#^https?://#i', $url) !== 1) {
            return new HttpResponse(0, '', 'Refused to call a non-HTTP address.');
        }
        return function_exists('curl_init') ? $this->curl($method, $url, $headers, $body) : $this->stream($method, $url, $headers, $body);
    }

    /** @param array<string, string> $headers */
    private function curl(string $method, string $url, array $headers, ?string $body): HttpResponse
    {
        $handle = curl_init($url);
        if ($handle === false) {
            return new HttpResponse(0, '', 'Could not start the request.');
        }
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => self::headerLines($headers),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_FOLLOWLOCATION => false,
            // Only web requests: never file://, gopher:// or other schemes a crafted URL might try.
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'BM-Matic/1.0 (+reviews sync)',
        ]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if (!is_string($response)) {
            return new HttpResponse($status, '', $error === '' ? 'The request failed.' : $error);
        }
        return new HttpResponse($status, $response, $status === 0 ? ($error === '' ? 'No response.' : $error) : '');
    }

    /** @param array<string, string> $headers */
    private function stream(string $method, string $url, array $headers, ?string $body): HttpResponse
    {
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", self::headerLines($headers)),
            'content' => $body ?? '',
            'timeout' => $this->timeout,
            'ignore_errors' => true,
            'follow_location' => 0,
        ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $http_response_header = [];
        $response = @file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m) === 1) {
                $status = (int) $m[1];
            }
        }
        if (!is_string($response)) {
            return new HttpResponse($status, '', 'The request failed.');
        }
        return new HttpResponse($status, $response, '');
    }

    /**
     * @param array<string, string> $headers
     * @return list<string>
     */
    private static function headerLines(array $headers): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . str_replace(["\r", "\n"], '', $value);
        }
        return $lines;
    }
}
