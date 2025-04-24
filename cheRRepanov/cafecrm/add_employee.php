<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

include 'db.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $hire_date = trim($_POST['hire_date']);
    $salary = trim($_POST['salary']);

    if (empty($full_name) || empty($position)) {
        $error = 'Необходимо заполнить ФИО и должность';
    } else {
        $stmt = $db->prepare("INSERT INTO employees (full_name, position, phone, email, hire_date, salary) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $position, $phone, $email, $hire_date, $salary])) {
            $success = 'Сотрудник успешно добавлен';
        } else {
            $error = 'Ошибка при добавлении сотрудника';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить сотрудника</title>
    <link rel="stylesheet" href="styles/add.css">
</head>
<body>
    <div class="container">
        <h1>Добавить сотрудника</h1>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="employee-form">
            <div class="form-group">
                <label for="full_name">ФИО:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>

            <div class="form-group">
                <label for="position">Должность:</label>
                <select id="position" name="position" required>
                    <option value="">Выберите должность</option>
                    <option value="Повар">Повар</option>
                    <option value="Официант">Официант</option>
                    <option value="Бармен">Бармен</option>
                </select>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>

            <div class="form-group">
                <label for="hire_date">Дата приема на работу:</label>
                <input type="date" id="hire_date" name="hire_date" required>
            </div>

            <div class="form-group">
                <label for="salary">Зарплата:</label>
                <input type="number" id="salary" name="salary" step="0.01">
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-submit">Добавить</button>
                <a href="admin.php" class="btn-cancel">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html> 