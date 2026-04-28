<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('quizzes.php');

$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
if (!$quiz) redirect('quizzes.php');

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $time_limit  = (int)($_POST['time_limit'] ?? 30);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$title) {
        $error = "Title is required.";
    } else {
        $upd = $conn->prepare("UPDATE quizzes SET title=?, description=?, time_limit=?, is_active=? WHERE id=?");
        $upd->bind_param("ssiii", $title, $description, $time_limit, $is_active, $id);
        if ($upd->execute()) {
            $success = "Quiz updated successfully!";
            $quiz = array_merge($quiz, compact('title','description','time_limit','is_active'));
        } else {
            $error = "Update failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Quiz - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="quizzes.php">Quizzes</a>
        <a href="../logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container-sm">
    <div class="page-header">
        <h2>Edit Quiz</h2>
        <a href="quizzes.php" class="btn btn-outline">← Back</a>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label>Quiz Title *</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($quiz['title']) ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($quiz['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Time Limit (minutes)</label>
                <input type="number" name="time_limit" min="1" max="180" value="<?= $quiz['time_limit'] ?>">
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="is_active" id="is_active" <?= $quiz['is_active'] ? 'checked' : '' ?>
                       style="width:auto; accent-color:var(--accent);">
                <label for="is_active" style="margin:0; color:var(--text); font-size:0.95rem;">Active (visible to students)</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px;">
                Save Changes
            </button>
        </form>
    </div>
</div>

</body>
</html>
