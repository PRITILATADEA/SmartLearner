<?php
require_once 'auth.php';
require_once 'db.php';
header('Content-Type: application/json');
$quiz_id = intval($_GET['quiz_id'] ?? 0);
$quiz = null;
$questions = [];
if ($quiz_id) {
    $stmt = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id=?");
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $quiz = $res->fetch_assoc();
    $stmt->close();
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id=?");
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($q = $res->fetch_assoc()) {
        $qid = $q['question_id'];
        $q['options'] = [];
        $stmt2 = $conn->prepare("SELECT option_number, option_text FROM quiz_options WHERE question_id=?");
        $stmt2->bind_param('i', $qid);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($o = $res2->fetch_assoc()) {
            $q['options'][$o['option_number']] = $o['option_text'];
        }
        $stmt2->close();
        $questions[] = $q;
    }
    $stmt->close();
}
echo json_encode(['quiz'=>$quiz, 'questions'=>$questions]);
