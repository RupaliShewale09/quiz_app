<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
if (!$quiz_id) redirect('quizzes.php');

$quiz_stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz = $quiz_stmt->get_result()->fetch_assoc();
if (!$quiz) redirect('quizzes.php');

$error = $success = '';
$is_new = isset($_GET['new']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question_text'] ?? '');
    $opt_a    = trim($_POST['option_a'] ?? '');
    $opt_b    = trim($_POST['option_b'] ?? '');
    $opt_c    = trim($_POST['option_c'] ?? '');
    $opt_d    = trim($_POST['option_d'] ?? '');
    $correct  = strtoupper(trim($_POST['correct_answer'] ?? ''));
    $marks    = isset($_POST['marks']) ? (int)$_POST['marks'] : 1;

    if ($question === '' || $opt_a === '' || $opt_b === '' || $opt_c === '' || $opt_d === '' || $correct === '') {
        $error = "All fields are required.";
    } elseif (!in_array($correct, ['A','B','C','D'])) {
        $error = "Correct answer must be A, B, C, or D.";
    } else {
        $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issssssi", $quiz_id, $question, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $marks);
        if ($stmt->execute()) {
            header("Location: add_question.php?quiz_id=$quiz_id&success=1");
            exit();
        } else {
            $error = "Failed to add question: " . $conn->error;
        }
    }
}

if (isset($_GET['success'])) {
    $success = "Question added successfully!";
}

// Existing questions for this quiz
$existing = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$existing->bind_param("i", $quiz_id);
$existing->execute();
$questions = $existing->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Question - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span> <span style="font-size:0.7rem; color:var(--accent2); background:rgba(255,101,132,0.15); padding:2px 8px; border-radius:20px;">Admin</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="quizzes.php">Quizzes</a>
        <a href="../logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if ($is_new): ?>
    <div class="alert alert-success" style="margin-bottom:1.5rem;">
        🎉 Quiz "<strong><?= htmlspecialchars($quiz['title']) ?></strong>" created! Now add questions below.
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; align-items:start;">
        <!-- Add Question Form -->
        <div>
            <div class="page-header" style="margin-bottom:1rem;">
                <h2>Add Question</h2>
                <a href="quizzes.php" class="btn btn-outline btn-sm">← Quizzes</a>
            </div>
            <p class="text-muted mb-2" style="font-size:0.85rem;">Quiz: <strong style="color:var(--text)"><?= htmlspecialchars($quiz['title']) ?></strong></p>

            <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label>Question Text *</label>
                        <textarea name="question_text" placeholder="Enter your question here..." rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Option A *</label>
                        <input type="text" name="option_a" placeholder="Option A" required>
                    </div>
                    <div class="form-group">
                        <label>Option B *</label>
                        <input type="text" name="option_b" placeholder="Option B" required>
                    </div>
                    <div class="form-group">
                        <label>Option C *</label>
                        <input type="text" name="option_c" placeholder="Option C" required>
                    </div>
                    <div class="form-group">
                        <label>Option D *</label>
                        <input type="text" name="option_d" placeholder="Option D" required>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label>Correct Answer *</label>
                            <select name="correct_answer" required>
                                <option value="">Select...</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Marks</label>
                            <input type="number" name="marks" min="1" max="10" value="1">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px;">
                        + Add Question
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Questions -->
        <div>
            <div class="page-header" style="margin-bottom:1rem;">
                <h2>Questions (<?= count($questions) ?>)</h2>
            </div>
            <?php if (empty($questions)): ?>
                <div class="card text-center" style="padding:2rem;">
                    <p class="text-muted">No questions yet. Add your first question!</p>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $i => $q): ?>
                <div class="card" style="margin-bottom:1rem; padding:1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;">
                        <div style="flex:1;">
                            <div style="font-size:0.75rem; color:var(--accent); font-weight:700; margin-bottom:4px;">Q<?= $i+1 ?></div>
                            <div style="font-size:0.9rem; font-weight:600; margin-bottom:0.5rem;"><?= htmlspecialchars($q['question_text']) ?></div>
                            <div style="font-size:0.78rem; color:var(--muted);">
                                A: <?= htmlspecialchars($q['option_a']) ?> &nbsp;|&nbsp;
                                B: <?= htmlspecialchars($q['option_b']) ?> &nbsp;|&nbsp;
                                C: <?= htmlspecialchars($q['option_c']) ?> &nbsp;|&nbsp;
                                D: <?= htmlspecialchars($q['option_d']) ?>
                            </div>
                            <div style="margin-top:6px; font-size:0.8rem;">
                                Correct: <strong style="color:var(--success)"><?= $q['correct_answer'] ?></strong>
                                &nbsp;|&nbsp; Marks: <?= $q['marks'] ?>
                            </div>
                        </div>
                        <a href="delete_question.php?id=<?= $q['id'] ?>&quiz_id=<?= $quiz_id ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this question?')"
                           style="flex-shrink:0;">✕</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
