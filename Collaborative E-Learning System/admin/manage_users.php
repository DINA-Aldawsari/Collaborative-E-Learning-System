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

// هذا يجيب رقم الأدمن الحالي
$admin_id = $_SESSION["user_id"];

// هذا للبحث
$search = trim($_GET["search"] ?? "");

// هذا إذا الأدمن ضغط زر التحديث
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {
    $user_id = (int) $_POST["user_id"];
    $role = trim($_POST["role"]);
    $status = trim($_POST["status"]);

    // هذا يمنع تعديل الأدمن الحالي إلى دور ثاني بالغلط
    if ($user_id === $admin_id && $role !== "admin") {
        echo "<script>alert('You cannot change your own role from admin');</script>";
    } else {
        $update_stmt = $conn->prepare("
            UPDATE users
            SET role = ?, status = ?
            WHERE user_id = ?
        ");
        $update_stmt->bind_param("ssi", $role, $status, $user_id);

        if ($update_stmt->execute()) {
            echo "<script>alert('User updated successfully'); window.location.href='manage_users.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to update user');</script>";
        }
    }
}

// هذا إذا الأدمن طلب حذف مستخدم
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $delete_user_id = (int) $_GET["delete"];

    // هذا يمنع الأدمن من حذف نفسه
    if ($delete_user_id === $admin_id) {
        echo "<script>alert('You cannot delete your own account'); window.location.href='manage_users.php';</script>";
        exit();
    }

    $delete_stmt = $conn->prepare("
        DELETE FROM users
        WHERE user_id = ?
    ");
    $delete_stmt->bind_param("i", $delete_user_id);

    if ($delete_stmt->execute()) {
        echo "<script>alert('User deleted successfully'); window.location.href='manage_users.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to delete user');</script>";
    }
}

// هذا الاستعلام الأساسي
$sql = "
    SELECT user_id, name, email, role, specialization, status, created_at
    FROM users
    WHERE 1=1
";

$params = [];
$types = "";

// هذا فلتر البحث
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $params[] = $search_value;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

// هذا يربط البراميتر إذا فيه بحث
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Manage Users</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php" class="active">Users</a></li>
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
            <h2>Users Management</h2>
            <p>View all users, update their roles and statuses, or remove accounts when needed.</p>
        </div>

        <!-- هذا البحث -->
        <div class="filter-box">
            <form method="GET" class="filter-form" style="grid-template-columns: 1fr auto;">
                <div class="filter-group">
                    <label>Search by Name or Email</label>
                    <input
                        type="text"
                        name="search"
                        class="input-field"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Enter name or email"
                    >
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="manage_users.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (!empty($users)): ?>
            <div class="admin-user-grid">
                <?php foreach ($users as $user): ?>
                    <div class="admin-user-card">
                        <h3><?php echo htmlspecialchars($user["name"]); ?></h3>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user["email"]); ?></p>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($user["specialization"] ?: "N/A"); ?></p>
                        <p><strong>Created At:</strong> <?php echo htmlspecialchars($user["created_at"]); ?></p>

                        <form method="POST" class="admin-user-form">
                            <input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">

                            <div>
                                <label>Role</label>
                                <select name="role" class="input-field" required>
                                    <option value="recipient" <?php echo ($user["role"] === "recipient") ? "selected" : ""; ?>>Recipient</option>
                                    <option value="presenter" <?php echo ($user["role"] === "presenter") ? "selected" : ""; ?>>Presenter</option>
                                    <option value="admin" <?php echo ($user["role"] === "admin") ? "selected" : ""; ?>>Admin</option>
                                </select>
                            </div>

                            <div>
                                <label>Status</label>
                                <select name="status" class="input-field" required>
                                    <option value="active" <?php echo ($user["status"] === "active") ? "selected" : ""; ?>>Active</option>
                                    <option value="inactive" <?php echo ($user["status"] === "inactive") ? "selected" : ""; ?>>Inactive</option>
                                    <option value="suspended" <?php echo ($user["status"] === "suspended") ? "selected" : ""; ?>>Suspended</option>
                                </select>
                            </div>

                            <div class="card-actions">
                                <button type="submit" name="update_user" class="btn btn-primary">Update</button>
                                <?php if ((int)$user["user_id"] !== (int)$admin_id): ?>
                                    <a href="manage_users.php?delete=<?php echo $user["user_id"]; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary" disabled>Current Admin</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">No users found.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>