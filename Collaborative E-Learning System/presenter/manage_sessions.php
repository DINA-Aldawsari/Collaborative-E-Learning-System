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

// هذا متغيرات عامة
$edit_mode = false;
$edit_session = null;

// هذا إذا المستخدم طلب حذف جلسة
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $session_id = (int) $_GET["delete"];

    // هذا يحذف الجلسة بشرط تكون تابعة لنفس المقدم
    $delete_stmt = $conn->prepare("
        DELETE FROM sessions
        WHERE session_id = ? AND presenter_id = ?
    ");
    $delete_stmt->bind_param("ii", $session_id, $presenter_id);

    if ($delete_stmt->execute()) {
        echo "<script>alert('Session deleted successfully'); window.location.href='manage_sessions.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to delete session');</script>";
    }
}

// هذا إذا المستخدم ضغط زر التعديل
if (isset($_GET["edit"]) && is_numeric($_GET["edit"])) {
    $session_id = (int) $_GET["edit"];

    // هذا يجيب بيانات الجلسة للتعديل
    $edit_stmt = $conn->prepare("
        SELECT *
        FROM sessions
        WHERE session_id = ? AND presenter_id = ?
    ");
    $edit_stmt->bind_param("ii", $session_id, $presenter_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();

    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_session = $edit_result->fetch_assoc();
        $edit_mode = true;
    }
}

// هذا إذا المستخدم حفظ التعديلات
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_session"])) {
    $session_id = (int) $_POST["session_id"];
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $session_date = trim($_POST["session_date"]);
    $session_time = trim($_POST["session_time"]);
    $meeting_link = trim($_POST["meeting_link"]);
    $specialization = trim($_POST["specialization"]);
    $status = trim($_POST["status"]);

    // هذا تحقق بسيط
    if (empty($title) || empty($session_date) || empty($session_time)) {
        echo "<script>alert('Please fill all required fields');</script>";
    } else {
        $update_stmt = $conn->prepare("
            UPDATE sessions
            SET title = ?, description = ?, session_date = ?, session_time = ?, meeting_link = ?, specialization = ?, status = ?
            WHERE session_id = ? AND presenter_id = ?
        ");
        $update_stmt->bind_param(
            "sssssssii",
            $title,
            $description,
            $session_date,
            $session_time,
            $meeting_link,
            $specialization,
            $status,
            $session_id,
            $presenter_id
        );

        if ($update_stmt->execute()) {
            echo "<script>alert('Session updated successfully'); window.location.href='manage_sessions.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to update session');</script>";
        }
    }
}

// هذا يجيب كل الجلسات الخاصة بالمقدم
$sessions = [];
$list_stmt = $conn->prepare("
    SELECT *
    FROM sessions
    WHERE presenter_id = ?
    ORDER BY created_at DESC
");
$list_stmt->bind_param("i", $presenter_id);
$list_stmt->execute();
$list_result = $list_stmt->get_result();

if ($list_result && $list_result->num_rows > 0) {
    while ($row = $list_result->fetch_assoc()) {
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
                <li><a href="create_session.php">Create Session</a></li>
                <li><a href="manage_sessions.php" class="active">Manage Sessions</a></li>
                <li><a href="upload_video.php">Upload Video</a></li>
                <li><a href="manage_videos.php">Manage Videos</a></li>
                <li><a href="manage_comments.php">Comments</a></li>
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

        <?php if ($edit_mode && $edit_session): ?>
            <div class="profile-wrapper" style="margin-bottom: 35px;">
                <div class="profile-card">
                    <h2>Edit Session</h2>

                    <form method="POST">
                        <input type="hidden" name="session_id" value="<?php echo $edit_session["session_id"]; ?>">

                        <div>
                            <label>Session Title</label>
                            <input type="text" name="title" class="input-field" value="<?php echo htmlspecialchars($edit_session["title"]); ?>" required>
                        </div>

                        <div>
                            <label>Description</label>
                            <textarea name="description" class="input-field textarea-field" rows="5"><?php echo htmlspecialchars($edit_session["description"]); ?></textarea>
                        </div>

                        <div>
                            <label>Session Date</label>
                            <input type="date" name="session_date" class="input-field" value="<?php echo htmlspecialchars($edit_session["session_date"]); ?>" required>
                        </div>

                        <div>
                            <label>Session Time</label>
                            <input type="time" name="session_time" class="input-field" value="<?php echo htmlspecialchars($edit_session["session_time"]); ?>" required>
                        </div>

                        <div>
                            <label>Meeting Link</label>
                            <input type="url" name="meeting_link" class="input-field" value="<?php echo htmlspecialchars($edit_session["meeting_link"]); ?>">
                        </div>

                        <div>
                            <label>Specialization</label>
                            <input type="text" name="specialization" class="input-field" value="<?php echo htmlspecialchars($edit_session["specialization"]); ?>">
                        </div>

                        <div>
                            <label>Status</label>
                            <select name="status" class="input-field" required>
                                <option value="upcoming" <?php echo ($edit_session["status"] === "upcoming") ? "selected" : ""; ?>>Upcoming</option>
                                <option value="ongoing" <?php echo ($edit_session["status"] === "ongoing") ? "selected" : ""; ?>>Ongoing</option>
                                <option value="completed" <?php echo ($edit_session["status"] === "completed") ? "selected" : ""; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($edit_session["status"] === "cancelled") ? "selected" : ""; ?>>Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" name="update_session" class="btn btn-primary">Update Session</button>
                        <a href="manage_sessions.php" class="btn btn-secondary" style="margin-top: 10px; display: inline-block; text-align: center;">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="section-title" style="text-align:left; margin-bottom: 25px;">
            <h2>Your Sessions</h2>
            <p>View, edit, and delete the sessions you have created.</p>
        </div>

        <?php if (!empty($sessions)): ?>
            <div class="session-grid">
                <?php foreach ($sessions as $session): ?>
                    <div class="session-card">
                        <h3><?php echo htmlspecialchars($session["title"]); ?></h3>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($session["session_date"]); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($session["session_time"]); ?></p>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($session["specialization"]); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($session["status"])); ?></p>
                        <p class="session-description"><?php echo htmlspecialchars($session["description"]); ?></p>

                        <div class="card-actions">
                            <a href="manage_sessions.php?edit=<?php echo $session["session_id"]; ?>" class="btn btn-primary">Edit</a>
                            <a href="manage_sessions.php?delete=<?php echo $session["session_id"]; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this session?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">You have not created any sessions yet.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>