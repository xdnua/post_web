<footer class="text-light py-5 gradient-bg mt-5" style="background: linear-gradient(90deg, #e8a9bb 0%, #99bbe8 100%)">
    <div class="container">
        <div class="row">
            <!-- Cột trái: Thông tin web -->
            <div class="col-md-4">
                <h3 class="text-light">Kiến thức 4.0</h3> <!-- Sử dụng text-warning để giả lập màu cam -->
                <p>Nơi bạn có thể chia sẻ kiến thức và kinh nghiệm của mình</p>
            </div>

            <!-- Cột giữa: Hỗ trợ -->
            <div class="col-md-4">
                <h5>Bạn cần hỗ trợ</h5>
                <p class="card-text"><strong>Điện thoại:</strong> 0876 176 706</p>
                <p class="card-text"><strong>Email:</strong> anhhongcchemgio@gmail.com</p>
                <p class="card-text"><strong>Địa chỉ:</strong> Phù Yên, Phúc Lâm, Mỹ Đức, Hà Nội</p>
                <div>
                    <a href="https://www.facebook.com/anh.hong.594371" class="me-2 text-primary"><i class="bi bi-facebook" style="font-size: 1.5rem;"></i></a>
                    <a href="https://www.tiktok.com/@aht_2811?lang=vi-VN" class="me-2 text-info"><i class="bi bi-tiktok" style="font-size: 1.5rem;"></i></a>
                    <a href="https://www.instagram.com/hongvu2811/" class="text-danger"><i class="bi bi-instagram" style="font-size: 1.5rem;"></i></a>
                </div>
            </div>

            <!-- Cột phải: Menu điều hướng -->
            <div class="col-md-4">
                <h5>Điều hướng trang web</h5>
                <ul class="list-unstyled">
                <li><a href="<?=$baseUrl?>/index.php" class="text-light text-decoration-none"><i class="bi bi-house-door"></i> Trang chủ</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?=$baseUrl?>/my_posts.php" class="text-light text-decoration-none"><i class="bi bi-journal-text"></i> Bài đăng của tôi</a></li>
                    <li><a href="<?=$baseUrl?>/create_post.php" class="text-light text-decoration-none"><i class="bi bi-plus-square"></i> Đăng bài mới</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?=$baseUrl?>/admin/posts.php" class="text-light text-decoration-none"><i class="bi bi-gear"></i> Quản lý bài đăng</a></li>
                        <li><a href="<?=$baseUrl?>/admin/users.php" class="text-light text-decoration-none"><i class="bi bi-people"></i> Quản lý người dùng</a></li>
                    <?php endif; ?>
                        <li><a href="<?=$baseUrl?>/logout.php" class="text-light text-decoration-none"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                <?php else: ?>
                    <li><a href="<?=$baseUrl?>/login.php" class="text-light text-decoration-none"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a></li>
                    <li><a href="<?=$baseUrl?>/register.php" class="text-light text-decoration-none"><i class="bi bi-person-plus"></i> Đăng ký</a></li>
                <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr class="bg-light mt-4 mb-3">
        <div class="row">
            <div class="col text-center">
                <p class="text-white mb-0">&copy; <?php echo date('Y'); ?> Bản quyền thuộc về Blog Chia Sẻ.</p>
            </div>
        </div>
    </div>
</footer>
<a href="#" class="btn btn-primary rounded-circle back-to-top" id="back-to-top" style="position: fixed; bottom: 20px; right: 20px; display: none;">
    <i class="bi bi-arrow-up"></i>
</a>
<script>
// Back to top button functionality
var backToTopButton = document.getElementById('back-to-top');

window.addEventListener('scroll', function() {
    if (window.scrollY > 100) {
        backToTopButton.style.display = 'block';
    } else {
        backToTopButton.style.display = 'none';
    }
});

backToTopButton.addEventListener('click', function(event) {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script> 