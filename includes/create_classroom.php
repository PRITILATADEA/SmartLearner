<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_name = trim($_POST['course_name']);
    $section     = trim($_POST['section']);
    $day1        = $_POST['day1'];
    $day1_start  = $_POST['day1_start'];
    $day1_end    = $_POST['day1_end'];
    $day1_room   = $_POST['day1_room'];

    $day2        = $_POST['day2'];
    $day2_start  = $_POST['day2_start'];
    $day2_end    = $_POST['day2_end'];
    $day2_room   = $_POST['day2_room'];

    $lab_day     = !empty($_POST['lab_day']) ? $_POST['lab_day'] : NULL;
    $lab_start   = !empty($_POST['lab_start']) ? $_POST['lab_start'] : NULL;
    $lab_end     = !empty($_POST['lab_end']) ? $_POST['lab_end'] : NULL;
    $lab_room    = !empty($_POST['lab_room']) ? $_POST['lab_room'] : NULL;

    $created_by  = $_SESSION['user_id'];
    $class_code  = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6);

    $stmt = $conn->prepare("INSERT INTO classrooms
        (course_name, section, class_code,
         day1, day1_start, day1_end, day1_room,
         day2, day2_start, day2_end, day2_room,
         lab_day, lab_start, lab_end, lab_room,
         created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssssssssssssi",
        $course_name, $section, $class_code,
        $day1, $day1_start, $day1_end, $day1_room,
        $day2, $day2_start, $day2_end, $day2_room,
        $lab_day, $lab_start, $lab_end, $lab_room,
        $created_by
    );

    if ($stmt->execute()) {
        $class_id = $stmt->insert_id;
        $stmt2 = $conn->prepare("INSERT INTO classroom_members (class_id, user_id, role) VALUES (?, ?, 'Teacher')");
        $stmt2->bind_param("ii", $class_id, $created_by);
        $stmt2->execute();
        $stmt2->close();

        header("Location: ../dashboard.php?success=classroom_created");
        exit();
    } else {
        header("Location: ../dashboard.php?error=failed");
        exit();
    }
}