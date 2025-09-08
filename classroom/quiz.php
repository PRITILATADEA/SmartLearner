<?php
// Quiz module for SmartLearner
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$class_id = isset($class_id) ? $class_id : (isset($_GET['id']) ? intval($_GET['id']) : 0);

// Get user role in this class
$role = 'Student';
$stmt = $conn->prepare("SELECT role FROM classroom_members WHERE class_id=? AND user_id=?");
$stmt->bind_param("ii", $class_id, $user_id);
$stmt->execute();
$stmt->bind_result($role_db);
if ($stmt->fetch()) $role = $role_db;
$stmt->close();

// Helper: is teacher/TA
$is_admin = ($role === 'Teacher' || $role === 'Teaching Assistant');

// Fetch quizzes for this class
$quizzes = [];
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE class_id=? ORDER BY start_time DESC");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $quizzes[] = $row;
$stmt->close();

// For students: fetch quiz attempts and marks
$quiz_attempts = [];
$quiz_marks = [];
if (!$is_admin) {
    $ids = array_column($quizzes, 'quiz_id');
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        // Attempts
        $stmt = $conn->prepare("SELECT DISTINCT quiz_id FROM quiz_responses WHERE user_id=? AND quiz_id IN ($in)");
        $stmt->bind_param('i' . $types, $user_id, ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $quiz_attempts[$r['quiz_id']] = true;
        $stmt->close();
        // Marks
        $stmt = $conn->prepare("SELECT quiz_id, marks FROM quiz_results WHERE user_id=? AND quiz_id IN ($in)");
        $stmt->bind_param('i' . $types, $user_id, ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $quiz_marks[$r['quiz_id']] = $r['marks'];
        $stmt->close();
    }
}


// Use MySQL server time to avoid timezone mismatch
$now = $conn->query("SELECT NOW() AS now")->fetch_assoc()['now'];

// Helper: determine published state for each quiz
function is_quiz_published($quiz) {
  return $quiz['start_time'] === $quiz['original_start_time'] && $quiz['end_time'] === $quiz['original_end_time'];
}

?>
<div class="mb-6 flex justify-between items-center">
  <h2 class="text-2xl font-bold text-[#1d91e8]">Quizzes</h2>
  <?php if ($is_admin): ?>
    <button onclick="openCreateQuizModal()" class="flex items-center px-4 py-2 bg-[#1d91e8] text-white rounded shadow hover:bg-[#187dc2]">
      <i class="fa-solid fa-plus mr-2"></i> Create Quiz
    </button>
  <?php endif; ?>
</div>

<!-- Quiz List -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<?php foreach ($quizzes as $quiz):
  $is_live = ($quiz['start_time'] <= $now && $quiz['end_time'] > $now);
  $is_published = is_quiz_published($quiz);
  $is_evaluated = $quiz['evaluated'];
  $quiz_id = $quiz['quiz_id'];
?>
  <div class="bg-white rounded-lg shadow p-6 flex flex-col justify-between border border-gray-200">
    <div>
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($quiz['title']) ?></h3>
        <?php if ($is_admin): ?>
          <span class="text-xs px-2 py-1 rounded <?= $is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
            <?= $is_published ? 'Published' : 'Unpublished' ?>
          </span>
        <?php endif; ?>
      </div>
      <div class="text-sm text-gray-600 mb-2">
        <i class="fa-regular fa-clock mr-1"></i>
        <?= date('M d, Y g:i A', strtotime($quiz['start_time'])) ?> - <?= date('M d, Y g:i A', strtotime($quiz['end_time'])) ?>
      </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
      <?php if ($is_admin): ?>
        <button onclick="openEditQuizModal(<?= $quiz_id ?>)" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-sm font-medium hover:bg-yellow-200"><i class="fa fa-edit mr-1"></i>Edit</button>
        <form action="includes/update_quiz.php" method="POST" class="inline">
          <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
          <input type="hidden" name="class_id" value="<?= $class_id ?>">
          <input type="hidden" name="action" value="toggle_publish">
          <button type="submit" class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium hover:bg-blue-200">
            <i class="fa fa-bullhorn mr-1"></i><?= $is_published ? 'Unpublish' : 'Publish' ?>
          </button>
        </form>
        <form action="includes/evaluate_quiz.php" method="POST" class="inline">
          <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
          <input type="hidden" name="class_id" value="<?= $class_id ?>">
          <button type="submit" class="px-3 py-1 bg-green-100 text-green-800 rounded text-sm font-medium hover:bg-green-200"><i class="fa fa-check mr-1"></i>Evaluate</button>
        </form>
        <button onclick="openResultsModal(<?= $quiz_id ?>)" class="px-3 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium hover:bg-purple-200"><i class="fa fa-list mr-1"></i>Results</button>
        <form action="includes/delete_quiz.php" method="POST" class="inline" onsubmit="return confirm('Delete this quiz and all its data?');">
          <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
          <input type="hidden" name="class_id" value="<?= $class_id ?>">
          <button type="submit" class="px-3 py-1 bg-red-100 text-red-800 rounded text-sm font-medium hover:bg-red-200"><i class="fa fa-trash mr-1"></i>Delete</button>
        </form>
      <?php else: ?>
        <?php if ($is_published && $is_live): ?>
          <?php if (empty($quiz_attempts[$quiz_id])): ?>
            <button onclick="openTakeQuizModal(<?= $quiz_id ?>)" class="px-4 py-2 bg-[#1d91e8] text-white rounded font-medium hover:bg-[#187dc2]">Take Quiz</button>
          <?php else: ?>
            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">Attempted</span>
          <?php endif; ?>
        <?php elseif ($is_published && !$is_live): ?>
          <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">Quiz not live</span>
        <?php else: ?>
          <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded text-sm">Not published</span>
        <?php endif; ?>
        <div class="mt-2 text-sm">
          <?php if (isset($quiz_marks[$quiz_id])): ?>
            <span class="font-semibold text-green-700">Your mark: <?= $quiz_marks[$quiz_id] ?></span>
          <?php elseif ($quiz['evaluated']): ?>
            <span class="font-semibold text-green-700">Your mark: 0</span>
          <?php elseif (!empty($quiz_attempts[$quiz_id])): ?>
            <span class="font-semibold text-gray-500">Your mark: 0 (Not evaluated)</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- Create Quiz Modal (hidden by default) -->
<div id="createQuizModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl relative flex flex-col max-h-[90vh]">
    <h2 class="text-2xl font-bold mb-4 text-[#1d91e8] px-8 pt-8">Create Quiz</h2>
    <form id="createQuizForm" action="includes/create_quiz.php" method="POST" class="flex-1 flex flex-col overflow-hidden">
      <input type="hidden" name="class_id" value="<?= $class_id ?>">
      <div class="overflow-y-auto px-8 pb-2" style="max-height:55vh;">
        <div class="mb-4">
          <label class="block text-gray-700">Quiz Title</label>
          <input type="text" name="title" required class="w-full border rounded px-3 py-2 focus:outline-none" style="border-color:#1d91e8;">
        </div>
        <div class="flex space-x-4 mb-4">
          <div class="flex-1">
            <label class="block text-gray-700">Start Time</label>
            <input type="datetime-local" name="start_time" required class="w-full border rounded px-3 py-2">
          </div>
          <div class="flex-1">
            <label class="block text-gray-700">End Time</label>
            <input type="datetime-local" name="end_time" required class="w-full border rounded px-3 py-2">
          </div>
        </div>
        <div id="questionsContainer">
          <!-- Questions will be added here -->
        </div>
        <button type="button" onclick="addQuestion()" class="mt-2 px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium hover:bg-blue-200"><i class="fa fa-plus mr-1"></i>Add Question</button>
      </div>
      <div class="flex justify-end space-x-3 mt-6 px-8 pb-8 bg-white border-t">
        <button type="button" onclick="closeCreateQuizModal()" class="px-4 py-2 border rounded text-gray-600">Cancel</button>
        <button type="submit" class="px-6 py-2 rounded text-white font-medium" style="background-color:#1d91e8;">Create</button>
      </div>
    </form>
    <button onclick="closeCreateQuizModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- Take Quiz Modal (hidden by default) -->
<div id="takeQuizModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-8 relative">
    <h2 class="text-2xl font-bold mb-4 text-[#1d91e8]">Take Quiz</h2>
    <form id="takeQuizForm" action="includes/take_quiz.php" method="POST">
      <input type="hidden" name="quiz_id" id="takeQuizId" value="">
      <input type="hidden" name="class_id" value="<?= $class_id ?>">
      <div id="takeQuizQuestions">
        <!-- Questions will be loaded here via JS -->
      </div>
      <div class="flex justify-end space-x-3 mt-6">
        <button type="button" onclick="closeTakeQuizModal()" class="px-4 py-2 border rounded text-gray-600">Cancel</button>
        <button type="submit" class="px-6 py-2 rounded text-white font-medium" style="background-color:#1d91e8;">Submit</button>
      </div>
    </form>
    <button onclick="closeTakeQuizModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- Results Modal (hidden by default) -->
<div id="resultsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-8 relative">
    <h2 class="text-2xl font-bold mb-4 text-[#1d91e8]">Quiz Results</h2>
    <div id="resultsTable">
      <!-- Results will be loaded here via JS -->
    </div>
    <button onclick="closeResultsModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<!-- Edit Quiz Modal (hidden by default) -->
<div id="editQuizModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl relative flex flex-col max-h-[90vh]">
    <h2 class="text-2xl font-bold mb-4 text-[#1d91e8] px-8 pt-8">Edit Quiz</h2>
    <form id="editQuizForm" action="includes/update_quiz.php" method="POST" class="flex-1 flex flex-col overflow-hidden">
      <input type="hidden" name="quiz_id" id="editQuizId" value="">
      <input type="hidden" name="class_id" value="<?= $class_id ?>">
      <div class="overflow-y-auto px-8 pb-2" style="max-height:55vh;">
        <div class="mb-4">
          <label class="block text-gray-700">Quiz Title</label>
          <input type="text" name="title" id="editQuizTitle" required class="w-full border rounded px-3 py-2 focus:outline-none" style="border-color:#1d91e8;">
        </div>
        <div class="flex space-x-4 mb-4">
          <div class="flex-1">
            <label class="block text-gray-700">Start Time</label>
            <input type="datetime-local" name="start_time" id="editQuizStart" required class="w-full border rounded px-3 py-2">
          </div>
          <div class="flex-1">
            <label class="block text-gray-700">End Time</label>
            <input type="datetime-local" name="end_time" id="editQuizEnd" required class="w-full border rounded px-3 py-2">
          </div>
        </div>
        <div id="editQuestionsContainer">
          <!-- Questions will be loaded here via JS -->
        </div>
        <button type="button" onclick="addEditQuestion()" class="mt-2 px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium hover:bg-blue-200"><i class="fa fa-plus mr-1"></i>Add Question</button>
      </div>
      <div class="flex justify-end space-x-3 mt-6 px-8 pb-8 bg-white border-t">
        <button type="button" onclick="closeEditQuizModal()" class="px-4 py-2 border rounded text-gray-600">Cancel</button>
        <button type="submit" class="px-6 py-2 rounded text-white font-medium" style="background-color:#1d91e8;">Update</button>
      </div>
    </form>
    <button onclick="closeEditQuizModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>
  </div>
</div>

<script>
// Modal controls
function openCreateQuizModal() { document.getElementById('createQuizModal').classList.remove('hidden'); }
function closeCreateQuizModal() { document.getElementById('createQuizModal').classList.add('hidden'); }
function openTakeQuizModal(quizId) {
  document.getElementById('takeQuizId').value = quizId;
  // AJAX load questions for quizId and render in #takeQuizQuestions
  fetch(`includes/get_quiz.php?quiz_id=${quizId}`)
    .then(r => r.json())
    .then(data => {
      const questions = data.questions;
      let html = '';
      questions.forEach((q, idx) => {
        html += `<div class='mb-6 p-4 border rounded bg-gray-50'>`;
        html += `<div class='mb-2 font-semibold'>Q${idx+1}: ${q.question_text}</div>`;
        html += `<div class='grid grid-cols-2 gap-2'>`;
        for (let i = 1; i <= 4; i++) {
          const opt = q.options[i] ? q.options[i] : '';
          html += `<label class='flex items-center'><input type='radio' name='responses[${q.question_id}]' value='${i}' required class='mr-2'>${opt}</label>`;
        }
        html += `</div></div>`;
      });
      document.getElementById('takeQuizQuestions').innerHTML = html;
      document.getElementById('takeQuizModal').classList.remove('hidden');
    });
}
function closeTakeQuizModal() { document.getElementById('takeQuizModal').classList.add('hidden'); }
// Edit Quiz Modal logic
let editQuestionCount = 0;
function openEditQuizModal(quizId) {
  // AJAX fetch quiz data
  fetch(`includes/get_quiz.php?quiz_id=${quizId}`)
    .then(r => r.json())
    .then(data => {
      document.getElementById('editQuizId').value = data.quiz.quiz_id;
      document.getElementById('editQuizTitle').value = data.quiz.title;
      document.getElementById('editQuizStart').value = data.quiz.start_time.replace(' ', 'T');
      document.getElementById('editQuizEnd').value = data.quiz.end_time.replace(' ', 'T');
      // Clear questions
      const cont = document.getElementById('editQuestionsContainer');
      cont.innerHTML = '';
      editQuestionCount = 0;
      data.questions.forEach(q => {
        editQuestionCount++;
        const qDiv = document.createElement('div');
        qDiv.className = 'mb-6 p-4 border rounded bg-gray-50';
        qDiv.innerHTML = `
          <div class=\"flex justify-between items-center mb-2\">
            <label class=\"font-semibold\">Question #${editQuestionCount}</label>
            <button type=\"button\" onclick=\"this.parentElement.parentElement.remove()\" class=\"text-red-500 text-sm\">Remove</button>
          </div>
          <input type=\"text\" name=\"questions[${editQuestionCount}][text]\" required placeholder=\"Question text\" class=\"w-full border rounded px-3 py-2 mb-2\" value=\"${q.question_text.replace(/"/g, '&quot;')}\">
          <div class=\"grid grid-cols-2 gap-2 mb-2\">
            <input type=\"text\" name=\"questions[${editQuestionCount}][options][1]\" required placeholder=\"Option 1\" class=\"border rounded px-2 py-1\" value=\"${q.options[1] || ''}\">
            <input type=\"text\" name=\"questions[${editQuestionCount}][options][2]\" required placeholder=\"Option 2\" class=\"border rounded px-2 py-1\" value=\"${q.options[2] || ''}\">
            <input type=\"text\" name=\"questions[${editQuestionCount}][options][3]\" required placeholder=\"Option 3\" class=\"border rounded px-2 py-1\" value=\"${q.options[3] || ''}\">
            <input type=\"text\" name=\"questions[${editQuestionCount}][options][4]\" required placeholder=\"Option 4\" class=\"border rounded px-2 py-1\" value=\"${q.options[4] || ''}\">
          </div>
          <label class=\"block text-sm\">Correct Option:
            <select name=\"questions[${editQuestionCount}][correct]\" required class=\"ml-2 border rounded px-2 py-1\">
              <option value=\"1\" ${q.correct_option==1?'selected':''}>1</option>
              <option value=\"2\" ${q.correct_option==2?'selected':''}>2</option>
              <option value=\"3\" ${q.correct_option==3?'selected':''}>3</option>
              <option value=\"4\" ${q.correct_option==4?'selected':''}>4</option>
            </select>
          </label>
        `;
        cont.appendChild(qDiv);
      });
      document.getElementById('editQuizModal').classList.remove('hidden');
    });
}
function closeEditQuizModal() { document.getElementById('editQuizModal').classList.add('hidden'); }
function addEditQuestion() {
  editQuestionCount++;
  const qDiv = document.createElement('div');
  qDiv.className = 'mb-6 p-4 border rounded bg-gray-50';
  qDiv.innerHTML = `
    <div class=\"flex justify-between items-center mb-2\">
      <label class=\"font-semibold\">Question #${editQuestionCount}</label>
      <button type=\"button\" onclick=\"this.parentElement.parentElement.remove()\" class=\"text-red-500 text-sm\">Remove</button>
    </div>
    <input type=\"text\" name=\"questions[${editQuestionCount}][text]\" required placeholder=\"Question text\" class=\"w-full border rounded px-3 py-2 mb-2\">
    <div class=\"grid grid-cols-2 gap-2 mb-2\">
      <input type=\"text\" name=\"questions[${editQuestionCount}][options][1]\" required placeholder=\"Option 1\" class=\"border rounded px-2 py-1\">
      <input type=\"text\" name=\"questions[${editQuestionCount}][options][2]\" required placeholder=\"Option 2\" class=\"border rounded px-2 py-1\">
      <input type=\"text\" name=\"questions[${editQuestionCount}][options][3]\" required placeholder=\"Option 3\" class=\"border rounded px-2 py-1\">
      <input type=\"text\" name=\"questions[${editQuestionCount}][options][4]\" required placeholder=\"Option 4\" class=\"border rounded px-2 py-1\">
    </div>
    <label class=\"block text-sm\">Correct Option:
      <select name=\"questions[${editQuestionCount}][correct]\" required class=\"ml-2 border rounded px-2 py-1\">
        <option value=\"1\">1</option>
        <option value=\"2\">2</option>
        <option value=\"3\">3</option>
        <option value=\"4\">4</option>
      </select>
    </label>
  `;
  document.getElementById('editQuestionsContainer').appendChild(qDiv);
}

// Results Modal logic
function openResultsModal(quizId) {
  fetch(`includes/get_quiz_results.php?quiz_id=${quizId}`)
    .then(r => r.json())
    .then(data => {
      let html = '<table class="min-w-full text-sm"><thead><tr><th class="text-left px-2 py-1">Student</th><th class="text-left px-2 py-1">Marks</th></tr></thead><tbody>';
      data.forEach(row => {
        html += `<tr><td class='px-2 py-1'>${row.name}</td><td class='px-2 py-1'>${row.marks}</td></tr>`;
      });
      html += '</tbody></table>';
      document.getElementById('resultsTable').innerHTML = html;
      document.getElementById('resultsModal').classList.remove('hidden');
    });
}
function closeResultsModal() { document.getElementById('resultsModal').classList.add('hidden'); }

// Dynamic add question UI for create quiz
let questionCount = 0;
function addQuestion() {
  questionCount++;
  const qDiv = document.createElement('div');
  qDiv.className = 'mb-6 p-4 border rounded bg-gray-50';
  qDiv.innerHTML = `
    <div class="flex justify-between items-center mb-2">
      <label class="font-semibold">Question #${questionCount}</label>
      <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-500 text-sm">Remove</button>
    </div>
    <input type="text" name="questions[${questionCount}][text]" required placeholder="Question text" class="w-full border rounded px-3 py-2 mb-2">
    <div class="grid grid-cols-2 gap-2 mb-2">
      <input type="text" name="questions[${questionCount}][options][1]" required placeholder="Option 1" class="border rounded px-2 py-1">
      <input type="text" name="questions[${questionCount}][options][2]" required placeholder="Option 2" class="border rounded px-2 py-1">
      <input type="text" name="questions[${questionCount}][options][3]" required placeholder="Option 3" class="border rounded px-2 py-1">
      <input type="text" name="questions[${questionCount}][options][4]" required placeholder="Option 4" class="border rounded px-2 py-1">
    </div>
    <label class="block text-sm">Correct Option:
      <select name="questions[${questionCount}][correct]" required class="ml-2 border rounded px-2 py-1">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
      </select>
    </label>
  `;
  document.getElementById('questionsContainer').appendChild(qDiv);
}
</script>
