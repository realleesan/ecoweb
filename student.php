<?php
// 1. Kết nối database
$host = "localhost"; 
$user = "root";     
$pass = "";         
$db   = "demo";     

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Kết nối thất bại: " . $conn->connect_error);

// 2. Insert
if (isset($_POST['insert'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $conn->query("INSERT INTO students (name,email,age) VALUES ('$name','$email',$age)");
}

// 3. Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $conn->query("UPDATE students SET name='$name', email='$email', age=$age WHERE id=$id");
}

// 4. Delete
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM students WHERE id=$id");
}

// 5. Search
$where = "";
if (isset($_POST['search'])) {
    $keyword = $_POST['keyword'];
    $where = "WHERE name LIKE '%$keyword%' OR email LIKE '%$keyword%'";
}

$result = $conn->query("SELECT * FROM students $where");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Students</title>
</head>
<body>

    <h3>Thêm sinh viên</h3>
    <form method="post">
        Name: <input type="text" name="name" required>
        Email: <input type="email" name="email" required>
        Age: <input type="number" name="age">
        <button type="submit" name="insert">Thêm</button>
    </form>

    <h3>Tìm kiếm sinh viên</h3>
    <form method="post">
        Keyword: <input type="text" name="keyword">
        <button type="submit" name="search">Tìm</button>
    </form>

    <h3>Danh sách sinh viên</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Age</th><th>Hành động</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <form method="post">
                <td><?php echo $row['id']; ?></td>
                <td><input type="text" name="name" value="<?php echo $row['name']; ?>"></td>
                <td><input type="email" name="email" value="<?php echo $row['email']; ?>"></td>
                <td><input type="number" name="age" value="<?php echo $row['age']; ?>"></td>
                <td>
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="update">Sửa</button>
                    <button type="submit" name="delete" onclick="return confirm('Xóa sinh viên này?')">Xóa</button>
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>

</body>
</html>
