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

// هذا إذا المستخدم ضغط حذف تعليق
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $comment_id = (int) $_GET["delete"];

    // هذا يتحقق إن التعليق على فيديو تابع للمقدم
    $delete_check = $conn->prepare("
        SELECT comments.comment_id
        FROM comments
        INNER JOIN videos ON comments.video_id = videos.video_id
        WHERE comments.comment_id = ?
          AND videos.presenter_id = ?
    ");
    $delete_check->bind_param("ii", $comment_id, $presenter_id);
    $delete_check->execute();
    $delete_result = $delete_check->get_result();

  
}

// هذا إذا المستخدم أرسل رد
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reply_comment"])) {
    $parent_comment_id = (int) $_POST["parent_comment_id"];
    $video_id = (int) $_POST["video_id"];
    $reply_content = trim($_POST["reply_content"]);

    if (empty($reply_content)) {
        echo "<script>alert('Please enter your reply');</script>";
    } else {
        // هذا يتحقق إن الفيديو تابع للمقدم
        $reply_check = $conn->prepare("
            SELECT video_id
            FROM videos
            WHERE video_id = ?
              AND presenter_id = ?
        ");
        $reply_check->bind_param("ii", $video_id, $presenter_id);
        $reply_check->execute();
        $reply_check_result = $reply_check->get_result();

        if ($reply_check_result && $reply_check_result->num_rows > 0) {
            // هذا يحفظ الرد كتعليق مربوط بالتعليق الأساسي
            $insert_reply = $conn->prepare("
                INSERT INTO comments (content, user_id, video_id, parent_comment_id)
                VALUES (?, ?, ?, ?)
            ");
            $insert_reply->bind_param("siii", $reply_content, $presenter_id, $video_id, $parent_comment_id);

            if ($insert_reply->execute()) {
                echo "<script>alert('Reply added successfully'); window.location.href='manage_comments.php';</script>";
                exit();
            } else {
                echo "<script>alert('Failed to add reply');</script>";
            }
        } else {
            echo "<script>alert('Invalid video or access denied');</script>";
        }
    }
}

// هذا يجيب التعليقات الرئيسية على فيديوهات المقدم
$comments = [];
$comments_stmt = $conn->prepare("
    SELECT
        comments.comment_id,
        comments.content,
        comments.created_at,
        comments.video_id,
        users.name AS student_name,
        videos.title AS video_title
    FROM comments
    INNER JOIN users ON comments.user_id = users.user_id
    INNER JOIN videos ON comments.video_id = videos.video_id
    WHERE videos.presenter_id = ?
      AND comments.parent_comment_id IS NULL
    ORDER BY comments.created_at DESC
");
$comments_stmt->bind_param("i", $presenter_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

if ($comments_result && $comments_result->num_rows > 0) {
    while ($row = $comments_result->fetch_assoc()) {
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
                <li><a href="create_session.php">Create Session</a></li>
                <li><a href="manage_sessions.php">Manage Sessions</a></li>
                <li><a href="upload_video.php">Upload Video</a></li>
                <li><a href="manage_videos.php">Manage Videos</a></li>
                <li><a href="manage_comments.php" class="active">Comments</a></li>
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
            <h2>Comments on Your Videos</h2>
            <p>Review student comments, reply to them, or remove inappropriate content.</p>
        </div>

        <?php if (!empty($comments)): ?>
            <div class="comment-list-box">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <h4><?php echo htmlspecialchars($comment["student_name"]); ?></h4>
                            <span><?php echo htmlspecialchars($comment["created_at"]); ?></span>
                        </div>

                        <p><strong>Video:</strong> <?php echo htmlspecialchars($comment["video_title"]); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($comment["content"])); ?></p>

                        <?php
                        // هذا يجيب الردود التابعة لهذا التعليق
                        $replies = [];
                        $reply_stmt = $conn->prepare("
                            SELECT
                                comments.comment_id,
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

                        <div class="reply-form-box">
                            <form method="POST">
                                <input type="hidden" name="parent_comment_id" value="<?php echo $comment["comment_id"]; ?>">
                                <input type="hidden" name="video_id" value="<?php echo $comment["video_id"]; ?>">

                                <div>
                                    <label>Reply</label>
                                    <textarea name="reply_content" class="input-field textarea-field" rows="3" required></textarea>
                                </div>

                                <button type="submit" name="reply_comment" class="btn btn-primary">Post Reply</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">No comments found on your videos.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>