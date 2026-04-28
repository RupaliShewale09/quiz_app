<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$stmt = $conn->prepare("
    SELECT a.*, q.title, q.time_limit
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE a.user_id = ?
    ORDER BY a.submitted_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$attempts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Results - QuizApp</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span></div>
    <div class="nav-links">
        <a href="index.php">Quizzes</a>
        <a href="my_results.php" class="active">My Results</a>
        <a href="logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2>My Results</h2>
    </div>

    <?php if (empty($attempts)): ?>
        <div class="card text-center" style="padding:3rem;">
            <p style="font-size:2rem;">📊</p>
            <p class="text-muted mt-1">You haven't attempted any quiz yet.</p>
            <a href="index.php" class="btn btn-primary mt-2">Browse Quizzes</a>
        </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $i => $a):
                        $pct = $a['total_marks'] > 0 ? round(($a['score']/$a['total_marks'])*100) : 0;
                        if ($pct >= 80)     { $grade='A'; $class='badge-success'; }
                        elseif ($pct >= 60) { $grade='B'; $class='badge-info'; }
                        elseif ($pct >= 40) { $grade='C'; $class='badge-warning'; }
                        else                { $grade='D'; $class='badge-danger'; }
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($a['title']) ?></td>
                        <td><?= $a['score'] ?> / <?= $a['total_marks'] ?></td>
                        <td><?= $pct ?>%</td>
                        <td><span class="badge <?= $class ?>"><?= $grade ?></span></td>
                        <td><?= date('d M Y, h:i A', strtotime($a['submitted_at'])) ?></td>
                        <td>
                            <a href="result.php?attempt_id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
