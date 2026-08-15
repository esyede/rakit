<?php

/*
|--------------------------------------------------------------------------
| Local Mock Endpoint For The Curl Test Suite
|--------------------------------------------------------------------------
|
| Echoes the incoming request back as JSON, mirroring the shape served by
| https://rakit.esyede.my.id/mock so the curl tests can run against a local
| PHP built-in server instead of hammering the production host:
|
|     {"headers": {...}, "method": "GET", "queries": {...},
|      "data": {..., "json": null, "stdin": ""}}
|
| Requesting /mock/<seconds> sleeps, which is how the client-side timeout is
| exercised. The sleep is capped (see MOCK_MAX_SLEEP) so a stray request can
| never pin a worker for minutes at a time.
|
| Start it manually with:
|
|     php -S 127.0.0.1:8910 tests/mock/server.php
|
| Compatible with PHP 5.4 through 8.5.
|
*/

define('MOCK_MAX_SLEEP', 3);

/**
 * Collect request headers from $_SERVER, normalizing names the way the
 * production mock does: 'header1' becomes 'Header1', 'user-agent' becomes
 * 'User-Agent'. getallheaders() is not used because it is unavailable on
 * the built-in server before PHP 7.3.
 *
 * @return array
 */
function mock_headers()
{
    $headers = [];

    foreach ($_SERVER as $key => $value) {
        if (0 === strpos($key, 'HTTP_')) {
            $name = substr($key, 5);
        } elseif ('CONTENT_TYPE' === $key || 'CONTENT_LENGTH' === $key) {
            $name = $key;
        } else {
            continue;
        }

        $words = explode('_', strtolower($name));

        foreach ($words as $index => $word) {
            $words[$index] = ucfirst($word);
        }

        $headers[implode('-', $words)] = $value;
    }

    return $headers;
}

$path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
$path = rtrim((string) $path, '/');

// /mock/<seconds> stalls the response so the client-side timeout can be tested.
if (preg_match('#^/mock/(\d+)$#', $path, $matches)) {
    sleep(min((int) $matches[1], MOCK_MAX_SLEEP));
} elseif ('/mock' !== $path && '' !== $path) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Not found', 'path' => $path]);
    return true;
}

$stdin = (string) file_get_contents('php://input');
$json = json_decode($stdin);

// Uploads are merged in as-is: $_FILES['file']['size'] and the multi-file
// $_FILES['files']['size']['owl.gif'] shape are both what the tests assert on.
$data = array_merge($_POST, $_FILES);
$data['json'] = (JSON_ERROR_NONE === json_last_error()) ? $json : null;
$data['stdin'] = $stdin;

header('Content-Type: application/json');

echo json_encode([
    'headers' => mock_headers(),
    'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET',
    'queries' => $_GET,
    'data' => $data,
]);

return true;
