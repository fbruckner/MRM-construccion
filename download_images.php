<?php
header('Content-Type: application/json; charset=utf-8');
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'img';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}
$images = [
    // Hero background: JPEG fallback + WebP
    'hero-bg.jpg' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80',
    'hero-bg-1920.webp' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?ixlib=rb-1.2.1&fm=webp&fit=crop&w=1920&q=80',
    // Trabajos 1
    'trabajos-1-400.jpg' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
    'trabajos-1-800.jpg' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
    'trabajos-1-400.webp' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-1.2.1&fm=webp&fit=crop&w=400&q=80',
    'trabajos-1-800.webp' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-1.2.1&fm=webp&fit=crop&w=800&q=80',
    // Trabajos 2
    'trabajos-2-400.jpg' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
    'trabajos-2-800.jpg' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
    'trabajos-2-400.webp' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&fm=webp&fit=crop&w=400&q=80',
    'trabajos-2-800.webp' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&fm=webp&fit=crop&w=800&q=80',
    // Trabajos 3
    'trabajos-3-400.jpg' => 'https://images.unsplash.com/photo-1507089947368-19c1da9775ae?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
    'trabajos-3-800.jpg' => 'https://images.unsplash.com/photo-1507089947368-19c1da9775ae?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
    'trabajos-3-400.webp' => 'https://images.unsplash.com/photo-1507089947368-19c1da9775ae?ixlib=rb-1.2.1&fm=webp&fit=crop&w=400&q=80',
    'trabajos-3-800.webp' => 'https://images.unsplash.com/photo-1507089947368-19c1da9775ae?ixlib=rb-1.2.1&fm=webp&fit=crop&w=800&q=80',
];
$result = [];
function fetchRemote($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MRM-Downloader');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code >= 200 && $code < 300 && $data !== false) return $data;
        return false;
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: MRM-Downloader\r\n"
            ]
        ]);
        return @file_get_contents($url, false, $ctx);
    }
}
foreach ($images as $name => $url) {
    $path = $baseDir . DIRECTORY_SEPARATOR . $name;
    if (file_exists($path) && filesize($path) > 0) {
        $result[$name] = 'ok';
        continue;
    }
    $data = fetchRemote($url);
    if ($data === false) {
        $result[$name] = 'error';
        continue;
    }
    $ok = @file_put_contents($path, $data);
    $result[$name] = $ok !== false ? 'ok' : 'error';
}
echo json_encode(['success' => true, 'files' => $result]);
