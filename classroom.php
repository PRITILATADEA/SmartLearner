<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch classroom details + membership

$stmt = $conn->prepare("SELECT c.*, u.name AS owner_name, u.profile_img AS owner_img, m.role
            FROM classrooms c
            JOIN users u ON c.created_by = u.user_id
            JOIN classroom_members m ON c.class_id = m.class_id
            WHERE c.class_id=? AND m.user_id=?");
if (!$stmt) {
  die("Database error: " . $conn->error);
}
$stmt->bind_param("ii", $class_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
  die("Database error: " . $stmt->error);
}
$class = $result->fetch_assoc();
$stmt->close();

if (!$class) {
    die("You don't have access to this classroom.");
}

$is_owner = ($class['created_by'] == $user_id);
$role     = $class['role'];

// Active tab (fix: always initialize)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'stream';

// COMMENT SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_comment'])) {
    $comment_text = trim($_POST['comment_text']);
    $post_id = intval($_POST['post_id']);

  if (!empty($comment_text)) {
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
    $stmt->execute();
    $stmt->close();

    // 🔔 Notification to the post owner
    $q = $conn->prepare("SELECT user_id FROM posts WHERE post_id=?");
    if ($q === false) {
      die("Database error (post owner select): " . $conn->error);
    }
    $q->bind_param("i", $post_id);
    $q->execute();
    $q->bind_result($owner_id);
    if ($q->fetch() && $owner_id != $user_id) {
      $msg = $_SESSION['user_name']." commented on your post";
      $link = "classroom.php?id=$class_id&tab=stream";
      $notif = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
      if ($notif === false) {
        // Don't die for notification, just skip
      } else {
        $notif->bind_param("iss", $owner_id, $msg, $link);
        $notif->execute();
        $notif->close();
      }
    }
    $q->close();

    header("Location: classroom.php?id=$class_id&tab=stream#post-$post_id");
    exit();
  }
}

// ✅ Process Stream Post Submission HERE — before HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        // Insert post
        $stmt = $conn->prepare("INSERT INTO posts (class_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $class_id, $user_id, $content);
        $stmt->execute();
        $stmt->close();

        if (($is_owner || $role == 'Teaching Assistant')) {
          // Notifications for Students
          $notify = $conn->prepare("SELECT user_id FROM classroom_members WHERE class_id=? AND role='Student'");
          $notify->bind_param("i", $class_id);
          $notify->execute();
          $students = $notify->get_result();
          $msg = $_SESSION['user_name']." posted a new announcement";
          $link = "classroom.php?id=".$class_id."&tab=stream";
          while ($s = $students->fetch_assoc()) {
              $n = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
              $n->bind_param("iss", $s['user_id'], $msg, $link);
              $n->execute();
              $n->close();
          }
          $notify->close();
        }

        // Safe Redirect — NOW will work because no HTML sent yet
        header("Location: classroom.php?id=$class_id&tab=stream&success=posted");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($class['course_name']) ?> - SmartLearner</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function openEditModal() {
      document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }
    function openTeacherModal() {
      document.getElementById('teacherModal').classList.remove('hidden');
    }
    function closeTeacherModal() {
      document.getElementById('teacherModal').classList.add('hidden');
    }
  </script>
  <link rel="shortcut icon" href="images/fav.ico" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">

<?php include "includes/navbar.php"; ?>

<!-- Top Banner -->
<div class="mt-16 bg-cover bg-center relative"
     style="background-image:url('images/background.jpg'); height:250px;">
  <div class="absolute inset-0 bg-black opacity-60"></div>
  <div class="relative z-10 p-6 text-white">
    <h1 class="text-3xl font-bold"><?= htmlspecialchars($class['course_name']) ?> (<?= htmlspecialchars($class['section']) ?>)</h1>
    <p class="mt-1">Teacher: <?= htmlspecialchars($class['owner_name']) ?></p>

    <!-- Show schedule details -->
    <div class="mt-2 text-sm">
      <?php if ($class['day1']): ?>
        <?= $class['day1'] ?> <?= date("g:i A", strtotime($class['day1_start'])) ?> - <?= date("g:i A", strtotime($class['day1_end'])) ?>,
        Room: <?= htmlspecialchars($class['day1_room']) ?><br>
      <?php endif; ?>
      <?php if ($class['day2']): ?>
        <?= $class['day2'] ?> <?= date("g:i A", strtotime($class['day2_start'])) ?> - <?= date("g:i A", strtotime($class['day2_end'])) ?>,
        Room: <?= htmlspecialchars($class['day2_room']) ?><br>
      <?php endif; ?>
      <?php if ($class['lab_day']): ?>
        Lab: <?= $class['lab_day'] ?> <?= date("g:i A", strtotime($class['lab_start'])) ?> - <?= date("g:i A", strtotime($class['lab_end'])) ?>,
        Room: <?= htmlspecialchars($class['lab_room']) ?><br>
      <?php endif; ?>
    </div>

    <!-- Class Code -->
    <?php if ($is_owner): ?>
      <p class="mt-2 text-sm">Class Code: 
        <span class="font-mono bg-white text-black px-2 py-1 rounded"><?= $class['class_code'] ?></span>
      </p>

      <!-- Action Buttons -->
      <div class="mt-4 flex space-x-3">
        <button onclick="openEditModal()" 
                class="px-2 py-1 rounded text-white font-sm shadow"
                style="background-color:#1d91e8;">Edit Class</button>
        <button onclick="openTeacherModal()" 
                class="px-2 py-1 rounded text-white font-sm shadow"
                style="background-color:#ff4072;">Add Teaching Assistant</button>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- =========== Edit Classroom Modal =========== -->
<div id="editModal" class="fixed inset-0 hidden bg-black bg-opacity-40 z-50 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative">
    <h2 class="text-xl font-bold mb-4" style="color:#1d91e8;">Edit Class Details</h2>
    <form action="includes/update_classroom.php" method="POST" class="space-y-4">
      <input type="hidden" name="class_id" value="<?= $class_id ?>">

      <div>
        <label class="block text-gray-700">Course Name</label>
        <input type="text" name="course_name" required value="<?= htmlspecialchars($class['course_name']) ?>"
               class="w-full border rounded px-3 py-2" style="border-color:#1d91e8;">
      </div>
      <div>
        <label class="block text-gray-700">Section</label>
        <input type="text" name="section" value="<?= htmlspecialchars($class['section']) ?>"
               class="w-full border rounded px-3 py-2">
      </div>

      <!-- Day 1 -->
      <div class="grid grid-cols-4 gap-3">
        <div>
          <label class="block">Day 1</label>
          <input type="text" name="day1" value="<?= $class['day1'] ?>" class="w-full border rounded px-2 py-1">
        </div>
        <div><label>Start</label><input type="time" name="day1_start" value="<?= $class['day1_start'] ?>" class="w-full border rounded"></div>
        <div><label>End</label><input type="time" name="day1_end" value="<?= $class['day1_end'] ?>" class="w-full border rounded"></div>
        <div><label>Room</label><input type="text" name="day1_room" value="<?= $class['day1_room'] ?>" class="w-full border rounded"></div>
      </div>

      <!-- Day 2 -->
      <div class="grid grid-cols-4 gap-3">
        <div><label>Day 2</label><input type="text" name="day2" value="<?= $class['day2'] ?>" class="w-full border rounded"></div>
        <div><label>Start</label><input type="time" name="day2_start" value="<?= $class['day2_start'] ?>" class="w-full border rounded"></div>
        <div><label>End</label><input type="time" name="day2_end" value="<?= $class['day2_end'] ?>" class="w-full border rounded"></div>
        <div><label>Room</label><input type="text" name="day2_room" value="<?= $class['day2_room'] ?>" class="w-full border rounded"></div>
      </div>

      <!-- Lab -->
      <div class="grid grid-cols-4 gap-3">
        <div><label>Lab Day</label><input type="text" name="lab_day" value="<?= $class['lab_day'] ?>" class="w-full border rounded"></div>
        <div><label>Start</label><input type="time" name="lab_start" value="<?= $class['lab_start'] ?>" class="w-full border rounded"></div>
        <div><label>End</label><input type="time" name="lab_end" value="<?= $class['lab_end'] ?>" class="w-full border rounded"></div>
        <div><label>Room</label><input type="text" name="lab_room" value="<?= $class['lab_room'] ?>" class="w-full border rounded"></div>
      </div>

      <div class="flex justify-end space-x-3">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 border rounded">Cancel</button>
        <button type="submit" class="px-6 py-2 rounded text-white" style="background-color:#1d91e8;">Update</button>
      </div>
    </form>
    <button onclick="closeEditModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- =========== Add Teaching Assistant Modal =========== -->
<div id="teacherModal" class="fixed inset-0 hidden bg-black bg-opacity-40 z-50 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
    <h2 class="text-xl font-bold mb-4" style="color:#ff4072;">Add Teaching Assistant</h2>
    <form action="includes/add_teacher.php" method="POST" class="space-y-4">
      <input type="hidden" name="class_id" value="<?= $class_id ?>">
      <div>
        <label class="block">Assistant Email</label>
        <input type="email" name="teacher_email" required class="w-full border rounded px-3 py-2">
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="closeTeacherModal()" class="px-3 py-1.5 text-sm border rounded">Cancel</button>
        <button type="submit" class="px-3 py-1.5 text-sm rounded text-white" style="background-color:#ff4072;">Add</button>
      </div>
    </form>
    <button onclick="closeTeacherModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- Second Navbar (Tabs) -->
<div class="bg-white shadow-md sticky top-16 z-30 border-b border-gray-200">
  <div class="max-w-6xl mx-auto">
    <ul class="flex space-x-8 h-14 items-center text-sm font-semibold px-6">
      <li><a href="?id=<?= $class_id ?>&tab=stream"
        class="pb-3 <?= ($tab=='stream')?'text-[#1d91e8] border-b-2 border-[#1d91e8]':'text-gray-600 hover:text-[#1d91e8]' ?>">Stream</a></li>
      <li><a href="?id=<?= $class_id ?>&tab=classwork"
        class="pb-3 <?= ($tab=='classwork')?'text-[#1d91e8] border-b-2 border-[#1d91e8]':'text-gray-600 hover:text-[#1d91e8]' ?>">Classwork</a></li>
      <li><a href="?id=<?= $class_id ?>&tab=quiz"
        class="pb-3 <?= ($tab=='quiz')?'text-[#1d91e8] border-b-2 border-[#1d91e8]':'text-gray-600 hover:text-[#1d91e8]' ?>">Quiz</a></li>
      <li><a href="?id=<?= $class_id ?>&tab=people"
        class="pb-3 <?= ($tab=='people')?'text-[#1d91e8] border-b-2 border-[#1d91e8]':'text-gray-600 hover:text-[#1d91e8]' ?>">People</a></li>
    </ul>
  </div>
</div>

<!-- Content Section -->
<div class="max-w-6xl mx-auto px-6 py-6 bg-white rounded-lg shadow-sm mt-4">
  <?php
  switch ($tab) {
    case "stream": include "classroom/stream.php"; break;
    case "classwork": include "classroom/classwork.php"; break;
    case "quiz": include "classroom/quiz.php"; break;
    case "people": include "classroom/people.php"; break;
    default: include "classroom/stream.php"; break;
  }
  ?>
</div>
</body>
</html>