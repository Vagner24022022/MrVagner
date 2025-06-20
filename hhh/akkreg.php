<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';

//--------------------------------------------------------------
// Быстрый JSON-ответ и выход
//--------------------------------------------------------------
function respond(int $code, string $msg): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// Принимаем только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Метод не поддерживается');
}

//--------------------------------------------------------------
// Читаем и валидируем входные поля
//--------------------------------------------------------------
$login    = trim($_POST['login']        ?? '');
$pass     = trim($_POST['password']     ?? '');
$fullname = trim($_POST['full_name']    ?? '');
$phone    = trim($_POST['phone']        ?? '');
$email    = trim($_POST['email']        ?? '');

if ($login === '' || strlen($login) < 3)         respond(400, 'Логин слишком короткий');
if (strlen($pass)  < 6)                           respond(400, 'Пароль слишком короткий');
if (strlen($fullname) < 5)                        respond(400, 'Некорректное ФИО');
if (!preg_match('/^\+7[\d\s-]{10,14}$/', $phone)) respond(400, 'Телефон должен быть в формате +7...');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   respond(400, 'Некорректный e-mail');

// Проверяем уникальность
$st = $pdo->prepare('SELECT 1 FROM users WHERE login = ? LIMIT 1');
$st->execute([$login]);
if ($st->fetch()) respond(409, 'Такой логин уже существует');

// Вставляем пользователя
$hash = password_hash($pass, PASSWORD_DEFAULT);
$st = $pdo->prepare('
    INSERT INTO users (login, password_hash, full_name, phone, email, role)
    VALUES (:login, :hash, :fullname, :phone, :email, :role)
');
$st->execute([
    ':login'    => $login,
    ':hash'     => $hash,
    ':fullname' => $fullname,
    ':phone'    => $phone,
    ':email'    => $email,
    ':role'     => 'client',
]);

// Автовход
$_SESSION['user_id'] = (int)$pdo->lastInsertId();
$_SESSION['login']   = $login;
$_SESSION['role']    = 'client';

respond(201, 'Регистрация успешна');
