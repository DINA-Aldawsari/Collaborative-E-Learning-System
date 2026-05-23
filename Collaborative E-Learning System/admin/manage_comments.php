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

// هذا إذا الأدمن طلب حذف تعليق
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $comment_id = (int) $_GET["delete"];

    $delete_stmt = $conn->prepare("
        DELETE FROM comments
        WHERE comment_id = ?
    ");
    $delete_stmt->bind_param("i", $comment_id);

    if ($delete_stmt->execute()) {
        echo "<script>alert('Comment deleted successfully'); window.location.href='manage_comments.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to delete comment');</script>";
    }
}

// هذا الاستعلام الأساسي
$sql = "
    SELECT
        comments.comment_id,
        comments.content,
        comments.created_at,
        comments.parent_comment_id,
        users.name AS user_name,
        users.email AS user_email,
        videos.title AS video_title
    FROM comments
    INNER JOIN users ON comments.user_id = users.user_id
    INNER JOIN videos ON comments.video_id = videos.video_id
    WHERE 1=1
";

$params = [];
$types = "";

// هذا فلتر البحث
if (!empty($search)) {
    $sql .= " AND (
        comments.content LIKE ?
        OR users.name LIKE ?
        OR users.email LIKE ?
        OR videos.title LIKE ?
    )";
    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $types .= "ssss";
}

$sql .= " ORDER BY comments.created_at DESC";

$stmt = $conn->prepare($sql);

// هذا يربط البراميتر إذا فيه بحث
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$comments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Comments</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Manage Comments</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_role_requests.php">Role Requests</a></li>
                <li><a href="manage_sessions.php">Sessions</a></li>
                <li><a href="manage_videos.php">Videos</a></li>
                <li><a href="manage_comments.php" class="active">Comments</a></li>
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
            <h2>Comments Management</h2>
            <p>Review all comments in the platform and remove inappropriate content when necessary.</p>
        </div>

        <!-- هذا البحث -->
        <div class="filter-box">
            <form method="GET" class="filter-form" style="grid-template-columns: 1fr auto;">
                <div class="filter-group">
                    <label>Search by Comment, User, or Video</label>
                    <input
                        type="text"
                        name="search"
                        class="input-field"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Enter comment text, user name, email, or video title"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="manage_comments.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (!empty($comments)): ?>
            <div class="comment-list-box">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <h4><?php echo htmlspecialchars($comment["user_name"]); ?></h4>
                            <span><?php echo htmlspecialchars($comment["created_at"]); ?></span>
                        </div>

                        <p><strong>Email:</strong> <?php echo htmlspecialchars($comment["user_email"]); ?></p>
                        <p><strong>Video:</strong> <?php echo htmlspecialchars($comment["video_title"]); ?></p>
                        <p><strong>Type:</strong> <?php echo $comment["parent_comment_id"] ? "Reply" : "Main Comment"; ?></p>
                        <p><?php echo nl2br(htmlspecialchars($comment["content"])); ?></p>

                        <div class="card-actions">
                            <a href="manage_comments.php?delete=<?php echo $comment["comment_id"]; ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this comment?');">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">No comments found.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>