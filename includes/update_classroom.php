<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $user_id  = $_SESSION['user_id'];
    $class_id = intval($_POST['class_id']);
    $stmt = $conn->prepare("UPDATE classrooms 
        SET course_name=?, section=?, day1=?, day1_start=?, day1_end=?, day1_room=?,
            day2=?, day2_start=?, day2_end=?, day2_room=?,
            lab_day=?, lab_start=?, lab_end=?, lab_room=?
        WHERE class_id=? AND created_by=?");
    $stmt->bind_param("ssssssssssssssii",
        $_POST['course_name'], $_POST['section'],
        $_POST['day1'], $_POST['day1_start'], $_POST['day1_end'], $_POST['day1_room'],
        $_POST['day2'], $_POST['day2_start'], $_POST['day2_end'], $_POST['day2_room'],
        $_POST['lab_day'], $_POST['lab_start'], $_POST['lab_end'], $_POST['lab_room'],
        $class_id, $user_id
    );
    $stmt->execute();
    $stmt->close();

    header("Location: ../classroom.php?id=$class_id&success=updated");
    exit();
}