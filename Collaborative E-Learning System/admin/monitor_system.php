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

// هذا الاستعلام الأساسي
$sql = "
    SELECT
        system_logs.log_id,
        system_logs.action,
        system_logs.log_date,
        users.name AS user_name,
        users.email AS user_email,
        users.role AS user_role
    FROM system_logs
    LEFT JOIN users ON system_logs.user_id = users.user_id
    WHERE 1=1
";

$params = [];
$types = "";

// هذا فلتر البحث
if (!empty($search)) {
    $sql .= " AND (
        system_logs.action LIKE ?
        OR users.name LIKE ?
        OR users.email LIKE ?
        OR users.role LIKE ?
    )";
    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $types .= "ssss";
}

// هذا ترتيب السجلات من الأحدث للأقدم
$sql .= " ORDER BY system_logs.log_date DESC";

$stmt = $conn->prepare($sql);

// هذا يربط البراميتر إذا فيه بحث
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$logs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor System</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>System Monitoring</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_role_requests.php">Role Requests</a></li>
                <li><a href="manage_sessions.php">Sessions</a></li>
                <li><a href="manage_videos.php">Videos</a></li>
                <li><a href="manage_comments.php">Comments</a></li>
                <li><a href="monitor_system.php" class="active">System Logs</a></li>
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
            <h2>System Logs</h2>
            <p>Monitor user activities and track important actions performed within the platform.</p>
        </div>

        <!-- هذا البحث -->
        <div class="filter-box">
            <form method="GET" class="filter-form" style="grid-template-columns: 1fr auto;">
                <div class="filter-group">
                    <label>Search by Action or User</label>
                    <input
                        type="text"
                        name="search"
                        class="input-field"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Enter action, name, email, or role"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="monitor_system.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (!empty($logs)): ?>
            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Log Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo (int) $log["log_id"]; ?></td>
                                <td><?php echo htmlspecialchars($log["user_name"] ?: "Unknown User"); ?></td>
                                <td><?php echo htmlspecialchars($log["user_email"] ?: "N/A"); ?></td>
                                <td><?php echo htmlspecialchars($log["user_role"] ?: "N/A"); ?></td>
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
</section>

</body>
</html>