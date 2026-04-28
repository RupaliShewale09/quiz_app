<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
if (!$quiz_id) redirect('index.php');

// Get quiz
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
if (!$quiz) redirect('index.php');

// Get questions
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total = count($questions);

if ($total === 0) {
    redirect('index.php');
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];

    // Create attempt record
    $ins = $conn->prepare("INSERT INTO attempts (user_id, quiz_id, submitted_at) VALUES (?, ?, NOW())");
    $ins->bind_param("ii", $_SESSION['user_id'], $quiz_id);
    $ins->execute();
    $attempt_id = $conn->insert_id;

    $score = 0;
    $total_marks = 0;

    foreach ($questions as $q) {
        $total_marks += $q['marks'];
        $selected = strtoupper($answers[$q['id']] ?? '');
        if (!$selected) continue;

        $is_correct = ($selected === $q['correct_answer']) ? 1 : 0;
        if ($is_correct) $score += $q['marks'];

        $ins2 = $conn->prepare("INSERT INTO answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
        $ins2->bind_param("iisi", $attempt_id, $q['id'], $selected, $is_correct);
        $ins2->execute();
    }

    // Update score
    $upd = $conn->prepare("UPDATE attempts SET score = ?, total_marks = ? WHERE id = ?");
    $upd->bind_param("iii", $score, $total_marks, $attempt_id);
    $upd->execute();

    redirect("result.php?attempt_id=$attempt_id");
}

$time_seconds = $quiz['time_limit'] * 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($quiz['title']) ?> - QuizApp</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">📝 Quiz<span>App</span></div>
    <div class="nav-links">
        <span style="color:var(--muted); font-size:0.9rem;"><?= htmlspecialchars($quiz['title']) ?></span>
    </div>
</nav>

<div class="container-sm">
    <!-- Timer -->
    <div class="timer-bar">
        <div>
            <div style="font-size:0.75rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em;">Time Remaining</div>
            <div class="timer-display" id="timer">--:--</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.75rem; color:var(--muted);">Questions</div>
            <div style="font-family:'Syne',sans-serif; font-weight:700;"><?= $total ?> total</div>
        </div>
    </div>

    <form method="POST" id="quizForm">
        <?php foreach ($questions as $i => $q): ?>
        <div class="question-card" id="q<?= $q['id'] ?>">
            <div class="question-number">Question <?= $i + 1 ?> of <?= $total ?></div>
            <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>
            <div class="options">
                <?php
                $opts = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
                foreach ($opts as $key => $val):
                ?>
                <label class="option-label" id="lbl_<?= $q['id'] ?>_<?= $key ?>">
                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $key ?>"
                           onchange="selectOption(<?= $q['id'] ?>, '<?= $key ?>')">
                    <span class="option-badge"><?= $key ?></span>
                    <?= htmlspecialchars($val) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div style="display:flex; gap:1rem; justify-content:space-between; align-items:center; margin-bottom:2rem;">
            <span class="text-muted" style="font-size:0.85rem;" id="answeredCount">0 / <?= $total ?> answered</span>
            <button type="button" class="btn btn-primary" onclick="confirmSubmit()" style="padding:12px 32px;">
                Submit Quiz ✓
            </button>
        </div>
    </form>
</div>

<script>
// Timer
let timeLeft = <?= $time_seconds ?>;
const timerEl = document.getElementById('timer');

function updateTimer() {
    const m = Math.floor(timeLeft / 60).toString().padStart(2, '0');
    const s = (timeLeft % 60).toString().padStart(2, '0');
    timerEl.textContent = `${m}:${s}`;
    timerEl.className = 'timer-display';
    if (timeLeft <= 60)  timerEl.classList.add('danger');
    else if (timeLeft <= 180) timerEl.classList.add('warning');
    if (timeLeft <= 0) {
        alert('Time is up! Submitting your quiz.');
        document.getElementById('quizForm').submit();
        return;
    }
    timeLeft--;
    setTimeout(updateTimer, 1000);
}
updateTimer();

// Option selection highlight
function selectOption(qid, key) {
    ['A','B','C','D'].forEach(k => {
        const lbl = document.getElementById(`lbl_${qid}_${k}`);
        if (lbl) lbl.classList.remove('selected');
    });
    const chosen = document.getElementById(`lbl_${qid}_${key}`);
    if (chosen) chosen.classList.add('selected');
    updateAnsweredCount();
}

function updateAnsweredCount() {
    const total = <?= $total ?>;
    let answered = 0;
    document.querySelectorAll('input[type="radio"]:checked').forEach(() => answered++);
    // Each question has 4 radios; count unique questions answered
    const names = new Set();
    document.querySelectorAll('input[type="radio"]:checked').forEach(r => names.add(r.name));
    document.getElementById('answeredCount').textContent = `${names.size} / ${total} answered`;
}

function confirmSubmit() {
    const total = <?= $total ?>;
    const names = new Set();
    document.querySelectorAll('input[type="radio"]:checked').forEach(r => names.add(r.name));
    const unanswered = total - names.size;
    if (unanswered > 0) {
        if (!confirm(`You have ${unanswered} unanswered question(s). Submit anyway?`)) return;
    }
    document.getElementById('quizForm').submit();
}
</script>
</body>
</html>
