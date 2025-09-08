<?php
require_once 'auth.php';
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$class_id = intval($_POST['class_id'] ?? 0);
$responses = $_POST['responses'] ?? [];
if (!$quiz_id || empty($responses)) {
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=invalid');
    exit();
}
// Check classroom membership and role
$stmt = $conn->prepare("SELECT role FROM classroom_members WHERE class_id=? AND user_id=?");
$stmt->bind_param('ii', $class_id, $user_id);
$stmt->execute();
$stmt->bind_result($role);
if (!$stmt->fetch()) {
    $stmt->close();
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=not_member');
    exit();
}
$stmt->close();
if ($role !== 'Student') {
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=not_student');
    exit();
}
// Only allow one attempt
$stmt = $conn->prepare("SELECT 1 FROM quiz_responses WHERE quiz_id=? AND user_id=? LIMIT 1");
$stmt->bind_param('ii', $quiz_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&error=already_attempted');
    exit();
}
$stmt->close();
// Insert responses
foreach ($responses as $qid => $selected) {
    $stmt = $conn->prepare("INSERT INTO quiz_responses (quiz_id, question_id, user_id, selected_option) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiii', $quiz_id, $qid, $user_id, $selected);
    $stmt->execute();
    $stmt->close();
}
header('Location: ../classroom.php?id=' . $class_id . '&tab=quiz&success=attempted');
exit();
