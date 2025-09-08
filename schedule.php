<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Define valid days
$weekdays = ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
$schedule = array_fill_keys($weekdays, []);

// Fetch active classrooms for this user
$stmt = $conn->prepare("SELECT c.class_id, c.course_name, c.section,
       c.day1, c.day1_start, c.day1_end, c.day1_room,
       c.day2, c.day2_start, c.day2_end, c.day2_room,
       c.lab_day, c.lab_start, c.lab_end, c.lab_room,
       m.role
FROM classrooms c
JOIN classroom_members m ON c.class_id = m.class_id
WHERE m.user_id = ? AND c.status='active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Helper to safely insert into schedule
function addToSchedule(&$schedule, $day, $course, $section, $start, $end, $room, $role) {
    if ($day && isset($schedule[$day])) {
        $schedule[$day][] = [
            'course' => $course,
            'section' => $section,
            'start' => $start,
            'end'   => $end,
            'room'  => $room,
            'role'  => $role
        ];
    }
}

// Populate schedule
while ($row = $result->fetch_assoc()) {
    addToSchedule($schedule, $row['day1'], $row['course_name'], $row['section'], 
                  $row['day1_start'], $row['day1_end'], $row['day1_room'], $row['role']);
    addToSchedule($schedule, $row['day2'], $row['course_name'], $row['section'], 
                  $row['day2_start'], $row['day2_end'], $row['day2_room'], $row['role']);
    if (!empty($row['lab_day'])) {
        addToSchedule($schedule, $row['lab_day'], $row['course_name']." Lab", $row['section'], 
                      $row['lab_start'], $row['lab_end'], $row['lab_room'], $row['role']);
    }
}
$stmt->close();

// Sort each day by start time
foreach ($schedule as &$classes) {
    usort($classes, function($a, $b) {
        return strcmp($a['start'], $b['start']);
    });
}
unset($classes);

// Ensure $maxCols is safe
$maxCols = max(1, max(array_map('count', $schedule)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule - SmartLearner</title>
    <link rel="shortcut icon" href="images/fav.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include "includes/navbar.php"; ?>

<main class="pt-20 px-6">
    <h1 class="text-2xl font-bold mb-6" style="color:#1d91e8;">My Weekly Schedule</h1>

    <div class="overflow-auto">
        <table class="table-auto border-collapse border border-gray-300 w-full bg-white shadow-lg">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 px-4 py-2">Day</th>
                    <?php for ($i=1; $i<=$maxCols; $i++): ?>
                        <th class="border border-gray-300 px-4 py-2">Class <?= $i ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($weekdays as $day): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-semibold"><?= $day ?></td>
                        <?php
                        $classes = $schedule[$day];
                        for ($i=0; $i<$maxCols; $i++):
                            if (isset($classes[$i])):
                                $c = $classes[$i]; ?>
                                <td class="border border-gray-300 px-4 py-2 text-sm align-top">
                                    <div class="font-bold" style="color:#1d91e8;"><?= htmlspecialchars($c['course']) ?>
                                        (<?= htmlspecialchars($c['section']) ?>)</div>
                                    <?php if (!empty($c['start']) && !empty($c['end'])): ?>
                                        <div><?= date("g:i A", strtotime($c['start'])) ?> - <?= date("g:i A", strtotime($c['end'])) ?></div>
                                    <?php endif; ?>
                                    <div>Room: <?= htmlspecialchars($c['room']) ?></div>
                                    <div class="text-gray-600 text-xs">Role: <?= htmlspecialchars($c['role']) ?></div>
                                </td>
                            <?php else: ?>
                                <td class="border border-gray-300 px-4 py-2 text-sm text-gray-400">—</td>
                            <?php endif;
                        endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>