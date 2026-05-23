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

$user_id = $_SESSION["user_id"];

// هذا يتحقق من رقم الفيديو
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo "<script>alert('Invalid video ID'); window.location.href='browse_videos.php';</script>";
    exit();
}

$video_id = (int) $_GET["id"];

// هذا إذا المستخدم أضاف تعليق
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_comment"])) {
    $content = trim($_POST["content"]);

    if (empty($content)) {
        echo "<script>alert('Please enter your comment');</script>";
    } else {
        $insert_comment = $conn->prepare("
            INSERT INTO comments (content, user_id, video_id)
            VALUES (?, ?, ?)
        ");
        $insert_comment->bind_param("sii", $content, $user_id, $video_id);

        if ($insert_comment->execute()) {
            echo "<script>alert('Comment added successfully'); window.location.href='video_details.php?id=$video_id';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to add comment');</script>";
        }
    }
}

// هذا يجيب بيانات الفيديو
$stmt = $conn->prepare("
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
    WHERE videos.video_id = ?
");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

// هذا يتحقق إذا الفيديو موجود
if ($result->num_rows === 0) {
    echo "<script>alert('Video not found'); window.location.href='browse_videos.php';</script>";
    exit();
}

$video = $result->fetch_assoc();

// هذا يجيب التعليقات الرئيسية
$comments = [];
$comment_stmt = $conn->prepare("
    SELECT
        comments.comment_id,
        comments.content,
        comments.created_at,
        users.name AS user_name
    FROM comments
    INNER JOIN users ON comments.user_id = users.user_id
    WHERE comments.video_id = ?
      AND comments.parent_comment_id IS NULL
    ORDER BY comments.created_at DESC
");
$comment_stmt->bind_param("i", $video_id);
$comment_stmt->execute();
$comment_result = $comment_stmt->get_result();

if ($comment_result && $comment_result->num_rows > 0) {
    while ($row = $comment_result->fetch_assoc()) {
        $comments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Details</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Video Details</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="browse_sessions.php">Sessions</a></li>
                <li><a href="browse_videos.php" class="active">Videos</a></li>
                <li><a href="edit_profile.php">Profile</a></li>
                <li><a href="request_upgrade.php">Upgrade Request</a></li>
            </ul>
        </nav>

        <div class="nav-actions">
            <a href="../logout.php" class="btn btn-outline">Logout</a>
        </div>
    </div>
</header>

<section class="section">
    <div class="container">

        <div class="details-wrapper">
            <div class="details-card">
                <div class="details-header">
                    <h2><?php echo htmlspecialchars($video["title"]); ?></h2>
                </div>

                <div class="details-content">
                    <p><strong>Presenter:</strong> <?php echo htmlspecialchars($video["presenter_name"]); ?></p>
                    <p><strong>Presenter Email:</strong> <?php echo htmlspecialchars($video["presenter_email"]); ?></p>
                    <p><strong>Specialization:</strong> <?php echo htmlspecialchars($video["specialization"]); ?></p>
                    <p><strong>Uploaded At:</strong> <?php echo htmlspecialchars($video["created_at"]); ?></p>

                    <p><strong>Summary:</strong></p>
                    <div class="details-description">
                        <?php echo nl2br(htmlspecialchars($video["summary"])); ?>
                    </div>

                    <p><strong>Video:</strong></p>
                    <div class="video-player-box">
                        <video controls class="video-player">
                            <source src="../<?php echo htmlspecialchars($video["file_path"]); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>

                    <div class="details-actions">
                        <a href="browse_videos.php" class="btn btn-secondary">Back to Videos</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="comment-section">
            <div class="comment-box">
                <h3>Add Comment</h3>

                <form method="POST">
                    <div>
                        <label>Your Comment</label>
                        <textarea name="content" class="input-field textarea-field" rows="4" required></textarea>
                    </div>

                    <button type="submit" name="add_comment" class="btn btn-primary">Post Comment</button>
                </form>
            </div>

            <div class="comment-list-box">
                <h3>Comments</h3>

                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <h4><?php echo htmlspecialchars($comment["user_name"]); ?></h4>
                                <span><?php echo htmlspecialchars($comment["created_at"]); ?></span>
                            </div>

                            <p><?php echo nl2br(htmlspecialchars($comment["content"])); ?></p>

                            <?php
                            // هذا يجيب الردود على كل تعليق
                            $replies = [];
                            $reply_stmt = $conn->prepare("
                                SELECT
                                    comments.content,
                                    comments.created_at,
                                    users.name AS user_name
                                FROM comments
                                INNER JOIN users ON comments.user_id = users.user_id
                                WHERE comments.parent_comment_id = ?
                                ORDER BY comments.created_at ASC
                            ");
                            $reply_stmt->bind_param("i", $comment["comment_id"]);
                            $reply_stmt->execute();
                            $reply_result = $reply_stmt->get_result();

                            if ($reply_result && $reply_result->num_rows > 0) {
                                while ($reply_row = $reply_result->fetch_assoc()) {
                                    $replies[] = $reply_row;
                                }
                            }
                            ?>

                            <?php if (!empty($replies)): ?>
                                <div class="reply-list">
                                    <?php foreach ($replies as $reply): ?>
                                        <div class="reply-item">
                                            <div class="comment-header">
                                                <h5><?php echo htmlspecialchars($reply["user_name"]); ?></h5>
                                                <span><?php echo htmlspecialchars($reply["created_at"]); ?></span>
                                            </div>
                                            <p><?php echo nl2br(htmlspecialchars($reply["content"])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-box">No comments yet.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

</body>
</html>