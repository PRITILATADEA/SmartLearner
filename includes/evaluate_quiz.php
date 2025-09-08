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
// Get all questions and correct options
$stmt = $conn->prepare("SELECT question_id, correct_option FROM quiz_questions WHERE quiz_id=?");
$stmt->bind_param('i', $quiz_id);
$stmt->execute();
$res = $stmt->get_result();
$questions = [];
while ($r = $res->fetch_assoc()) $questions[$r['question_id']] = $r['correct_option'];
$stmt->close();
// Get all students who attempted
$stmt = $conn->prepare("SELECT DISTINCT user_id FROM quiz_responses WHERE quiz_id=?");
$stmt->bind_param('i', $quiz_id);
$stmt->execute();
$res = $stmt->get_result();
$students = [];
while ($r = $res->fetch_assoc()) $students[] = $r['user_id'];
$stmt->close();
// For each student, calculate marks
foreach ($students as $uid) {
    $marks = 0;
    $stmt = $conn->prepare("SELECT question_id, selected_option FROM quiz_responses WHERE quiz_id=? AND user_id=?");
    $stmt->bind_param('ii', $quiz_id, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($questions[$row['question_id']]) && $row['selected_option'] == $questions[$row['question_id']]) {
            $marks++;
        }
    }
    $stmt->close();
    // Insert or update result
    $stmt = $conn->prepare("REPLACE INTO quiz_results (quiz_id, user_id, marks, evaluated_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iii', $quiz_id, $uid, $marks);
    $stmt->execute();
    $stmt->close();
}
// Set quiz as evaluated
$stmt = $conn->prepare("UPDATE quizzes SET evaluated=1 WHERE quiz_id=?");
$stmt->bind_param('i', $quiz_id);
$stmt->execute();
$stmt->close();
// Notify all students
$stmt = $conn->prepare("SELECT user_id FROM classroom_members WHERE class_id=? AND role='Student'");
$stmt->bind_param('i', $class_id);
$stmt->execute();
$res = $stmt->get_result();
$msg = 'Quiz evaluated! Check your marks.';
$link = "classroom.php?id=$class_id&tab=quiz";
while ($row = $res->fetch_assoc()) {
    $n = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $n->bind_param('iss', $row['user_id'], $msg, $link);
    $n->execute();
    $n->close();
}
$stmt->close();
header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&success=evaluated');
exit();
