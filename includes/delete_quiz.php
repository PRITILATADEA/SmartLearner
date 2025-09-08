<?php
require_once 'auth.php';
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit();
}
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$class_id = intval($_POST['class_id'] ?? 0);
if (!$quiz_id) {
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=invalid');
    exit();
}
$conn->begin_transaction();
try {
    // Delete options
    $stmt = $conn->prepare("SELECT question_id FROM quiz_questions WHERE quiz_id=?");
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $qids = [];
    while ($r = $res->fetch_assoc()) $qids[] = $r['question_id'];
    $stmt->close();
    if ($qids) {
        $in = implode(',', array_fill(0, count($qids), '?'));
        $types = str_repeat('i', count($qids));
        $del = $conn->prepare("DELETE FROM quiz_options WHERE question_id IN ($in)");
        $del->bind_param($types, ...$qids);
        $del->execute();
        $del->close();
    }
    // Delete responses, results, questions, quiz
    $del = $conn->prepare("DELETE FROM quiz_responses WHERE quiz_id=?");
    $del->bind_param('i', $quiz_id);
    $del->execute();
    $del->close();
    $del = $conn->prepare("DELETE FROM quiz_results WHERE quiz_id=?");
    $del->bind_param('i', $quiz_id);
    $del->execute();
    $del->close();
    $del = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id=?");
    $del->bind_param('i', $quiz_id);
    $del->execute();
    $del->close();
    $del = $conn->prepare("DELETE FROM quizzes WHERE quiz_id=?");
    $del->bind_param('i', $quiz_id);
    $del->execute();
    $del->close();
    $conn->commit();
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&success=deleted');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=server');
    exit();
}
