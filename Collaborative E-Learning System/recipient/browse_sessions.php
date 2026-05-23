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

// هذا نجيب كل التخصصات الموجودة عشان نحطها في الفلتر
$specializations = [];
$spec_result = $conn->query("
    SELECT DISTINCT specialization 
    FROM sessions 
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
        sessions.session_id,
        sessions.title,
        sessions.description,
        sessions.session_date,
        sessions.session_time,
        sessions.specialization,
        sessions.status,
        users.name AS presenter_name
    FROM sessions
    INNER JOIN users ON sessions.presenter_id = users.user_id
    WHERE sessions.status IN ('upcoming', 'ongoing')
";

$params = [];
$types = "";

// هذا فلتر البحث بالعنوان
if (!empty($search)) {
    $sql .= " AND sessions.title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// هذا فلتر التخصص
if (!empty($specialization)) {
    $sql .= " AND sessions.specialization = ?";
    $params[] = $specialization;
    $types .= "s";
}

// هذا ترتيب الجلسات
$sql .= " ORDER BY sessions.session_date ASC, sessions.session_time ASC";

$stmt = $conn->prepare($sql);

// هذا يربط البراميتر إذا فيه بحث أو فلترة
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
    <title>Browse Sessions</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<!-- هذا الهيدر -->
<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Browse Sessions</h1>
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

<!-- هذا القسم الرئيسي -->
<section class="section">
    <div class="container">

        <div class="section-title" style="text-align:left; margin-bottom: 25px;">
            <h2>Available Sessions</h2>
            <p>Browse educational sessions, search by title, or filter by specialization.</p>
        </div>

        <!-- هذا الفورم حق البحث والفلترة -->
        <div class="filter-box">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Search by Title</label>
                    <input 
                        type="text" 
                        name="search" 
                        class="input-field" 
                        value="<?php echo htmlspecialchars($search); ?>" 
                        placeholder="Enter session title"
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
                    <a href="browse_sessions.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- هذا عرض الجلسات -->
        <?php if (!empty($sessions)): ?>
            <div class="session-grid">
                <?php foreach ($sessions as $session): ?>
                    <div class="session-card">
                        <h3><?php echo htmlspecialchars($session["title"]); ?></h3>
                        <p><strong>Presenter:</strong> <?php echo htmlspecialchars($session["presenter_name"]); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($session["session_date"]); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($session["session_time"]); ?></p>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($session["specialization"]); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($session["status"])); ?></p>
                        <p class="session-description">
                            <?php echo htmlspecialchars($session["description"]); ?>
                        </p>
                        <a href="session_details.php?id=<?php echo $session["session_id"]; ?>" class="btn btn-primary">
                            View Details
                        </a>
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