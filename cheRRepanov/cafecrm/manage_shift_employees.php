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
$shift_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получение информации о смене
$stmt = $db->prepare("SELECT * FROM shifts WHERE id = ?");
$stmt->execute([$shift_id]);
$shift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shift) {
    header('Location: shifts.php');
    exit();
}

// Обработка добавления сотрудника на смену
if (isset($_POST['add_employee'])) {
    $employee_id = (int)$_POST['employee_id'];
    $position = $_POST['position'];
    
    // Проверка, не назначен ли уже сотрудник на эту смену
    $stmt = $db->prepare("SELECT id FROM shift_assignments WHERE shift_id = ? AND employee_id = ?");
    $stmt->execute([$shift_id, $employee_id]);
    
    if ($stmt->fetch()) {
        $error = 'Этот сотрудник уже назначен на данную смену';
    } else {
        $stmt = $db->prepare("INSERT INTO shift_assignments (shift_id, employee_id, position) VALUES (?, ?, ?)");
        if ($stmt->execute([$shift_id, $employee_id, $position])) {
            $success = 'Сотрудник успешно добавлен на смену';
        } else {
            $error = 'Ошибка при добавлении сотрудника на смену';
        }
    }
}

// Обработка удаления сотрудника со смены
if (isset($_GET['remove'])) {
    $assignment_id = (int)$_GET['remove'];
    $stmt = $db->prepare("DELETE FROM shift_assignments WHERE id = ? AND shift_id = ?");
    if ($stmt->execute([$assignment_id, $shift_id])) {
        $success = 'Сотрудник удален со смены';
    }
}

// Получение списка сотрудников на смене
$stmt = $db->prepare("
    SELECT sa.id as assignment_id, e.*, sa.position as assigned_position 
    FROM shift_assignments sa 
    JOIN employees e ON sa.employee_id = e.id 
    WHERE sa.shift_id = ?
");
$stmt->execute([$shift_id]);
$assigned_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение списка всех активных сотрудников для выбора
$stmt = $db->query("SELECT * FROM employees WHERE status = 'active' ORDER BY full_name");
$all_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление сотрудниками на смене</title>
    <link rel="stylesheet" href="styles/add.css">
</head>
<body>
    <div class="container">
        <h1>Управление сотрудниками на смене</h1>
        <h2>Смена: <?php echo date('d.m.Y', strtotime($shift['shift_date'])); ?> 
            (<?php echo date('H:i', strtotime($shift['start_time'])); ?> - 
            <?php echo date('H:i', strtotime($shift['end_time'])); ?>)</h2>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Форма добавления сотрудника на смену -->
        <div class="add-employee-form">
            <h3>Добавить сотрудника на смену</h3>
            <form method="POST" class="form-inline">
                <div class="form-group">
                    <label for="employee_id">Сотрудник:</label>
                    <select name="employee_id" id="employee_id" required>
                        <option value="">Выберите сотрудника</option>
                        <?php foreach ($all_employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['full_name']); ?> 
                                (<?php echo htmlspecialchars($employee['position']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="position">Должность на смене:</label>
                    <select name="position" id="position" required>
                        <option value="Повар">Повар</option>
                        <option value="Официант">Официант</option>
                        <option value="Бармен">Бармен</option>
                    </select>
                </div>

                <button type="submit" name="add_employee" class="btn-add">Добавить</button>
            </form>
        </div>

        <!-- Список назначенных сотрудников -->
        <div class="assigned-employees">
            <h3>Назначенные сотрудники</h3>
            <?php if ($assigned_employees): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Основная должность</th>
                            <th>Должность на смене</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assigned_employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['position']); ?></td>
                            <td><?php echo htmlspecialchars($employee['assigned_position']); ?></td>
                            <td>
                                <a href="?id=<?php echo $shift_id; ?>&remove=<?php echo $employee['assignment_id']; ?>" 
                                   class="btn-delete"
                                   onclick="return confirm('Вы уверены, что хотите удалить сотрудника со смены?')">
                                    Удалить со смены
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>На эту смену пока не назначено ни одного сотрудника</p>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="shifts.php" class="btn-back">Вернуться к списку смен</a>
        </div>
    </div>
</body>
</html>