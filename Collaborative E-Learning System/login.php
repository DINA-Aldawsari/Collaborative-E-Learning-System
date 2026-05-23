<?php
session_start();
require_once "config_connection.php";

// هذا إذا المستخدم ضغط زر تسجيل الدخول
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // هذا نجيب البيانات من الفورم
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // هذا تحقق بسيط
    if (empty($email) || empty($password)) {
        echo "<script>alert('Please fill all fields');</script>";
    } else {

        // هذا نجيب المستخدم من الداتابيس
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            // هذا نتحقق من كلمة المرور
            if (password_verify($password, $user["password"])) {

                // هذا نخزن بيانات المستخدم في السيشن
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];

                // هذا نحول المستخدم حسب نوعه
                if ($user["role"] == "recipient") {
                    echo "<script>alert('Login successful'); window.location.href='recipient/dashboard.php';</script>";
                } elseif ($user["role"] == "presenter") {
                    echo "<script>alert('Login successful'); window.location.href='presenter/dashboard.php';</script>";
                } elseif ($user["role"] == "admin") {
                    echo "<script>alert('Login successful'); window.location.href='admin/dashboard.php';</script>";
                }

                exit();

            } else {
                echo "<script>alert('Incorrect password');</script>";
            }

        } else {
            echo "<script>alert('Email not found');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container" style="margin-top: 60px; max-width: 450px;">

    <div class="info-card">
        <h2 style="text-align:center; margin-bottom:20px;">Login</h2>

        <form method="POST">

            <!-- هذا الإيميل -->
            <div>
                <label>Email</label>
                <input type="email" name="email" class="input-field" required>
            </div>

            <!-- كلمة المرور -->
            <div>
                <label>Password</label>
                <input type="password" name="password" class="input-field" required>
            </div>

            <!-- زر الدخول -->
            <button type="submit" class="btn btn-primary">
                Login
            </button>

        </form>

        <!-- رابط التسجيل -->
        <p style="text-align:center; margin-top:15px;">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>
    </div>

</div>

</body>
</html>