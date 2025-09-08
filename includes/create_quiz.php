<?php
// Handles quiz creation: quizzes, quiz_questions, quiz_options, notifications
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = intval($_POST['class_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$questions = $_POST['questions'] ?? [];

if (!$class_id || !$title || !$start_time || !$end_time || empty($questions)) {
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=missing');
    exit();
}

$conn->begin_transaction();
try {
    // Insert quiz
    $stmt = $conn->prepare("INSERT INTO quizzes (class_id, title, start_time, end_time, original_start_time, original_end_time, created_by, evaluated) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param('isssssi', $class_id, $title, $start_time, $end_time, $start_time, $end_time, $user_id);
    $stmt->execute();
    $quiz_id = $stmt->insert_id;
    $stmt->close();

    // Insert questions and options
    foreach ($questions as $q) {
        $qtext = trim($q['text'] ?? '');
        $correct = intval($q['correct'] ?? 0);
        if (!$qtext || !$correct || empty($q['options'])) continue;
        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, correct_option) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $quiz_id, $qtext, $correct);
        $stmt->execute();
        $question_id = $stmt->insert_id;
        $stmt->close();
        // Options
        foreach ($q['options'] as $num => $otext) {
            $otext = trim($otext);
            if (!$otext) continue;
            $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, option_number) VALUES (?, ?, ?)");
            $stmt->bind_param('isi', $question_id, $otext, $num);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Notify all students in class
    $stmt = $conn->prepare("SELECT user_id FROM classroom_members WHERE class_id=? AND role='Student'");
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $msg = $_SESSION['user_name'] . " posted a new quiz: $title";
    $link = "classroom.php?id=$class_id&tab=quiz";
    while ($row = $res->fetch_assoc()) {
        $n = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $n->bind_param('iss', $row['user_id'], $msg, $link);
        $n->execute();
        $n->close();
    }
    $stmt->close();

    $conn->commit();
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&success=created');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=server');
    exit();
}
