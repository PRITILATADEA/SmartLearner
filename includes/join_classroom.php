<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_code = strtoupper(trim($_POST['class_code']));
    $user_id = $_SESSION['user_id'];

    // Find classroom by code
    $stmt = $conn->prepare("SELECT class_id FROM classrooms WHERE class_code=?");
    $stmt->bind_param("s", $class_code);
    $stmt->execute();
    $stmt->bind_result($class_id);
    if ($stmt->fetch()) {
        $stmt->close();

        // Check if already a member
        $check = $conn->prepare("SELECT member_id FROM classroom_members WHERE class_id=? AND user_id=?");
        $check->bind_param("ii", $class_id, $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            header("Location: ../dashboard.php?error=already_joined");
            exit();
        }
        $check->close();

        // Add as student
        $insert = $conn->prepare("INSERT INTO classroom_members (class_id, user_id, role) VALUES (?, ?, 'Student')");
        $insert->bind_param("ii", $class_id, $user_id);
        if ($insert->execute()) {
            $insert->close();
            header("Location: ../dashboard.php?success=joined");
            exit();
        } else {
            header("Location: ../dashboard.php?error=unable_to_join");
            exit();
        }
    } else {
        $stmt->close();
        header("Location: ../dashboard.php?error=invalid_code");
        exit();
    }
}