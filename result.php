<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

// Support both attempt_id and quiz_id (show latest attempt for quiz)
$attempt_id = (int)($_GET['attempt_id'] ?? 0);
$quiz_id    = (int)($_GET['quiz_id'] ?? 0);

if (!$attempt_id && $quiz_id) {
    $stmt = $conn->prepare("SELECT id FROM attempts WHERE user_id = ? AND quiz_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $stmt->bind_param("ii", $_SESSION['user_id'], $quiz_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $attempt_id = $row['id'] ?? 0;
}

if (!$attempt_id) redirect('index.php');

// Get attempt details
$stmt = $conn->prepare("
    SELECT a.*, q.title, q.description, q.id AS quiz_id
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->bind_param("ii", $attempt_id, $_SESSION['user_id']);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();
if (!$attempt) redirect('index.php');

$pct = $attempt['total_marks'] > 0 ? round(($attempt['score'] / $attempt['total_marks']) * 100) : 0;

if ($pct >= 80)       { $grade = 'A'; $grade_color = 'var(--success)'; $msg = 'Excellent! 🎉'; }
elseif ($pct >= 60)   { $grade = 'B'; $grade_color = 'var(--accent)';  $msg = 'Good Job! 👍'; }
elseif ($pct >= 40)   { $grade = 'C'; $grade_color = 'var(--warning)'; $msg = 'Keep Practicing! 📚'; }
else                  { $grade = 'D'; $grade_color = 'var(--danger)';  $msg = 'Need Improvement! 💪'; }

// Get all questions with user answers
$stmt2 = $conn->prepare("
    SELECT q.*, ans.selected_answer, ans.is_correct
    FROM questions q
    LEFT JOIN answers ans ON ans.question_id = q.id AND ans.attempt_id = ?
    WHERE q.quiz_id = ?
    ORDER BY q.id
");
$stmt2->bind_param("ii", $attempt_id, $attempt['quiz_id']);
$stmt2->execute();
$questions = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$correct = array_sum(array_column($questions, 'is_correct'));
$wrong   = array_filter($questions, fn($q) => $q['selected_answer'] && !$q['is_correct']);
$skipped = array_filter($questions, fn($q) => !$q['selected_answer']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Result - <?= htmlspecialchars($attempt['title']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span></div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="my_results.php">My Results</a>
        <a href="logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container-sm">
    <!-- Score Card -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="result-box">
            <div class="score-circle" style="border-color:<?= $grade_color ?>;">
                <div class="score-num" style="color:<?= $grade_color ?>;"><?= $pct ?>%</div>
                <div class="score-label">Score</div>
            </div>
            <h2 style="font-size:1.6rem; margin-bottom:0.4rem;"><?= $msg ?></h2>
            <p class="text-muted"><?= htmlspecialchars($attempt['title']) ?></p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-val"><?= $attempt['score'] ?>/<?= $attempt['total_marks'] ?></div>
            <div class="stat-lbl">Marks Obtained</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:var(--success);"><?= $correct ?></div>
            <div class="stat-lbl">Correct</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:var(--danger);"><?= count($wrong) ?></div>
            <div class="stat-lbl">Wrong</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:var(--warning);"><?= count($skipped) ?></div>
            <div class="stat-lbl">Skipped</div>
        </div>
    </div>

    <!-- Answer Review -->
    <h3 style="font-family:'Syne',sans-serif; margin-bottom:1rem;">Answer Review</h3>

    <?php foreach ($questions as $i => $q):
        $selected = $q['selected_answer'] ?? null;
        $correct_ans = $q['correct_answer'];
        $opts = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
    ?>
    <div class="question-card">
        <div class="question-number">
            Question <?= $i + 1 ?>
            <?php if (!$selected): ?>
                <span class="badge badge-warning" style="margin-left:8px;">Skipped</span>
            <?php elseif ($q['is_correct']): ?>
                <span class="badge badge-success" style="margin-left:8px;">✓ Correct</span>
            <?php else: ?>
                <span class="badge badge-danger" style="margin-left:8px;">✗ Wrong</span>
            <?php endif; ?>
        </div>
        <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>
        <div class="options">
            <?php foreach ($opts as $key => $val): ?>
            <div class="option-label
                <?php
                if ($key === $correct_ans) echo ' correct-answer';
                elseif ($selected === $key && !$q['is_correct']) echo ' wrong-answer';
                ?>">
                <span class="option-badge"><?= $key ?></span>
                <?= htmlspecialchars($val) ?>
                <?php if ($key === $correct_ans): ?>
                    <span style="margin-left:auto; color:var(--success); font-size:0.8rem;">✓ Correct</span>
                <?php elseif ($selected === $key && !$q['is_correct']): ?>
                    <span style="margin-left:auto; color:var(--danger); font-size:0.8rem;">Your Answer</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div style="display:flex; gap:1rem; margin-bottom:3rem;">
        <a href="attempt.php?quiz_id=<?= $attempt['quiz_id'] ?>" class="btn btn-primary">Retry Quiz</a>
        <a href="index.php" class="btn btn-outline">Back to Home</a>
    </div>
</div>

</body>
</html>
