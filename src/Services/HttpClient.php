<?php

namespace Lkn\WcCieloPaymentGateway\Services;

use Lkn\WcCieloPaymentGateway\Exceptions\NetworkException;

/**
 * HTTP Client service for API requests
 *
 * @since 1.25.0
 */
class HttpClient
{
    /**
     * Default timeout for requests
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Default headers
     */
    private const DEFAULT_HEADERS = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'User-Agent' => 'LknWcCieloPaymentGateway/1.25.0'
    ];

    /**
     * Make a GET request
     *
     * @param string $url Request URL
     * @param array $headers Additional headers
     * @param int $timeout Request timeout
     * @return array Response data
     * @throws NetworkException
     */
    public function get(string $url, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): array
    {
        return $this->request('GET', $url, null, $headers, $timeout);
    }

    /**
     * Make a POST request
     *
     * @param string $url Request URL
     * @param array|null $data Request data
     * @param array $headers Additional headers
     * @param int $timeout Request timeout
     * @return array Response data
     * @throws NetworkException
     */
    public function post(string $url, ?array $data = null, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): array
    {
        return $this->request('POST', $url, $data, $headers, $timeout);
    }

    /**
     * Make a PUT request
     *
     * @param string $url Request URL
     * @param array|null $data Request data
     * @param array $headers Additional headers
     * @param int $timeout Request timeout
     * @return array Response data
     * @throws NetworkException
     */
    public function put(string $url, ?array $data = null, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): array
    {
        return $this->request('PUT', $url, $data, $headers, $timeout);
    }

    /**
     * Make a DELETE request
     *
     * @param string $url Request URL
     * @param array $headers Additional headers
     * @param int $timeout Request timeout
     * @return array Response data
     * @throws NetworkException
     */
    public function delete(string $url, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): array
    {
        return $this->request('DELETE', $url, null, $headers, $timeout);
    }

    /**
     * Make HTTP request using WordPress wp_remote_request
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array|null $data Request data
     * @param array $headers Additional headers
     * @param int $timeout Request timeout
     * @return array Response data
     * @throws NetworkException
     */
    private function request(string $method, string $url, ?array $data = null, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): array
    {
        $args = [
            'method' => $method,
            'timeout' => $timeout,
            'headers' => array_merge(self::DEFAULT_HEADERS, $headers),
            'sslverify' => true
        ];

        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }

        $response = \wp_remote_request($url, $args);

        if (\is_wp_error($response)) {
            throw new NetworkException(
                'HTTP request failed: ' . $response->get_error_message(),
                0,
                null,
                null,
                ['url' => $url, 'method' => $method]
            );
        }

        $response_code = \wp_remote_retrieve_response_code($response);
        $response_body = \wp_remote_retrieve_body($response);

        $decoded_body = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new NetworkException(
                'Invalid JSON response: ' . json_last_error_msg(),
                $response_code,
                null,
                null,
                ['url' => $url, 'method' => $method, 'response' => $response_body]
            );
        }

        if ($response_code >= 400) {
            throw new NetworkException(
                'HTTP error response: ' . $response_code,
                $response_code,
                null,
                $decoded_body['error_code'] ?? null,
                ['url' => $url, 'method' => $method, 'response' => $decoded_body]
            );
        }

        return [
            'status_code' => $response_code,
            'data' => $decoded_body,
            'headers' => \wp_remote_retrieve_headers($response)->getAll()
        ];
    }
}
