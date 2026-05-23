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

// هذا إذا الأدمن وافق على الطلب
if (isset($_GET["approve"]) && is_numeric($_GET["approve"])) {
    $request_id = (int) $_GET["approve"];

    // هذا يجيب بيانات الطلب
    $request_stmt = $conn->prepare("
        SELECT request_id, user_id, status
        FROM role_requests
        WHERE request_id = ?
    ");
    $request_stmt->bind_param("i", $request_id);
    $request_stmt->execute();
    $request_result = $request_stmt->get_result();

    if ($request_result && $request_result->num_rows > 0) {
        $request = $request_result->fetch_assoc();

        if ($request["status"] === "pending") {
            $user_id = (int) $request["user_id"];

            // هذا يبدأ ترانزكشن عشان التحديثين يصيرون مع بعض
            $conn->begin_transaction();

            try {
                // هذا يحدث حالة الطلب
                $update_request = $conn->prepare("
                    UPDATE role_requests
                    SET status = 'approved', reviewed_by = ?, review_date = NOW()
                    WHERE request_id = ?
                ");
                $update_request->bind_param("ii", $admin_id, $request_id);
                $update_request->execute();

                // هذا يحدث دور المستخدم إلى presenter
                $update_user = $conn->prepare("
                    UPDATE users
                    SET role = 'presenter'
                    WHERE user_id = ?
                ");
                $update_user->bind_param("i", $user_id);
                $update_user->execute();

                $conn->commit();

                echo "<script>alert('Request approved successfully'); window.location.href='manage_role_requests.php';</script>";
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Failed to approve request'); window.location.href='manage_role_requests.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Only pending requests can be approved'); window.location.href='manage_role_requests.php';</script>";
            exit();
        }
    }
}

// هذا إذا الأدمن رفض الطلب
if (isset($_GET["reject"]) && is_numeric($_GET["reject"])) {
    $request_id = (int) $_GET["reject"];

    // هذا يحدث حالة الطلب إلى مرفوض
    $reject_stmt = $conn->prepare("
        UPDATE role_requests
        SET status = 'rejected', reviewed_by = ?, review_date = NOW()
        WHERE request_id = ? AND status = 'pending'
    ");
    $reject_stmt->bind_param("ii", $admin_id, $request_id);

    if ($reject_stmt->execute()) {
        echo "<script>alert('Request rejected successfully'); window.location.href='manage_role_requests.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to reject request');</script>";
    }
}

// هذا يجيب كل الطلبات مع بيانات المستخدم والمراجع
$requests = [];
$stmt = $conn->prepare("
    SELECT 
        role_requests.request_id,
        role_requests.request_date,
        role_requests.status,
        role_requests.review_date,
        users.name AS student_name,
        users.email AS student_email,
        users.specialization,
        reviewers.name AS reviewer_name
    FROM role_requests
    INNER JOIN users ON role_requests.user_id = users.user_id
    LEFT JOIN users AS reviewers ON role_requests.reviewed_by = reviewers.user_id
    ORDER BY role_requests.request_date DESC
");
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Role Requests</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Manage Role Requests</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_role_requests.php" class="active">Role Requests</a></li>
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
            <h2>Role Upgrade Requests</h2>
            <p>Review recipient student requests and decide whether to approve or reject them.</p>
        </div>

        <?php if (!empty($requests)): ?>
            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Review Date</th>
                            <th>Reviewed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request["student_name"]); ?></td>
                                <td><?php echo htmlspecialchars($request["student_email"]); ?></td>
                                <td><?php echo htmlspecialchars($request["specialization"] ?: "N/A"); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($request["status"]); ?>">
                                        <?php echo htmlspecialchars(ucfirst($request["status"])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($request["request_date"]); ?></td>
                                <td><?php echo htmlspecialchars($request["review_date"] ?: "Not reviewed"); ?></td>
                                <td><?php echo htmlspecialchars($request["reviewer_name"] ?: "Not assigned"); ?></td>
                                <td>
                                    <?php if ($request["status"] === "pending"): ?>
                                        <div class="table-actions">
                                            <a href="manage_role_requests.php?approve=<?php echo $request["request_id"]; ?>" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this request?');">Approve</a>
                                            <a href="manage_role_requests.php?reject=<?php echo $request["request_id"]; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this request?');">Reject</a>
                                        </div>
                                    <?php else: ?>
                                        <span class="status-text">Already Reviewed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-box">No role upgrade requests found.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>