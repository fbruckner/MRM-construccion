<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$allowedEmail = 'tobi.bruckner02@gmail.com';
$passwordHash = '$2y$10$kV8O5x6i6uCBoaIkOkk7VOBSP5SGzGFft1ghnyIEllrlTsPQU8AcC';
$action = isset($_GET['action']) ? $_GET['action'] : '';

$respondJson = function (int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
};

$isAuthenticated = function () use ($allowedEmail): bool {
    return !empty($_SESSION['admin_authenticated']) && isset($_SESSION['admin_email']) && $_SESSION['admin_email'] === $allowedEmail;
};

if ($action === 'status') {
    $respondJson(200, ['authenticated' => $isAuthenticated()]);
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $respondJson(405, ['success' => false, 'message' => 'Método no permitido']);
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    $email = isset($data['email']) ? trim((string) $data['email']) : '';
    $password = isset($data['password']) ? (string) $data['password'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $respondJson(400, ['success' => false, 'message' => 'Credenciales inválidas']);
    }

    if (!hash_equals($allowedEmail, $email) || !password_verify($password, $passwordHash)) {
        session_regenerate_id(true);
        $respondJson(401, ['success' => false, 'message' => 'Correo o contraseña incorrectos']);
    }

    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_email'] = $allowedEmail;
    $respondJson(200, ['success' => true]);
}

if ($action === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $respondJson(405, ['success' => false, 'message' => 'Método no permitido']);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    $respondJson(200, ['success' => true]);
}

if ($action === 'export') {
    if (!$isAuthenticated()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Acceso denegado';
        exit;
    }

    $file = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'contactos.csv';
    if (!file_exists($file)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No se encontró el log';
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contactos.csv"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    readfile($file);
    exit;
}

$respondJson(404, ['success' => false, 'message' => 'Acción no encontrada']);
