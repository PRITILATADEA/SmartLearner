<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['redirect'])) {
    $user_id = $_SESSION['user_id'];
    $redirect = urldecode($_GET['redirect']);

    // Mark ALL as read for this user
    $stmt = $conn->prepare("UPDATE notifications SET status='read' WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../" . $redirect);
    exit();
} else {
    header("Location: ../dashboard.php");
    exit();
}