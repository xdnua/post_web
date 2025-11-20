<?php include __DIR__ . '/../../controllers/CreatePostController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng bài mới - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/src/styles/global.css">
</head>

<body>
    <?php include __DIR__ . '/../../layout/navbar.php'; // Hiển thị thanh điều hướng ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Đăng bài mới</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div> <!-- Hiển thị lỗi nếu có -->
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <!-- Hiển thị thông báo thành công nếu có -->
                        <?php endif; ?>

                        <form method="POST" action="" onsubmit="return submitQuillContent();">
                            <div class="mb-3">
                                <label for="title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <!-- Dropdown chọn chủ đề -->
                            <div class="mb-3">
                                <label for="topic" class="form-label">Chủ đề</label>
                                <select class="form-select" id="topic" name="topic_id">
                                    <option value="">-- Chọn chủ đề --</option>
                                    <?php foreach ($topics as $topic): ?>
                                        <option value="<?php echo $topic['id']; ?>">
                                            <?php echo htmlspecialchars($topic['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">Nội dung</label>
                                <!-- Editor QuillJS để nhập nội dung bài viết -->
                                <div id="quill-editor" style="height: 300px;"></div>
                                <input type="hidden" id="content" name="content">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Đăng bài</button>
                                <a href="index.php" class="btn btn-secondary">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.7/quill.js"></script>
    <script>
        // Khởi tạo QuillJS editor với các công cụ định dạng
        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', 'code-block'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ script: 'sub' }, { script: 'super' }],
                        [{ indent: '-1' }, { indent: '+1' }],
                        [{ direction: 'rtl' }],
                        [{ color: [] }, { background: [] }],
                        [{ align: [] }],
                        ['link', 'image'],
                        ['clean']
                    ],
                    handlers: {
                        image: imageHandler // Xử lý khi chèn ảnh
                    }
                }
            }
        });

        // Hàm upload ảnh lên server khi chèn vào Quill
        function uploadImageToServer(file, callback) {
            var formData = new FormData(); // Tạo đối tượng FormData để gửi file ảnh
            formData.append('image', file);
            var xhr = new XMLHttpRequest(); // Tạo đối tượng XMLHttpRequest để gửi yêu cầu AJAX
            xhr.open('POST', 'upload_image.php', true); // Gửi yêu cầu POST tới file upload_image.php
            xhr.onload = function () {
                if (xhr.status === 200) { // Kiểm tra nếu upload thành công (HTTP 200 OK)
                    var res = JSON.parse(xhr.responseText);
                    if (res.url) { // Kiểm tra nếu server trả về URL của ảnh
                        callback(res.url); // Trả về link ảnh sau khi upload thành công
                    } else {
                        alert(res.error || 'Lỗi upload ảnh');
                    }
                } else {
                    alert('Lỗi upload ảnh');
                }
            };
            xhr.send(formData);
        }

        // Xử lý khi người dùng chọn/chèn ảnh vào editor
        function imageHandler() {
            var input = document.createElement('input'); // Tạo một input để chọn file ảnh
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();
            input.onchange = function () {
                var file = input.files[0];
                if (file) {
                    uploadImageToServer(file, function (url) {
                        var range = quill.getSelection(); // Lấy vị trí con trỏ hiện tại trong editor
                        quill.insertEmbed(range.index, 'image', url); // Chèn ảnh vào vị trí con trỏ hiện tại trong editor
                    });
                }
            };
        }

        // Xử lý paste ảnh từ clipboard vào editor
        quill.root.addEventListener('paste', function (e) {
            var clipboardData = e.clipboardData || window.clipboardData; // lấy dữ liệu clipboard
            if (clipboardData && clipboardData.items) {
                for (var i = 0; i < clipboardData.items.length; i++) {  // Duyệt qua các mục trong clipboard
                    var item = clipboardData.items[i];
                    if (item.type.indexOf('image') !== -1) {
                        e.preventDefault();
                        var file = item.getAsFile();
                        uploadImageToServer(file, function (url) { // Gọi hàm upload ảnh lên server
                            var range = quill.getSelection(); // Lấy vị trí con trỏ hiện tại trong editor
                            quill.insertEmbed(range.index, 'image', url);
                        });
                    }
                }
            }
        });
        // Xử lý kéo-thả ảnh vào ô nhập bài viết: khi bạn kéo ảnh vào, ảnh sẽ được tải lên và chèn vào đúng vị trí con trỏ
        quill.root.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                e.preventDefault();
                var file = e.dataTransfer.files[0];
                if (file && file.type.indexOf('image') !== -1) {
                    uploadImageToServer(file, function (url) {
                        var range = quill.getSelection();
                        quill.insertEmbed(range.index, 'image', url);
                    });
                }
            }
        });

        // Khi bấm nút Đăng bài, lấy nội dung đã soạn từ Quill và loại bỏ ảnh dán trực tiếp (ảnh base64), chỉ giữ lại ảnh đã tải lên
        function submitQuillContent() {
            var html = quill.root.innerHTML.replace(/<img[^>]+src=["']data:image\/(png|jpeg|jpg|gif|webp);base64,[^"']+["'][^>]*>/gi, '');
            document.getElementById('content').value = html;
            return true;
        }
    </script>
</body>

</html>
<?php include __DIR__ . '/../../layout/footer.php'; ?>