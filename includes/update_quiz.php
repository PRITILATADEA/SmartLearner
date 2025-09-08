<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$action = $_POST['action'] ?? '';

// Toggle publish/unpublish
if ($action === 'toggle_publish') {
    // To support toggling, store original times if not present
    $stmt = $conn->prepare("SELECT start_time, end_time, original_start_time, original_end_time FROM quizzes WHERE quiz_id=?");
    if (!$stmt) {
        die("Database error: quizzes table must have columns original_start_time and original_end_time.\n" . $conn->error);
    }
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $stmt->bind_result($start, $end, $orig_start, $orig_end);
    $stmt->fetch();
    $stmt->close();
    // If currently published (start_time and end_time match original), unpublish (set both to past)
    if ($start === $orig_start && $end === $orig_end) {
        $past = date('Y-m-d H:i:s', strtotime('-2 minutes'));
        $stmt = $conn->prepare("UPDATE quizzes SET start_time=?, end_time=? WHERE quiz_id=?");
        if (!$stmt) die("Database error: " . $conn->error);
        $stmt->bind_param('ssi', $past, $past, $quiz_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Publish: always restore original times
        if ($orig_start && $orig_end) {
            $stmt = $conn->prepare("UPDATE quizzes SET start_time=?, end_time=? WHERE quiz_id=?");
            if (!$stmt) die("Database error: " . $conn->error);
            $stmt->bind_param('ssi', $orig_start, $orig_end, $quiz_id);
            $stmt->execute();
            $stmt->close();
        } else {
        }
    }
    header('Location: ../classroom.php?id=' . intval($_POST['class_id']) . '&tab=quiz');
    exit();
}

// Edit quiz (title, time, questions, options)
$title = trim($_POST['title'] ?? '');
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$questions = $_POST['questions'] ?? [];

if ($quiz_id && $title && $start_time && $end_time && $questions) {
    $conn->begin_transaction();
    try {
    $stmt = $conn->prepare("UPDATE quizzes SET title=?, start_time=?, end_time=?, original_start_time=?, original_end_time=? WHERE quiz_id=?");
    $stmt->bind_param('sssssi', $title, $start_time, $end_time, $start_time, $end_time, $quiz_id);
    $stmt->execute();
    $stmt->close();
        // Remove old questions/options
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
            $del = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id=?");
            $del->bind_param('i', $quiz_id);
            $del->execute();
            $del->close();
        }
        // Insert new questions/options
        foreach ($questions as $q) {
            $qtext = trim($q['text'] ?? '');
            $correct = intval($q['correct'] ?? 0);
            if (!$qtext || !$correct || empty($q['options'])) continue;
            $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, correct_option) VALUES (?, ?, ?)");
            $stmt->bind_param('isi', $quiz_id, $qtext, $correct);
            $stmt->execute();
            $question_id = $stmt->insert_id;
            $stmt->close();
            foreach ($q['options'] as $num => $otext) {
                $otext = trim($otext);
                if (!$otext) continue;
                $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, option_number) VALUES (?, ?, ?)");
                $stmt->bind_param('isi', $question_id, $otext, $num);
                $stmt->execute();
                $stmt->close();
            }
        }
        $conn->commit();
        header('Location: ../classroom.php?id=' . intval($_POST['class_id']) . '&tab=quiz&success=updated');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: ../classroom.php?id=' . intval($_POST['class_id']) . '&tab=quiz&error=server');
        exit();
    }
}
header('Location: ../classroom.php?id=' . intval($_POST['class_id']) . '&tab=quiz&error=invalid');
exit();
