<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Получаем информацию о пользователе по токену
$token = $_SESSION['token'];
$admin = $db->query("SELECT id, login, type FROM users WHERE token = '$token'")->fetch();

// Если пользователь не найден или не админ
if (!$admin || $admin['type'] !== 'admin') {
    $_SESSION['token'] = '';
    header("Location: login.php");
    exit();
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shift_date = trim($_POST['shift_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $status = trim($_POST['status']);

    if (empty($shift_date) || empty($start_time) || empty($end_time)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        $stmt = $db->prepare("INSERT INTO shifts (shift_date, start_time, end_time, status) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$shift_date, $start_time, $end_time, $status])) {
            $success = 'Смена успешно добавлена';
        } else {
            $error = 'Ошибка при добавлении смены';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить смену</title>
    <link rel="stylesheet" href="styles/add.css">
</head>
<body>
    <div class="container">
        <h1>Добавить смену</h1>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="shift-form">
            <div class="form-group">
                <label for="shift_date">Дата смены:</label>
                <input type="date" id="shift_date" name="shift_date" required>
            </div>

            <div class="form-group">
                <label for="start_time">Время начала:</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>

            <div class="form-group">
                <label for="end_time">Время окончания:</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>

            <div class="form-group">
                <label for="status">Статус:</label>
                <select id="status" name="status" required>
                    <option value="planned">Запланирована</option>
                    <option value="in_progress">В процессе</option>
                    <option value="completed">Завершена</option>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-submit">Добавить</button>
                <a href="shifts.php" class="btn-cancel">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>