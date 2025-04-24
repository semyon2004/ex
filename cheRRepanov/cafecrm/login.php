<?php
session_start();

// Обработка выхода из системы
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Удаляем токен из сессии
    $_SESSION['token'] = '';
    // Перенаправляем на страницу авторизации
    header("Location: login.php");
    exit;
}

$db = new PDO('mysql:host=localhost; dbname=module; charset=utf8', 
'root', 
null, 
[PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// 1. Проверка наличия токена : локально ($_SESSION['token']) и сравнение с бд
//                  Если есть -> перекидываем на странцу пользователя / админа
//                  Если нет -> остаёмся на этой странице

$_SESSION['token'] = '';

// Проверка : существует ли токен и что он не пустой
if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
    $token = $_SESSION['token'];
    //
    $user = $db->query("SELECT id, type FROM users WHERE token = '$token'")->fetchALL();
    
    if (empty($user)) {
        $userType = $token[0]['type'];
        $isAdmin = $userType == 'admin';
        $isUser = $userType == 'user';
    }
    
    $isAdmin && header('Location: admin.php');
    $isUser && header('Location: user.php');
}
//  Проверака логина и пароля с БД , запись токена в БД, редирект
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Получить отправленные данные (логин и пароль)
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 2. Проверить переданы ли они, если нет вернуть ошибку
    if (empty($login) || empty($password)) {
        $error = 'Поля необходимо заполнить';
    } else {
        // 3. Проверяем не заблокирован ли пользователь
        $user = $db->query("SELECT id, password, type, blocked, amountAttempt FROM users WHERE login = '$login'")->fetch();
        
        if ($user && $user['blocked'] == 1) {
            $error = 'Пользователь заблокирован. Обратитесь к администрации';
        } else if ($user && $user['password'] === $password) {
            // Сбрасываем количество попыток при успешном входе
            $userId = $user['id'];
            $db->query("UPDATE users SET amountAttempt = 0, latest = NOW() WHERE id = $userId");
            
            // Генерируем токен
            $token = bin2hex(random_bytes(16));
            
            // Записываем токен в сессию
            $_SESSION['token'] = $token;
            
            // Записываем токен в БД
            $db->query("UPDATE users SET token = '$token' WHERE id = $userId");
            
            // Редирект в зависимости от типа пользователя
            if ($user['type'] === 'admin') {
                header('Location: admin.php');
                exit;
            } else {
                header('Location: user.php');
                exit;
            }
        } else {
            // Увеличиваем счетчик неудачных попыток
            if ($user) {
                $newAttempt = $user['amountAttempt'] + 1;
                $userId = $user['id'];
                
                // Если достигнут лимит попыток - блокируем пользователя
                if ($newAttempt >= 3) {
                    $db->query("UPDATE users SET blocked = 1, amountAttempt = $newAttempt WHERE id = $userId");
                    $error = 'Пользователь заблокирован. Обратитесь к администрации';
                } else {
                    $db->query("UPDATE users SET amountAttempt = $newAttempt WHERE id = $userId");
                    $error = 'Неверный логин или пароль. Осталось попыток: ' . (3 - $newAttempt);
                }
            } else {
                $error = 'Неверный логин или пароль';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <title>Авторизация</title>
</head>
<body>
    <div class="auth-container">
        <h1>Авторизация</h1>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="login">Введите логин</label>
                <input type="text" id="login" name="login" required>
            </div>
            <div class="form-group">
                <label for="password">Введите пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>