<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Fetch active classrooms that the user is a member of
$stmt = $conn->prepare("SELECT c.class_id, c.course_name, c.section, c.class_code, c.created_by,
                               u.name AS owner_name, u.profile_img AS owner_img, m.role
                        FROM classrooms c
                        JOIN classroom_members m ON c.class_id = m.class_id
                        JOIN users u ON c.created_by = u.user_id
                        WHERE m.user_id = ? AND c.status='active'
                        ORDER BY c.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$classrooms = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - SmartLearner</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="shortcut icon" href="images/fav.ico" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">

<?php include "includes/navbar.php"; ?>

<main class="pt-20 px-6">
  <h1 class="text-2xl font-bold mb-6" style="color:#1d91e8;">My Classrooms</h1>

  <?php if (empty($classrooms)): ?>
      <div class="text-gray-600 text-lg">You haven’t joined or created any classrooms yet.</div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($classrooms as $class): 
            $is_owner = ($class['created_by'] == $user_id);
      ?>
        <div class="relative bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
          <!-- Clickable header linking to classroom -->
          <a href="classroom.php?id=<?= $class['class_id'] ?>" class="block">
            <!-- Header -->
            <div class="h-32 bg-cover bg-center relative" 
                 style="background-image: url('images/background.jpg');">
              <div class="absolute inset-0 bg-black opacity-40"></div>
              <div class="absolute bottom-2 left-3 text-white z-10">
                <h2 class="text-lg font-bold">
                  <?= htmlspecialchars($class['course_name']) ?> (<?= htmlspecialchars($class['section']) ?>)
                </h2>
                <p class="text-sm">By <?= htmlspecialchars($class['owner_name']) ?></p>
              </div>
              <!-- Owner Profile -->
              <div class="absolute -bottom-8 right-4 z-20">
                <img src="<?= htmlspecialchars($class['owner_img']) ?>" 
                     onerror="this.onerror=null;this.src='images/user.png';"
                     class="w-20 h-20 rounded-full border-2 border-white shadow-md object-cover" 
                     alt="Owner">
              </div>
            </div>
          </a>

          <!-- Bottom White Box -->
          <div class="h-28 flex justify-between items-center px-4">
            <div>
              <?php if ($is_owner): ?>
                <button onclick="openArchiveModal(<?= $class['class_id']?>)" 
                        class="text-xs px-4 py-2 rounded text-white font-medium shadow" 
                        style="background-color:#1d91e8;">Archive</button>
              <?php elseif ($class['role']=='Student'): ?>
                <button onclick="openUnenrollModal(<?= $class['class_id']?>)" 
                        class="text-xs px-4 py-2 rounded text-white font-medium shadow" 
                        style="background-color:#ff4072;">Unenroll</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Archive Modal -->
<div id="archiveModal" class="fixed inset-0 hidden bg-black bg-opacity-40 z-50 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
    <h2 class="text-xl font-bold mb-4" style="color:#1d91e8;">Archive Classroom</h2>
    <p class="mb-4">Are you sure you want to archive this classroom?</p>
    <form method="POST" action="includes/archive_classroom.php" class="flex justify-end space-x-3">
      <input type="hidden" id="archive_class_id" name="class_id" value="">
      <button type="button" onclick="closeArchiveModal()" class="px-4 py-2 border rounded text-gray-600">Cancel</button>
      <button type="submit" class="px-4 py-2 text-white rounded" style="background-color:#1d91e8;">Confirm</button>
    </form>
    <button onclick="closeArchiveModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- Unenroll Modal -->
<div id="unenrollModal" class="fixed inset-0 hidden bg-black bg-opacity-40 z-50 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
    <h2 class="text-xl font-bold mb-4" style="color:#ff4072;">Unenroll</h2>
    <p class="mb-4">Are you sure you want to unenroll from this classroom?</p>
    <form method="POST" action="includes/unenroll_classroom.php" class="flex justify-end space-x-3">
      <input type="hidden" id="unenroll_class_id" name="class_id" value="">
      <button type="button" onclick="closeUnenrollModal()" class="px-4 py-2 border rounded text-gray-600">Cancel</button>
      <button type="submit" class="px-4 py-2 text-white rounded" style="background-color:#ff4072;">Confirm</button>
    </form>
    <button onclick="closeUnenrollModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<script>
  function openArchiveModal(classId) {
    document.getElementById("archive_class_id").value = classId;
    document.getElementById("archiveModal").classList.remove("hidden");
  }
  function closeArchiveModal() {
    document.getElementById("archiveModal").classList.add("hidden");
  }
  function openUnenrollModal(classId) {
    document.getElementById("unenroll_class_id").value = classId;
    document.getElementById("unenrollModal").classList.remove("hidden");
  }
  function closeUnenrollModal() {
    document.getElementById("unenrollModal").classList.add("hidden");
  }
</script>

</body>
</html>