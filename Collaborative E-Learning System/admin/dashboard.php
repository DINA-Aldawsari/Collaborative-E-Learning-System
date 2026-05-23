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

// هذا يجيب اسم الأدمن
$admin_name = $_SESSION["name"];

// هذا يجيب إجمالي المستخدمين
$total_users = 0;
$users_result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($users_result && $users_result->num_rows > 0) {
    $total_users = $users_result->fetch_assoc()["total"];
}

// هذا يجيب إجمالي الجلسات
$total_sessions = 0;
$sessions_result = $conn->query("SELECT COUNT(*) AS total FROM sessions");
if ($sessions_result && $sessions_result->num_rows > 0) {
    $total_sessions = $sessions_result->fetch_assoc()["total"];
}

// هذا يجيب إجمالي الفيديوهات
$total_videos = 0;
$videos_result = $conn->query("SELECT COUNT(*) AS total FROM videos");
if ($videos_result && $videos_result->num_rows > 0) {
    $total_videos = $videos_result->fetch_assoc()["total"];
}

// هذا يجيب عدد طلبات الترقية المعلقة
$pending_requests = 0;
$requests_result = $conn->query("SELECT COUNT(*) AS total FROM role_requests WHERE status = 'pending'");
if ($requests_result && $requests_result->num_rows > 0) {
    $pending_requests = $requests_result->fetch_assoc()["total"];
}

// هذا يجيب إجمالي التعليقات
$total_comments = 0;
$comments_result = $conn->query("SELECT COUNT(*) AS total FROM comments");
if ($comments_result && $comments_result->num_rows > 0) {
    $total_comments = $comments_result->fetch_assoc()["total"];
}

// هذا يجيب آخر 5 طلبات ترقية
$latest_requests = [];
$latest_requests_stmt = $conn->prepare("
    SELECT 
        role_requests.request_id,
        role_requests.request_date,
        role_requests.status,
        users.name AS student_name,
        users.email AS student_email
    FROM role_requests
    INNER JOIN users ON role_requests.user_id = users.user_id
    ORDER BY role_requests.request_date DESC
    LIMIT 5
");
$latest_requests_stmt->execute();
$latest_requests_result = $latest_requests_stmt->get_result();

if ($latest_requests_result && $latest_requests_result->num_rows > 0) {
    while ($row = $latest_requests_result->fetch_assoc()) {
        $latest_requests[] = $row;
    }
}

// هذا يجيب آخر 5 أنشطة بالنظام
$latest_logs = [];
$latest_logs_stmt = $conn->prepare("
    SELECT 
        system_logs.log_id,
        system_logs.action,
        system_logs.log_date,
        users.name AS user_name
    FROM system_logs
    LEFT JOIN users ON system_logs.user_id = users.user_id
    ORDER BY system_logs.log_date DESC
    LIMIT 5
");
$latest_logs_stmt->execute();
$latest_logs_result = $latest_logs_stmt->get_result();

if ($latest_logs_result && $latest_logs_result->num_rows > 0) {
    while ($row = $latest_logs_result->fetch_assoc()) {
        $latest_logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Admin Dashboard</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_role_requests.php">Role Requests</a></li>
                <li><a href="manage_sessions.php">Sessions</a></li>
                <li><a href="manage_videos.php">Videos</a></li>
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
            <h2>Welcome, <?php echo htmlspecialchars($admin_name); ?></h2>
            <p>Monitor the platform, manage users, review requests, and control system content.</p>
        </div>

        <!-- هذا كروت الإحصائيات -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h3>Total Users</h3>
                <p><?php echo $total_users; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Total Sessions</h3>
                <p><?php echo $total_sessions; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Total Videos</h3>
                <p><?php echo $total_videos; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Pending Requests</h3>
                <p><?php echo $pending_requests; ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Total Comments</h3>
                <p><?php echo $total_comments; ?></p>
            </div>
        </div>

        <!-- هذا آخر طلبات الترقية -->
        <div class="dashboard-section">
            <div class="section-header-row">
                <h3>Latest Role Upgrade Requests</h3>
                <a href="manage_role_requests.php" class="btn btn-primary">View All</a>
            </div>

            <?php if (!empty($latest_requests)): ?>
                <div class="table-wrapper">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Request Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request["student_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($request["student_email"]); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($request["status"]); ?>">
                                            <?php echo htmlspecialchars(ucfirst($request["status"])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request["request_date"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-box">No role requests found.</div>
            <?php endif; ?>
        </div>

        <!-- هذا آخر الأنشطة -->
        <div class="dashboard-section">
            <div class="section-header-row">
                <h3>Recent System Activity</h3>
                <a href="monitor_system.php" class="btn btn-primary">View All Logs</a>
            </div>

            <?php if (!empty($latest_logs)): ?>
                <div class="table-wrapper">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log["user_name"] ?? "Unknown User"); ?></td>
                                    <td><?php echo htmlspecialchars($log["action"]); ?></td>
                                    <td><?php echo htmlspecialchars($log["log_date"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-box">No system logs found.</div>
            <?php endif; ?>
        </div>

    </div>
</section>

</body>
</html>