<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';

function respond(int $code, string $msg): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Метод не поддерживается');
}

$login = trim($_POST['login']    ?? '');
$pass  = trim($_POST['password'] ?? '');

if ($login === '' || $pass === '') respond(400, 'Пустой логин или пароль');

// Ищем пользователя
$st = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE login = ? LIMIT 1');
$st->execute([$login]);
$user = $st->fetch();

if (!$user || !password_verify($pass, $user['password_hash'])) {
    respond(401, 'Неверный логин или пароль');
}

// Всё ок → новая сессия
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['login']   = $login;
$_SESSION['role']    = $user['role'];

respond(200, 'Успешный вход');
