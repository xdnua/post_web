<?php
require_once 'config/database.php';// Kết nối tới cơ sở dữ liệu
require_once 'auth/auth.php';// Kiểm tra đăng nhập, xác thực người dùng
// Thiết lập đường dẫn cơ sở cho các liên kết trong giao diện
$baseUrl = '/posts'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới thiệu - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
</head>
<body style="padding-top: 60px;">
<?php include 'navbar.php'; // Hiển thị thanh điều hướng (menu) ?>

<div class="container mt-5">
    <h2 class="text-center mb-4"><i class="bi bi-stars text-primary"></i> Giới thiệu về <strong>Kiến thức 4.0</strong></h2>
    <p class="text-center lead mb-5">Nơi bạn có thể học, chia sẻ và phát triển nghề nghiệp trong lĩnh vực <strong>kiểm thử phần mềm</strong>.</p>

    <div class="text-center mb-5">
        <img src="<?=$baseUrl?>/uploads/dao-tao-tester-1.png" alt="Học kiểm thử phần mềm" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
    </div>

    <div class="row g-4">
        <!-- Mục tiêu -->
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow position-relative overflow-hidden custom-hover-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-bullseye text-success"></i> Mục tiêu</h5>
                    <ul>
                        <li>Trang bị nền tảng vững chắc cho người mới vào nghề tester.</li>
                        <li>Cung cấp tài liệu, ví dụ thực tiễn và hướng dẫn các công cụ kiểm thử.</li>
                        <li>Xây dựng cộng đồng chia sẻ kiến thức chất lượng và thân thiện.</li>
                    </ul>
                </div>
                <!-- Hiệu ứng nền nhẹ nhàng -->
                <div class="card-bg-effect"><i class="bi bi-bullseye"></i></div>
            </div>
        </div>

        <!-- Nội dung nổi bật -->
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow position-relative overflow-hidden custom-hover-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-journal-code text-warning"></i> Nội dung nổi bật</h5>
                    <ul>
                        <li>Các loại kiểm thử: Manual, Automation, Unit Test…</li>
                        <li>Các công cụ: Selenium, Postman, JMeter, TestNG…</li>
                        <li>Kỹ năng viết test case, test plan, báo cáo lỗi…</li>
                    </ul>
                </div>
                <div class="card-bg-effect"><i class="bi bi-journal-code"></i></div>
            </div>
        </div>

        <!-- Đối tượng phù hợp -->
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow position-relative overflow-hidden custom-hover-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-people-fill text-info"></i> Dành cho ai?</h5>
                    <p>Trang phù hợp cho:</p>
                    <ul>
                        <li>Sinh viên CNTT đang học về kiểm thử.</li>
                        <li>Người đang chuẩn bị phỏng vấn vị trí QA/QC.</li>
                        <li>Tester đã đi làm muốn cập nhật kiến thức mới.</li>
                    </ul>
                </div>
                <div class="card-bg-effect"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>

        <!-- phần cuối - Cảm hứng và kêu gọi hành động -->
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow position-relative overflow-hidden custom-hover-card">
                <div class="card-body text-center">
                    <!-- Icon lớn nổi bật -->
                    <div class="mb-3">
                        <i class="bi bi-envelope-paper-heart text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title fw-bold mb-2">Testing không đơn thuần là tìm bug</h5>
                    <p class="fst-italic text-secondary mb-3">Mà là bảo vệ trải nghiệm người dùng.</p>
                    <p>Bạn có thể bắt đầu bằng cách đọc <a href="<?=$baseUrl?>/index.php" class="link-primary fw-semibold"> Bài viết mới nhất</a>, hoặc <a href="<?=$baseUrl?>/create_post.php" class="link-success fw-semibold">Gửi bài chia sẻ</a> nếu bạn cũng đam mê kiểm thử!<br>Cảm ơn bạn đã ghé thăm! <i class="bi bi-emoji-smile text-warning"></i></p>
                </div>
                <div class="card-bg-effect"><i class="bi bi-stars"></i></div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
