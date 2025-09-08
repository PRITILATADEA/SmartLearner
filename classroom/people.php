<?php
// classroom/people.php
include_once __DIR__ . '/../includes/db.php';
if (!isset($class_id)) $class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


// Fetch teachers and TAs
$teachers = [];
$stmt = $conn->prepare("SELECT u.name, u.profile_img, m.role FROM classroom_members m JOIN users u ON m.user_id=u.user_id WHERE m.class_id=? AND (m.role='Teacher' OR m.role='TA')");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $teachers[] = $row;
$stmt->close();

// Fetch students
$students = [];
$stmt = $conn->prepare("SELECT u.name, u.profile_img FROM classroom_members m JOIN users u ON m.user_id=u.user_id WHERE m.class_id=? AND m.role='Student'");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $students[] = $row;
$stmt->close();
?>

<div class="max-w-3xl mx-auto">
  <h2 class="text-2xl font-bold mb-4 mt-2" style="font-family: 'Google Sans', Arial, sans-serif;">Teachers</h2>
  <div class="border-t border-gray-200 mb-6"></div>
  <?php foreach ($teachers as $t): ?>
    <div class="flex items-center space-x-3 mb-4">
      <img src="<?= htmlspecialchars($t['profile_img']) ?>" class="w-9 h-9 rounded-full border border-gray-300 object-cover" alt="<?= htmlspecialchars($t['role']) ?>">
      <span class="text-base text-gray-900 font-medium" style="font-family: 'Google Sans', Arial, sans-serif;">
        <?= htmlspecialchars($t['name']) ?>
        <?php if ($t['role'] === 'TA'): ?>
          <span class="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">TA</span>
        <?php endif; ?>
      </span>
    </div>
  <?php endforeach; ?>

  <h2 class="text-2xl font-bold mb-4 mt-8" style="font-family: 'Google Sans', Arial, sans-serif;">Classmates</h2>
  <div class="flex justify-between items-center mb-1">
    <div></div>
    <div class="text-gray-500 text-sm"><?= count($students) ?> students</div>
  </div>
  <div class="border-t border-gray-200 mb-3"></div>
  <?php foreach ($students as $s): ?>
    <div class="flex items-center space-x-3 py-3 border-b border-gray-100">
      <?php if ($s['profile_img']): ?>
        <img src="<?= htmlspecialchars($s['profile_img']) ?>" class="w-8 h-8 rounded-full border border-gray-200 object-cover" alt="Student">
      <?php else: ?>
        <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold text-base">
          <?= strtoupper(substr($s['name'],0,1)) ?>
        </div>
      <?php endif; ?>
      <span class="text-sm text-gray-900" style="font-family: 'Google Sans', Arial, sans-serif;"><?= htmlspecialchars($s['name']) ?></span>
    </div>
  <?php endforeach; ?>
</div>
