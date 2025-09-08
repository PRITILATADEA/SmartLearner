<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_profile_img = $_SESSION['user_profile_img'] ?? "images/user.png";

// Fetch ALL notifications for current user, latest first
$stmt = $conn->prepare("SELECT notification_id, message, link, status, created_at 
                        FROM notifications 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$notif_count = 0;
foreach ($notifications as $n) {
    if ($n['status'] === 'unread') $notif_count++;
}
$stmt->close();
?>

<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
      crossorigin="anonymous" referrerpolicy="no-referrer" />

<nav class="bg-white shadow-md fixed w-full top-0 left-0 z-50">
    <div class="max-w-7xl mx-auto px-4 flex justify-between h-16 items-center">

        <!-- Left: Logo -->
        <a href="dashboard.php" class="flex items-center space-x-2">
            <img src="images/logo.png" class="h-10 w-10" alt="Logo" 
                 onerror="this.onerror=null;this.src='images/user.png';">
            <span class="font-bold text-xl" style="color:#1d91e8;">SmartLearner</span>
        </a>

        <!-- Right -->
        <div class="flex items-center space-x-8">

            <!-- Notifications -->
            <div class="relative">
                <button id="notifBtn" class="relative flex items-center text-gray-700 hover:opacity-75">
                <i class="fa-solid fa-bell text-xl" style="color:#1d91e8;"></i>
                <span class="ml-1 hidden sm:inline font-medium">Updates</span>
                <?php if ($notif_count > 0): ?>
                    <span class="absolute -top-1 -right-2 bg-red-500 text-white text-xs px-1 rounded-full">
                    <?= $notif_count ?>
                    </span>
                <?php endif; ?>
                </button>
                <!-- Dropdown -->
                <div id="notifDropdown"
                    class="hidden absolute right-0 mt-2 w-80 bg-white shadow-lg rounded-lg z-50 max-h-96 overflow-y-auto">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notif): ?>
                    <a href="includes/mark_all.php?redirect=<?= urlencode($notif['link']) ?>"
                        class="block px-4 py-2 text-sm border-b 
                                <?= $notif['status']=='unread' ? 'bg-blue-50 font-semibold' : 'text-gray-700' ?> 
                                hover:bg-gray-100">
                        <div><?= htmlspecialchars($notif['message']) ?></div>
                        <div class="text-xs text-gray-400"><?= date("M j, g:i A", strtotime($notif['created_at'])) ?></div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="px-4 py-3 text-gray-500 text-sm text-center">No notifications</p>
                <?php endif; ?>
                </div>
            </div>

            <!-- Profile -->
            <a href="profile.php" class="flex items-center space-x-2 hover:opacity-75">
                <img src="<?= htmlspecialchars($user_profile_img) ?>" class="h-9 w-9 rounded-full border" alt="Profile">
                <span><?= htmlspecialchars($user_name) ?></span>
            </a>

            <!-- Add Classroom -->
            <button onclick="openClassOptionsModal()" class="hover:opacity-75">
                <i class="fa-solid fa-circle-plus text-2xl" style="color:#ff4072;"></i>
            </button>

            <!-- Schedule -->
            <a href="schedule.php" class="hover:opacity-75">
                <i class="fa-solid fa-calendar-days text-xl" style="color:#1d91e8;"></i>
            </a>

            <!-- Logout -->
            <a href="logout.php" class="flex items-center space-x-1 hover:opacity-75">
                <i class="fa-solid fa-right-from-bracket text-xl" style="color:#ff4072;"></i>
                <span class="hidden md:inline">Logout</span>
            </a>
        </div>
    </div>
</nav>

<!-- Classroom Options Modal -->
<div id="classOptionsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg p-6 shadow-lg w-96 relative">
    <h2 class="text-xl font-bold mb-4" style="color:#1d91e8;">Classroom Options</h2>
    <button onclick="openCreateClassModal()" 
            class="block w-full py-2 rounded mb-3 text-white font-medium"
            style="background-color:#1d91e8;">
        Create Classroom
    </button>
    <button onclick="openJoinClassModal()" 
            class="block w-full py-2 rounded mb-3 text-white font-medium"
            style="background-color:#ff4072;">
        Join Classroom
    </button>
    <button onclick="closeClassOptionsModal()" 
            class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- Create Classroom Modal -->
<div id="createClassModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-8 relative">
    <h2 class="text-2xl font-bold mb-4" style="color:#1d91e8;">Create Classroom</h2>
    <form action="includes/create_classroom.php" method="POST" class="space-y-4">
      
      <!-- Course Name -->
      <div>
        <label class="block text-gray-700">Course Name</label>
        <input type="text" name="course_name" required class="w-full border rounded px-3 py-2 focus:outline-none" style="border-color:#1d91e8;">
      </div>

      <!-- Section -->
      <div>
        <label class="block text-gray-700">Section</label>
        <input type="text" name="section" class="w-full border rounded px-3 py-2 focus:outline-none" style="border-color:#1d91e8;">
      </div>

      <!-- Day 1 -->
      <div class="grid grid-cols-4 gap-3">
        <div>
          <label class="block text-gray-700">Day 1</label>
          <select name="day1" class="w-full border rounded px-3 py-2">
            <option value="">Select</option>
            <option>Mon</option><option>Tue</option><option>Wed</option>
            <option>Thu</option><option>Fri</option><option>Sat</option><option>Sun</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700">Start Time</label>
          <input type="time" name="day1_start" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-gray-700">End Time</label>
          <input type="time" name="day1_end" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-gray-700">Room</label>
          <input type="text" name="day1_room" class="w-full border rounded px-3 py-2">
        </div>
      </div>

      <!-- Day 2 -->
      <div class="grid grid-cols-4 gap-3">
        <div>
          <label class="block text-gray-700">Day 2</label>
          <select name="day2" class="w-full border rounded px-3 py-2">
            <option value="">Select</option>
            <option>Mon</option><option>Tue</option><option>Wed</option>
            <option>Thu</option><option>Fri</option><option>Sat</option><option>Sun</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700">Start Time</label>
          <input type="time" name="day2_start" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-gray-700">End Time</label>
          <input type="time" name="day2_end" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-gray-700">Room</label>
          <input type="text" name="day2_room" class="w-full border rounded px-3 py-2">
        </div>
      </div>

      <!-- Lab (optional) -->
      <div class="grid grid-cols-4 gap-3">
        <div>
          <label class="block text-gray-700">Lab Day (optional)</label>
          <select name="lab_day" class="w-full border rounded px-3 py-2">
            <option value="">None</option>
            <option>Mon</option><option>Tue</option><option>Wed</option>
            <option>Thu</option><option>Fri</option><option>Sat</option><option>Sun</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700">Start Time</label>
          <input type="time" name="lab_start" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-gray-700">End Time</label>
          <input type="time" name="lab_end" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-gray-700">Lab Room</label>
          <input type="text" name="lab_room" class="w-full border rounded px-3 py-2">
        </div>
      </div>

      <!-- Submit -->
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="closeCreateClassModal()" class="px-4 py-2 border rounded text-gray-600">Cancel</button>
        <button type="submit" class="px-6 py-2 rounded text-white font-medium" style="background-color:#1d91e8;">Create</button>
      </div>
    </form>
    <button onclick="closeCreateClassModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- Join Classroom Modal -->
<div id="joinClassModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-8 relative">
    <h2 class="text-2xl font-bold mb-4" style="color:#ff4072;">Join Classroom</h2>
    <form action="includes/join_classroom.php" method="POST" class="space-y-4">
      <div>
        <label class="block text-gray-700">Class Code</label>
        <input type="text" name="class_code" placeholder="Enter class code (e.g., AB12CD)" required
               class="w-full border rounded px-3 py-2 focus:outline-none" 
               style="border-color:#ff4072;">
      </div>
      <div class="flex justify-end space-x-3">
        <button type="button" onclick="closeJoinClassModal()" 
                class="px-4 py-2 border rounded text-gray-600">Cancel</button>
        <button type="submit" 
                class="px-6 py-2 rounded text-white font-medium"
                style="background-color:#ff4072;">Join</button>
      </div>
    </form>
    <button onclick="closeJoinClassModal()" 
            class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<script>
  // Notifications dropdown
  document.addEventListener("click", function(e){
      const btn = document.getElementById("notifBtn");
      const dropdown = document.getElementById("notifDropdown");
      if (btn.contains(e.target)) {
          dropdown.classList.toggle("hidden");
      } else if (!dropdown.contains(e.target)) {
          dropdown.classList.add("hidden");
      }
  });

  // Modals
  function openClassOptionsModal(){ document.getElementById("classOptionsModal").classList.remove("hidden"); }
  function closeClassOptionsModal(){ document.getElementById("classOptionsModal").classList.add("hidden"); }
  function openCreateClassModal(){ 
      closeClassOptionsModal();
      document.getElementById("createClassModal").classList.remove("hidden"); 
  }
  function closeCreateClassModal(){ document.getElementById("createClassModal").classList.add("hidden"); }
  function openJoinClassModal(){ alert("Join Classroom modal coming soon..."); }

   // Join Class Modal
  function openJoinClassModal(){ 
      closeClassOptionsModal(); 
      document.getElementById("joinClassModal").classList.remove("hidden"); 
  }
  function closeJoinClassModal(){ 
      document.getElementById("joinClassModal").classList.add("hidden"); 
  }
</script>