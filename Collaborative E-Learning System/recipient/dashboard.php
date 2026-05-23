<?php
session_start();
require_once "../config_connection.php";

// هذا يتحقق إن المستخدم مسجل دخول
if (!isset($_SESSION["user_id"])) {
    echo "<script>alert('You must log in first'); window.location.href='../login.php';</script>";
    exit();
}

// هذا يتحقق إن المستخدم Recipient فقط
if ($_SESSION["role"] !== "recipient") {
    echo "<script>alert('Access denied'); window.location.href='../login.php';</script>";
    exit();
}

// هذا نجيب بيانات المستخدم من السيشن
$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["name"];

// هذا نجيب عدد الجلسات المتاحة
$sessions_count = 0;
$sessions_result = $conn->query("SELECT COUNT(*) AS total FROM sessions WHERE status IN ('upcoming', 'ongoing')");
if ($sessions_result && $sessions_result->num_rows > 0) {
    $sessions_count = $sessions_result->fetch_assoc()["total"];
}

// هذا نجيب عدد الفيديوهات
$videos_count = 0;
$videos_result = $conn->query("SELECT COUNT(*) AS total FROM videos");
if ($videos_result && $videos_result->num_rows > 0) {
    $videos_count = $videos_result->fetch_assoc()["total"];
}

// هذا نجيب آخر حالة لطلب الترقية
$request_status = "No request submitted";
$request_stmt = $conn->prepare("
    SELECT status
    FROM role_requests
    WHERE user_id = ?
    ORDER BY request_id DESC
    LIMIT 1
");
$request_stmt->bind_param("i", $user_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();

if ($request_result && $request_result->num_rows > 0) {
    $request_data = $request_result->fetch_assoc();
    $request_status = ucfirst($request_data["status"]);
}

// هذا نجيب أحدث 3 جلسات
$latest_sessions = [];
$sessions_stmt = $conn->prepare("
    SELECT sessions.session_id, sessions.title, sessions.session_date, sessions.session_time, sessions.specialization, users.name AS presenter_name
    FROM sessions
    INNER JOIN users ON sessions.presenter_id = users.user_id
    WHERE sessions.status IN ('upcoming', 'ongoing')
    ORDER BY sessions.session_date ASC, sessions.session_time ASC
    LIMIT 3
");
$sessions_stmt->execute();
$sessions_data = $sessions_stmt->get_result();

if ($sessions_data && $sessions_data->num_rows > 0) {
    while ($row = $sessions_data->fetch_assoc()) {
        $latest_sessions[] = $row;
    }
}

// هذا نجيب أحدث 3 فيديوهات
$latest_videos = [];
$videos_stmt = $conn->prepare("
    SELECT videos.video_id, videos.title, videos.summary, videos.specialization, users.name AS presenter_name
    FROM videos
    INNER JOIN users ON videos.presenter_id = users.user_id
    ORDER BY videos.created_at DESC
    LIMIT 3
");
$videos_stmt->execute();
$videos_data = $videos_stmt->get_result();

if ($videos_data && $videos_data->num_rows > 0) {
    while ($row = $videos_data->fetch_assoc()) {
        $latest_videos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<!-- هذا الهيدر -->
<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Recipient Dashboard</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="browse_sessions.php">Sessions</a></li>
                <li><a href="browse_videos.php">Videos</a></li>
                <li><a href="edit_profile.php">Profile</a></li>
                <li><a href="request_upgrade.php">Upgrade Request</a></li>
            </ul>
        </nav>

        <div class="nav-actions">
            <a href="../logout.php" class="btn btn-outline">Logout</a>
        </div>
    </div>
</header>

<!-- هذا المحتوى الرئيسي -->
<section class="section">
    <div class="container">

        <div class="section-title" style="text-align:left; margin-bottom: 25px;">
            <h2>Welcome, <?php echo htmlspecialchars($user_name); ?></h2>
            <p>Here you can explore sessions, watch videos, and manage your learning activities.</p>
        </div>

        <!-- هذا كروت الإحصائيات -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h3>Available Sessions</h3>
                <p><?php echo $sessions_count; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Available Videos</h3>
                <p><?php echo $videos_count; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Upgrade Request</h3>
                <p><?php echo htmlspecialchars($request_status); ?></p>
            </div>
        </div>

        <!-- هذا الجلسات الحديثة -->
        <div class="dashboard-section">
            <div class="section-header-row">
                <h3>Latest Sessions</h3>
                <a href="browse_sessions.php" class="btn btn-primary">View All</a>
            </div>

            <?php if (!empty($latest_sessions)): ?>
                <div class="dashboard-list">
                    <?php foreach ($latest_sessions as $session): ?>
                        <div class="dashboard-item">
                            <h4><?php echo htmlspecialchars($session["title"]); ?></h4>
                            <p><strong>Presenter:</strong> <?php echo htmlspecialchars($session["presenter_name"]); ?></p>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($session["session_date"]); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars($session["session_time"]); ?></p>
                            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($session["specialization"]); ?></p>
                            <a href="session_details.php?id=<?php echo $session["session_id"]; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">No sessions available right now.</div>
            <?php endif; ?>
        </div>

        <!-- هذا الفيديوهات الحديثة -->
        <div class="dashboard-section">
            <div class="section-header-row">
                <h3>Latest Videos</h3>
                <a href="browse_videos.php" class="btn btn-primary">View All</a>
            </div>

            <?php if (!empty($latest_videos)): ?>
                <div class="dashboard-list">
                    <?php foreach ($latest_videos as $video): ?>
                        <div class="dashboard-item">
                            <h4><?php echo htmlspecialchars($video["title"]); ?></h4>
                            <p><strong>Presenter:</strong> <?php echo htmlspecialchars($video["presenter_name"]); ?></p>
                            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($video["specialization"]); ?></p>
                            <p><?php echo htmlspecialchars($video["summary"]); ?></p>
                            <a href="video_details.php?id=<?php echo $video["video_id"]; ?>" class="btn btn-primary">Watch Video</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">No videos available right now.</div>
            <?php endif; ?>
        </div>

    </div>
</section>

</body>
</html>