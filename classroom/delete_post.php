<?php
require_once "../includes/db.php";
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['class_id'])) {
    $post_id = intval($_POST['post_id']);
    $class_id = intval($_POST['class_id']);
    // Only allow owner, TA, or post author to delete
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id=?");
    if ($stmt) {
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $stmt->bind_result($author_id);
        $stmt->fetch();
        $stmt->close();
        $is_owner = false;
        $role = '';
        $q = $conn->prepare("SELECT c.created_by, m.role FROM classrooms c JOIN classroom_members m ON c.class_id=m.class_id WHERE c.class_id=? AND m.user_id=?");
        if ($q) {
            $q->bind_param("ii", $class_id, $_SESSION['user_id']);
            $q->execute();
            $q->bind_result($created_by, $role);
            $q->fetch();
            $is_owner = ($created_by == $_SESSION['user_id']);
            $q->close();
        }
        if ($is_owner || $role == 'Teaching Assistant' || $author_id == $_SESSION['user_id']) {
            $del = $conn->prepare("DELETE FROM posts WHERE post_id=?");
            if ($del) {
                $del->bind_param("i", $post_id);
                $del->execute();
                $del->close();
            }
        }
    }
    header("Location: ../classroom.php?id=$class_id&tab=stream");
    exit();
}
header("Location: ../dashboard.php");
exit();
?>
