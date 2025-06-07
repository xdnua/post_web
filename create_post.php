<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Require login to create posts
requireLogin();

$error = '';
$success = '';

// Fetch topics from the database
$topics_query = "SELECT id, name FROM topics ORDER BY name ASC";
$topics_result = mysqli_query($conn, $topics_query);
$topics = mysqli_fetch_all($topics_result, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $topic_id = $_POST['topic_id'] ?? null; // Get selected topic_id
    
    // Validate topic_id
    if ($topic_id !== null && $topic_id !== '') {
        $topic_id = (int)$topic_id;
        // Optional: Validate if the topic_id exists in the database
        $check_topic_query = "SELECT id FROM topics WHERE id = $topic_id";
        $check_topic_result = mysqli_query($conn, $check_topic_query);
        if (mysqli_num_rows($check_topic_result) == 0) {
            $topic_id = null; // Set to null if topic_id is invalid
        }
    } else {
        $topic_id = null; // Set to null if no topic is selected
    }

    if (empty($title) || empty($content)) {
        $error = 'Please fill in all fields';
    } else {
        $title = mysqli_real_escape_string($conn, $title);
        $content = mysqli_real_escape_string($conn, $content);
        $user_id = $_SESSION['user_id'];
        
        // Include topic_id in the INSERT query
        $query = "INSERT INTO posts (user_id, title, content, topic_id) VALUES ($user_id, '$title', '$content', " . ($topic_id === null ? "NULL" : $topic_id) . ")";

        if (mysqli_query($conn, $query)) {
            $post_id = mysqli_insert_id($conn);
            header("Location: post.php?id=$post_id");
            exit();
        } else {
            $error = 'Failed to create post';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng bài mới - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
</head>
<body>
<?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Đăng bài mới</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" onsubmit="return submitQuillContent();">
                            <div class="mb-3">
                                <label for="title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <!-- Topic Selection Dropdown -->
                            <div class="mb-3">
                                <label for="topic" class="form-label">Chủ đề</label>
                                <select class="form-select" id="topic" name="topic_id">
                                    <option value="">-- Chọn chủ đề --</option>
                                    <?php foreach ($topics as $topic): ?>
                                        <option value="<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">Nội dung</label>
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
                    image: imageHandler
                }
            }
        }
    });

    function uploadImageToServer(file, callback) {
        var formData = new FormData();
        formData.append('image', file);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload_image.php', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var res = JSON.parse(xhr.responseText);
                if (res.url) {
                    callback(res.url);
                } else {
                    alert(res.error || 'Lỗi upload ảnh');
                }
            } else {
                alert('Lỗi upload ảnh');
            }
        };
        xhr.send(formData);
    }

    function imageHandler() {
        var input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();
        input.onchange = function() {
            var file = input.files[0];
            if (file) {
                uploadImageToServer(file, function(url) {
                    var range = quill.getSelection();
                    quill.insertEmbed(range.index, 'image', url);
                });
            }
        };
    }

    // Xử lý paste ảnh
    quill.root.addEventListener('paste', function(e) {
        var clipboardData = e.clipboardData || window.clipboardData;
        if (clipboardData && clipboardData.items) {
            for (var i = 0; i < clipboardData.items.length; i++) {
                var item = clipboardData.items[i];
                if (item.type.indexOf('image') !== -1) {
                    e.preventDefault();
                    var file = item.getAsFile();
                    uploadImageToServer(file, function(url) {
                        var range = quill.getSelection();
                        quill.insertEmbed(range.index, 'image', url);
                    });
                }
            }
        }
    });
    // Xử lý drag-drop ảnh
    quill.root.addEventListener('drop', function(e) {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            e.preventDefault();
            var file = e.dataTransfer.files[0];
            if (file && file.type.indexOf('image') !== -1) {
                uploadImageToServer(file, function(url) {
                    var range = quill.getSelection();
                    quill.insertEmbed(range.index, 'image', url);
                });
            }
        }
    });

    function submitQuillContent() {
        // Loại bỏ ảnh base64 nếu còn sót lại
        var html = quill.root.innerHTML.replace(/<img[^>]+src=["']data:image\/(png|jpeg|jpg|gif|webp);base64,[^"']+["'][^>]*>/gi, '');
        document.getElementById('content').value = html;
        return true;
    }
    </script>
</body>
</html>
<?php include 'footer.php'; ?> 