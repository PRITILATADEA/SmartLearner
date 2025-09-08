<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['link'])) {
    $id = intval($_GET['id']);
    $link = urldecode($_GET['link']);

    $stmt = $conn->prepare("UPDATE notifications SET status='read' WHERE notification_id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    header("Location: ../" . $link);
    exit();
} else {
    header("Location: ../dashboard.php");
    exit();
}