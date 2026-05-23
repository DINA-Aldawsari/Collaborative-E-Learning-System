<?php
session_start();
require_once "../config_connection.php";

// هذا يتحقق إن المستخدم مسجل دخول
if (!isset($_SESSION["user_id"])) {
    echo "<script>alert('You must log in first'); window.location.href='../login.php';</script>";
    exit();
}

// هذا يتحقق إن المستخدم Admin فقط
if ($_SESSION["role"] !== "admin") {
    echo "<script>alert('Access denied'); window.location.href='../login.php';</script>";
    exit();
}

// هذا للبحث
$search = trim($_GET["search"] ?? "");

// هذا إذا الأدمن طلب حذف فيديو
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $video_id = (int) $_GET["delete"];

    // هذا يجيب مسار الملف قبل الحذف
    $file_stmt = $conn->prepare("
        SELECT file_path
        FROM videos
        WHERE video_id = ?
    ");
    $file_stmt->bind_param("i", $video_id);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();

    if ($file_result && $file_result->num_rows > 0) {
        $video_data = $file_result->fetch_assoc();
        $file_path = "../" . $video_data["file_path"];

        // هذا يحذف الفيديو من الداتابيس
        $delete_stmt = $conn->prepare("
            DELETE FROM videos
            WHERE video_id = ?
        ");
        $delete_stmt->bind_param("i", $video_id);

        if ($delete_stmt->execute()) {
            // هذا يحذف الملف من السيرفر إذا موجود
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            echo "<script>alert('Video deleted successfully'); window.location.href='manage_videos.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to delete video');</script>";
        }
    } else {
        echo "<script>alert('Video not found'); window.location.href='manage_videos.php';</script>";
        exit();
    }
}

// هذا الاستعلام الأساسي
$sql = "
    SELECT
        videos.video_id,
        videos.title,
        videos.summary,
        videos.file_path,
        videos.specialization,
        videos.created_at,
        users.name AS presenter_name,
        users.email AS presenter_email
    FROM videos
    INNER JOIN users ON videos.presenter_id = users.user_id
    WHERE 1=1
";

$params = [];
$types = "";

// هذا فلتر البحث
if (!empty($search)) {
    $sql .= " AND videos.title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

$sql .= " ORDER BY videos.created_at DESC";

$stmt = $conn->prepare($sql);

// هذا يربط البراميتر إذا فيه بحث
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$videos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
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
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_role_requests.php">Role Requests</a></li>
                <li><a href="manage_sessions.php">Sessions</a></li>
                <li><a href="manage_videos.php" class="active">Videos</a></li>
                <li><a href="manage_comments.php">Comments</a></li>
                <li><a href="monitor_system.php">System Logs</a></li>
            </ul>
        </nav>

        <div class="nav-actions">
            <a href="../logout.php" class="btn btn-outline">Logout</a>
        </div>
    </div>
</header>

<section class="section">
    <div class="container">

        <div class="section-title" style="text-align:left; margin-bottom: 25px;">
            <h2>Videos Management</h2>
            <p>Review all uploaded videos in the platform and remove inappropriate content when necessary.</p>
        </div>

        <!-- هذا البحث -->
        <div class="filter-box">
            <form method="GET" class="filter-form" style="grid-template-columns: 1fr auto;">
                <div class="filter-group">
                    <label>Search by Video Title</label>
                    <input
                        type="text"
                        name="search"
                        class="input-field"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Enter video title"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="manage_videos.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (!empty($videos)): ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <h3><?php echo htmlspecialchars($video["title"]); ?></h3>
                        <p><strong>Presenter:</strong> <?php echo htmlspecialchars($video["presenter_name"]); ?></p>
                        <p><strong>Presenter Email:</strong> <?php echo htmlspecialchars($video["presenter_email"]); ?></p>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($video["specialization"] ?: "N/A"); ?></p>
                        <p><strong>Uploaded:</strong> <?php echo htmlspecialchars($video["created_at"]); ?></p>

                        <?php if (!empty($video["summary"])): ?>
                            <p class="video-summary"><?php echo htmlspecialchars($video["summary"]); ?></p>
                        <?php else: ?>
                            <p class="video-summary">No summary available.</p>
                        <?php endif; ?>

                        <div class="video-player-box">
                            <video controls class="video-player">
                                <source src="../<?php echo htmlspecialchars($video["file_path"]); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>

                        <div class="card-actions">
                            <a href="manage_videos.php?delete=<?php echo $video["video_id"]; ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this video?');">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">No videos found.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>