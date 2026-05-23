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

// هذا يجيب رقم المقدم
$presenter_id = $_SESSION["user_id"];

// هذا إذا المستخدم ضغط زر رفع الفيديو
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // هذا يجيب البيانات من الفورم
    $title = trim($_POST["title"]);
    $summary = trim($_POST["summary"]);
    $specialization = trim($_POST["specialization"]);
    $file_path = "";

    // هذا تحقق بسيط
    if (empty($title)) {
        echo "<script>alert('Please enter the video title');</script>";
    } elseif (!isset($_FILES["video_file"]) || $_FILES["video_file"]["error"] !== 0) {
        echo "<script>alert('Please choose a video file');</script>";
    } else {

        // هذا أنواع الملفات المسموح فيها
        $allowed_types = ["video/mp4", "video/webm", "video/ogg"];
        $file_type = $_FILES["video_file"]["type"];

        if (!in_array($file_type, $allowed_types)) {
            echo "<script>alert('Invalid video type. Please upload MP4, WEBM, or OGG');</script>";
        } else {

            // هذا المسار اللي بنرفع فيه الفيديو
            $upload_dir = "../uploads/videos/";

            // هذا يتأكد إن المجلد موجود
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = time() . "_" . basename($_FILES["video_file"]["name"]);
            $target_path = $upload_dir . $file_name;

            // هذا يرفع الفيديو فعليًا
            if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_path)) {
                $file_path = "uploads/videos/" . $file_name;

                // هذا يحفظ الفيديو بالداتابيس
                $stmt = $conn->prepare("
                    INSERT INTO videos (title, summary, file_path, specialization, presenter_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssi", $title, $summary, $file_path, $specialization, $presenter_id);

                if ($stmt->execute()) {
                    echo "<script>alert('Video uploaded successfully'); window.location.href='manage_videos.php';</script>";
                    exit();
                } else {
                    echo "<script>alert('Failed to save video data');</script>";
                }
            } else {
                echo "<script>alert('Failed to upload video file');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Upload Video</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_session.php">Create Session</a></li>
                <li><a href="manage_sessions.php">Manage Sessions</a></li>
                <li><a href="upload_video.php" class="active">Upload Video</a></li>
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
                <h2>Upload New Educational Video</h2>

                <form method="POST" enctype="multipart/form-data">

                    <div>
                        <label>Video Title</label>
                        <input type="text" name="title" class="input-field" required>
                    </div>

                    <div>
                        <label>Summary</label>
                        <textarea name="summary" class="input-field textarea-field" rows="5"></textarea>
                    </div>

                    <div>
                        <label>Specialization</label>
                        <input type="text" name="specialization" class="input-field" placeholder="Enter target specialization">
                    </div>

                    <div>
                        <label>Video File</label>
                        <input type="file" name="video_file" class="input-field" accept="video/mp4,video/webm,video/ogg" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Upload Video</button>
                </form>
            </div>
        </div>

    </div>
</section>

</body>
</html>