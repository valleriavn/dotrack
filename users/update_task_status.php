<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = $_POST['task_id'] ?? null;
$task_type = $_POST['task_type'] ?? null;
$status = $_POST['status'] ?? null;

if (!$task_id || !$task_type || !$status || !in_array($status, ['Pending', 'Done'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    if ($task_type === 'personal') {
        $stmt = $conn->prepare("UPDATE personal_todo SET status = ? WHERE todo_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $status, $task_id, $user_id);
    } elseif ($task_type === 'collaborative') {
        // Verify user has access to the collaborative task
        $stmt = $conn->prepare("UPDATE collaborate_task t
                                JOIN collaborate_project p ON t.colproj_id = p.colproj_id
                                JOIN collaborate_teammem tm ON p.colgroup_id = tm.colgroup_id
                                SET t.status = ?
                                WHERE t.coltask_id = ? AND tm.user_id = ?");
        $stmt->bind_param("sii", $status, $task_id, $user_id);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid task type']);
        exit;
    }

    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No task updated']);
    }
} catch (Exception $e) {
    error_log("Error updating task status: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>