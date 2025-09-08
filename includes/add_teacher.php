<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"]=="POST") {
    $class_id = intval($_POST['class_id']);
    $email    = trim($_POST['teacher_email']);
    $owner_id = $_SESSION['user_id'];

    // Validate classroom belongs to this owner
    $check = $conn->prepare("SELECT created_by, course_name, section FROM classrooms WHERE class_id=?");
    $check->bind_param("i", $class_id);
    $check->execute();
    $check->bind_result($created_by, $course_name, $section);
    $check->fetch();
    $check->close();

    if ($created_by != $owner_id) {
        die("Unauthorized");
    }

    // Find user by email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($teacher_id);
    if ($stmt->fetch()) {
        $stmt->close();

        // Already added?
        $check2 = $conn->prepare("SELECT member_id FROM classroom_members WHERE class_id=? AND user_id=?");
        $check2->bind_param("ii", $class_id, $teacher_id);
        $check2->execute();
        $check2->store_result();

        if ($check2->num_rows > 0) {
            $check2->close();
            header("Location: ../classroom.php?id=$class_id");
            exit();
        }
        $check2->close();

        // Insert as Teaching Assistant
        $insert = $conn->prepare("INSERT INTO classroom_members (class_id, user_id, role) 
                                  VALUES (?, ?, 'Teaching Assistant')");
        $insert->bind_param("ii", $class_id, $teacher_id);
        $insert->execute();
        $insert->close();

        // Send notification
        $msg = "You were added as a Teaching Assistant to $course_name ($section)";
        $link = "classroom.php?id=$class_id";

        $notif = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $notif->bind_param("iss", $teacher_id, $msg, $link);
        $notif->execute();
        $notif->close();

        header("Location: ../classroom.php?id=$class_id");
        exit();
    } else {
        $stmt->close();
        header("Location: ../classroom.php?id=$class_id&error=User not found");
        exit();
    }
}