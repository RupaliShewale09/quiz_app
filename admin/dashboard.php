<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

$stats = [
    'quizzes'  => $conn->query("SELECT COUNT(*) AS c FROM quizzes")->fetch_assoc()['c'],
    'questions'=> $conn->query("SELECT COUNT(*) AS c FROM questions")->fetch_assoc()['c'],
    'users'    => $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='student'")->fetch_assoc()['c'],
    'attempts' => $conn->query("SELECT COUNT(*) AS c FROM attempts")->fetch_assoc()['c'],
];

$recent = $conn->query("
    SELECT a.score, a.total_marks, a.submitted_at, u.name AS student, q.title
    FROM attempts a
    JOIN users u ON a.user_id = u.id
    JOIN quizzes q ON a.quiz_id = q.id
    ORDER BY a.submitted_at DESC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - QuizApp</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span> <span style="font-size:0.7rem; color:var(--accent2); background:rgba(255,101,132,0.15); padding:2px 8px; border-radius:20px; margin-left:4px;">Admin</span></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="quizzes.php">Quizzes</a>
        <a href="questions.php">Questions</a>
        <a href="results.php">Results</a>
        <a href="../logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2>Dashboard</h2>
        <a href="create_quiz.php" class="btn btn-primary">+ Create Quiz</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-val"><?= $stats['quizzes'] ?></div>
            <div class="stat-lbl">Total Quizzes</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stats['questions'] ?></div>
            <div class="stat-lbl">Total Questions</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stats['users'] ?></div>
            <div class="stat-lbl">Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stats['attempts'] ?></div>
            <div class="stat-lbl">Attempts</div>
        </div>
    </div>

    <!-- Recent Attempts -->
    <div class="card">
        <div class="card-title">Recent Attempts</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>%</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $recent->fetch_assoc()):
                        $pct = $r['total_marks'] > 0 ? round(($r['score']/$r['total_marks'])*100) : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r['student']) ?></td>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= $r['score'] ?>/<?= $r['total_marks'] ?></td>
                        <td>
                            <span class="badge <?= $pct>=60?'badge-success':'badge-danger' ?>"><?= $pct ?>%</span>
                        </td>
                        <td><?= date('d M, h:i A', strtotime($r['submitted_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
