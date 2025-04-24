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
$shift = null;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM shifts WHERE id = ?");
    $stmt->execute([$id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shift) {
        header('Location: shifts.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $shift_date = trim($_POST['shift_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $status = trim($_POST['status']);

    if (empty($shift_date) || empty($start_time) || empty($end_time)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        $stmt = $db->prepare("UPDATE shifts SET shift_date = ?, start_time = ?, end_time = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$shift_date, $start_time, $end_time, $status, $id])) {
            $success = 'Данные смены обновлены';
            // Обновляем данные для отображения
            $shift = [
                'id' => $id,
                'shift_date' => $shift_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'status' => $status
            ];
        } else {
            $error = 'Ошибка при обновлении данных';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование смены</title>
    <link rel="stylesheet" href="styles/add.css">
</head>
<body>
    <div class="container">
        <h1>Редактирование смены</h1>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="shift-form">
            <input type="hidden" name="id" value="<?php echo $shift['id']; ?>">
            
            <div class="form-group">
                <label for="shift_date">Дата смены:</label>
                <input type="date" id="shift_date" name="shift_date" value="<?php echo $shift['shift_date']; ?>" required>
            </div>

            <div class="form-group">
                <label for="start_time">Время начала:</label>
                <input type="time" id="start_time" name="start_time" value="<?php echo $shift['start_time']; ?>" required>
            </div>

            <div class="form-group">
                <label for="end_time">Время окончания:</label>
                <input type="time" id="end_time" name="end_time" value="<?php echo $shift['end_time']; ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Статус:</label>
                <select id="status" name="status" required>
                    <option value="planned" <?php echo $shift['status'] === 'planned' ? 'selected' : ''; ?>>Запланирована</option>
                    <option value="in_progress" <?php echo $shift['status'] === 'in_progress' ? 'selected' : ''; ?>>В процессе</option>
                    <option value="completed" <?php echo $shift['status'] === 'completed' ? 'selected' : ''; ?>>Завершена</option>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-submit">Сохранить</button>
                <a href="shifts.php" class="btn-cancel">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html> 