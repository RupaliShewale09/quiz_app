<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $time_limit  = (int)($_POST['time_limit'] ?? 30);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$title) {
        $error = "Quiz title is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO quizzes (title, description, time_limit, is_active, created_by) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssiii", $title, $description, $time_limit, $is_active, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            redirect("add_question.php?quiz_id=$new_id&new=1");
        } else {
            $error = "Failed to create quiz.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Quiz - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span> <span style="font-size:0.7rem; color:var(--accent2); background:rgba(255,101,132,0.15); padding:2px 8px; border-radius:20px;">Admin</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="quizzes.php">Quizzes</a>
        <a href="questions.php">Questions</a>
        <a href="../logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container-sm">
    <div class="page-header">
        <h2>Create New Quiz</h2>
        <a href="quizzes.php" class="btn btn-outline">← Back</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label>Quiz Title *</label>
                <input type="text" name="title" placeholder="e.g. PHP Basics Quiz" required
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Brief description about this quiz..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Time Limit (minutes)</label>
                <input type="number" name="time_limit" min="1" max="180" value="<?= $_POST['time_limit'] ?? 30 ?>">
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="is_active" id="is_active" checked style="width:auto; accent-color:var(--accent);">
                <label for="is_active" style="margin:0; color:var(--text); font-size:0.95rem;">Make this quiz active (visible to students)</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px;">
                Create Quiz & Add Questions →
            </button>
        </form>
    </div>
</div>

</body>
</html>
