<?php
session_start();

// هذا يحدد هل المستخدم مسجل دخول ولا لا
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['name'] ?? '';

// هذا يحدد رابط الداشبورد حسب نوع المستخدم
$dashboard_link = '';
if ($is_logged_in) {
    if ($user_role === 'recipient') {
        $dashboard_link = 'recipient/dashboard.php';
    } elseif ($user_role === 'presenter') {
        $dashboard_link = 'presenter/dashboard.php';
    } elseif ($user_role === 'admin') {
        $dashboard_link = 'admin/dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaborative E-Learning System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- هذا الهيدر الرئيسي -->
    <header class="main-header">
        <div class="container nav-container">
            <div class="logo">
                <h1>Collaborative E-Learning</h1>
            </div>

            <nav class="navbar">
                <ul class="nav-links">
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#roles">Roles</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>

            <div class="nav-actions">
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="btn btn-primary">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- هذا قسم الترحيب -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="hero-text">
                <h2>Learn Together, Share Knowledge, and Grow Academically</h2>

                <p>
                    The Collaborative E-Learning System is a smart academic platform that helps students
                    exchange knowledge through live sessions, recorded videos, and interactive comments
                    under administrative supervision.
                </p>

                <?php if ($is_logged_in): ?>
                    <p class="welcome-text">
                        Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>.
                    </p>
                    <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="btn btn-large btn-primary">Go to Dashboard</a>
                <?php else: ?>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-large btn-primary">Create Account</a>
                        <a href="login.php" class="btn btn-large btn-outline">Login</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="hero-box">
                <div class="hero-card">
                    <h3>Platform Goals</h3>
                    <ul>
                        <li>Support collaborative learning among students</li>
                        <li>Enable presenter students to share academic content</li>
                        <li>Provide live and recorded learning resources</li>
                        <li>Ensure content quality through admin supervision</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- هذا قسم عن النظام -->
    <section class="section" id="about">
        <div class="container">
            <div class="section-title">
                <h2>About the Platform</h2>
                <p>
                    This platform was developed to create an organized academic environment where students
                    can collaborate, attend live educational sessions, watch uploaded videos, and interact
                    with content in a secure and structured way.
                </p>
            </div>

            <div class="about-grid">
                <div class="info-card">
                    <h3>Academic Collaboration</h3>
                    <p>
                        Students can benefit from peer learning by joining sessions and accessing useful
                        educational videos related to their specialization.
                    </p>
                </div>

                <div class="info-card">
                    <h3>Role-Based Access</h3>
                    <p>
                        The system supports three user roles: Recipient Student, Presenter Student, and
                        Administrator, each with specific permissions and responsibilities.
                    </p>
                </div>

                <div class="info-card">
                    <h3>Supervised Learning Environment</h3>
                    <p>
                        Administrative supervision helps maintain content quality and ensures that the
                        platform remains safe and academically appropriate.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- هذا قسم المميزات -->
    <section class="section light-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Main Features</h2>
                <p>
                    The platform combines several functions in one place to make learning more interactive,
                    organized, and accessible.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <h3>Live Sessions</h3>
                    <p>
                        Presenter students can create live educational sessions for specific academic specializations.
                    </p>
                </div>

                <div class="feature-card">
                    <h3>Recorded Videos</h3>
                    <p>
                        Educational videos can be uploaded with summaries so students can review content anytime.
                    </p>
                </div>

                <div class="feature-card">
                    <h3>Comments and Interaction</h3>
                    <p>
                        Students can add comments to videos, while presenters can respond to support discussion.
                    </p>
                </div>

                <div class="feature-card">
                    <h3>Role Upgrade Requests</h3>
                    <p>
                        Recipient students can request to upgrade their role to presenter for content creation.
                    </p>
                </div>

                <div class="feature-card">
                    <h3>Content Management</h3>
                    <p>
                        Presenter students can manage their own sessions and videos in a simple and organized way.
                    </p>
                </div>

                <div class="feature-card">
                    <h3>System Monitoring</h3>
                    <p>
                        Administrators can monitor users, requests, and content to keep the system under control.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- هذا قسم الأدوار -->
    <section class="section" id="roles">
        <div class="container">
            <div class="section-title">
                <h2>User Roles</h2>
                <p>
                    Each role in the system is designed to serve a specific function in the collaborative learning process.
                </p>
            </div>

            <div class="roles-grid">
                <div class="role-card">
                    <h3>Recipient Student</h3>
                    <p>
                        Can register, browse sessions, join live sessions, watch videos, add comments, and request role upgrade.
                    </p>
                </div>

                <div class="role-card">
                    <h3>Presenter Student</h3>
                    <p>
                        Can create sessions, upload videos, manage educational content, and interact with comments.
                    </p>
                </div>

                <div class="role-card">
                    <h3>Administrator</h3>
                    <p>
                        Can manage users, review role requests, remove inappropriate content, and monitor system activity.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- هذا قسم تواصل بسيط -->
    <section class="section light-section" id="contact">
        <div class="container">
            <div class="section-title">
                <h2>Contact Information</h2>
                <p>
                    For questions about the platform, users can contact the system administrator through the official support channels.
                </p>
            </div>

            <div class="contact-box">
                <p><strong>Email:</strong> support@collab-elearning.com</p>
                <p><strong>System Type:</strong> Web-Based Collaborative Educational Platform</p>
                <p><strong>Target Users:</strong> Recipient Students, Presenter Students, and Administrators</p>
            </div>
        </div>
    </section>

    <!-- هذا الفوتر -->
    <footer class="main-footer">
        <div class="container footer-content">
            <p>&copy; <?php echo date('Y'); ?> Collaborative E-Learning System. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>