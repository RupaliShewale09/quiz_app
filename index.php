<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

// Fetch active quizzes
$quizzes = $conn->query("
    SELECT q.*, 
           u.name AS creator,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS q_count,
           (SELECT COUNT(*) FROM attempts WHERE quiz_id = q.id AND user_id = {$_SESSION['user_id']}) AS attempted
    FROM quizzes q
    JOIN users u ON q.created_by = u.id
    WHERE q.is_active = 1
    ORDER BY q.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuizApp - Home</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span></div>
    <div class="nav-links">
        <a href="index.php" class="active">Quizzes</a>
        <a href="my_results.php">My Results</a>
        <?php if (isAdmin()): ?>
            <a href="admin/dashboard.php">Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h2>Available Quizzes</h2>
            <p class="text-muted mt-1" style="font-size:0.9rem">Hello, <?= htmlspecialchars($_SESSION['name']) ?>! Pick a quiz to begin.</p>
        </div>
    </div>

    <div class="quiz-grid">
        <?php while ($quiz = $quizzes->fetch_assoc()): ?>
        <div class="quiz-card">
            <h3><?= htmlspecialchars($quiz['title']) ?></h3>
            <p><?= htmlspecialchars($quiz['description'] ?: 'No description provided.') ?></p>
            <div class="quiz-meta">
                <span>❓ <?= $quiz['q_count'] ?> Questions</span>
                <span>⏱ <?= $quiz['time_limit'] ?> mins</span>
            </div>
            <?php if ($quiz['attempted'] > 0): ?>
                <span class="badge badge-success" style="margin-bottom:0.8rem;">✔ Attempted</span><br>
                <a href="result.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-outline btn-sm">View Result</a>
                <a href="attempt.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-primary btn-sm">Retry</a>
            <?php else: ?>
                <a href="attempt.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-primary">Start Quiz →</a>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($quizzes->num_rows === 0): ?>
        <div class="card text-center" style="padding:3rem;">
            <p style="font-size:2rem;">📭</p>
            <p class="text-muted mt-1">No quizzes available right now. Check back later!</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
