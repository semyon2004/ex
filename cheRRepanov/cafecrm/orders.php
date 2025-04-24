<?php
// orders.php
session_start();
// Проверка авторизации
if (!isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit();
}

// Подключение к базе данных
include 'db.php';

// Получение списка заказов
$query = "SELECT * FROM orders";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Список заказов</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Клиент</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['customer_name']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td>
                <a href="update_order.php?id=<?php echo $row['id']; ?>">Изменить статус</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="add_order.php">Создать заказ</a>
    <a href="logout.php">Выход</a>
</body>
</html>
