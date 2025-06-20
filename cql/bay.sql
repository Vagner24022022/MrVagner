/* =========================================================
   UNIVERSAL DDL: «Cleaning Service»
   Базовый синтаксис — SQLite3
   ---------------------------------------------------------
   Под каждой «ключевой» строкой сразу даны готовые варианты
   для MySQL и PostgreSQL — копируйте, не задумываясь.
========================================================= */

-- ---------- 1. Пользователи ----------
CREATE TABLE users (
    -- id (PK)
    id            INTEGER PRIMARY KEY AUTOINCREMENT,         -- SQLite (файл)
    -- id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- MySQL / MariaDB
    -- id            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- PostgreSQL

    -- Логин (уникальный)
    login         TEXT    NOT NULL UNIQUE,                    -- SQLite / PG
    -- login         VARCHAR(50) NOT NULL UNIQUE,                 -- MySQL

    -- Хэш пароля (bcrypt ≈ 60‑72 символа)
    password_hash TEXT    NOT NULL,

    -- ФИО, телефон, e‑mail
    full_name     TEXT    NOT NULL,
    phone         TEXT    NOT NULL,
    email         TEXT    NOT NULL,

    -- Роль пользователя
    role          TEXT    NOT NULL DEFAULT 'client',          -- SQLite
    -- role          ENUM('client','worker','admin') NOT NULL DEFAULT 'client', -- MySQL
    -- role          TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client','worker','admin')), -- PostgreSQL

    CONSTRAINT uq_users_login UNIQUE (login)
);

-- ---------- 2. Каталог услуг ----------
CREATE TABLE services (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,             -- SQLite
    -- id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- MySQL
    -- id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- PostgreSQL

    name      TEXT UNIQUE NOT NULL,                          -- SQLite / PG
    -- name      VARCHAR(100) UNIQUE NOT NULL,                    -- MySQL

    is_active INTEGER NOT NULL DEFAULT 1                     -- 1 = активна, 0 = скрыта
);

-- ---------- 3. Заявки ----------
CREATE TABLE applications (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,        -- SQLite
    -- id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- MySQL
    -- id             INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- PostgreSQL

    user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    service_id     INTEGER NOT NULL REFERENCES services(id),

    custom_service TEXT,                                     -- Заполняется, если service_id = 0
    address        TEXT NOT NULL,
    phone          TEXT NOT NULL,
    visit_date     DATE NOT NULL,
    visit_time     TIME NOT NULL,

    -- Тип оплаты
    payment_type   TEXT CHECK (payment_type IN ('cash','card')) NOT NULL, -- SQLite / PG
    -- payment_type   ENUM('cash','card') NOT NULL,                        -- MySQL

    -- Статус заявки
    status         TEXT NOT NULL DEFAULT 'new'
                   CHECK (status IN ('new','in_progress','done','cancelled')), -- SQLite / PG
    -- status         ENUM('new','in_progress','done','cancelled') NOT NULL DEFAULT 'new', -- MySQL

    CONSTRAINT fk_app_user FOREIGN KEY (user_id)    REFERENCES users(id)     ON DELETE CASCADE,
    CONSTRAINT fk_app_srv  FOREIGN KEY (service_id) REFERENCES services(id)
);

-- ---------- 4. История смены статусов (необязательно) ----------
CREATE TABLE application_status_history (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,        -- SQLite
    -- id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- MySQL
    -- id             INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- PostgreSQL

    application_id INTEGER NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    changed_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    old_status     TEXT,
    new_status     TEXT NOT NULL,
    reason         TEXT
);

-- Конец файла
