<?php
// shifts.php
session_start();

// Проверка авторизации
if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
    header("Location: login.php");
    exit();
}

// Подключение к БД
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

// Обработка удаления смены
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM shifts WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: shifts.php');
    exit();
}

// Получение списка смен с информацией о назначенных сотрудниках
$query = "SELECT s.*, 
    GROUP_CONCAT(CONCAT(e.full_name, ' (', sa.position, ')') SEPARATOR ', ') as employees
    FROM shifts s
    LEFT JOIN shift_assignments sa ON s.id = sa.shift_id
    LEFT JOIN employees e ON sa.employee_id = e.id
    GROUP BY s.id
    ORDER BY s.shift_date DESC, s.start_time";
$shifts = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление сменами</title>
    <link rel="stylesheet" href="styles/add.css">
</head>
<body>
    <div class="container">
        <h1>Управление сменами</h1>
        
        <div class="actions">
            <a href="add_shift.php" class="btn-add">Добавить смену</a>
            <a href="admin.php" class="btn-back">Вернуться в админ-панель</a>
        </div>

        <div class="shifts-list">
            <table>
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Время начала</th>
                        <th>Время окончания</th>
                        <th>Статус</th>
                        <th>Сотрудники</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $shift): ?>
                    <tr>
                        <td><?php echo date('d.m.Y', strtotime($shift['shift_date'])); ?></td>
                        <td><?php echo date('H:i', strtotime($shift['start_time'])); ?></td>
                        <td><?php echo date('H:i', strtotime($shift['end_time'])); ?></td>
                        <td>
                            <?php
                            $status_text = [
                                'planned' => 'Запланирована',
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершена'
                            ];
                            echo $status_text[$shift['status']];
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($shift['employees'] ?: 'Не назначены'); ?></td>
                        <td>
                            <a href="edit_shift.php?id=<?php echo $shift['id']; ?>" class="btn-edit">Редактировать</a>
                            <a href="manage_shift_employees.php?id=<?php echo $shift['id']; ?>" class="btn-manage">Управление сотрудниками</a>
                            <a href="shifts.php?delete=<?php echo $shift['id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Вы уверены, что хотите удалить эту смену?')">Удалить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
