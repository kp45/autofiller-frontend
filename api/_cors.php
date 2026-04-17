<?php

function get_allowed_cors_origins(): array
{
    $origins = [
        'http://localhost:8001',
        'http://127.0.0.1:8001',
        'http://localhost:5500',
        'http://127.0.0.1:5500',
    ];

    $envPath = __DIR__ . '/../.env';
    if (is_readable($envPath)) {
        $env = @parse_ini_file($envPath, false, INI_SCANNER_RAW);
        if (is_array($env) && !empty($env['FRONTEND_ORIGIN'])) {
            $origins[] = trim((string)$env['FRONTEND_ORIGIN']);
        }
    }

    $normalized = [];
    foreach ($origins as $origin) {
        $clean = rtrim(strtolower(trim((string)$origin)), '/');
        if ($clean !== '') {
            $normalized[$clean] = true;
        }
    }

    return array_keys($normalized);
}

function send_cors_headers(string $methods): void
{
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    $serverHost = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    $serverHost = explode(':', $serverHost)[0] ?? '';

    if ($origin !== '') {
        $originKey = rtrim(strtolower($origin), '/');
        $isAllowed = in_array($originKey, get_allowed_cors_origins(), true);

        if (!$isAllowed) {
            $originHost = strtolower((string)(parse_url($origin, PHP_URL_HOST) ?? ''));
            if ($originHost !== '' && $serverHost !== '' && $originHost === $serverHost) {
                $isAllowed = true;
            }
        }

        if ($isAllowed) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
    }

    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: ' . $methods);
    header('Access-Control-Allow-Headers: Content-Type');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
