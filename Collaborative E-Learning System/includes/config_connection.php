<?php
// هذا ملف الاتصال بقاعدة البيانات

$host = "localhost";
$user = "root";
$pass = "";
$db   = "collaborative_elearning_system"; 

// نحاول نربط مع قاعدة البيانات
$conn = new mysqli($host, $user, $pass, $db);

// هذا نتاكد إذا فيه مشكلة بالاتصال
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// هذا نضبط الترميز عشان يدعم العربي والانجليزي
$conn->set_charset("utf8mb4");

?>