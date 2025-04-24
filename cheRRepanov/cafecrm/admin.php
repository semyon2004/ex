<?php
session_start();

// Проверка авторизации
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
$admin = $db->query("SELECT id, login, type, name, surname FROM users WHERE token = '$token'")->fetch();

// Если пользователь не найден или не админ
if (!$admin || $admin['type'] !== 'admin') {
    $_SESSION['token'] = '';
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Обработка добавления нового пользователя
if (isset($_POST['add_user'])) {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    
    if (empty($login) || empty($password) || empty($name) || empty($surname)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        // Проверяем, не существует ли уже такой логин
        $stmt = $db->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким логином уже существует';
        } else {
            $stmt = $db->prepare("INSERT INTO users (login, password, name, surname, type) VALUES (?, ?, ?, ?, 'user')");
            if ($stmt->execute([$login, $password, $name, $surname])) {
                $success = 'Пользователь успешно добавлен';
            } else {
                $error = 'Ошибка при добавлении пользователя';
            }
        }
    }
}

// Обработка редактирования пользователя
if (isset($_POST['edit_user'])) {
    $userId = (int)$_POST['user_id'];
    $name = trim($_POST['edit_name'] ?? '');
    $surname = trim($_POST['edit_surname'] ?? '');
    $password = trim($_POST['edit_password'] ?? '');
    
    if (empty($name) || empty($surname)) {
        $error = 'Имя и фамилия обязательны для заполнения';
    } else {
        if (!empty($password)) {
            $stmt = $db->prepare("UPDATE users SET name = ?, surname = ?, password = ? WHERE id = ?");
            $result = $stmt->execute([$name, $surname, $password, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, surname = ? WHERE id = ?");
            $result = $stmt->execute([$name, $surname, $userId]);
        }
        
        if ($result) {
            $success = 'Данные пользователя обновлены';
        } else {
            $error = 'Ошибка при обновлении данных';
        }
    }
}

// Обработка разблокировки пользователя
if (isset($_POST['unblock_user']) && !empty($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $stmt = $db->prepare("UPDATE users SET blocked = 0, amountAttempt = 0 WHERE id = ?");
    if ($stmt->execute([$userId])) {
        $success = 'Пользователь разблокирован';
    } else {
        $error = 'Ошибка при разблокировке пользователя';
    }
}

// Обработка блокировки пользователя
if (isset($_POST['block_user']) && !empty($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $stmt = $db->prepare("UPDATE users SET blocked = 1 WHERE id = ?");
    if ($stmt->execute([$userId])) {
        $success = 'Пользователь заблокирован';
    } else {
        $error = 'Ошибка при блокировке пользователя';
    }
}

// Получаем список всех пользователей
$users = $db->query("SELECT id, login, name, surname, blocked, amountAttempt, latest FROM users WHERE type != 'admin' ORDER BY latest DESC")->fetchAll();

// Проверяем и блокируем неактивных пользователей (более месяца)
$monthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
$db->query("UPDATE users SET blocked = 1 WHERE latest < '$monthAgo' AND type != 'admin'");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <title>Панель администратора</title>
</head>
<body>
    <div class="admin-panel">
        <h1>Панель администратора</h1>
        <div class="admin-info">
            <p>Администратор: <?php echo htmlspecialchars($admin['name'] . ' ' . $admin['surname']); ?></p>
        </div>
        
        <div class="button-container">
            <a href="#" onclick="toggleEmployees(event)" class="btn-employees">Список сотрудников</a>
        </div>

        <!-- Add employees section -->
        <div id="employees-section" style="display: none;" class="employees-container">
            <h2>Список сотрудников</h2>
            <table class="employees-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Должность</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Подключение к базе данных
                    $query = "SELECT * FROM employees";
                    $result = $db->query($query);
                    while ($row = $result->fetch()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['position']); ?></td>
                        <td>
                            <a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="btn-edit">Редактировать</a>
                            <a href="delete_employee.php?id=<?php echo $row['id']; ?>" class="btn-delete">Удалить</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <a href="add_employee.php" class="btn-add">Добавить сотрудника</a>
        </div>

        <?php if(!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <!-- Форма добавления пользователя -->
        <div class="add-user-form">
            <h2>Добавить пользователя</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="name">Имя</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="surname">Фамилия</label>
                    <input type="text" id="surname" name="surname" required>
                </div>
                <button type="submit" name="add_user" class="btn-add">Добавить пользователя</button>
            </form>
        </div>
        
        <h2>Список пользователей</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Логин</th>
                        <th>Имя</th>
                        <th>Фамилия</th>
                        <th>Статус</th>
                        <th>Попыток входа</th>
                        <th>Последняя активность</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['login']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['surname']); ?></td>
                        <td class="status-<?php echo $user['blocked'] ? 'blocked' : 'active'; ?>">
                            <?php echo $user['blocked'] ? 'Заблокирован' : 'Активен'; ?>
                        </td>
                        <td><?php echo $user['amountAttempt']; ?></td>
                        <td><?php echo $user['latest'] ? date('d.m.Y H:i', strtotime($user['latest'])) : 'Нет данных'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if($user['blocked']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="unblock_user" class="btn-unblock">Разблокировать</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="block_user" class="btn-block">Заблокировать</button>
                                </form>
                                <?php endif; ?>
                                <form action="edit_user.php" method="post">
                                    <button type="submit" class="btn-unblock">Редактировать</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Модальное окно редактирования -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Редактировать пользователя</h2>
                <form method="POST">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="form-group">
                        <label for="edit_login">Логин</label>
                        <input type="text" id="edit_login" disabled>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Имя</label>
                        <input type="text" id="edit_name" name="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_surname">Фамилия</label>
                        <input type="text" id="edit_surname" name="edit_surname" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">Новый пароль (оставьте пустым, чтобы не менять)</label>
                        <input type="password" id="edit_password" name="edit_password">
                    </div>
                    <button type="submit" name="edit_user" class="btn-save">Сохранить изменения</button>
                </form>
            </div>
        </div>
        
        <p><a href="login.php?logout=1" class="logout-link">Выйти</a></p>
    </div>

    <script>
    // Функции для работы с модальным окном
    const modal = document.getElementById('editModal');
    const span = document.getElementsByClassName('close')[0];

    function openEditModal(userData) {
        modal.style.display = 'block';
        document.getElementById('edit_user_id').value = userData.id;
        document.getElementById('edit_login').value = userData.login;
        document.getElementById('edit_name').value = userData.name;
        document.getElementById('edit_surname').value = userData.surname;
        document.getElementById('edit_password').value = '';
    }

    span.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function toggleEmployees(event) {
        event.preventDefault();
        const employeesSection = document.getElementById('employees-section');
        if (employeesSection.style.display === 'none') {
            employeesSection.style.display = 'block';
        } else {
            employeesSection.style.display = 'none';
        }
    }
    </script>
</body>
</html> 