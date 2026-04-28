<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

$filter_quiz = (int)($_GET['quiz_id'] ?? 0);
$quizzes = $conn->query("SELECT id, title FROM quizzes ORDER BY title");

$where = $filter_quiz ? "WHERE a.quiz_id = $filter_quiz" : '';
$attempts = $conn->query("
    SELECT a.*, u.name AS student, u.email, q.title AS quiz_title
    FROM attempts a
    JOIN users u ON a.user_id = u.id
    JOIN quizzes q ON a.quiz_id = q.id
    $where
    ORDER BY a.submitted_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Results - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span> <span style="font-size:0.7rem; color:var(--accent2); background:rgba(255,101,132,0.15); padding:2px 8px; border-radius:20px;">Admin</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="quizzes.php">Quizzes</a>
        <a href="questions.php">Questions</a>
        <a href="results.php" class="active">Results</a>
        <a href="../logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2>All Results</h2>
        <!-- Filter -->
        <form method="GET" style="display:flex; gap:0.5rem;">
            <select name="quiz_id" class="form-group" style="margin:0; padding:8px 12px; background:var(--card); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.85rem;">
                <option value="0">All Quizzes</option>
                <?php while ($qz = $quizzes->fetch_assoc()): ?>
                <option value="<?= $qz['id'] ?>" <?= $filter_quiz == $qz['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($qz['title']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>%</th>
                        <th>Grade</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($r = $attempts->fetch_assoc()):
                        $pct = $r['total_marks'] > 0 ? round(($r['score']/$r['total_marks'])*100) : 0;
                        if ($pct >= 80)     { $grade='A'; $cls='badge-success'; }
                        elseif ($pct >= 60) { $grade='B'; $cls='badge-info'; }
                        elseif ($pct >= 40) { $grade='C'; $cls='badge-warning'; }
                        else                { $grade='D'; $cls='badge-danger'; }
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div><?= htmlspecialchars($r['student']) ?></div>
                            <div style="font-size:0.75rem; color:var(--muted);"><?= htmlspecialchars($r['email']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($r['quiz_title']) ?></td>
                        <td><?= $r['score'] ?>/<?= $r['total_marks'] ?></td>
                        <td><?= $pct ?>%</td>
                        <td><span class="badge <?= $cls ?>"><?= $grade ?></span></td>
                        <td><?= date('d M Y, h:i A', strtotime($r['submitted_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
