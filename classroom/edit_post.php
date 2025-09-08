<?php
require_once "../includes/db.php";
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if (!isset($_GET['id']) && !isset($_POST['post_id'])) {
    header("Location: ../dashboard.php");
    exit();
}
$post_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['post_id']);
$class_id = isset($_GET['class']) ? intval($_GET['class']) : intval($_POST['class_id']);

// Fetch post
$stmt = $conn->prepare("SELECT p.*, c.created_by, m.role FROM posts p JOIN classrooms c ON p.class_id=c.class_id JOIN classroom_members m ON c.class_id=m.class_id AND m.user_id=? WHERE p.post_id=?");
$stmt->bind_param("ii", $_SESSION['user_id'], $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$post) {
    die("Post not found or you do not have permission.");
}
$is_owner = ($post['created_by'] == $_SESSION['user_id']);
$role = $post['role'];
if (!($is_owner || $role == 'Teaching Assistant' || $post['user_id'] == $_SESSION['user_id'])) {
    die("You do not have permission to edit this post.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $u = $conn->prepare("UPDATE posts SET content=? WHERE post_id=?");
        $u->bind_param("si", $content, $post_id);
        $u->execute();
        $u->close();
        header("Location: ../classroom.php?id=$class_id&tab=stream");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Post - SmartLearner</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-xl border border-gray-200">
    <h2 class="text-2xl font-bold mb-4 text-[#1d91e8]">Edit Post</h2>
    <form method="post">
      <input type="hidden" name="post_id" value="<?= $post_id ?>">
      <input type="hidden" name="class_id" value="<?= $class_id ?>">
      <textarea name="content" rows="6" required class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 mb-4 focus:ring-2 focus:ring-[#1d91e8]"><?= htmlspecialchars($post['content']) ?></textarea>
      <div class="flex justify-end space-x-3">
        <a href="../classroom.php?id=<?= $class_id ?>&tab=stream" class="px-4 py-2 border rounded-xl text-gray-600">Cancel</a>
        <button type="submit" class="px-6 py-2 rounded-xl text-white font-semibold shadow" style="background-color:#1d91e8;">Update</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
