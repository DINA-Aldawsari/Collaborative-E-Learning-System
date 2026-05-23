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

// هذا يجيب بيانات المستخدم من السيشن
$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["name"];

// هذا نجيب عدد الجلسات التابعة للمقدم
$sessions_count = 0;
$sessions_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM sessions
    WHERE presenter_id = ?
");
$sessions_stmt->bind_param("i", $user_id);
$sessions_stmt->execute();
$sessions_result = $sessions_stmt->get_result();
if ($sessions_result && $sessions_result->num_rows > 0) {
    $sessions_count = $sessions_result->fetch_assoc()["total"];
}

// هذا نجيب عدد الفيديوهات التابعة للمقدم
$videos_count = 0;
$videos_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM videos
    WHERE presenter_id = ?
");
$videos_stmt->bind_param("i", $user_id);
$videos_stmt->execute();
$videos_result = $videos_stmt->get_result();
if ($videos_result && $videos_result->num_rows > 0) {
    $videos_count = $videos_result->fetch_assoc()["total"];
}

// هذا نجيب عدد الجلسات المكتملة أو المنفذة
$conducted_count = 0;
$conducted_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM sessions
    WHERE presenter_id = ?
      AND status IN ('completed', 'ongoing')
");
$conducted_stmt->bind_param("i", $user_id);
$conducted_stmt->execute();
$conducted_result = $conducted_stmt->get_result();
if ($conducted_result && $conducted_result->num_rows > 0) {
    $conducted_count = $conducted_result->fetch_assoc()["total"];
}

// هذا نجيب عدد التعليقات على فيديوهات المقدم
$comments_count = 0;
$comments_stmt = $conn->prepare("
    SELECT COUNT(comments.comment_id) AS total
    FROM comments
    INNER JOIN videos ON comments.video_id = videos.video_id
    WHERE videos.presenter_id = ?
");
$comments_stmt->bind_param("i", $user_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
if ($comments_result && $comments_result->num_rows > 0) {
    $comments_count = $comments_result->fetch_assoc()["total"];
}

// هذا نجيب أحدث 3 جلسات
$latest_sessions = [];
$latest_sessions_stmt = $conn->prepare("
    SELECT session_id, title, session_date, session_time, specialization, status
    FROM sessions
    WHERE presenter_id = ?
    ORDER BY created_at DESC
    LIMIT 3
");
$latest_sessions_stmt->bind_param("i", $user_id);
$latest_sessions_stmt->execute();
$latest_sessions_result = $latest_sessions_stmt->get_result();

if ($latest_sessions_result && $latest_sessions_result->num_rows > 0) {
    while ($row = $latest_sessions_result->fetch_assoc()) {
        $latest_sessions[] = $row;
    }
}

// هذا نجيب أحدث 3 فيديوهات
$latest_videos = [];
$latest_videos_stmt = $conn->prepare("
    SELECT video_id, title, summary, specialization, created_at
    FROM videos
    WHERE presenter_id = ?
    ORDER BY created_at DESC
    LIMIT 3
");
$latest_videos_stmt->bind_param("i", $user_id);
$latest_videos_stmt->execute();
$latest_videos_result = $latest_videos_stmt->get_result();

if ($latest_videos_result && $latest_videos_result->num_rows > 0) {
    while ($row = $latest_videos_result->fetch_assoc()) {
        $latest_videos[] = $row;
    }
}

// هذا نجيب آخر التعليقات على فيديوهات المقدم
$latest_comments = [];
$latest_comments_stmt = $conn->prepare("
    SELECT 
        comments.comment_id,
        comments.content,
        comments.created_at,
        users.name AS student_name,
        videos.title AS video_title
    FROM comments
    INNER JOIN users ON comments.user_id = users.user_id
    INNER JOIN videos ON comments.video_id = videos.video_id
    WHERE videos.presenter_id = ?
      AND comments.parent_comment_id IS NULL
    ORDER BY comments.created_at DESC
    LIMIT 5
");
$latest_comments_stmt->bind_param("i", $user_id);
$latest_comments_stmt->execute();
$latest_comments_result = $latest_comments_stmt->get_result();

if ($latest_comments_result && $latest_comments_result->num_rows > 0) {
    while ($row = $latest_comments_result->fetch_assoc()) {
        $latest_comments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presenter Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Presenter Dashboard</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="create_session.php">Create Session</a></li>
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

        <div class="section-title" style="text-align:left; margin-bottom: 25px;">
            <h2>Welcome, <?php echo htmlspecialchars($user_name); ?></h2>
            <p>Manage your sessions, videos, and student interactions from one place.</p>
        </div>

        <!-- هذا كروت الإحصائيات -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h3>Total Sessions</h3>
                <p><?php echo $sessions_count; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Total Videos</h3>
                <p><?php echo $videos_count; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Total Comments</h3>
                <p><?php echo $comments_count; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Conducted Sessions</h3>
                <p><?php echo $conducted_count; ?></p>
            </div>
        </div>

        <!-- هذا الجلسات الحديثة -->
        <div class="dashboard-section">
            <div class="section-header-row">
                <h3>Latest Sessions</h3>
                <a href="manage_sessions.php" class="btn btn-primary">Manage All</a>
            </div>

            <?php if (!empty($latest_sessions)): ?>
                <div class="dashboard-list">
                    <?php foreach ($latest_sessions as $session): ?>
                        <div class="dashboard-item">
                            <h4><?php echo htmlspecialchars($session["title"]); ?></h4>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($session["session_date"]); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars($session["session_time"]); ?></p>
                            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($session["specialization"]); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($session["status"])); ?></p>
                            <a href="manage_sessions.php" class="btn btn-primary">Manage Sessions</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">No sessions created yet.</div>
            <?php endif; ?>
        </div>

        <!-- هذا الفيديوهات الحديثة -->
        <div class="dashboard-section">
            <div class="section-header-row">
                <h3>Latest Videos</h3>
                <a href="manage_videos.php" class="btn btn-primary">Manage All</a>
            </div>

            <?php if (!empty($latest_videos)): ?>
                <div class="dashboard-list">
                    <?php foreach ($latest_videos as $video): ?>
                        <div class="dashboard-item">
                            <h4><?php echo htmlspecialchars($video["title"]); ?></h4>
                            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($video["specialization"]); ?></p>
                            <p><strong>Uploaded:</strong> <?php echo htmlspecialchars($video["created_at"]); ?></p>
                            <p><?php echo htmlspecialchars($video["summary"]); ?></p>
                            <a href="manage_videos.php" class="btn btn-primary">Manage Videos</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">No videos uploaded yet.</div>
            <?php endif; ?>
        </div>

        <!-- هذا آخر التعليقات -->
        <div class="dashboard-section">
            <div class="section-header-row">
                <h3>Latest Comments</h3>
                <a href="manage_comments.php" class="btn btn-primary">View All</a>
            </div>

            <?php if (!empty($latest_comments)): ?>
                <div class="comment-list-box">
                    <?php foreach ($latest_comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <h4><?php echo htmlspecialchars($comment["student_name"]); ?></h4>
                                <span><?php echo htmlspecialchars($comment["created_at"]); ?></span>
                            </div>
                            <p><strong>Video:</strong> <?php echo htmlspecialchars($comment["video_title"]); ?></p>
                            <p><?php echo nl2br(htmlspecialchars($comment["content"])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">No comments on your videos yet.</div>
            <?php endif; ?>
        </div>

    </div>
</section>

</body>
</html>