<?php
session_start();
require_once "db.php";
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $class_id = intval($_POST['class_id']);
    $user_id = $_SESSION['user_id'];
    // Remove student membership
    $stmt = $conn->prepare("DELETE FROM classroom_members WHERE class_id=? AND user_id=?");
    $stmt->bind_param("ii", $class_id, $user_id);
    $stmt->execute();
    header("Location: ../dashboard.php?success=unenrolled");
    exit();
}