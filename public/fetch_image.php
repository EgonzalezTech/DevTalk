<?php
require_once __DIR__ . '/../src/config.php';

$url = $_GET['url'] ?? '';

function imageError(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

if (!is_string($url) || $url === '') {
    imageError(400, 'Invalid image URL.');
}

// Only allow images from trusted HTTPS domains.
$allowedHosts = ['images.unsplash.com'];

$parts = parse_url($url);

if (
    $parts === false ||
    strtolower($parts['scheme'] ?? '') !== 'https' ||
    !isset($parts['host']) ||
    !in_array(strtolower($parts['host']), $allowedHosts, true) ||
    isset($parts['user']) ||
    isset($parts['pass']) ||
    isset($parts['port']) ||
    isset($parts['fragment'])
) {
    imageError(400, 'Image URL not allowed.');
}

// Limit the amount of image data downloaded.
$maxBytes = 5 * 1024 * 1024;
$data = '';
$tooLarge = false;

$curl = curl_init($url);

if ($curl === false) {
    imageError(502, 'Could not load image.');
}

curl_setopt_array($curl, [
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_HEADER => false,
    CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$data, &$tooLarge, $maxBytes) {
        if (strlen($data) + strlen($chunk) > $maxBytes) {
            $tooLarge = true;
            return 0;
        }

        $data .= $chunk;
        return strlen($chunk);
    },
]);

$success = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($tooLarge) {
    imageError(413, 'Image is too large.');
}

if ($success === false || $status !== 200) {
    imageError(502, 'Could not load image.');
}

// Verify the downloaded content is an image.
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->buffer($data);

$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
];

if (!in_array($mime, $allowedTypes, true)) {
    imageError(415, 'Unsupported image type.');
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($data));

echo $data;
