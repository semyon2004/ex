<?php
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=module;charset=utf8mb4',
        'root',  // Имя пользователя MySQL (по умолчанию в XAMPP - root)
        '',      // Пароль (по умолчанию в XAMPP пустой)
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    // В production версии лучше записывать ошибки в лог, а не выводить напрямую
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

// Функция для безопасного получения значения из массива
function get_post_value($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// Функция для безопасного получения значения из GET параметров
function get_get_value($key, $default = '') {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// Функция для проверки авторизации
function check_auth() {
    global $db;
    
    if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
        return false;
    }

    $token = $_SESSION['token'];
    $stmt = $db->prepare("SELECT id, type FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    return $user && $user['type'] === 'admin';
}

// Функция для генерации случайного токена
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Функция для обновления токена пользователя
function update_user_token($user_id, $token) {
    global $db;
    $stmt = $db->prepare("UPDATE users SET token = ? WHERE id = ?");
    return $stmt->execute([$token, $user_id]);
}

// Функция для форматирования даты
function format_date($date) {
    return date('d.m.Y', strtotime($date));
}

// Функция для форматирования времени
function format_time($time) {
    return date('H:i', strtotime($time));
}