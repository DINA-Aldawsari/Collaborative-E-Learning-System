<?php
session_start();

// هذا نحذف كل بيانات السيشن
$_SESSION = [];

// هذا يدمر السيشن بالكامل
session_destroy();

// هذا يرجع المستخدم لصفحة تسجيل الدخول
echo "<script>alert('You have been logged out successfully'); window.location.href='login.php';</script>";
exit();
?>