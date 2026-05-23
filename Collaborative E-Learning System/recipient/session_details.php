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

// هذا يجيب رقم المستخدم
$user_id = $_SESSION["user_id"];

// هذا يتحقق من رقم الجلسة
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo "<script>alert('Invalid session ID'); window.location.href='browse_sessions.php';</script>";
    exit();
}

$session_id = (int) $_GET["id"];

// هذا إذا المستخدم ضغط زر الانضمام
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["join_session"])) {

    // هذا يتحقق إذا المستخدم منضم قبل
    $check_join = $conn->prepare("
        SELECT id 
        FROM join_sessions 
        WHERE user_id = ? AND session_id = ?
    ");
    $check_join->bind_param("ii", $user_id, $session_id);
    $check_join->execute();
    $join_result = $check_join->get_result();

    if ($join_result->num_rows > 0) {
        echo "<script>alert('You already joined this session');</script>";
    } else {
        // هذا يضيف سجل الانضمام
        $join_stmt = $conn->prepare("
            INSERT INTO join_sessions (user_id, session_id)
            VALUES (?, ?)
        ");
        $join_stmt->bind_param("ii", $user_id, $session_id);

        if ($join_stmt->execute()) {
            echo "<script>alert('Session joined successfully'); window.location.href='session_details.php?id=$session_id';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to join session');</script>";
        }
    }
}

// هذا يجيب تفاصيل الجلسة
$stmt = $conn->prepare("
    SELECT 
        sessions.session_id,
        sessions.title,
        sessions.description,
        sessions.session_date,
        sessions.session_time,
        sessions.meeting_link,
        sessions.specialization,
        sessions.status,
        sessions.created_at,
        users.name AS presenter_name,
        users.email AS presenter_email
    FROM sessions
    INNER JOIN users ON sessions.presenter_id = users.user_id
    WHERE sessions.session_id = ?
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();

// هذا يتحقق إذا الجلسة موجودة
if ($result->num_rows === 0) {
    echo "<script>alert('Session not found'); window.location.href='browse_sessions.php';</script>";
    exit();
}

$session = $result->fetch_assoc();

// هذا يتحقق إذا المستخدم منضم للجلسة
$is_joined = false;
$joined_stmt = $conn->prepare("
    SELECT id 
    FROM join_sessions 
    WHERE user_id = ? AND session_id = ?
");
$joined_stmt->bind_param("ii", $user_id, $session_id);
$joined_stmt->execute();
$joined_result = $joined_stmt->get_result();

if ($joined_result->num_rows > 0) {
    $is_joined = true;
}

// هذا يجيب التقييم الحالي إذا كان موجود
$user_review = null;
$review_stmt = $conn->prepare("
    SELECT review_id, rating, review_text, reviewed_at
    FROM session_reviews
    WHERE user_id = ? AND session_id = ?
    LIMIT 1
");
$review_stmt->bind_param("ii", $user_id, $session_id);
$review_stmt->execute();
$review_result = $review_stmt->get_result();

if ($review_result && $review_result->num_rows > 0) {
    $user_review = $review_result->fetch_assoc();
}

// هذا إذا المستخدم أرسل تقييم جديد
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"])) {
    $rating = (int) $_POST["rating"];
    $review_text = trim($_POST["review_text"]);

    // هذا يتحقق إن الجلسة مكتملة والمستخدم منضم وما قيّم قبل
    if (!$is_joined) {
        echo "<script>alert('You must join the session first');</script>";
    } elseif ($session["status"] !== "completed") {
        echo "<script>alert('You can review only completed sessions');</script>";
    } elseif ($user_review) {
        echo "<script>alert('You have already reviewed this session');</script>";
    } elseif ($rating < 1 || $rating > 5) {
        echo "<script>alert('Rating must be between 1 and 5');</script>";
    } else {
        $insert_review = $conn->prepare("
            INSERT INTO session_reviews (user_id, session_id, rating, review_text)
            VALUES (?, ?, ?, ?)
        ");
        $insert_review->bind_param("iiis", $user_id, $session_id, $rating, $review_text);

        if ($insert_review->execute()) {
            echo "<script>alert('Review submitted successfully'); window.location.href='session_details.php?id=$session_id';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to submit review');</script>";
        }
    }
}

// هذا يجيب كل التقييمات الخاصة بالج session
$all_reviews = [];
$all_reviews_stmt = $conn->prepare("
    SELECT 
        session_reviews.rating,
        session_reviews.review_text,
        session_reviews.reviewed_at,
        users.name AS student_name
    FROM session_reviews
    INNER JOIN users ON session_reviews.user_id = users.user_id
    WHERE session_reviews.session_id = ?
    ORDER BY session_reviews.reviewed_at DESC
");
$all_reviews_stmt->bind_param("i", $session_id);
$all_reviews_stmt->execute();
$all_reviews_result = $all_reviews_stmt->get_result();

if ($all_reviews_result && $all_reviews_result->num_rows > 0) {
    while ($row = $all_reviews_result->fetch_assoc()) {
        $all_reviews[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Details</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<!-- هذا الهيدر -->
<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Session Details</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="browse_sessions.php" class="active">Sessions</a></li>
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

        <div class="details-wrapper">
            <div class="details-card">
                <div class="details-header">
                    <h2><?php echo htmlspecialchars($session["title"]); ?></h2>
                    <span class="status-badge status-<?php echo strtolower($session["status"]); ?>">
                        <?php echo htmlspecialchars(ucfirst($session["status"])); ?>
                    </span>
                </div>

                <div class="details-content">
                    <p><strong>Presenter:</strong> <?php echo htmlspecialchars($session["presenter_name"]); ?></p>
                    <p><strong>Presenter Email:</strong> <?php echo htmlspecialchars($session["presenter_email"]); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($session["session_date"]); ?></p>
                    <p><strong>Time:</strong> <?php echo htmlspecialchars($session["session_time"]); ?></p>
                    <p><strong>Specialization:</strong> <?php echo htmlspecialchars($session["specialization"]); ?></p>
                    <p><strong>Description:</strong></p>
                    <div class="details-description">
                        <?php echo nl2br(htmlspecialchars($session["description"])); ?>
                    </div>

                    <p><strong>Meeting Link:</strong></p>
                    <?php if (!empty($session["meeting_link"])): ?>
                        <div class="meeting-link-box">
                            <a href="<?php echo htmlspecialchars($session["meeting_link"]); ?>" target="_blank" class="meeting-link">
                                Open Session Link
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-box">No meeting link available.</div>
                    <?php endif; ?>

                    <div class="details-actions">
                        <?php if ($is_joined): ?>
                            <button class="btn btn-success" disabled>Already Joined</button>
                        <?php else: ?>
                            <form method="POST">
                                <button type="submit" name="join_session" class="btn btn-primary">Join Session</button>
                            </form>
                        <?php endif; ?>

                        <a href="browse_sessions.php" class="btn btn-secondary">Back to Sessions</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_joined && $session["status"] === "completed"): ?>
            <div class="details-wrapper" style="margin-top: 30px;">
                <div class="details-card">
                    <h3 style="margin-bottom: 20px; color: var(--primary-color);">Session Review</h3>

                    <?php if ($user_review): ?>
                        <div class="review-text-box">
                            <p><strong>Your Rating:</strong> <?php echo (int) $user_review["rating"]; ?> / 5</p>
                            <p><strong>Review Date:</strong> <?php echo htmlspecialchars($user_review["reviewed_at"]); ?></p>
                            <p style="margin-top: 10px;"><strong>Your Review:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($user_review["review_text"])); ?></p>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div style="margin-bottom: 15px;">
                                <label>Rating</label>
                                <select name="rating" class="input-field" required>
                                    <option value="">Select Rating</option>
                                    <option value="1">1 - Poor</option>
                                    <option value="2">2 - Fair</option>
                                    <option value="3">3 - Good</option>
                                    <option value="4">4 - Very Good</option>
                                    <option value="5">5 - Excellent</option>
                                </select>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <label>Review</label>
                                <textarea name="review_text" class="input-field textarea-field" rows="4" placeholder="Write your feedback here"></textarea>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="details-wrapper" style="margin-top: 30px;">
            <div class="details-card">
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">Student Reviews</h3>

                <?php if (!empty($all_reviews)): ?>
                    <div class="review-grid">
                        <?php foreach ($all_reviews as $review): ?>
                            <div class="review-card">
                                <h3><?php echo htmlspecialchars($review["student_name"]); ?></h3>
                                <p><strong>Rating:</strong> <?php echo (int) $review["rating"]; ?> / 5</p>
                                <p><strong>Review Date:</strong> <?php echo htmlspecialchars($review["reviewed_at"]); ?></p>
                                <div class="review-text-box">
                                    <?php echo nl2br(htmlspecialchars($review["review_text"])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-box">No reviews yet for this session.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

</body>
</html>