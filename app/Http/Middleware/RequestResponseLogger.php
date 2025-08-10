<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestResponseLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log the incoming request
        $this->logRequest($request);

        // Process the request
        $response = $next($request);

        // Calculate execution time
        $executionTime = microtime(true) - $startTime;

        // Log the response
        $this->logResponse($response, $executionTime);

        return $response;
    }

    /**
     * Log the incoming request
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'type' => 'request',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'user_role' => $request->user()?->role?->name,
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'body' => $this->sanitizeBody($request->all()),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('api')->info('API Request', $logData);
    }

    /**
     * Log the response
     */
    private function logResponse(Response $response, float $executionTime): void
    {
        $logData = [
            'type' => 'response',
            'status_code' => $response->getStatusCode(),
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'content_type' => $response->headers->get('Content-Type'),
            'body' => $this->sanitizeResponseBody($response->getContent()),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('api')->info('API Response', $logData);
    }

    /**
     * Sanitize headers to remove sensitive information
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-csrf-token'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Sanitize request body to remove sensitive information
     */
    private function sanitizeBody(array $body): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key'];

        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '***REDACTED***';
            }
        }

        return $body;
    }

    /**
     * Sanitize response body
     */
    private function sanitizeResponseBody(string $content): string
    {
        // Limit response body size to prevent log bloat
        if (strlen($content) > 1000) {
            return substr($content, 0, 1000) . '... [TRUNCATED]';
        }

        return $content;
    }
}
