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

// هذا يجيب كل التقييمات على جلسات المقدم
$reviews = [];
$stmt = $conn->prepare("
    SELECT
        session_reviews.review_id,
        session_reviews.rating,
        session_reviews.review_text,
        session_reviews.reviewed_at,
        sessions.title AS session_title,
        users.name AS student_name
    FROM session_reviews
    INNER JOIN sessions ON session_reviews.session_id = sessions.session_id
    INNER JOIN users ON session_reviews.user_id = users.user_id
    WHERE sessions.presenter_id = ?
    ORDER BY session_reviews.reviewed_at DESC
");
$stmt->bind_param("i", $presenter_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Received</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Reviews Received</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_session.php">Create Session</a></li>
                <li><a href="manage_sessions.php">Manage Sessions</a></li>
                <li><a href="upload_video.php">Upload Video</a></li>
                <li><a href="manage_videos.php">Manage Videos</a></li>
                <li><a href="manage_comments.php">Comments</a></li>
                <li><a href="reviews_received.php" class="active">Reviews Received</a></li>
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
            <h2>Session Reviews</h2>
            <p>View ratings and feedback submitted by students for your completed sessions.</p>
        </div>

        <?php if (!empty($reviews)): ?>
            <div class="review-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <h3><?php echo htmlspecialchars($review["session_title"]); ?></h3>
                        <p><strong>Student:</strong> <?php echo htmlspecialchars($review["student_name"]); ?></p>
                        <p><strong>Rating:</strong> <?php echo (int) $review["rating"]; ?> / 5</p>
                        <p><strong>Review Date:</strong> <?php echo htmlspecialchars($review["reviewed_at"]); ?></p>

                        <div class="review-text-box">
                            <?php echo nl2br(htmlspecialchars($review["review_text"])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">No reviews received yet.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>