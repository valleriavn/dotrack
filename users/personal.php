<?php
session_start();
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /dotrack/auth/loginandsignup.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';
$successMessage = '';
$errorMessage = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getTodos($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT * FROM personal_todo WHERE user_id = ? AND deleted_at IS NULL ORDER BY 
        CASE WHEN status = 'Done' THEN 1 ELSE 0 END,
        CASE priority 
            WHEN 'Urgent' THEN 0 
            WHEN 'High' THEN 1 
            WHEN 'Medium' THEN 2 
            WHEN 'Low' THEN 3 
            ELSE 4 
        END");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getProjects($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT * FROM personal_project WHERE user_id = ? AND deleted_at IS NULL ORDER BY 
        CASE WHEN status = 'Completed' THEN 1 ELSE 0 END");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getHabits($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT * FROM personal_habit WHERE user_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $habits = ['Monday' => [], 'Tuesday' => [], 'Wednesday' => [], 'Thursday' => [], 'Friday' => [], 'Saturday' => [], 'Sunday' => []];

    while ($habit = $result->fetch_assoc()) {
        $habits[$habit['day']][] = $habit;
    }
    return $habits;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header("Location: /dotrack/users/personal.php?tab=$tab");
        exit;
    }

    if (isset($_POST['add_todo'])) {
        $task = trim($_POST['todo_task']);
        $status = $_POST['todo_status'] ?? 'Not Started';
        $due_date = $_POST['todo_due_date'] ?? null;
        $priority = $_POST['todo_priority'] ?? 'Medium';
        $category = trim($_POST['todo_category'] ?? '');

        if ($task !== '') {
            $stmt = $conn->prepare("INSERT INTO personal_todo (user_id, todo_name, status, due_date, priority, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $user_id, $task, $status, $due_date, $priority, $category);
            $stmt->execute();
            $_SESSION['success'] = 'Todo added successfully';
        } else {
            $_SESSION['error'] = 'Task name cannot be empty';
        }
        header("Location: /dotrack/users/personal.php?tab=todo");
        exit;
    }

    if (isset($_POST['mark_done'])) {
        $todo_id = (int)$_POST['done_index'];
        $status = isset($_POST['done_checkbox']) && $_POST['done_checkbox'] === 'on' ? 'Done' : 'Not Started';

        $stmt = $conn->prepare("UPDATE personal_todo SET status = ? WHERE todo_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $status, $todo_id, $user_id);
        $stmt->execute();
        $_SESSION['success'] = 'Todo status updated';
        header("Location: /dotrack/users/personal.php?tab=todo");
        exit;
    }

    if (isset($_POST['edit_todo_save'])) {
        $todo_id = (int)$_POST['edit_index'];
        $task = trim($_POST['todo_task']);
        $status = $_POST['todo_status'] ?? 'Not Started';
        $due_date = $_POST['todo_due_date'] ?? null;
        $priority = $_POST['todo_priority'] ?? 'Medium';
        $category = trim($_POST['todo_category'] ?? '');

        if ($task !== '') {
            $stmt = $conn->prepare("UPDATE personal_todo SET todo_name = ?, status = ?, due_date = ?, priority = ?, category = ? WHERE todo_id = ? AND user_id = ?");
            $stmt->bind_param("sssssii", $task, $status, $due_date, $priority, $category, $todo_id, $user_id);
            $stmt->execute();
            $_SESSION['success'] = 'Todo updated successfully';
        } else {
            $_SESSION['error'] = 'Task name cannot be empty';
        }
        header("Location: /dotrack/users/personal.php?tab=todo");
        exit;
    }

    if (isset($_POST['delete_todo'])) {
        $todo_id = (int)$_POST['delete_index'];
        $stmt = $conn->prepare("UPDATE personal_todo SET deleted_at = NOW() WHERE todo_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $todo_id, $user_id);
        $stmt->execute();
        $_SESSION['success'] = 'Todo deleted successfully';
        header("Location: /dotrack/users/personal.php?tab=todo");
        exit;
    }

    if (isset($_POST['add_project'])) {
        $name = trim($_POST['project_name']);
        $details = trim($_POST['project_details']);
        $priority = $_POST['project_priority'] ?? 'Medium';
        $status = $_POST['project_status'] ?? 'Not Started';
        $start = $_POST['project_start'] ?? null;
        $due = $_POST['project_due'] ?? null;

        $subtask_count = (int)$_POST['subtask_count'];
        $subtasks = [];
        for ($i = 0; $i < $subtask_count; $i++) {
            $subtask_name = trim($_POST['subtask_name_' . $i] ?? '');
            if (!empty($subtask_name)) {
                $done = isset($_POST['subtask_done_' . $i]) ? '[DONE] ' : '';
                $subtasks[] = $done . $subtask_name;
            }
        }
        $subtasks_text = implode("\n", $subtasks);

        if ($name && $details && $start && $due) {
            $stmt = $conn->prepare("INSERT INTO personal_project (user_id, project_name, persubtask_name, description, priority, status, start_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $user_id, $name, $subtasks_text, $details, $priority, $status, $start, $due);
            $stmt->execute();
            $_SESSION['success'] = 'Project added successfully';
        } else {
            $_SESSION['error'] = 'All required fields must be filled';
        }
        header("Location: /dotrack/users/personal.php?tab=project");
        exit;
    }

    if (isset($_POST['edit_project_save'])) {
        $project_id = (int)$_POST['edit_project_index'];
        $name = trim($_POST['project_name']);
        $details = trim($_POST['project_details']);
        $priority = $_POST['project_priority'] ?? 'Medium';
        $status = $_POST['project_status'] ?? 'Not Started';
        $start = $_POST['project_start'] ?? null;
        $due = $_POST['project_due'] ?? null;

        $subtask_count = (int)$_POST['edit_subtask_count'];
        $subtasks = [];
        for ($i = 0; $i < $subtask_count; $i++) {
            $subtask_name = trim($_POST['subtask_name_' . $i] ?? '');
            if (!empty($subtask_name)) {
                $done = isset($_POST['subtask_done_' . $i]) ? '[DONE] ' : '';
                $subtasks[] = $done . $subtask_name;
            }
        }
        $subtasks_text = implode("\n", $subtasks);

        if ($name && $details && $start && $due) {
            $stmt = $conn->prepare("UPDATE personal_project SET project_name = ?, description = ?, priority = ?, status = ?, start_date = ?, due_date = ?, persubtask_name = ? WHERE project_id = ? AND user_id = ?");
            $stmt->bind_param("sssssssii", $name, $details, $priority, $status, $start, $due, $subtasks_text, $project_id, $user_id);
            $stmt->execute();
            $_SESSION['success'] = 'Project updated successfully';
        } else {
            $_SESSION['error'] = 'All required fields must be filled';
        }
        header("Location: /dotrack/users/personal.php?tab=project");
        exit;
    }

    if (isset($_POST['delete_project'])) {
        $project_id = (int)$_POST['delete_index'];
        $stmt = $conn->prepare("UPDATE personal_project SET deleted_at = NOW() WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $project_id, $user_id);
        $stmt->execute();
        $_SESSION['success'] = 'Project deleted successfully';
        header("Location: /dotrack/users/personal.php?tab=project");
        exit;
    }

    if (isset($_POST['toggle_project_done'])) {
        $project_id = (int)$_POST['project_index'];
        $status = $_POST['project_status'] === 'Completed' ? 'Not Started' : 'Completed';
        $stmt = $conn->prepare("UPDATE personal_project SET status = ? WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $status, $project_id, $user_id);
        $stmt->execute();
        $_SESSION['success'] = 'Project status updated';
        header("Location: /dotrack/users/personal.php?tab=project");
        exit;
    }

    if (isset($_POST['toggle_subtask'])) {
        $project_id = (int)$_POST['project_index'];
        $subtask_index = (int)$_POST['subtask_index'];

        $stmt = $conn->prepare("SELECT persubtask_name FROM personal_project WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $project_id, $user_id);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();

        if ($project) {
            $subtasks = explode("\n", $project['persubtask_name']);
            if (isset($subtasks[$subtask_index])) {
                if (strpos($subtasks[$subtask_index], '[DONE] ') === 0) {
                    $subtasks[$subtask_index] = substr($subtasks[$subtask_index], 7);
                } else {
                    $subtasks[$subtask_index] = '[DONE] ' . $subtasks[$subtask_index];
                }
                $new_subtasks = implode("\n", $subtasks);
                $update_stmt = $conn->prepare("UPDATE personal_project SET persubtask_name = ? WHERE project_id = ? AND user_id = ?");
                $update_stmt->bind_param("sii", $new_subtasks, $project_id, $user_id);
                $update_stmt->execute();
                $_SESSION['success'] = "Subtask updated successfully";
            }
        }
        header("Location: /dotrack/users/personal.php?tab=project");
        exit;
    }

    if (isset($_POST['add_habit'])) {
        $day = $_POST['habit_day'] ?? '';
        $task = trim($_POST['habit_task']);

        if ($day && $task) {
            $stmt = $conn->prepare("INSERT INTO personal_habit (user_id, habit_name, day) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $task, $day);
            $stmt->execute();
            $_SESSION['success'] = 'Habit added successfully';
        } else {
            $_SESSION['error'] = 'All fields are required';
        }
        header("Location: /dotrack/users/personal.php?tab=habit");
        exit;
    }

    if (isset($_POST['edit_habit_save'])) {
        $habit_id = (int)$_POST['habit_index'];
        $new_task = trim($_POST['habit_task_edit']);
        $day = trim($_POST['habit_day']);

        if ($new_task && $day) {
            $stmt = $conn->prepare("UPDATE personal_habit SET habit_name = ?, day = ? WHERE habit_id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $new_task, $day, $habit_id, $user_id);
            $stmt->execute();
            $_SESSION['success'] = 'Habit updated successfully';
        } else {
            $_SESSION['error'] = 'All fields are required';
        }
        header("Location: /dotrack/users/personal.php?tab=habit");
        exit;
    }

    if (isset($_POST['delete_habit'])) {
        $habit_id = (int)$_POST['habit_index'];
        $stmt = $conn->prepare("UPDATE personal_habit SET deleted_at = NOW() WHERE habit_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $habit_id, $user_id);
        $stmt->execute();
        $_SESSION['success'] = 'Habit deleted successfully';
        header("Location: /dotrack/users/personal.php?tab=habit");
        exit;
    }
}

if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

$todos = getTodos($conn, $user_id);
$projects = getProjects($conn, $user_id);
$habits = getHabits($conn, $user_id);
$subtask_count = 5;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoTrack - Personal Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
    <link rel="shortcut icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
    <link rel="stylesheet" href="/dotrack/assets/css/personal.css">
</head>

<body>

    <header class="header">
        <div class="header-left">
            <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
            <span class="site-title">Personal</span>
        </div>
        <nav class="main-nav">
            <a href="/dotrack/home.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-4H9v4a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2z" />
                </svg>
                Home
            </a>
            <a href="/dotrack/users/mode.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="9" />
                    <rect x="14" y="3" width="7" height="5" />
                    <rect x="14" y="12" width="7" height="9" />
                    <rect x="3" y="16" width="7" height="5" />
                </svg>
                Mode
            </a>
            <a href="/dotrack/account.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 14c2 0 4 1 4 3v2H8v-2c0-2 2-3 4-3z" />
                    <circle cx="12" cy="8" r="3" />
                </svg>
                Account
            </a>
            <a href="/dotrack/about.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="16" x2="12" y2="12" />
                    <line x1="12" y1="8" x2="12" y2="8" />
                </svg>
                About
            </a>
        </nav>
    </header>

    <main class="dashboard-container">
        <?php if (!$tab): ?>
            <div class="container">
                <div class="row g-4 justify-content-center align-items-center">
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="/dotrack/users/personal.php?tab=todo" class="card-button d-block text-center">
                            <i class='bx bx-check-circle display-1 mb-3'></i>
                            <h2>To-Do</h2>
                            <p class="mb-0">Add tasks and stay organized.</p>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="/dotrack/users/personal.php?tab=project" class="card-button d-block text-center">
                            <i class='bx bx-folder display-1 mb-3'></i>
                            <h2>Project Tracker</h2>
                            <p class="mb-0">Monitor progress, deadlines & priorities.</p>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="/dotrack/users/personal.php?tab=habit" class="card-button d-block text-center">
                            <i class='bx bx-calendar-check display-1 mb-3'></i>
                            <h2>Habit</h2>
                            <p class="mb-0">Track and build daily habits.</p>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="container-fluid px-4">
                <div class="row">
                    <div class="col-auto px-0 position-relative">
                        <div class="sidebar">
                            <a href="/dotrack/users/personal.php?tab=todo" class="tab-btn <?php echo $tab == 'todo' ? 'active' : ''; ?>">
                                <i class='bx bx-check-circle'></i>
                                <span class="label">To-Do</span>
                            </a>
                            <a href="/dotrack/users/personal.php?tab=project" class="tab-btn <?php echo $tab == 'project' ? 'active' : ''; ?>">
                                <i class='bx bx-folder'></i>
                                <span class="label">Project Tracker</span>
                            </a>
                            <a href="/dotrack/users/personal.php?tab=habit" class="tab-btn <?php echo $tab == 'habit' ? 'active' : ''; ?>">
                                <i class='bx bx-calendar-check'></i>
                                <span class="label">Habit</span>
                            </a>
                        </div>
                    </div>
                    <div class="col">
                        <div class="content">
                            <?php if ($successMessage): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($successMessage); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($errorMessage): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($errorMessage); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($tab == 'todo'): ?>
                                <h2 class="mb-4">To-Do List</h2>
                                <form method="POST" class="row g-3 mb-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="add_todo" value="1">
                                    <div class="col-md-4">
                                        <input type="text" name="todo_task" class="form-control" placeholder="Task Name" required>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="todo_status" class="form-select">
                                            <option value="Not Started">Not Started</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="On Hold">On Hold</option>
                                            <option value="Cancelled">Cancelled</option>
                                            <option value="Done">Done</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="todo_due_date" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="todo_priority" class="form-select">
                                            <option value="Low">Low</option>
                                            <option value="Medium" selected>Medium</option>
                                            <option value="High">High</option>
                                            <option value="Urgent">Urgent</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="todo_category" class="form-control" placeholder="Category">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Add Task</button>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Status</th>
                                                <th>Due Date</th>
                                                <th>Priority</th>
                                                <th>Category</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($todos as $todo):
                                                $edit_mode = isset($_GET['edit_todo']) && $_GET['edit_todo'] == $todo['todo_id'];
                                            ?>
                                                <?php if ($edit_mode): ?>
                                                    <tr>
                                                        <td colspan="6" class="p-3">
                                                            <form method="POST">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="edit_todo_save" value="1">
                                                                <input type="hidden" name="edit_index" value="<?php echo $todo['todo_id']; ?>">
                                                                <div class="row g-2">
                                                                    <div class="col-md-4">
                                                                        <input type="text" name="todo_task" class="form-control" value="<?php echo htmlspecialchars($todo['todo_name']); ?>" required>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <select name="todo_status" class="form-select">
                                                                            <option value="Not Started" <?php echo $todo['status'] == 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
                                                                            <option value="In Progress" <?php echo $todo['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                            <option value="On Hold" <?php echo $todo['status'] == 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                                                                            <option value="Cancelled" <?php echo $todo['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                            <option value="Done" <?php echo $todo['status'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <input type="date" name="todo_due_date" class="form-control" value="<?php echo htmlspecialchars($todo['due_date']); ?>">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <select name="todo_priority" class="form-select">
                                                                            <option value="Low" <?php echo $todo['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                                                                            <option value="Medium" <?php echo $todo['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                                                            <option value="High" <?php echo $todo['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                                                                            <option value="Urgent" <?php echo $todo['priority'] == 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <input type="text" name="todo_category" class="form-control" value="<?php echo htmlspecialchars($todo['category']); ?>">
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                                                        <a href="/dotrack/users/personal.php?tab=todo" class="btn btn-secondary btn-sm">Cancel</a>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <tr>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="mark_done" value="1">
                                                                <input type="hidden" name="done_index" value="<?php echo $todo['todo_id']; ?>">
                                                                <input type="checkbox" name="done_checkbox" class="form-check-input me-2" onchange="this.form.submit()" <?php echo $todo['status'] == 'Done' ? 'checked' : ''; ?>>
                                                                <span class="<?php echo $todo['status'] == 'Done' ? 'text-decoration-line-through' : ''; ?>"><?php echo htmlspecialchars($todo['todo_name']); ?></span>
                                                            </form>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($todo['status']); ?></td>
                                                        <td><?php echo htmlspecialchars($todo['due_date']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $todo['priority'] == 'Urgent' ? 'bg-danger' : ($todo['priority'] == 'High' ? 'bg-warning' : ($todo['priority'] == 'Medium' ? 'bg-info' : 'bg-secondary')); ?>">
                                                                <?php echo htmlspecialchars($todo['priority']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($todo['category']); ?></td>
                                                        <td>
                                                            <a href="?tab=todo&edit_todo=<?php echo $todo['todo_id']; ?>" class="btn btn-sm btn-warning"><i class='bx bx-edit'></i></a>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="delete_todo" value="1">
                                                                <input type="hidden" name="delete_index" value="<?php echo $todo['todo_id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger"><i class='bx bx-trash'></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php elseif ($tab == 'project'): ?>
                                <h2 class="mb-4">Project Tracker</h2>
                                <form method="POST" class="mb-5">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="add_project" value="1">
                                    <input type="hidden" name="subtask_count" id="subtask_count" value="<?php echo $subtask_count; ?>">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <input type="text" name="project_name" class="form-control" placeholder="Project Name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" name="project_details" class="form-control" placeholder="Project Details" required>
                                        </div>
                                        <div class="col-md-3">
                                            <select name="project_priority" class="form-select">
                                                <option value="Low">Low</option>
                                                <option value="Medium" selected>Medium</option>
                                                <option value="High">High</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select name="project_status" class="form-select">
                                                <option value="Not Started">Not Started</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="On Hold">On Hold</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" name="project_start" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" name="project_due" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="fw-bold mb-2">Subtasks</label>
                                        <div id="subtasks-container">
                                            <?php for ($i = 0; $i < $subtask_count; $i++): ?>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-text">
                                                        <input type="checkbox" name="subtask_done_<?php echo $i; ?>">
                                                    </div>
                                                    <input type="text" name="subtask_name_<?php echo $i; ?>" class="form-control" placeholder="Subtask name">
                                                    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">×</button>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addSubtask()">+ Add Subtask</button>
                                    </div>

                                    <button type="submit" class="btn btn-primary mt-3">Add Project</button>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th>Done</th>
                                                <th>Project Name</th>
                                                <th>Details</th>
                                                <th>Subtasks</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Start</th>
                                                <th>Due</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($projects as $project):
                                                $edit_mode = isset($_GET['edit_project']) && $_GET['edit_project'] == $project['project_id'];
                                            ?>
                                                <?php if ($edit_mode): ?>
                                                    <tr>
                                                        <td colspan="9" class="p-3">
                                                            <form method="POST">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="edit_project_save" value="1">
                                                                <input type="hidden" name="edit_project_index" value="<?php echo $project['project_id']; ?>">
                                                                <input type="hidden" name="edit_subtask_count" id="edit_subtask_count" value="<?php echo max(1, count(explode("\n", $project['persubtask_name']))); ?>">

                                                                <div class="row g-2 mb-3">
                                                                    <div class="col-md-6">
                                                                        <input type="text" name="project_name" class="form-control" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <input type="text" name="project_details" class="form-control" value="<?php echo htmlspecialchars($project['description']); ?>" required>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <select name="project_priority" class="form-select">
                                                                            <option value="Low" <?php echo $project['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                                                                            <option value="Medium" <?php echo $project['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                                                            <option value="High" <?php echo $project['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <select name="project_status" class="form-select">
                                                                            <option value="Not Started" <?php echo $project['status'] == 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
                                                                            <option value="In Progress" <?php echo $project['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                            <option value="On Hold" <?php echo $project['status'] == 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                                                                            <option value="Completed" <?php echo $project['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <input type="date" name="project_start" class="form-control" value="<?php echo htmlspecialchars($project['start_date']); ?>" required>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <input type="date" name="project_due" class="form-control" value="<?php echo htmlspecialchars($project['due_date']); ?>" required>
                                                                    </div>
                                                                </div>

                                                                <div class="mt-2">
                                                                    <label class="fw-bold mb-2">Subtasks</label>
                                                                    <div id="edit-subtasks-container">
                                                                        <?php
                                                                        $subtasks = !empty($project['persubtask_name']) ? explode("\n", $project['persubtask_name']) : [];
                                                                        foreach ($subtasks as $si => $subtask):
                                                                            $is_done = strpos($subtask, '[DONE] ') === 0;
                                                                            $subtask_name = $is_done ? substr($subtask, 7) : $subtask;
                                                                        ?>
                                                                            <div class="input-group mb-2">
                                                                                <div class="input-group-text">
                                                                                    <input type="checkbox" name="subtask_done_<?php echo $si; ?>" <?php echo $is_done ? 'checked' : ''; ?>>
                                                                                </div>
                                                                                <input type="text" name="subtask_name_<?php echo $si; ?>" class="form-control" value="<?php echo htmlspecialchars($subtask_name); ?>">
                                                                                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">×</button>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addEditSubtask()">+ Add Subtask</button>
                                                                </div>

                                                                <div class="mt-3">
                                                                    <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
                                                                    <a href="?tab=project" class="btn btn-secondary btn-sm">Cancel</a>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <tr>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="toggle_project_done" value="1">
                                                                <input type="hidden" name="project_index" value="<?php echo $project['project_id']; ?>">
                                                                <input type="hidden" name="project_status" value="<?php echo $project['status']; ?>">
                                                                <input type="checkbox" class="form-check-input" onchange="this.form.submit()" <?php echo $project['status'] == 'Completed' ? 'checked' : ''; ?>>
                                                            </form>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($project['description']); ?></td>
                                                        <td>
                                                            <?php if (!empty($project['persubtask_name'])):
                                                                $subtasks = explode("\n", $project['persubtask_name']);
                                                                usort($subtasks, function ($a, $b) {
                                                                    $a_done = strpos($a, '[DONE] ') === 0;
                                                                    $b_done = strpos($b, '[DONE] ') === 0;
                                                                    return $a_done - $b_done;
                                                                });
                                                            ?>
                                                                <ul class="list-unstyled mb-0">
                                                                    <?php foreach ($subtasks as $si => $subtask):
                                                                        $is_done = strpos($subtask, '[DONE] ') === 0;
                                                                        $subtask_name = $is_done ? substr($subtask, 7) : $subtask;
                                                                    ?>
                                                                        <li class="mb-1">
                                                                            <form method="POST" class="d-inline">
                                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                                <input type="hidden" name="toggle_subtask" value="1">
                                                                                <input type="hidden" name="project_index" value="<?php echo $project['project_id']; ?>">
                                                                                <input type="hidden" name="subtask_index" value="<?php echo $si; ?>">
                                                                                <input type="checkbox" class="form-check-input me-1" onchange="this.form.submit()" <?php echo $is_done ? 'checked' : ''; ?>>
                                                                                <span class="<?php echo $is_done ? 'text-decoration-line-through' : ''; ?>"><?php echo htmlspecialchars($subtask_name); ?></span>
                                                                            </form>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <span class="text-muted">No subtasks</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $project['priority'] == 'High' ? 'bg-warning' : ($project['priority'] == 'Medium' ? 'bg-info' : 'bg-secondary'); ?>">
                                                                <?php echo htmlspecialchars($project['priority']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $project['status'] == 'Completed' ? 'bg-success' : ($project['status'] == 'In Progress' ? 'bg-primary' : 'bg-secondary'); ?>">
                                                                <?php echo htmlspecialchars($project['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($project['start_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($project['due_date']); ?></td>
                                                        <td>
                                                            <a href="?tab=project&edit_project=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-warning"><i class='bx bx-edit'></i></a>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this project?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <input type="hidden" name="delete_project" value="1">
                                                                <input type="hidden" name="delete_index" value="<?php echo $project['project_id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger"><i class='bx bx-trash'></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php elseif ($tab == 'habit'): ?>
                                <h2 class="mb-4">Habit Tracker</h2>
                                <form method="POST" class="row g-3 mb-5">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="add_habit" value="1">
                                    <div class="col-md-4">
                                        <select name="habit_day" class="form-select" required>
                                            <option value="Monday">Monday</option>
                                            <option value="Tuesday">Tuesday</option>
                                            <option value="Wednesday">Wednesday</option>
                                            <option value="Thursday">Thursday</option>
                                            <option value="Friday">Friday</option>
                                            <option value="Saturday">Saturday</option>
                                            <option value="Sunday">Sunday</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="habit_task" class="form-control" placeholder="Habit Task" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Add Habit</button>
                                    </div>
                                </form>

                                <div class="row g-4">
                                    <?php
                                    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($daysOfWeek as $day):
                                        $dayHabits = $habits[$day] ?? [];
                                    ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="habit-card">
                                                <h3 class="habit-day-title"><?php echo htmlspecialchars($day); ?></h3>
                                                <?php if (!empty($dayHabits)): ?>
                                                    <ul class="list-unstyled">
                                                        <?php foreach ($dayHabits as $habit):
                                                            $edit_mode = isset($_GET['edit_habit']) && $_GET['edit_habit'] == $habit['habit_id'] && isset($_GET['edit_day']) && $_GET['edit_day'] == $day;
                                                        ?>
                                                            <li class="habit-item mb-2">
                                                                <?php if ($edit_mode): ?>
                                                                    <form method="POST" class="d-flex flex-wrap gap-2">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                        <input type="hidden" name="edit_habit_save" value="1">
                                                                        <input type="hidden" name="habit_index" value="<?php echo $habit['habit_id']; ?>">
                                                                        <select name="habit_day" class="form-select form-select-sm" style="width: auto;">
                                                                            <?php foreach ($daysOfWeek as $d): ?>
                                                                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $d == $day ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                        <input type="text" name="habit_task_edit" class="form-control form-control-sm flex-grow-1" value="<?php echo htmlspecialchars($habit['habit_name']); ?>" required>
                                                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                                                        <a href="?tab=habit" class="btn btn-secondary btn-sm">Cancel</a>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span><?php echo htmlspecialchars($habit['habit_name']); ?></span>
                                                                        <div>
                                                                            <a href="?tab=habit&edit_habit=<?php echo $habit['habit_id']; ?>&edit_day=<?php echo urlencode($day); ?>" class="btn btn-sm btn-warning"><i class='bx bx-edit'></i></a>
                                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this habit?');">
                                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                                <input type="hidden" name="delete_habit" value="1">
                                                                                <input type="hidden" name="habit_index" value="<?php echo $habit['habit_id']; ?>">
                                                                                <button type="submit" class="btn btn-sm btn-danger"><i class='bx bx-trash'></i></button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">No habits yet.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-section footer-left">
            <span>DoTrack</span>
        </div>
        <div class="footer-section footer-center">
            <p>&copy; 2026 DoTrack. All rights reserved.</p>
        </div>
        <div class="footer-section footer-right">
            <a href="/dotrack/ourteam.php">Our Team</a>
            <a href="/dotrack/contact.php">Contact Us</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let subtaskIndex = <?php echo $subtask_count; ?>;

        function addSubtask() {
            const container = document.getElementById('subtasks-container');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
        <div class="input-group-text">
            <input type="checkbox" name="subtask_done_${subtaskIndex}">
        </div>
        <input type="text" name="subtask_name_${subtaskIndex}" class="form-control" placeholder="Subtask name">
        <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">×</button>
    `;
            container.appendChild(div);
            document.getElementById('subtask_count').value = ++subtaskIndex;
        }

        function addEditSubtask() {
            const container = document.getElementById('edit-subtasks-container');
            const currentCount = container.querySelectorAll('.input-group').length;
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
        <div class="input-group-text">
            <input type="checkbox" name="subtask_done_${currentCount}">
        </div>
        <input type="text" name="subtask_name_${currentCount}" class="form-control" placeholder="Subtask name">
        <button type="button" class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">×</button>
    `;
            container.appendChild(div);
            document.getElementById('edit_subtask_count').value = currentCount + 1;
        }
    </script>
</body>

</html>