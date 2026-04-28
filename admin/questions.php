<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

$filter_quiz = (int)($_GET['quiz_id'] ?? 0);
$quizzes = $conn->query("SELECT id, title FROM quizzes ORDER BY title");

$where = $filter_quiz ? "AND q.quiz_id = $filter_quiz" : '';
$questions = $conn->query("
    SELECT q.*, qz.title AS quiz_title
    FROM questions q
    JOIN quizzes qz ON q.quiz_id = qz.id
    WHERE 1=1 $where
    ORDER BY qz.title, q.id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Questions - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span> <span style="font-size:0.7rem; color:var(--accent2); background:rgba(255,101,132,0.15); padding:2px 8px; border-radius:20px;">Admin</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="quizzes.php">Quizzes</a>
        <a href="questions.php" class="active">Questions</a>
        <a href="results.php">Results</a>
        <a href="../logout.php" style="color:var(--danger)">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2>All Questions</h2>
        <form method="GET" style="display:flex; gap:0.5rem;">
            <select name="quiz_id" style="padding:8px 12px; background:var(--card); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.85rem;">
                <option value="0">All Quizzes</option>
                <?php while ($qz = $quizzes->fetch_assoc()): ?>
                <option value="<?= $qz['id'] ?>" <?= $filter_quiz == $qz['id'] ? 'selected':'' ?>>
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
                        <th>Quiz</th>
                        <th>Question</th>
                        <th>Correct Answer</th>
                        <th>Marks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($q = $questions->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($q['quiz_title']) ?></span></td>
                        <td style="max-width:300px;"><?= htmlspecialchars(substr($q['question_text'], 0, 80)) ?>...</td>
                        <td>
                            <strong style="color:var(--success);"><?= $q['correct_answer'] ?></strong> —
                            <?php
                            $map = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d']];
                            echo htmlspecialchars(substr($map[$q['correct_answer']], 0, 40));
                            ?>
                        </td>
                        <td><?= $q['marks'] ?></td>
                        <td>
                            <a href="delete_question.php?id=<?= $q['id'] ?>&quiz_id=<?= $q['quiz_id'] ?>&back=questions"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this question?')">Delete</a>
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
