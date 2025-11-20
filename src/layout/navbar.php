<?php
if (session_status() === PHP_SESSION_NONE)
    session_start(); // Đảm bảo session đã start

include __DIR__ . '/../controllers/connect.php';

$currentPage = basename($_SERVER['PHP_SELF']); //  Lấy tên file hiện tại để xác định trang đang xem
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/src/styles/global.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark navbar-custom fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $baseUrl ?>/index.php" style="margin-right: 70px;">
            <i class="bi-back"></i><span> Kiến thức 4.0</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu bên trái -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php if ($currentPage == 'index.php')
                        echo 'active-nav-link'; ?>" href="<?= $baseUrl ?>/index.php"><i class="bi bi-house-door"></i>
                        Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if ($currentPage == 'about.php')
                        echo 'active-nav-link'; ?>" href="<?= $baseUrl ?>/src/views/about/about.php">Giới thiệu</a>
                </li>

                <?php if (isLoggedIn()): ?>
                    <?php
                    $loggedInUserId = $_SESSION['user_id']; // Lấy ID người dùng đang đăng nhập từ session
                    $userSql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ? LIMIT 1";
                    $userStmt = mysqli_prepare($conn, $userSql); // Chuẩn bị câu truy vấn để lấy thông tin người dùng
                    $loggedInUser = null;


                    if ($userStmt) {
                        mysqli_stmt_bind_param($userStmt, 'i', $loggedInUserId);
                        mysqli_stmt_execute($userStmt);
                        $userResult = mysqli_stmt_get_result($userStmt);
                        $loggedInUser = mysqli_fetch_assoc($userResult);
                        mysqli_stmt_close($userStmt);
                    }

                    $displayName = htmlspecialchars($loggedInUser['username'] ?? '');
                    if (!empty($loggedInUser['first_name']) && !empty($loggedInUser['last_name'])) {
                        $displayName = htmlspecialchars($loggedInUser['first_name']) . ' ' . htmlspecialchars($loggedInUser['last_name']);
                    } elseif (!empty($loggedInUser['first_name'])) {
                        $displayName = htmlspecialchars($loggedInUser['first_name']);
                    } elseif (!empty($loggedInUser['last_name'])) {
                        $displayName = htmlspecialchars($loggedInUser['last_name']);
                    }

                    $avatarPath = $baseUrl . '/src/assets/dist/avatars/' . htmlspecialchars($loggedInUser['avatar'] ?? 'default_avatar.png');
                    ?>
                    <!-- Dropdown menu bài viết -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php if ($currentPage == 'my_posts.php' || $currentPage == 'create_post.php')
                            echo 'active-nav-link'; ?>" href="#" id="navbarDropdownPosts" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-journal-text"></i> Bài viết
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownPosts">
                            <li><a class="dropdown-item <?php if ($currentPage == 'my_posts.php')
                                echo 'active-dropdown-link'; ?>" href="<?= $baseUrl ?>/src/views/post/my_posts.php"><i
                                        class="bi bi-journal-text"></i> Bài đăng của
                                    tôi</a></li>
                            <li><a class="dropdown-item <?php if ($currentPage == 'create_post.php')
                                echo 'active-dropdown-link'; ?>"
                                    href="<?= $baseUrl ?>/src/views/post/create_post.php"><i class="bi bi-plus-square"></i>
                                    Đăng bài mới</a>
                            </li>
                            <li><a class="dropdown-item <?php if ($currentPage == 'my_saves.php')
                                echo 'active-dropdown-link'; ?>"
                                    href="<?= $baseUrl ?>/src/views/bookmarks/my_bookmarks.php"><i class="bi-bookmark"></i>
                                    Bài
                                    viết đã lưu</a>
                            </li>
                            <li><a class="dropdown-item <?php if ($currentPage == 'read_history.php')
                                echo 'active-dropdown-link'; ?>"
                                    href="<?= $baseUrl ?>/src/views/post/read_history.php">
                                    <i class="bi bi-clock-history"></i> Lịch sử đọc bài viết
                                </a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Menu bên phải -->
            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if (isLoggedIn()): ?>
                    <?php
                    function getLatestNotifications($conn, $receiverId, $limit = 5)
                    { // Hàm lấy thông báo mới nhất của người dùng
                        $sql = "
                            SELECT n.*, u.username, u.avatar, p.title 
                            FROM notifications n
                            JOIN users u ON n.sender_id = u.id
                            JOIN posts p ON n.post_id = p.id
                            WHERE n.receiver_id = ?
                            ORDER BY n.created_at DESC
                            LIMIT ?
                        ";

                        $notifications = [];

                        if ($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, 'ii', $receiverId, $limit);
                            mysqli_stmt_execute($stmt);

                            $result = mysqli_stmt_get_result($stmt);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $notifications[] = $row;
                                }
                            }

                            mysqli_stmt_close($stmt);
                        } else {
                            error_log("Query prepare failed: " . mysqli_error($conn));
                        }

                        return $notifications;
                    }

                    // Lấy thông tin thông báo mới nhất của người dùng đang đăng nhập
                    $notifications = getLatestNotifications($conn, $loggedInUserId);
                    ?>
                    <!-- Thông báo -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <?php
                            $unseenCount = array_reduce($notifications, fn($c, $n) => $c + ($n['seen'] == 0 ? 1 : 0), 0); // Đếm số thông báo chưa xem
                            if ($unseenCount > 0): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unseenCount ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end"
                            style="width: 300px; max-height: 400px; overflow-y: auto;">
                            <?php if (empty($notifications)): ?>
                                <li class="dropdown-item text-muted">Không có thông báo</li>
                            <?php else: ?>
                                <?php foreach ($notifications as $noti): ?>
                                    <a href="/posts/src/views/post/post.php?id=<?= $noti['post_id'] ?>&notification_id=<?= $noti['id'] ?>"
                                        class="text-decoration-none text-dark">
                                        <li class="dropdown-item d-flex gap-2 align-items-start">
                                            <img src="<?= $baseUrl ?>/src/assets/dist/avatars/<?= htmlspecialchars($noti['avatar'] ?? 'default_avatar.png') ?>"
                                                class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
                                            <div style="flex: 1;">
                                                <div>
                                                    <strong><?= htmlspecialchars($noti['username']) ?></strong>
                                                    <?php
                                                    if (trim($noti['type']) == 'like') {
                                                        echo 'đã thích bài viết của bạn';
                                                    } elseif (trim($noti['type']) == 'dislike') {
                                                        echo 'không thích bài viết của bạn';
                                                    } elseif (trim($noti['type']) == 'comment') {
                                                        echo 'đã bình luận bài viết của bạn';
                                                    } else {
                                                        echo 'có hoạt động mới trên bài viết của bạn';
                                                    }
                                                    ?>
                                                </div>
                                                <small
                                                    class="text-muted"><?= date('d/m/Y H:i', strtotime($noti['created_at'])) ?></small>
                                            </div>
                                        </li>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <!-- User -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center <?php if ($currentPage == 'profile.php')
                            echo 'active-nav-link'; ?>" href="#" id="navbarDropdownUser" role="button"
                            data-bs-toggle="dropdown">
                            <img src="<?= $avatarPath ?>" alt="Avatar" class="rounded-circle me-2"
                                style="width: 30px; height: 30px; object-fit: cover;">
                            <?= $displayName ?>
                            <?php if (isAdmin()): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="<?= $baseUrl ?>/src/views/admin/index.php"><i
                                            class="bi bi-gear"></i>
                                        Trang Admin</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>/src/views/profile/profile.php"><i
                                        class="bi bi-person-circle"></i> Thông tin tài khoản</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?= $baseUrl ?>/src/views/auth/logout.php"><i
                                        class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?= $baseUrl ?>/src/views/auth/register.php" class="btn btn-outline-light me-2">Đăng ký</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= $baseUrl ?>/src/views/auth/login.php" class="btn btn-outline-light">Đăng nhập</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Start of xdnuahelp Zendesk Widget script -->
<script id="ze-snippet"
    src="https://static.zdassets.com/ekr/snippet.js?key=4e7d8406-3209-43c7-b246-b341aff1fa7e"> </script>
<!-- End of xdnuahelp Zendesk Widget script -->