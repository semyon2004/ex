<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Получаем информацию о пользователе по токену
$token = $_SESSION['token'];
try {
    $admin = $db->query("SELECT id, login, type FROM users WHERE token = " . $db->quote($token))->fetch();

    // Если пользователь не найден или не админ
    if (!$admin || $admin['type'] !== 'admin') {
        $_SESSION['token'] = '';
        header("Location: login.php");
        exit();
    }

    $error = $success = '';
    $employee = [
        'id' => '',
        'full_name' => '',
        'position' => '',
        'phone' => '',
        'email' => '',
        'hire_date' => date('Y-m-d'),
        'salary' => '',
        'status' => 'active'
    ];

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $temp_employee = $stmt->fetch();
        
        if ($temp_employee) {
            $employee = array_merge($employee, $temp_employee);
        } else {
            header('Location: employees.php');
            exit();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)$_POST['id'];
        $full_name = trim($_POST['full_name']);
        $position = trim($_POST['position']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $hire_date = trim($_POST['hire_date']);
        $salary = trim($_POST['salary']);
        $status = $_POST['status'];

        if (empty($full_name) || empty($position)) {
            $error = 'Необходимо заполнить ФИО и должность';
        } else {
            $stmt = $db->prepare("UPDATE employees SET 
                full_name = ?, 
                position = ?, 
                phone = ?, 
                email = ?, 
                hire_date = ?, 
                salary = ?,
                status = ?
                WHERE id = ?");
            
            if ($stmt->execute([$full_name, $position, $phone, $email, $hire_date, $salary, $status, $id])) {
                $success = 'Данные сотрудника обновлены';
                $employee = [
                    'id' => $id,
                    'full_name' => $full_name,
                    'position' => $position,
                    'phone' => $phone,
                    'email' => $email,
                    'hire_date' => $hire_date,
                    'salary' => $salary,
                    'status' => $status
                ];
            } else {
                $error = 'Ошибка при обновлении данных';
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Ошибка базы данных: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование сотрудника</title>
    <link rel="stylesheet" href="styles/add.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .employee-form {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: grid;
            gap: 8px;
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-submit,
        .btn-cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
        }

        .btn-submit {
            background-color: #28a745;
            color: white;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .btn-submit:hover {
            background-color: #218838;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Редактирование сотрудника</h1>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="employee-form">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($employee['id']); ?>">
            
            <div class="form-group">
                <label for="full_name">ФИО:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="position">Должность:</label>
                <select id="position" name="position" required>
                    <option value="Повар" <?php echo $employee['position'] === 'Повар' ? 'selected' : ''; ?>>Повар</option>
                    <option value="Официант" <?php echo $employee['position'] === 'Официант' ? 'selected' : ''; ?>>Официант</option>
                    <option value="Бармен" <?php echo $employee['position'] === 'Бармен' ? 'selected' : ''; ?>>Бармен</option>
                </select>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>">
            </div>

            <div class="form-group">
                <label for="hire_date">Дата приема на работу:</label>
                <input type="date" id="hire_date" name="hire_date" value="<?php echo $employee['hire_date']; ?>" required>
            </div>

            <div class="form-group">
                <label for="salary">Зарплата:</label>
                <input type="number" id="salary" name="salary" step="0.01" value="<?php echo $employee['salary']; ?>">
            </div>

            <div class="form-group">
                <label for="status">Статус:</label>
                <select id="status" name="status">
                    <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>Активен</option>
                    <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-submit">Сохранить</button>
                <a href="employees.php" class="btn-cancel">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html> 