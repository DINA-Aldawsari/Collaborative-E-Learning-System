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

// هذا يجيب آخر طلب ترقية للمستخدم
$request_stmt = $conn->prepare("
    SELECT request_id, status, request_date, review_date
    FROM role_requests
    WHERE user_id = ?
    ORDER BY request_id DESC
    LIMIT 1
");
$request_stmt->bind_param("i", $user_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();

$latest_request = null;
if ($request_result && $request_result->num_rows > 0) {
    $latest_request = $request_result->fetch_assoc();
}

// هذا إذا المستخدم ضغط زر إرسال الطلب
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_request"])) {

    // هذا يمنع إرسال طلب جديد إذا آخر طلب ما زال pending
    if ($latest_request && $latest_request["status"] === "pending") {
        echo "<script>alert('You already have a pending request');</script>";
    } else {

        // هذا يضيف طلب جديد
        $insert_stmt = $conn->prepare("
            INSERT INTO role_requests (user_id, status)
            VALUES (?, 'pending')
        ");
        $insert_stmt->bind_param("i", $user_id);

        if ($insert_stmt->execute()) {
            echo "<script>alert('Role upgrade request submitted successfully'); window.location.href='request_upgrade.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to submit request');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Role Upgrade</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Role Upgrade Request</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="browse_sessions.php">Sessions</a></li>
                <li><a href="browse_videos.php">Videos</a></li>
                <li><a href="edit_profile.php">Profile</a></li>
                <li><a href="request_upgrade.php" class="active">Upgrade Request</a></li>
            </ul>
        </nav>

        <div class="nav-actions">
            <a href="../logout.php" class="btn btn-outline">Logout</a>
        </div>
    </div>
</header>

<section class="section">
    <div class="container">

        <div class="upgrade-wrapper">
            <div class="upgrade-card">
                <h2>Request Upgrade to Presenter Student</h2>
                <p class="upgrade-text">
                    You can submit a request to upgrade your account from Recipient Student to Presenter Student.
                    After submitting the request, the administrator will review it and decide whether to approve or reject it.
                </p>

                <?php if ($latest_request): ?>
                    <div class="request-status-box">
                        <h3>Latest Request Status</h3>
                        <p><strong>Status:</strong>
                            <span class="status-badge status-<?php echo strtolower($latest_request["status"]); ?>">
                                <?php echo htmlspecialchars(ucfirst($latest_request["status"])); ?>
                            </span>
                        </p>
                        <p><strong>Request Date:</strong> <?php echo htmlspecialchars($latest_request["request_date"]); ?></p>

                        <?php if (!empty($latest_request["review_date"])): ?>
                            <p><strong>Review Date:</strong> <?php echo htmlspecialchars($latest_request["review_date"]); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-box">You have not submitted any upgrade request yet.</div>
                <?php endif; ?>

                <form method="POST" class="upgrade-form">
                    <?php if ($latest_request && $latest_request["status"] === "pending"): ?>
                        <button type="button" class="btn btn-warning" disabled>Request Pending</button>
                    <?php else: ?>
                        <button type="submit" name="submit_request" class="btn btn-primary">Submit Upgrade Request</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

    </div>
</section>

</body>
</html>