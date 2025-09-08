<?php
session_start();
require_once "db.php";
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $class_id = intval($_POST['class_id']);
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE classrooms SET status='archived' WHERE class_id=? AND created_by=?");
    $stmt->bind_param("ii", $class_id, $user_id);
    $stmt->execute();
    header("Location: ../dashboard.php?success=archived");
    exit();
}