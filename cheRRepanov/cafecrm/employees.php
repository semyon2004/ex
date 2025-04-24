<?php
// employees.php
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

// Обработка удаления сотрудника
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: employees.php');
    exit();
}

// Получение списка сотрудников
$stmt = $db->query("SELECT * FROM employees ORDER BY full_name");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление сотрудниками</title>
    <link rel="stylesheet" href="styles/add.css">
</head>
<body>
    <div class="container">
        <h1>Управление сотрудниками</h1>
        
        <div class="actions">
            <a href="add_employee.php" class="btn-add">Добавить сотрудника</a>
            <a href="admin.php" class="btn-back">Вернуться в админ-панель</a>
        </div>

        <div class="employees-list">
            <table>
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Должность</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата приема</th>
                        <th>Зарплата</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                        <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($employee['hire_date'])); ?></td>
                        <td><?php echo number_format($employee['salary'], 2); ?> ₽</td>
                        <td><?php echo $employee['status'] === 'active' ? 'Активен' : 'Неактивен'; ?></td>
                        <td>
                            <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" class="btn-edit">Редактировать</a>
                            <a href="employees.php?delete=<?php echo $employee['id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Вы уверены, что хотите удалить этого сотрудника?')">Удалить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
