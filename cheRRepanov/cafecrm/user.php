<?php
session_start();

// 1. Проверка авторизации
// Если пользователь не авторизован (нет или не правильный токен) -> на страницу login
if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
    header("Location: login.php");
    exit();
}

// Подключение к БД
$db = new PDO('mysql:host=localhost; dbname=module; charset=utf8', 
'root', 
null, 
[PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Получаем информацию о пользователе по токену
$token = $_SESSION['token'];
$user = $db->query("SELECT id, login, type, name, surname FROM users WHERE token = '$token'")->fetch();

// Если пользователь не найден (токен не совпадает)
if (!$user) {
    $_SESSION['token'] = '';
    header("Location: login.php");
    exit();
}

// Обновляем дату последней активности
$userId = $user['id'];
$db->query("UPDATE users SET latest = NOW() WHERE id = $userId");

// Если тип пользователя = admin -> на страницу admin
if ($user['type'] == 'admin') {
    header("Location: admin.php");
    exit();
}

// Если тип пользователя = user -> остаемся на этой странице
// Здесь ничего не делаем, так как мы уже на нужной странице

$error = '';
$success = '';

// 2. Обновление пароля
// Отправляем форму на этот же файл
// Обрабатываем отправку через проверку $_SERVER['REQUEST_METHOD'] = POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Проверяем что поля переданы, пароли совпадают
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Проверяем, что пароли введены
    if (empty($newPassword)) {
        $error = 'Введите новый пароль';
    } elseif (empty($confirmPassword)) {
        $error = 'Подтвердите пароль';
    } 
    // Проверяем совпадение паролей
    elseif ($newPassword !== $confirmPassword) {
        $error = 'Пароли не совпадают';
    } else {
        // По токену обновляем пароль пользователю
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$newPassword, $userId]);
        
        if ($result) {
            $success = 'Пароль успешно изменен';
        } else {
            $error = 'Ошибка при изменении пароля';
        }
    }
}

// 3. Отображение ФИО пользователя и типа пользователя
// Данные уже загружены из базы в переменную $user

// 4. Кнопка выхода из учетной записи
// Реализуем обработку параметра logout в текущем файле
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Получаем ID пользователя из сессии
    $userId = $user['id'];
    
    // Сбрасываем токен в БД
    $db->query("UPDATE users SET token = '' WHERE id = $userId");
    
    // Сбрасываем токен в сессии
    $_SESSION['token'] = '';
    
    // Перенаправляем на страницу входа
    header("Location: login.php");
    exit();
}

// При клике:
// Сбрасываем $_SESSION['token']
// Сбрасываем токен в БД
// Переходим на страницу login.php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <title>Пользователь
    </title>
</head>
<body>
    <div class="login">
        <form method="POST" action="user.php">
            <h1>Пользователь</h1>
            <p>Вы вошли как: <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></p>
            <p>Тип пользователя: <?php echo htmlspecialchars($user['type']); ?></p>
            
            <label for="password">
                Новый пароль
                <?php if($error === 'Введите новый пароль'): ?><span class="error">Необходимо заполнить</span><?php endif; ?>
            </label>
            <input type="password" name="password" id="password" required>
            
            <label for="confirm_password">
                Подтвердите пароль
                <?php if($error === 'Подтвердите пароль'): ?><span class="error">Необходимо заполнить</span><?php endif; ?>
            </label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            
            <button type="submit">Сменить пароль</button>
            
            <?php if(!empty($error)): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
            <?php if(!empty($success)): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>
            
            <p><a href="user.php?logout=1">Выйти</a></p>
        </form>
    </div>
</body>
</html>