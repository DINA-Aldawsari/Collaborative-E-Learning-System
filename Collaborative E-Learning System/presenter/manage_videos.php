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

// هذا متغيرات عامة
$edit_mode = false;
$edit_video = null;

// هذا إذا المستخدم طلب حذف فيديو
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $video_id = (int) $_GET["delete"];

    // هذا يجيب الملف قبل الحذف
    $file_stmt = $conn->prepare("
        SELECT file_path
        FROM videos
        WHERE video_id = ? AND presenter_id = ?
    ");
    $file_stmt->bind_param("ii", $video_id, $presenter_id);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();

    if ($file_result && $file_result->num_rows > 0) {
        $video_data = $file_result->fetch_assoc();
        $old_file = "../" . $video_data["file_path"];

        // هذا يحذف الفيديو من الداتابيس
        $delete_stmt = $conn->prepare("
            DELETE FROM videos
            WHERE video_id = ? AND presenter_id = ?
        ");
        $delete_stmt->bind_param("ii", $video_id, $presenter_id);

        if ($delete_stmt->execute()) {
            // هذا يحذف الملف من السيرفر إذا موجود
            if (file_exists($old_file)) {
                unlink($old_file);
            }

            echo "<script>alert('Video deleted successfully'); window.location.href='manage_videos.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to delete video');</script>";
        }
    }
}

// هذا إذا المستخدم ضغط زر التعديل
if (isset($_GET["edit"]) && is_numeric($_GET["edit"])) {
    $video_id = (int) $_GET["edit"];

    // هذا يجيب بيانات الفيديو للتعديل
    $edit_stmt = $conn->prepare("
        SELECT *
        FROM videos
        WHERE video_id = ? AND presenter_id = ?
    ");
    $edit_stmt->bind_param("ii", $video_id, $presenter_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();

    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_video = $edit_result->fetch_assoc();
        $edit_mode = true;
    }
}

// هذا إذا المستخدم حفظ التعديلات
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_video"])) {
    $video_id = (int) $_POST["video_id"];
    $title = trim($_POST["title"]);
    $summary = trim($_POST["summary"]);
    $specialization = trim($_POST["specialization"]);

    // هذا يجيب الملف الحالي
    $current_stmt = $conn->prepare("
        SELECT file_path
        FROM videos
        WHERE video_id = ? AND presenter_id = ?
    ");
    $current_stmt->bind_param("ii", $video_id, $presenter_id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();

    if ($current_result->num_rows === 0) {
        echo "<script>alert('Video not found'); window.location.href='manage_videos.php';</script>";
        exit();
    }

    $current_video = $current_result->fetch_assoc();
    $file_path = $current_video["file_path"];

    // هذا إذا المستخدم رفع ملف جديد
    if (isset($_FILES["video_file"]) && $_FILES["video_file"]["error"] === 0) {
        $allowed_types = ["video/mp4", "video/webm", "video/ogg"];
        $file_type = $_FILES["video_file"]["type"];

        if (in_array($file_type, $allowed_types)) {
            $upload_dir = "../uploads/videos/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = time() . "_" . basename($_FILES["video_file"]["name"]);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_path)) {
                // هذا يحذف الملف القديم
                $old_file = "../" . $file_path;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }

                $file_path = "uploads/videos/" . $file_name;
            } else {
                echo "<script>alert('Failed to upload new video file');</script>";
            }
        } else {
            echo "<script>alert('Invalid video type');</script>";
        }
    }

    // هذا يحدث بيانات الفيديو
    $update_stmt = $conn->prepare("
        UPDATE videos
        SET title = ?, summary = ?, specialization = ?, file_path = ?
        WHERE video_id = ? AND presenter_id = ?
    ");
    $update_stmt->bind_param("ssssii", $title, $summary, $specialization, $file_path, $video_id, $presenter_id);

    if ($update_stmt->execute()) {
        echo "<script>alert('Video updated successfully'); window.location.href='manage_videos.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to update video');</script>";
    }
}

// هذا يجيب كل الفيديوهات الخاصة بالمقدم
$videos = [];
$list_stmt = $conn->prepare("
    SELECT *
    FROM videos
    WHERE presenter_id = ?
    ORDER BY created_at DESC
");
$list_stmt->bind_param("i", $presenter_id);
$list_stmt->execute();
$list_result = $list_stmt->get_result();

if ($list_result && $list_result->num_rows > 0) {
    while ($row = $list_result->fetch_assoc()) {
        $videos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Manage Videos</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_session.php">Create Session</a></li>
                <li><a href="manage_sessions.php">Manage Sessions</a></li>
                <li><a href="upload_video.php">Upload Video</a></li>
                <li><a href="manage_videos.php" class="active">Manage Videos</a></li>
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

        <?php if ($edit_mode && $edit_video): ?>
            <div class="profile-wrapper" style="margin-bottom: 35px;">
                <div class="profile-card">
                    <h2>Edit Video</h2>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="video_id" value="<?php echo $edit_video["video_id"]; ?>">

                        <div>
                            <label>Video Title</label>
                            <input type="text" name="title" class="input-field" value="<?php echo htmlspecialchars($edit_video["title"]); ?>" required>
                        </div>

                        <div>
                            <label>Summary</label>
                            <textarea name="summary" class="input-field textarea-field" rows="5"><?php echo htmlspecialchars($edit_video["summary"]); ?></textarea>
                        </div>

                        <div>
                            <label>Specialization</label>
                            <input type="text" name="specialization" class="input-field" value="<?php echo htmlspecialchars($edit_video["specialization"]); ?>">
                        </div>

                        <div>
                            <label>Replace Video File (Optional)</label>
                            <input type="file" name="video_file" class="input-field" accept="video/mp4,video/webm,video/ogg">
                        </div>

                        <button type="submit" name="update_video" class="btn btn-primary">Update Video</button>
                        <a href="manage_videos.php" class="btn btn-secondary" style="margin-top: 10px; display: inline-block; text-align: center;">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="section-title" style="text-align:left; margin-bottom: 25px;">
            <h2>Your Videos</h2>
            <p>View, edit, and delete the videos you have uploaded.</p>
        </div>

        <?php if (!empty($videos)): ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <h3><?php echo htmlspecialchars($video["title"]); ?></h3>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($video["specialization"]); ?></p>
                        <p><strong>Uploaded:</strong> <?php echo htmlspecialchars($video["created_at"]); ?></p>
                        <p class="video-summary"><?php echo htmlspecialchars($video["summary"]); ?></p>

                        <div class="video-player-box">
                            <video controls class="video-player">
                                <source src="../<?php echo htmlspecialchars($video["file_path"]); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>

                        <div class="card-actions">
                            <a href="manage_videos.php?edit=<?php echo $video["video_id"]; ?>" class="btn btn-primary">Edit</a>
                            <a href="manage_videos.php?delete=<?php echo $video["video_id"]; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this video?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">You have not uploaded any videos yet.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>