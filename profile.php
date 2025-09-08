<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch current user info from DB
$stmt = $conn->prepare("SELECT name, email, phone, gender, profile_img FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($db_name, $db_email, $db_phone, $db_gender, $db_profile_img);
$stmt->fetch();
$stmt->close();

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name   = htmlspecialchars(trim($_POST['name']));
    $phone  = htmlspecialchars(trim($_POST['phone']));
    $gender = $_POST['gender'];
    $password = $_POST['password'] ?? "";
    $profile_img = $db_profile_img;

    // File upload
    if (!empty($_FILES['profile_img']['name'])) {
        $target_dir = "images/uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $ext = strtolower(pathinfo($_FILES["profile_img"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed)) {
            $new_name = "user_" . $user_id . "." . $ext;
            $target_file = $target_dir . $new_name;
            if (move_uploaded_file($_FILES["profile_img"]["tmp_name"], $target_file)) {
                $profile_img = $target_file;
            }
        } else {
            $error = "Only JPG, PNG, or GIF images allowed.";
        }
    }

    // Update DB
    if (empty($error)) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, gender=?, profile_img=?, password=? WHERE user_id=?");
            $stmt->bind_param("sssssi", $name, $phone, $gender, $profile_img, $hashed, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, gender=?, profile_img=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $name, $phone, $gender, $profile_img, $user_id);
        }
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            $_SESSION['user_name'] = $name;
            $_SESSION['user_profile_img'] = $profile_img;
            $db_name = $name; $db_phone = $phone; $db_gender = $gender; $db_profile_img = $profile_img;
        } else {
            $error = "Error updating profile. Try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - SmartLearner</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="shortcut icon" href="images/fav.ico" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13ZdX7+7Sk6XhU9Zg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
      .theme-color { color: #1d91e8; }
      .theme-btn {
          background-color: #1d91e8;
          transition: all .2s ease;
      }
      .theme-btn:hover {
          background-color: #ff4072; /* hover effect using your pink */
      }
  </style>
</head>
<body class="bg-gray-100">

<?php include "includes/navbar.php"; ?>

<main class="pt-20 flex justify-center px-4">
    <div class="bg-white shadow-lg rounded-lg w-full max-w-3xl p-8 mt-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center theme-color">
            <i class="fa-solid fa-user-circle mr-2"></i> Your Profile
        </h2>

        <?php if($success): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= $error ?></div>
        <?php endif; ?>

        <form action="profile.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            <!-- Profile Picture -->
            <div class="flex items-center space-x-6">
                <img src="<?= htmlspecialchars($db_profile_img) ?>" alt="Profile" class="w-24 h-24 rounded-full border">
                <label class="block">
                    <span class="text-gray-700">Change Profile Picture</span>
                    <input type="file" name="profile_img" accept="image/*"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0 file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                </label>
            </div>

            <!-- Name -->
            <div>
                <label class="block text-gray-700">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($db_name) ?>"
                       class="w-full px-4 py-2 mt-1 border rounded-lg focus:outline-none focus:ring-2"
                       style="border-color:#1d91e8; focus:ring-color:#1d91e8;">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-gray-700">Email (cannot change)</label>
                <input type="email" value="<?= htmlspecialchars($db_email) ?>" disabled
                       class="w-full px-4 py-2 mt-1 border rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
            </div>

            <!-- Phone -->
            <div>
                <label class="block text-gray-700">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($db_phone) ?>"
                       class="w-full px-4 py-2 mt-1 border rounded-lg focus:outline-none focus:ring-2"
                       style="border-color:#1d91e8; focus:ring-color:#1d91e8;">
            </div>

            <!-- Gender -->
            <div>
                <label class="block text-gray-700">Gender</label>
                <select name="gender"
                        class="w-full px-4 py-2 mt-1 border rounded-lg focus:outline-none focus:ring-2"
                        style="border-color:#1d91e8; focus:ring-color:#1d91e8;">
                    <option value="">Select</option>
                    <option value="Male" <?= $db_gender=='Male'?'selected':'' ?>>Male</option>
                    <option value="Female" <?= $db_gender=='Female'?'selected':'' ?>>Female</option>
                    <option value="Other" <?= $db_gender=='Other'?'selected':'' ?>>Other</option>
                </select>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-gray-700">Change Password</label>
                <input type="password" name="password" placeholder="Enter new password (leave blank to keep current)"
                       class="w-full px-4 py-2 mt-1 border rounded-lg focus:outline-none focus:ring-2"
                       style="border-color:#1d91e8; focus:ring-color:#1d91e8;">
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="theme-btn text-white px-6 py-2 rounded-lg shadow font-medium">
                    <i class="fa-solid fa-save mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</main>

</body>
</html>