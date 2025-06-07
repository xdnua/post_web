<?php
require_once 'config/database.php'; // Kết nối CSDL
require_once 'auth/auth.php'; // Kiểm tra đăng nhập, xác thực người dùng


// Xử lý phân trang, tìm kiếm, lọc chủ đề

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Lấy số trang hiện tại từ URL, mặc định là 1
$limit = 10; // Số bài viết mỗi trang
$offset = ($page - 1) * $limit; // Vị trí bắt đầu lấy dữ liệu
$search_term = $_GET['search'] ?? ''; // Từ khóa tìm kiếm (nếu có)
$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : null; // Lọc theo chủ đề (nếu có)

$conditions = []; // Mảng điều kiện truy vấn
$param_values = []; // Mảng giá trị tham số truy vấn
$param_types = ''; // Chuỗi kiểu dữ liệu tham số 

// Nếu có từ khóa tìm kiếm, thêm điều kiện tìm kiếm vào truy vấn
if (!empty($search_term)) {
    $conditions[] = " (p.title LIKE ? OR p.content LIKE ?) "; // Tìm trong tiêu đề hoặc nội dung
    $param_values[] = '%' . $search_term . '%';
    $param_values[] = '%' . $search_term . '%';
    $param_types .= 'ss'; // 2 tham số kiểu string
}

// Nếu có lọc theo chủ đề, thêm điều kiện chủ đề vào truy vấn
if ($topic_id !== null) {
    $conditions[] = " p.topic_id = ? ";
    $param_values[] = $topic_id;
    $param_types .= 'i'; // 1 tham số kiểu int
}

// Ghép các điều kiện thành chuỗi WHERE
$where_clause = '';
if (!empty($conditions)) {
    $where_clause = " WHERE " . implode(" AND ", $conditions);
}


// Lấy danh sách chủ đề để hiển thị tab chủ đề
// Sửa lại truy vấn để sắp xếp theo id tăng dần (theo thứ tự thêm vào)
$topics_query = "SELECT id, name FROM topics ORDER BY id ASC";
$topics_result = mysqli_query($conn, $topics_query);

// Nếu có chọn chủ đề, lấy tên chủ đề để hiển thị
$selected_topic_name = '';
if ($topic_id !== null) {
    $topic_name_query = "SELECT name FROM topics WHERE id = ? LIMIT 1";
    $stmt_topic = mysqli_prepare($conn, $topic_name_query);
     if ($stmt_topic) {
        mysqli_stmt_bind_param($stmt_topic, 'i', $topic_id);
        mysqli_stmt_execute($stmt_topic);
        $topic_name_result = mysqli_stmt_get_result($stmt_topic);
        $topic_name_row = mysqli_fetch_assoc($topic_name_result);
        if ($topic_name_row) {
            $selected_topic_name = $topic_name_row['name'];
        }
        mysqli_stmt_close($stmt_topic);
     } else {
        // Nếu lỗi truy vấn
        $error = 'Lỗi CSDL khi lấy tên chủ đề.';
     }
}


// Đếm tổng số bài viết (phục vụ phân trang)
$count_query_sql = "SELECT COUNT(*) as total FROM posts p" . $where_clause;
$stmt_count = mysqli_prepare($conn, $count_query_sql);

if ($stmt_count) {
     if (!empty($param_values)) {
        mysqli_stmt_bind_param($stmt_count, $param_types, ...$param_values);
     }
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
    $total_posts = mysqli_fetch_assoc($count_result)['total']; // Tổng số bài viết
    $total_pages = ceil($total_posts / $limit); // Tổng số trang
    mysqli_stmt_close($stmt_count);
} else {
     // Nếu lỗi truy vấn
     $error = 'Lỗi CSDL khi đếm bài viết.';
     $total_posts = 0;
     $total_pages = 0;
}


// Lấy danh sách bài viết cho trang hiện tại (có áp dụng tìm kiếm, lọc chủ đề, phân trang)
$query_sql = "SELECT p.*, u.username, u.first_name, u.last_name, u.avatar,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as like_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislike_count
        FROM posts p
        JOIN users u ON p.user_id = u.id ";

$query_sql .= $where_clause; // Thêm điều kiện WHERE nếu có

$query_sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?"; // Sắp xếp mới nhất, phân trang

$stmt = mysqli_prepare($conn, $query_sql);

if ($stmt) {
     // Tạo mảng tham số và kiểu dữ liệu cho truy vấn chính (bao gồm limit, offset)
     $main_query_param_values = $param_values; // Bắt đầu với các giá trị filter
     $main_query_param_types = $param_types;

     // Thêm kiểu và giá trị cho limit, offset
     $main_query_param_types .= 'ii';
     $main_query_param_values[] = $limit;
     $main_query_param_values[] = $offset;

    mysqli_stmt_bind_param($stmt, $main_query_param_types, ...$main_query_param_values);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt); // Kết quả danh sách bài viết
    mysqli_stmt_close($stmt);
} else {
     // Nếu lỗi truy vấn
    $error = 'Lỗi CSDL khi lấy danh sách bài viết.';
    $result = false; // Không có kết quả
}

$baseUrl = '/posts'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
    
</head>
<body style="padding-top: 60px;">
<?php include 'navbar.php'; ?>

    <!-- Hero Section - Discover, Learn, Enjoy -->
    <section class="hero-design-section py-5">
        <div class="container">
            <!-- Title and Slogan -->
            <div class="row">
                <div class="col-12 text-center text-white mb-4">
                    <h1 class="display-3 fw-bold">Khám phá - Học hỏi - Tận hưởng</h1>
                    <p class="lead">Nền tảng cho những người sáng tạo trên khắp thế giới.</p>
                </div>
            </div>

            <!-- Search Bar Row -->
            <div class="row mb-5">
                <div class="col-12">
                    <form method="GET" action="" class="d-flex justify-content-center">
                        <div class="input-group input-group-lg rounded-pill shadow-sm overflow-hidden" style="max-width: 600px; border: 1px solid white;">
                            <span class="input-group-text border-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-0 ps-1" name="search" placeholder="Tìm kiếm ..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button class="btn btn-primary text-white px-4 border-0" type="submit" style="border: none !important;">Tìm kiếm</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Featured Cards Row -->
           <div class="row g-4 mx-5">
                <!-- Web Design Card -->
                <div class="  col-md-4">
                    <div class="card card-hover h-100 featured-card shadow-sm" style="background-image: url('<?=$baseUrl?>/dist/imgs/blog2.png'); background-size: cover; background-position: center; color: white;">
                        <div class="card-body">
                            <div class=" card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Xin Chào</h5>
                                </div>
                                <p class="card-text text-light">Chào mừng bạn đến với Kiến thức 4.0! Nơi chia sẻ kiến thức và kinh nghiệm về kiểm thử phần mềm và nhiều chủ đề thú vị khác.</p>
                            </div>
                        </div>    
                    </div>
                </div>

                <!-- Gioi thieu-->
                <div class=" col-md-8">
                     <div class=" card card-hover h-100 featured-card shadow-sm" style="background-image: url('<?=$baseUrl?>/dist/imgs/blog.png'); background-size: cover; background-position: center; color: white;">
                          <div class="card-body">
                             <div class="  card-body d-flex flex-column">
                             <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Giới Thiệu</h5>
                             </div>
                             <p class="card-text text-light" style="height: 200px;">Kiến thức 4.0 là nền tảng chia sẻ kiến thức trực tuyến, nơi bạn có thể tìm thấy các bài viết chất lượng về kiểm thử phần mềm và nhiều lĩnh vực khác. Chúng tôi cung cấp môi trường học tập và chia sẻ kiến thức cho cộng đồng.</p>
                            
                            </div>
                        </div>
                      </div>
                 </div>
             </div>
           </div>
        </div>
    </section>

    <!-- Chu de -->
    <section class="topics-section py-5">
        <div class="container">
            <h2 class="text-center mb-4" id="topics-section">Chủ Đề</h2>

            <!-- Topic Tabs -->
            <div style="overflow-x: auto; white-space: nowrap; padding-bottom: 15px; -webkit-overflow-scrolling: touch;">
                <ul class="nav nav-tabs justify-content-center border-0 mb-4" id="topicTabs" role="tablist" style="flex-wrap: nowrap;">
                 <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($topic_id === null) ? 'active' : ''; ?>" id="all-topics-tab" data-bs-toggle="tab" data-bs-target="#all-topics" type="button" role="tab" aria-controls="all-topics" aria-selected="<?php echo ($topic_id === null) ? 'true' : 'false'; ?>" onclick="window.location.href='index.php<?php echo !empty($search_term) ? '?search=' . htmlspecialchars($search_term) : ''; ?>#topics-section'">Tất cả</button>
                </li>
                <?php 
                 // Rewind the topics result set to iterate again for tabs
                if ($topics_result) {
                    mysqli_data_seek($topics_result, 0);
                }
                ?>
                <?php if ($topics_result && mysqli_num_rows($topics_result) > 0): ?>
                    <?php while ($topic = mysqli_fetch_assoc($topics_result)): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($topic_id == $topic['id']) ? 'active' : ''; ?>" id="topic-<?php echo $topic['id']; ?>-tab" data-bs-toggle="tab" data-bs-target="#topic-<?php echo $topic['id']; ?>" type="button" role="tab" aria-controls="topic-<?php echo $topic['id']; ?>" aria-selected="<?php echo ($topic_id == $topic['id']) ? 'true' : 'false'; ?>" onclick="window.location.href='index.php?topic_id=<?php echo $topic['id']; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>#topics-section'">
                                <?php echo htmlspecialchars($topic['name']); ?>
                            </button>
                        </li>
                    <?php endwhile; ?>
                <?php endif; ?>
            </ul>
            </div>

            
            <!--
    
                Hiển thị danh sách bài viết (posts)
                
                - Duyệt qua kết quả truy vấn $result để hiển thị từng bài viết.
                - Hiển thị tiêu đề, tóm tắt nội dung (lấy 100 ký tự đầu, loại bỏ thẻ HTML), tên tác giả, avatar, ngày đăng, số lượt like/dislike.
                - Nếu có họ tên thì ưu tiên hiển thị, nếu không thì lấy username.
                - Avatar lấy từ thư mục /dist/avatars, nếu không có thì dùng ảnh mặc định.
                - Nếu không có bài viết nào phù hợp với bộ lọc/tìm kiếm thì hiển thị thông báo phù hợp.
                - Nút "Đọc tiếp" dẫn đến trang chi tiết bài viết (post.php?id=...).
            -->
            <div class="tab-content" id="topicTabsContent">
                <div class="tab-pane fade show active" id="all-topics" role="tabpanel" aria-labelledby="all-topics-tab">
                    <div class="row">
                         <?php 
                         // Đảm bảo con trỏ kết quả ở đầu để duyệt lại
                         if ($result) {
                             mysqli_data_seek($result, 0);
                         }
                         ?>
                         <?php 
                         // Nếu có kết quả bài viết thì hiển thị, kể cả khi có filter
                         if ($result && mysqli_num_rows($result) > 0): ?>
                             <?php while ($post = mysqli_fetch_assoc($result)): ?>
                                     <div class="col-md-4 mb-4">
                                         <div class="card h-100">
                                             <div class="card-body">
                                                 <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                                 <p class="card-text"><?php
                                                     // Lấy tóm tắt nội dung: loại bỏ thẻ HTML, lấy 100 ký tự đầu
                                                     $summary = mb_substr(strip_tags($post['content']), 0, 100);
                                                     if (mb_strlen(strip_tags($post['content'])) > 100) $summary .= '...';
                                                     echo htmlspecialchars($summary);
                                                 ?></p>
                                                 <div class="d-flex justify-content-between align-items-center">
                                                     <small class="text-muted">
                                                         <?php
                                                         // Xác định tên hiển thị của tác giả: ưu tiên họ tên, nếu không có thì lấy username
                                                         $authorDisplayName = htmlspecialchars($post['username']);
                                                         if (!empty($post['first_name']) && !empty($post['last_name'])) {
                                                             $authorDisplayName = htmlspecialchars($post['first_name']) . ' ' . htmlspecialchars($post['last_name']);
                                                         } else if (!empty($post['first_name'])) {
                                                              $authorDisplayName = htmlspecialchars($post['first_name']);
                                                         } else if (!empty($post['last_name'])) {
                                                              $authorDisplayName = htmlspecialchars($post['last_name']);
                                                         }
                                                         // Lấy đường dẫn avatar, nếu không có thì dùng avatar mặc định
                                                         $authorAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($post['avatar'] ?? 'default_avatar.png');
                                                         ?>
                                                         Bởi<img src="<?=$authorAvatarPath?>" alt="Avatar" class="rounded-circle me-1" style="width: 20px; height: 20px; object-fit: cover;">
                                                         <?=$authorDisplayName?>
                                                     </small>
                                                     <small class="text-muted"><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></small>
                                                 </div>
                                             </div>
                                             <div class="card-footer bg-transparent">
                                                 <div class="d-flex justify-content-between align-items-center">
                                                     <div>
                                                         <span class="me-2"><i class="bi bi-hand-thumbs-up"></i> <?php echo $post['like_count']; ?></span>
                                                         <span><i class="bi bi-hand-thumbs-down"></i> <?php echo $post['dislike_count']; ?></span>
                                                     </div>
                                                     <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">Đọc tiếp</a>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 <?php endwhile; ?>
                          <?php elseif (($result && mysqli_num_rows($result) == 0) || ($topics_result && mysqli_num_rows($topics_result) == 0 && empty($search_term) && $topic_id === null)): ?>
                               <!--
                                    Nếu không có bài viết nào phù hợp với filter/tìm kiếm hoặc không có chủ đề nào thì hiển thị thông báo phù hợp:
                                    - Nếu có từ khóa tìm kiếm: thông báo không tìm thấy bài đăng cho từ khóa đó
                                    - Nếu có lọc chủ đề: thông báo không tìm thấy bài đăng trong chủ đề đó
                                    - Nếu không có chủ đề: thông báo không tìm thấy chủ đề
                                    - Nếu không có bài đăng nào: thông báo chưa có bài đăng
                                -->
                              <div class="col-12 text-center">
                                     <div class="alert alert-info text-center mt-3 mb-0 mx-auto gradient-bg text-light" style="max-width: 300px;">
                                         <i class="bi bi-emoji-frown" style="font-size:2rem;"></i><br>
                                         <?php 
                                             if (!empty($search_term)) {
                                                 echo "Không tìm thấy bài đăng nào cho \"" . htmlspecialchars($search_term) . "\"";
                                             } else if ($topic_id !== null && !empty($selected_topic_name)) {
                                                  echo "Không tìm thấy bài đăng nào trong chủ đề \"" . htmlspecialchars($selected_topic_name) . "\".";
                                             } else if ($topic_id !== null && empty($selected_topic_name)) {
                                                 echo "Không tìm thấy chủ đề.";
                                             } else { // Không có tìm kiếm, không có chủ đề, không có bài đăng
                                                 echo "Chưa có bài đăng nào tại hiện tại!";
                                             }
                                         ?>
                                     </div>
                              </div>
                          <?php endif; ?>
                    </div>
                </div>
                <!-- Nếu muốn lọc chủ đề phía client thì thêm tab-pane ở đây -->
            </div>

            <!--
               
                Phân trang (Pagination)
               
                - Hiển thị các nút chuyển trang nếu tổng số trang > 1
                - Nút << và >> để chuyển về trang trước/sau
                - Các nút số trang, trang hiện tại được bôi đậm
                - Giữ lại các tham số tìm kiếm, lọc chủ đề khi chuyển trang
            -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                  <ul class="pagination justify-content-center">
                    <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                      <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?><?php echo ($topic_id !== null) ? '&topic_id=' . htmlspecialchars($topic_id) : ''; ?>" tabindex="-1">&laquo;</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                      <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?><?php echo ($topic_id !== null) ? '&topic_id=' . htmlspecialchars($topic_id) : ''; ?>"><?php echo $i; ?></a>
                      </li>
                    <?php endfor; ?>
                    <li class="page-item<?php if ($page >= $total_pages) echo ' disabled'; ?>">
                      <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?><?php echo ($topic_id !== null) ? '&topic_id=' . htmlspecialchars($topic_id) : ''; ?>">&raquo;</a>
                    </li>
                  </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <!-- How it works Section -->
    <section class="how-it-works-section py-5 bg-light" style="background-image: url('<?=$baseUrl?>/dist/imgs/how-it-works.png'); background-size: cover; background-position: center; color: white;">
        <div class="container">
            <h2 class="text-center mb-5 text-light">Hoạt động như thế nào?</h2>
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="timeline-how-it-works">
                        <!-- Step 1 -->
                        <div class="timeline-item">
                            <span class="timeline-icon"><i class="bi bi-search text-dark"></i></span>
                            <div class="timeline-content text-dark">
                                <h4>Tìm kiếm chủ đề yêu thích của bạn</h4>
                                <p>Bắt đầu hành trình khám phá kiến ​​thức của bạn bằng cách sử dụng thanh tìm kiếm thông minh hoặc duyệt qua các danh mục đa dạng. Hệ thống của chúng tôi được thiết kế để giúp bạn dễ dàng tìm thấy các bài viết, tài liệu hoặc khóa học phù hợp nhất với sở thích và nhu cầu học tập của bạn, từ cơ bản đến nâng cao.</p>
                            </div>
                        </div>
                        <!-- Step 2 -->
                        <div class="timeline-item">
                             <span class="timeline-icon"><i class="bi bi-bookmark text-dark"></i></span>
                            <div class="timeline-content text-dark">
                                <h4>Đánh dấu & Lưu lại cho riêng bạn</h4>
                                <p>Bạn tìm thấy điều gì đó hữu ích nhưng không có thời gian để đọc ngay? Đừng lo! Chỉ với một cú nhấp chuột, bạn có thể dễ dàng đánh dấu trang các bài viết yêu thích của mình. Tất cả các dấu trang được lưu trữ an toàn trong thư viện cá nhân của bạn, giúp bạn dễ dàng quay lại và tiếp tục học bất cứ lúc nào.</p>
                            </div>
                        </div>
                        <!-- Step 3 -->
                        <div class="timeline-item">
                            <span class="timeline-icon"><i class="bi bi-book text-dark"></i></span>
                            <div class="timeline-content text-dark">
                                <h4>Đọc & Tận hưởng</h4>
                                <p>Đắm mình vào thế giới tri thức với giao diện đọc thân thiện với người dùng và được tối ưu hóa. Tận hưởng trải nghiệm học tập liền mạch, không bị gián đoạn, với các nguồn tài nguyên được trình bày rõ ràng và hấp dẫn. Dành thời gian để tiếp thu kiến ​​thức mới và áp dụng vào thực tế, làm phong phú thêm sự hiểu biết của bạn.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5">
        <div class="container">
             <div class="row align-items-center">
                 <div class="col-md-6">
                    <h2 class="mb-4">Câu hỏi thường gặp</h2>
                    <div class="accordion" id="faqAccordion">
                         <!-- Existing FAQ Items -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Kiểm thử phần mềm là gì?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Kiểm thử phần mềm là quá trình đánh giá và xác minh rằng một sản phẩm phần mềm thực hiện đúng như mong đợi. Mục tiêu chính là phát hiện lỗi, đảm bảo chất lượng và tăng độ tin cậy của sản phẩm trước khi đưa vào sử dụng thực tế. Đây là một bước không thể thiếu trong quy trình phát triển phần mềm để đảm bảo trải nghiệm người dùng tốt nhất.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Làm thế nào để bắt đầu với kiểm thử phần mềm?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Để bắt đầu với kiểm thử phần mềm, bạn nên tìm hiểu các kiến thức cơ bản về quy trình kiểm thử, các loại kiểm thử (kiểm thử thủ công, kiểm thử tự động, hồi quy, v.v.) và các công cụ phổ biến. Thực hành viết test case, báo cáo lỗi và làm quen với các môi trường phát triển là rất quan trọng. Có nhiều khóa học trực tuyến và tài liệu miễn phí có thể giúp bạn khởi đầu.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Chi phí để học kiểm thử phần mềm là bao nhiêu?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                   Chi phí học kiểm thử phần mềm có thể rất đa dạng, từ miễn phí đến hàng chục triệu đồng, tùy thuộc vào phương pháp học và nguồn tài liệu bạn chọn. Có rất nhiều tài nguyên trực tuyến miễn phí như bài viết, video, và các khóa học giới thiệu. Nếu bạn muốn theo đuổi các chứng chỉ chuyên nghiệp hoặc các khóa học chuyên sâu tại các trung tâm đào tạo, chi phí sẽ cao hơn.
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
                 <div class="col-md-6 text-center">
                    <!-- Placeholder Image -->
                     <img src="<?=$baseUrl?>/dist/imgs/image.png" alt="Minh họa Câu hỏi thường gặp" class="img-fluid" style="height: 400px; border-radius: 10px;">
                 </div>
             </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'footer.php'; ?>