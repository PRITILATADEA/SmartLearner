<?php
// Toggle: allow students to create full posts
$allow_students_post = true; 

// Fetch posts with users
$stmt = $conn->prepare("SELECT p.*, u.name, u.profile_img 
                        FROM posts p
                        JOIN users u ON p.user_id=u.user_id
                        WHERE p.class_id=?
                        ORDER BY p.created_at DESC");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$posts = $stmt->get_result();
$stmt->close();
?>

<!-- Post Input -->
<?php if ($is_owner || $role == 'Teaching Assistant' || ($allow_students_post && $role == 'Student')): ?>
<div class="bg-white p-4 rounded-lg border border-gray-200 mb-6 flex space-x-3">
  <img src="<?= htmlspecialchars($_SESSION['user_profile_img']) ?>" class="w-8 h-8 rounded-full border">
  <form method="post" class="flex-1">
    <input type="hidden" name="new_post" value="1">
    <textarea name="content" rows="2" required
              placeholder="Announce something to your class..."
              class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-[#1d91e8]"></textarea>
    <div class="flex justify-end mt-2">
      <button type="submit" class="px-4 py-1.5 bg-[#1d91e8] text-white rounded">Post</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Stream Posts -->
<?php if ($posts->num_rows > 0): ?>
  <?php while($p = $posts->fetch_assoc()): ?>
  <div id="post-<?= $p['post_id'] ?>" class="bg-white p-0 rounded-lg border border-gray-300 mb-8 relative">
      <!-- 3-dot menu absolutely positioned at top right of card -->
      <?php if ($is_owner || $role == 'Teaching Assistant' || $p['user_id'] == $user_id): ?>
      <div class="absolute top-4 right-4 z-10">
        <button onclick="toggleMenu('menu-<?= $p['post_id'] ?>')"
                class="text-gray-500 focus:outline-none p-2 rounded-full hover:bg-gray-100 transition">
          <i class="fa-solid fa-ellipsis-vertical text-lg"></i>
        </button>
        <div id="menu-<?= $p['post_id'] ?>" class="hidden absolute right-0 mt-2 bg-white border border-gray-200 rounded w-32 z-50">
          <a href="classroom/edit_post.php?id=<?= $p['post_id'] ?>&class=<?= $class_id ?>" class="block px-4 py-2 text-sm hover:bg-gray-100">Edit</a>
          <form method="post" action="classroom/delete_post.php" onsubmit="return confirm('Are you sure you want to delete this post?');">
            <input type="hidden" name="post_id" value="<?= $p['post_id'] ?>">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">
            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Delete</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
      <!-- Post header -->
      <div class="flex items-center space-x-3 px-5 pt-5 pb-2">
        <img src="<?= htmlspecialchars($p['profile_img']) ?>" class="w-9 h-9 rounded-full border border-gray-300" alt="User">
        <div>
          <p class="font-semibold text-base text-gray-900 mb-0.5"><?= htmlspecialchars($p['name']) ?></p>
          <p class="text-xs text-gray-500 mb-0.5">
            <?= date("M j, Y", strtotime($p['created_at'])) ?>
            <?php 
            $updated_at = $p['updated_at'] ?? null;
            if ($updated_at && $updated_at !== $p['created_at']): ?>
              (Edited <?= date("M j, Y", strtotime($updated_at)) ?>)
            <?php endif; ?>
          </p>
        </div>
      </div>
      <div class="px-5 pb-4 pt-1 text-gray-900 text-base leading-relaxed">
        <?= nl2br(htmlspecialchars($p['content'])) ?>
      </div>
      <div class="border-t border-gray-200"></div>
      <!-- Comments summary -->
      <?php
      $cid = $p['post_id'];
      $cstmt = $conn->prepare("SELECT c.*, u.name, u.profile_img, c.created_at as comment_created FROM comments c JOIN users u ON c.user_id=u.user_id WHERE c.post_id=? ORDER BY c.created_at ASC");
      $cstmt->bind_param("i", $cid);
      $cstmt->execute();
      $comments = $cstmt->get_result();
      $comment_count = $comments->num_rows;
      ?>
      <div class="px-5 py-2 flex items-center space-x-2 text-sm text-[#1d91e8] font-medium cursor-pointer">
        <i class="fa-solid fa-users"></i>
        <span><?= $comment_count ?> class comment<?= $comment_count != 1 ? 's' : '' ?></span>
      </div>
      <!-- Comments list -->
      <div class="px-5 pb-4">
        <?php while($com = $comments->fetch_assoc()): ?>
          <div class="flex items-start space-x-3 py-2 border-b border-gray-100 last:border-b-0">
            <img src="<?= htmlspecialchars($com['profile_img']) ?>" class="w-8 h-8 rounded-full border border-gray-200 mt-1">
            <div class="flex-1">
              <div class="flex items-center space-x-2">
                <span class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($com['name']) ?></span>
                <span class="text-xs text-gray-500"><?= date("M j, Y", strtotime($com['comment_created'])) ?></span>
              </div>
              <div class="text-gray-800 text-sm leading-snug mt-0.5">
                <?= nl2br(htmlspecialchars($com['comment_text'])) ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
      <?php $cstmt->close(); ?>
      <!-- Comment Form -->
      <form method="post" class="px-5 pb-5 flex space-x-2">
        <input type="hidden" name="new_comment" value="1">
        <input type="hidden" name="post_id" value="<?= $p['post_id'] ?>">
        <input name="comment_text" placeholder="Add class comment..." required
               class="flex-1 border border-gray-200 rounded-2xl px-4 py-2 focus:ring-2 focus:ring-[#1d91e8] bg-white text-sm">
        <button type="submit" class="px-4 py-2 bg-[#1d91e8] text-white rounded-2xl text-sm font-semibold"><i class="fa-solid fa-paper-plane"></i></button>
      </form>
    </div>
  <?php endwhile; ?>
<?php else: ?>
  <p class="text-gray-600">No announcements yet.</p>
<?php endif; ?>

<script>
function toggleMenu(id){
  document.querySelectorAll("[id^='menu-']").forEach(el=>{ if(el.id!==id) el.classList.add('hidden'); });
  document.getElementById(id).classList.toggle('hidden');
}
</script>