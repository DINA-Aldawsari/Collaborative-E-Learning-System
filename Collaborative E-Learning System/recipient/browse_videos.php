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

// هذا نجيب قيم البحث والفلترة
$search = trim($_GET["search"] ?? "");
$specialization = trim($_GET["specialization"] ?? "");

// هذا نجيب كل التخصصات الموجودة بالفيديوهات
$specializations = [];
$spec_result = $conn->query("
    SELECT DISTINCT specialization
    FROM videos
    WHERE specialization IS NOT NULL
      AND specialization != ''
    ORDER BY specialization ASC
");

if ($spec_result && $spec_result->num_rows > 0) {
    while ($row = $spec_result->fetch_assoc()) {
        $specializations[] = $row["specialization"];
    }
}

// هذا الاستعلام الأساسي
$sql = "
    SELECT
        videos.video_id,
        videos.title,
        videos.summary,
        videos.file_path,
        videos.specialization,
        videos.created_at,
        users.name AS presenter_name
    FROM videos
    INNER JOIN users ON videos.presenter_id = users.user_id
    WHERE 1=1
";

$params = [];
$types = "";

// هذا فلتر البحث بالعنوان
if (!empty($search)) {
    $sql .= " AND videos.title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// هذا فلتر التخصص
if (!empty($specialization)) {
    $sql .= " AND videos.specialization = ?";
    $params[] = $specialization;
    $types .= "s";
}

// هذا ترتيب الفيديوهات
$sql .= " ORDER BY videos.created_at DESC";

$stmt = $conn->prepare($sql);

// هذا يربط البراميتر إذا فيه بيانات
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$videos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Videos</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Browse Videos</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="browse_sessions.php">Sessions</a></li>
                <li><a href="browse_videos.php" class="active">Videos</a></li>
                <li><a href="edit_profile.php">Profile</a></li>
                <li><a href="request_upgrade.php">Upgrade Request</a></li>
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
            <h2>Available Videos</h2>
            <p>Browse educational videos, search by title, or filter by specialization.</p>
        </div>

        <div class="filter-box">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Search by Title</label>
                    <input
                        type="text"
                        name="search"
                        class="input-field"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Enter video title"
                    >
                </div>

                <div class="filter-group">
                    <label>Filter by Specialization</label>
                    <select name="specialization" class="input-field">
                        <option value="">All Specializations</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($specialization === $spec) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($spec); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="browse_videos.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (!empty($videos)): ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <h3><?php echo htmlspecialchars($video["title"]); ?></h3>
                        <p><strong>Presenter:</strong> <?php echo htmlspecialchars($video["presenter_name"]); ?></p>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($video["specialization"]); ?></p>
                        <p><strong>Uploaded:</strong> <?php echo htmlspecialchars($video["created_at"]); ?></p>
                        <p class="video-summary">
                            <?php echo htmlspecialchars($video["summary"]); ?>
                        </p>
                        <a href="video_details.php?id=<?php echo $video["video_id"]; ?>" class="btn btn-primary">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">No videos found.</div>
        <?php endif; ?>

    </div>
</section>

</body>
</html>