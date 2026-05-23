<?php
session_start();
require_once "../config_connection.php";

// هذا يتحقق إن المستخدم مسجل دخول
if (!isset($_SESSION["user_id"])) {
    echo "<script>alert('You must log in first'); window.location.href='../login.php';</script>";
    exit();
}

// هذا يتحقق إن المستخدم Presenter فقط
if ($_SESSION["role"] !== "presenter") {
    echo "<script>alert('Access denied'); window.location.href='../login.php';</script>";
    exit();
}

// هذا يجيب رقم المقدم من السيشن
$presenter_id = $_SESSION["user_id"];

// هذا إذا المستخدم ضغط زر الحفظ
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // هذا نجيب البيانات من الفورم
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $session_date = trim($_POST["session_date"]);
    $session_time = trim($_POST["session_time"]);
    $meeting_link = trim($_POST["meeting_link"]);
    $specialization = trim($_POST["specialization"]);
    $status = trim($_POST["status"]);

    // هذا تحقق بسيط من الحقول الأساسية
    if (empty($title) || empty($session_date) || empty($session_time)) {
        echo "<script>alert('Please fill all required fields');</script>";
    } else {

        // هذا يحفظ الجلسة بالداتابيس
        $stmt = $conn->prepare("
            INSERT INTO sessions (title, description, session_date, session_time, meeting_link, specialization, status, presenter_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssi",
            $title,
            $description,
            $session_date,
            $session_time,
            $meeting_link,
            $specialization,
            $status,
            $presenter_id
        );

        if ($stmt->execute()) {
            echo "<script>alert('Session created successfully'); window.location.href='manage_sessions.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to create session');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Session</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Create Session</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_session.php" class="active">Create Session</a></li>
                <li><a href="manage_sessions.php">Manage Sessions</a></li>
                <li><a href="upload_video.php">Upload Video</a></li>
                <li><a href="manage_videos.php">Manage Videos</a></li>
                <li><a href="manage_comments.php">Comments</a></li>
                <li><a href="reviews_received.php">Reviews Received</a></li>

            </ul>
        </nav>

        <div class="nav-actions">
            <a href="../logout.php" class="btn btn-outline">Logout</a>
        </div>
    </div>
</header>

<section class="section">
    <div class="container">

        <div class="profile-wrapper">
            <div class="profile-card">
                <h2>Create New Educational Session</h2>

                <form method="POST">

                    <div>
                        <label>Session Title</label>
                        <input type="text" name="title" class="input-field" required>
                    </div>

                    <div>
                        <label>Description</label>
                        <textarea name="description" class="input-field textarea-field" rows="5"></textarea>
                    </div>

                    <div>
                        <label>Session Date</label>
                        <input type="date" name="session_date" class="input-field" required>
                    </div>

                    <div>
                        <label>Session Time</label>
                        <input type="time" name="session_time" class="input-field" required>
                    </div>

                    <div>
                        <label>Meeting Link</label>
                        <input type="url" name="meeting_link" class="input-field" placeholder="https://example.com/meeting">
                    </div>

                    <div>
                        <label>Specialization</label>
                        <input type="text" name="specialization" class="input-field" placeholder="Enter target specialization">
                    </div>

                    <div>
                        <label>Status</label>
                        <select name="status" class="input-field" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Session</button>
                </form>
            </div>
        </div>

    </div>
</section>

</body>
</html>