<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$asunto = isset($_POST['asunto']) ? trim($_POST['asunto']) : '';
if ($nombre === '' || $apellido === '' || $email === '' || $telefono === '' || $asunto === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}
if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($asunto, 'UTF-8') > 250) $asunto = mb_substr($asunto, 0, 250, 'UTF-8');
} else {
    if (strlen($asunto) > 250) $asunto = substr($asunto, 0, 250);
}
$sanitize = function ($v) {
    $v = trim($v);
    $v = str_replace(["\r", "\n"], ' ', $v);
    if (preg_match('/^[=+\-@]/', $v)) $v = "'" . $v;
    return $v;
};
$nombre = $sanitize($nombre);
$apellido = $sanitize($apellido);
$email = $sanitize($email);
$telefono = $sanitize($telefono);
$asunto = $sanitize($asunto);
$dir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
    // Add index.html to prevent directory listing
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.html', '<!DOCTYPE html><title></title>');
}
$file = $dir . DIRECTORY_SEPARATOR . 'contactos.csv';
$exists = file_exists($file);
// Asegurar BOM UTF-8 para compatibilidad con Excel
if ($exists) {
    $fh = @fopen($file, 'r');
    if ($fh) {
        $prefix = fread($fh, 3);
        fclose($fh);
        if ($prefix !== "\xEF\xBB\xBF") {
            $contents = @file_get_contents($file);
            if ($contents !== false) {
                $fix = @fopen($file, 'wb');
                if ($fix) {
                    fwrite($fix, "\xEF\xBB\xBF");
                    fwrite($fix, $contents);
                    fclose($fix);
                }
            }
        }
    }
}
$fp = @fopen($file, 'a');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo escribir el log']);
    exit;
}
if (!$exists || filesize($file) <= 3) {
    // Escribir BOM si el archivo es nuevo o sólo tiene BOM
    if (!$exists) {
        fwrite($fp, "\xEF\xBB\xBF");
    }
    fputcsv($fp, ['fecha', 'nombre', 'apellido', 'email', 'telefono', 'asunto'], ';');
}
fputcsv($fp, [date('c'), $nombre, $apellido, $email, $telefono, $asunto], ';');
fclose($fp);
echo json_encode(['success' => true]);
