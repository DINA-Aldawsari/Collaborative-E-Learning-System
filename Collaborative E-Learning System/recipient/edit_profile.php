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

// هذا يجيب بيانات المستخدم الحالية
$stmt = $conn->prepare("
    SELECT name, email, specialization, profile_image
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('User not found'); window.location.href='../login.php';</script>";
    exit();
}

$user = $result->fetch_assoc();

// هذا إذا المستخدم ضغط زر الحفظ
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // هذا نجيب البيانات من الفورم
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $specialization = trim($_POST["specialization"]);
    $new_password = trim($_POST["new_password"]);

    $profile_image = $user["profile_image"];

    // هذا تحقق بسيط
    if (empty($name) || empty($email)) {
        echo "<script>alert('Name and email are required');</script>";
    } else {

        // هذا يتحقق إذا الإيميل مستخدم من شخص ثاني
        $email_check = $conn->prepare("
            SELECT user_id
            FROM users
            WHERE email = ? AND user_id != ?
        ");
        $email_check->bind_param("si", $email, $user_id);
        $email_check->execute();
        $email_result = $email_check->get_result();

        if ($email_result->num_rows > 0) {
            echo "<script>alert('This email is already used by another account');</script>";
        } else {

            // هذا يرفع الصورة إذا المستخدم اختار صورة
            if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] === 0) {

                $allowed_types = ["image/jpeg", "image/png", "image/jpg", "image/webp"];
                $file_type = $_FILES["profile_image"]["type"];

                if (in_array($file_type, $allowed_types)) {
                    $upload_dir = "../uploads/profiles/";

                    // هذا يتأكد إن الفولدر موجود
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
                    $target_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_path)) {
                        $profile_image = "uploads/profiles/" . $file_name;
                    }
                } else {
                    echo "<script>alert('Invalid image type. Please upload JPG, PNG, or WEBP');</script>";
                }
            }

            // هذا إذا المستخدم كتب كلمة مرور جديدة
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $conn->prepare("
                    UPDATE users
                    SET name = ?, email = ?, specialization = ?, profile_image = ?, password = ?
                    WHERE user_id = ?
                ");
                $update_stmt->bind_param("sssssi", $name, $email, $specialization, $profile_image, $hashed_password, $user_id);
            } else {
                // هذا إذا ما كتب كلمة مرور جديدة
                $update_stmt = $conn->prepare("
                    UPDATE users
                    SET name = ?, email = ?, specialization = ?, profile_image = ?
                    WHERE user_id = ?
                ");
                $update_stmt->bind_param("ssssi", $name, $email, $specialization, $profile_image, $user_id);
            }

            if ($update_stmt->execute()) {
                // هذا يحدث الاسم بالسيشن بعد التعديل
                $_SESSION["name"] = $name;

                echo "<script>alert('Profile updated successfully'); window.location.href='edit_profile.php';</script>";
                exit();
            } else {
                echo "<script>alert('Failed to update profile');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <h1>Edit Profile</h1>
        </div>

        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="browse_sessions.php">Sessions</a></li>
                <li><a href="browse_videos.php">Videos</a></li>
                <li><a href="edit_profile.php" class="active">Profile</a></li>
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

        <div class="profile-wrapper">
            <div class="profile-card">
                <h2>Update Your Profile</h2>

                <form method="POST" enctype="multipart/form-data">

                    <?php if (!empty($user["profile_image"])): ?>
                        <div class="profile-image-box">
                            <img src="../<?php echo htmlspecialchars($user["profile_image"]); ?>" alt="Profile Image" class="profile-preview">
                        </div>
                    <?php endif; ?>

                    <div>
                        <label>Full Name</label>
                        <input type="text" name="name" class="input-field" value="<?php echo htmlspecialchars($user["name"]); ?>" required>
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="email" name="email" class="input-field" value="<?php echo htmlspecialchars($user["email"]); ?>" required>
                    </div>

                    <div>
                        <label>Specialization</label>
                        <input type="text" name="specialization" class="input-field" value="<?php echo htmlspecialchars($user["specialization"]); ?>">
                    </div>

                    <div>
                        <label>New Password</label>
                        <input type="password" name="new_password" class="input-field" placeholder="Leave empty if you do not want to change it">
                    </div>

                    <div>
                        <label>Profile Image</label>
                        <input type="file" name="profile_image" class="input-field" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

    </div>
</section>

</body>
</html>