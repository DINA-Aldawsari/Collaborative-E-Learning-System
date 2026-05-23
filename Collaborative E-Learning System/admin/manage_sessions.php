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

// هذا إذا الأدمن طلب حذف جلسة
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $session_id = (int) $_GET["delete"];

    $delete_stmt = $conn->prepare("
        DELETE FROM sessions
        WHERE session_id = ?
    ");
    $delete_stmt->bind_param("i", $session_id);

    if ($delete_stmt->execute()) {
        echo "<script>alert('Session deleted successfully'); window.location.href='manage_sessions.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to delete session');</script>";
    }
}

// هذا الاستعلام الأساسي
$sql = "
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
    WHERE 1=1
";

$params = [];
$types = "";

// هذا فلتر البحث
if (!empty($search)) {
    $sql .= " AND sessions.title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

$sql .= " ORDER BY sessions.created_at DESC";

$stmt = $conn->prepare($sql);

// هذا يربط البراميتر إذا فيه بحث
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$sessions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sessions</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Manage Sessions</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_role_requests.php">Role Requests</a></li>
                <li><a href="manage_sessions.php" class="active">Sessions</a></li>
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
            <h2>Sessions Management</h2>
            <p>Review all sessions in the platform and remove inappropriate or unnecessary content when needed.</p>
        </div>

        <!-- هذا البحث -->
        <div class="filter-box">
            <form method="GET" class="filter-form" style="grid-template-columns: 1fr auto;">
                <div class="filter-group">
                    <label>Search by Session Title</label>
                    <input
                        type="text"
                        name="search"
                        class="input-field"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Enter session title"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="manage_sessions.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (!empty($sessions)): ?>
            <div class="session-grid">
                <?php foreach ($sessions as $session): ?>
                    <div class="session-card">
                        <div class="details-header" style="margin-bottom: 15px;">
                            <h3><?php echo htmlspecialchars($session["title"]); ?></h3>
                            <span class="status-badge status-<?php echo strtolower($session["status"]); ?>">
                                <?php echo htmlspecialchars(ucfirst($session["status"])); ?>
                            </span>
                        </div>

                        <p><strong>Presenter:</strong> <?php echo htmlspecialchars($session["presenter_name"]); ?></p>
                        <p><strong>Presenter Email:</strong> <?php echo htmlspecialchars($session["presenter_email"]); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($session["session_date"]); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($session["session_time"]); ?></p>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($session["specialization"] ?: "N/A"); ?></p>

                        <?php if (!empty($session["description"])): ?>
                            <p class="session-description"><?php echo htmlspecialchars($session["description"]); ?></p>
                        <?php else: ?>
                            <p class="session-description">No description available.</p>
                        <?php endif; ?>

                        <div class="card-actions">
                            <a href="<?php echo !empty($session["meeting_link"]) ? htmlspecialchars($session["meeting_link"]) : '#'; ?>"
                               class="btn btn-secondary"
                               target="_blank"
                               <?php echo empty($session["meeting_link"]) ? 'onclick="return false;" style="opacity:0.6; cursor:not-allowed;"' : ''; ?>>
                                Meeting Link
                            </a>

                            <a href="manage_sessions.php?delete=<?php echo $session["session_id"]; ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this session?');">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">No sessions found.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>