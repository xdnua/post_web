<?php
if (session_status() === PHP_SESSION_NONE) session_start(); // Đảm bảo đã khởi động session
require_once __DIR__ . '/auth/auth.php'; // Nhúng các hàm xác thực đăng nhập
require_once __DIR__ . '/config/database.php'; // Kết nối tới cơ sở dữ liệu
$baseUrl = '/posts'; // Đường dẫn gốc của dự án

// Lấy tên file trang hiện tại để làm nổi bật menu tương ứng
$currentPage = basename($_SERVER['PHP_SELF']);

?>
<link rel="stylesheet" href="<?=$baseUrl?>/global.css">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark navbar-custom fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?=$baseUrl?>/index.php" style="margin-right: 70px;"><i class="bi-back"></i><span> Kiến thức 4.0</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu bên trái -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php if($currentPage == 'index.php') echo 'active-nav-link'; ?>" href="<?=$baseUrl?>/index.php"><i class="bi bi-house-door"></i> Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if($currentPage == 'about.php') echo 'active-nav-link'; ?>" href="<?=$baseUrl?>/about.php">Giới thiệu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if($currentPage == 'contact.php') echo 'active-nav-link'; ?>" href="<?=$baseUrl?>/contact.php">Liên hệ</a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <?php
                    // Lấy thông tin người dùng đang đăng nhập để hiển thị tên và avatar
                    $loggedInUserId = $_SESSION['user_id'];
                    $userSql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ? LIMIT 1";
                    $userStmt = mysqli_prepare($conn, $userSql);
                    $loggedInUser = null;
                    if ($userStmt) {
                        mysqli_stmt_bind_param($userStmt, 'i', $loggedInUserId);
                        mysqli_stmt_execute($userStmt);
                        $userResult = mysqli_stmt_get_result($userStmt);
                        $loggedInUser = mysqli_fetch_assoc($userResult);
                        mysqli_stmt_close($userStmt);
                    }

                    // Ưu tiên hiển thị họ tên đầy đủ, nếu không có thì lấy tên hoặc username
                    $displayName = htmlspecialchars($loggedInUser['username'] ?? '');
                    if ($loggedInUser && !empty($loggedInUser['first_name']) && !empty($loggedInUser['last_name'])) {
                         $displayName = htmlspecialchars($loggedInUser['first_name']) . ' ' . htmlspecialchars($loggedInUser['last_name']);
                    } else if ($loggedInUser && !empty($loggedInUser['first_name'])) {
                         $displayName = htmlspecialchars($loggedInUser['first_name']);
                    } else if ($loggedInUser && !empty($loggedInUser['last_name'])) {
                         $displayName = htmlspecialchars($loggedInUser['last_name']);
                    }

                    $avatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($loggedInUser['avatar'] ?? 'default_avatar.png');
                    ?>
                    <!-- Dropdown menu cho phần Bài viết của tôi -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php if($currentPage == 'my_posts.php' || $currentPage == 'create_post.php') echo 'active-nav-link'; ?>" href="#" id="navbarDropdownPosts" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-journal-text"></i> Bài viết
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownPosts">
                            <li><a class="dropdown-item <?php if($currentPage == 'my_posts.php') echo 'active-dropdown-link'; ?>" href="<?=$baseUrl?>/my_posts.php"><i class="bi bi-journal-text"></i> Bài đăng của tôi</a></li>
                            <li><a class="dropdown-item <?php if($currentPage == 'create_post.php') echo 'active-dropdown-link'; ?>" href="<?=$baseUrl?>/create_post.php"><i class="bi bi-plus-square"></i> Đăng bài mới</a></li>
                            <li><a class="dropdown-item" href="<?=$baseUrl?>/bookmarks.php"><i class="bi bi-bookmark"></i> Bài viết đã lưu</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <!-- Menu bên phải: Đăng nhập/Đăng ký hoặc Tên người dùng/Đăng xuất -->
            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if (isLoggedIn() && $loggedInUser): // Đã đăng nhập và lấy được thông tin user ?>
                     <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center <?php if($currentPage == 'profile.php') echo 'active-nav-link'; ?>" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?=$avatarPath?>" alt="Avatar" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                            <?=$displayName?>
                            <?php if (isAdmin()): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                            <?php if (isAdmin()): ?>
                                <li>
                                    <a class="dropdown-item <?php if($currentPage == 'profile.php') echo 'active-dropdown-item'; ?>" href="<?=$baseUrl?>/admin/index.php">
                                        <i class="bi bi-gear"></i> Trang Admin
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item <?php if($currentPage == 'profile.php') echo 'active-dropdown-item'; ?>" href="<?=$baseUrl?>/profile.php">
                                <i class="bi bi-person-circle"></i> Thông tin tài khoản
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger <?php if($currentPage == 'logout.php') echo 'active-dropdown-item'; ?>" href="<?=$baseUrl?>/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-primary <?php if($currentPage == 'login.php') echo 'active-nav-link'; ?>" href="<?=$baseUrl?>/login.php"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-primary <?php if($currentPage == 'register.php') echo 'active-nav-link'; ?>" href="<?=$baseUrl?>/register.php"><i class="bi bi-person-plus"></i> Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>