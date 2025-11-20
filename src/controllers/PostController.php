<?php
class PostController
{
    private $postModel;

    public function __construct($conn)
    {
        $this->postModel = new PostModel($conn);
    }

    public function index()
    {
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = 6;
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['search'] ?? '');
        $topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : null;

        $total_posts = $this->postModel->countPosts($search, $topic_id);
        $total_pages = ceil($total_posts / $limit);

        $posts = $this->postModel->getPosts($search, $topic_id, $limit, $offset);

        require __DIR__ . '/../views/posts/index.php';
    }
}
?>