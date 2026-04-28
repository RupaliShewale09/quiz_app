<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

// Toggle active/inactive
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE quizzes SET is_active = NOT is_active WHERE id = $id");
    redirect('quizzes.php');
}

// Delete quiz
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM quizzes WHERE id = $id");
    redirect('quizzes.php');
}

$quizzes = $conn->query("
    SELECT q.*, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS q_count,
           (SELECT COUNT(*) FROM attempts WHERE quiz_id = q.id) AS attempts_count
    FROM quizzes q
    ORDER BY q.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Quizzes - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span> <span style="font-size:0.7rem; color:var(--accent2); background:rgba(255,101,132,0.15); padding:2px 8px; border-radius:20px;">Admin</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="quizzes.php" class="active">Quizzes</a>
        <a href="questions.php">Questions</a>
        <a href="results.php">Results</a>
        <a href="../logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2>Manage Quizzes</h2>
        <a href="create_quiz.php" class="btn btn-primary">+ Create Quiz</a>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Time Limit</th>
                        <th>Questions</th>
                        <th>Attempts</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($q = $quizzes->fetch_assoc()): ?>
                    <tr>
                        <td><?= $q['id'] ?></td>
                        <td><strong><?= htmlspecialchars($q['title']) ?></strong></td>
                        <td><?= $q['time_limit'] ?> mins</td>
                        <td><?= $q['q_count'] ?></td>
                        <td><?= $q['attempts_count'] ?></td>
                        <td>
                            <span class="badge <?= $q['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $q['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-2 items-center">
                                <a href="add_question.php?quiz_id=<?= $q['id'] ?>" class="btn btn-outline btn-sm">+ Question</a>
                                <a href="edit_quiz.php?id=<?= $q['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <a href="?toggle=<?= $q['id'] ?>" class="btn btn-sm" 
                                   style="background:var(--warning);color:#000;">
                                   <?= $q['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </a>
                                <a href="?delete=<?= $q['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this quiz and all its questions?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
