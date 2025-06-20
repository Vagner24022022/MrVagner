<?php
/**
 * Универсальное подключение к БД через PDO.
 *
 * ─────────── КАК ПЕРЕКЛЮЧАТЬ БАЗУ ───────────
 * 1. Способ «без кода» (реком.):  
 *    задайте переменные окружения перед запуском
 *
 *      export DB_DRIVER=pgsql        # sqlite | mysql | pgsql
 *      export DB_HOST=localhost
 *      export DB_PORT=5432
 *      export DB_NAME=cleaning_service
 *      export DB_USER=myuser
 *      export DB_PASS=secret
 *
 * 2. Способ «в коде»: раскомментируйте нужный
 *    блок в секции SWITCH BELOW и/или правьте
 *    значения по-умолчанию.
 *
 * База по-умолчанию — SQLite (файл в том же каталоге).
 * Для MySQL и PostgreSQL обязательно создать БД
 * `cleaning_service` и прогнать DDL.
 */

declare(strict_types=1);

//--------------------------------------------------------------------
// 1. Читаем переменные окружения или берём дефолты
//--------------------------------------------------------------------
$driver = getenv('DB_DRIVER') ?: 'sqlite';   // sqlite | mysql | pgsql
$host   = getenv('DB_HOST')   ?: '127.0.0.1';
$port   = getenv('DB_PORT')   ?: null;       // подставится ниже
$dbname = getenv('DB_NAME')   ?: 'cleaning_service';
$user   = getenv('DB_USER')   ?: 'root';
$pass   = getenv('DB_PASS')   ?: '';
$charset= 'utf8mb4';                         // MySQL: кодировка

//--------------------------------------------------------------------
// 2. Формируем DSN в зависимости от драйвера
//--------------------------------------------------------------------
switch (strtolower($driver)) {

    //--------------------------- SQLite -----------------------------
    // ① ничего устанавливать не нужно;
    // ② будет создан файл cleaning_service.db рядом с проектом.
    case 'sqlite':
        $dsn  = "sqlite:" . __DIR__ . "/cleaning_service.db";
        $user = $pass = null;          // для SQLite не используются
        break;

    //------------------------ MySQL / MariaDB -----------------------
    // ① установить сервер MySQL/MariaDB;
    // ② создать БД:   CREATE DATABASE cleaning_service;
    // ③ (при необходимости) создать учётку и дать ей права:
    //      CREATE USER 'clean'@'%' IDENTIFIED BY 'secret';
    //      GRANT ALL ON cleaning_service.* TO 'clean'@'%';
    // ④ задать      DB_DRIVER=mysql
    case 'mysql':
        $port = $port ?: 3306;
        $dsn  = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
        break;

    //--------------------------- PostgreSQL -------------------------
    // ① установить сервер PostgreSQL;
    // ② создать БД и роль (пользователя):
    //      CREATE DATABASE cleaning_service;
    //      CREATE USER clean WITH PASSWORD 'secret';
    //      GRANT ALL PRIVILEGES ON DATABASE cleaning_service TO clean;
    // ③ задать      DB_DRIVER=pgsql
    case 'pgsql':
    case 'postgresql':
        $driver = 'pgsql';              // унифицируем
        $port   = $port ?: 5432;
        $dsn    = "pgsql:host=$host;port=$port;dbname=$dbname";
        break;

    default:
        http_response_code(500);
        exit("Unsupported DB_DRIVER: $driver");
}

//--------------------------------------------------------------------
// 3. Создаём PDO-подключение
//--------------------------------------------------------------------
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit("DB connection error: " . $e->getMessage());
}
