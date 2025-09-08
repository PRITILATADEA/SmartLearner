<?php
require_once 'auth.php';
require_once 'db.php';
header('Content-Type: application/json');
$quiz_id = intval($_GET['quiz_id'] ?? 0);
$out = [];
if ($quiz_id) {
    $stmt = $conn->prepare("SELECT u.name, IFNULL(r.marks,0) as marks FROM classroom_members m JOIN users u ON m.user_id=u.user_id LEFT JOIN quiz_results r ON r.user_id=u.user_id AND r.quiz_id=? WHERE m.class_id=(SELECT class_id FROM quizzes WHERE quiz_id=?) AND m.role='Student'");
    $stmt->bind_param('ii', $quiz_id, $quiz_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $out[] = $row;
    $stmt->close();
}
echo json_encode($out);
