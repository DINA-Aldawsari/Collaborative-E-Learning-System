<?php
session_start();
require_once "config_connection.php";

// هذا متغير لعرض الرسائل
$message = "";

// هذا إذا المستخدم ضغط زر التسجيل
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // هذا نجيب البيانات من الفورم
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $specialization = trim($_POST["specialization"]);
    $role = $_POST["role"]; // recipient أو presenter

    // هذا تحقق بسيط من المدخلات
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        echo "<script>alert('Please fill all required fields');</script>";
    } elseif ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match');</script>";
    } else {

        // هذا تشفير كلمة المرور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // هذا يتحقق إذا الإيميل موجود قبل
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Email already exists');</script>";
        } else {

            // هذا ندخل المستخدم الجديد
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, role, specialization)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $specialization);

            if ($stmt->execute()) {
                echo "<script>alert('Account created successfully'); window.location.href='login.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error creating account');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container" style="margin-top: 50px; max-width: 500px;">

    <div class="info-card">
        <h2 style="text-align:center; margin-bottom:20px;">Create Account</h2>

        <form method="POST">

            <!-- هذا الاسم -->
            <div style="margin-bottom:15px;">
                <label>Full Name</label>
                <input type="text" name="name" class="input-field" required>
            </div>

            <!-- هذا الإيميل -->
            <div style="margin-bottom:15px;">
                <label>Email</label>
                <input type="email" name="email" class="input-field" required>
            </div>

            <!-- كلمة المرور -->
            <div style="margin-bottom:15px;">
                <label>Password</label>
                <input type="password" name="password" class="input-field" required>
            </div>

            <!-- تأكيد كلمة المرور -->
            <div style="margin-bottom:15px;">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="input-field" required>
            </div>

            <!-- التخصص -->
            <div style="margin-bottom:15px;">
                <label>Specialization</label>
                <input type="text" name="specialization" class="input-field">
            </div>

            <!-- اختيار الدور -->
            <div style="margin-bottom:20px;">
                <label>Account Type</label>
                <select name="role" class="input-field" required>
                    <option value="recipient">Recipient Student</option>
                    <option value="presenter">Presenter Student</option>
                </select>
            </div>

            <!-- زر التسجيل -->
            <button type="submit" class="btn btn-primary" style="width:100%;">
                Register
            </button>

        </form>

        <!-- رابط تسجيل الدخول -->
        <p style="text-align:center; margin-top:15px;">
            Already have an account?
            <a href="login.php">Login</a>
        </p>
    </div>

</div>

</body>
</html>