<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
requireAdmin();

$id      = (int)($_GET['id'] ?? 0);
$quiz_id = (int)($_GET['quiz_id'] ?? 0);

if ($id) {
    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

redirect("add_question.php?quiz_id=$quiz_id");
?>
