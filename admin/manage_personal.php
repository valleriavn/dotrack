<?php
require_once '../auth/auth.php';
include '../includes/db.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-delete entities that have been deleted for more than 1 month
$auto_delete_tables = [
    ['table' => 'users', 'id_field' => 'user_id'],
    ['table' => 'collaborate_group', 'id_field' => 'colgroup_id'],
    ['table' => 'collaborate_project', 'id_field' => 'colproj_id'],
    ['table' => 'collaborate_task', 'id_field' => 'coltask_id'],
    ['table' => 'collaborate_teammem', 'id_field' => 'teammem_id'],
    ['table' => 'personal_todo', 'id_field' => 'todo_id'],
    ['table' => 'personal_project', 'id_field' => 'project_id'],
    ['table' => 'personal_habit', 'id_field' => 'habit_id'],
];

foreach ($auto_delete_tables as $tbl) {
    $conn->query("DELETE FROM {$tbl['table']} WHERE deleted_at IS NOT NULL AND deleted_at < (NOW() - INTERVAL 1 MONTH)");
}

// Handle filter input
$filter_type = $_GET['type'] ?? 'todos';
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_search = $_GET['search'] ?? '';
$filter_day = $_GET['day'] ?? '';

$where_todo = "WHERE t.deleted_at IS NULL";
$where_project = "WHERE p.deleted_at IS NULL";
$where_habit = "WHERE h.deleted_at IS NULL";
$params = [];
$types = "";

// Filtering for todos
if ($filter_type === 'todos') {
    if ($filter_status) {
        $where_todo .= " AND t.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    if ($filter_priority) {
        $where_todo .= " AND t.priority = ?";
        $params[] = $filter_priority;
        $types .= "s";
    }
    if ($filter_search) {
        $where_todo .= " AND (t.todo_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$filter_search%";
        $params[] = "%$filter_search%";
        $types .= "ss";
    }
}

// Filtering for projects
if ($filter_type === 'projects') {
    if ($filter_status) {
        $where_project .= " AND p.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    if ($filter_priority) {
        $where_project .= " AND p.priority = ?";
        $params[] = $filter_priority;
        $types .= "s";
    }
    if ($filter_search) {
        $where_project .= " AND (p.project_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$filter_search%";
        $params[] = "%$filter_search%";
        $types .= "ss";
    }
}

// Filtering for habits
if ($filter_type === 'habits') {
    if ($filter_day) {
        $where_habit .= " AND h.day = ?";
        $params[] = $filter_day;
        $types .= "s";
    }
    if ($filter_search) {
        $where_habit .= " AND (h.habit_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$filter_search%";
        $params[] = "%$filter_search%";
        $types .= "ss";
    }
}

// Get all data for each section
if ($filter_type === 'todos') {
    $sql = "
        SELECT t.*, u.username 
        FROM personal_todo t
        JOIN users u ON t.user_id = u.user_id
        $where_todo
        ORDER BY t.due_date ASC
    ";
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $all_todos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $todos_result = $conn->query("
        SELECT t.*, u.username 
        FROM personal_todo t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.deleted_at IS NULL
        ORDER BY t.due_date ASC
    ");
    $all_todos = $todos_result->fetch_all(MYSQLI_ASSOC);
}

if ($filter_type === 'projects') {
    $sql = "
        SELECT p.*, u.username 
        FROM personal_project p
        JOIN users u ON p.user_id = u.user_id
        $where_project
        ORDER BY p.due_date ASC
    ";
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $all_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $projects_result = $conn->query("
        SELECT p.*, u.username 
        FROM personal_project p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.deleted_at IS NULL
        ORDER BY p.due_date ASC
    ");
    $all_projects = $projects_result->fetch_all(MYSQLI_ASSOC);
}

if ($filter_type === 'habits') {
    $sql = "
        SELECT h.*, u.username 
        FROM personal_habit h
        JOIN users u ON h.user_id = u.user_id
        $where_habit
        ORDER BY h.day, h.habit_name
    ";
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $all_habits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $habits_result = $conn->query("
        SELECT h.*, u.username 
        FROM personal_habit h
        JOIN users u ON h.user_id = u.user_id
        WHERE h.deleted_at IS NULL
        ORDER BY h.day, h.habit_name
    ");
    $all_habits = $habits_result->fetch_all(MYSQLI_ASSOC);
}

// Handle deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: manage_personal.php");
        exit;
    }
    if (isset($_POST['delete_todo'])) {
        $todo_id = (int)$_POST['todo_id'];
        $stmt = $conn->prepare("UPDATE personal_todo SET deleted_at = NOW() WHERE todo_id = ?");
        $stmt->bind_param("i", $todo_id);
        $stmt->execute();
        header("Location: manage_personal.php?type=todos");
        exit;
    }
    elseif (isset($_POST['delete_project'])) {
        $project_id = (int)$_POST['project_id'];
        $stmt = $conn->prepare("UPDATE personal_project SET deleted_at = NOW() WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        header("Location: manage_personal.php?type=projects");
        exit;
    }
    elseif (isset($_POST['delete_habit'])) {
        $habit_id = (int)$_POST['habit_id'];
        $stmt = $conn->prepare("UPDATE personal_habit SET deleted_at = NOW() WHERE habit_id = ?");
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        header("Location: manage_personal.php?type=habits");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Personal Items | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .modal {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 90%;
            background: white;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: modalFadeIn 0.3s ease-out;
        }
        .modal::backdrop {
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            padding: 30px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .modal-header h3 {
            font-size: 1.3rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .select-wrapper {
            position: relative;
        }
        .select-wrapper i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            pointer-events: none;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--content-bg);
            transition: all 0.3s;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 139, 92, 0.1);
        }
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
    </style>
</head>
<body class="admin-container">
    <header class="admin-header">
        <h1>
            <?php 
            switch($filter_type) {
                case 'todos':
                    echo 'Manage Personal Todos';
                    break;
                case 'projects':
                    echo 'Manage Personal Projects';
                    break;
                case 'habits':
                    echo 'Manage Personal Habits';
                    break;
                default:
                    echo 'Manage Personal Items';
            }
            ?>
        </h1>
        <div class="user-menu">
            <span><i class='bx bx-user'></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../auth/logout.php?redirect=/dotrack/index.php"><i class='bx bx-log-out'></i> Logout</a>
        </div>
    </header>

    <nav class="admin-sidebar">
        <div class="sidebar-brand">
            <h2><i class='bx bx-data'></i> DoTrack Admin</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class='bx bx-grid-alt'></i> Dashboard</a></li>
            <li><a href="manage_users.php"><i class='bx bx-user'></i> Users</a></li>
            <li><a href="manage_personal.php?type=todos" class="<?= $filter_type === 'todos' ? 'active' : '' ?>"><i class='bx bx-task'></i> Personal Todos</a></li>
            <li><a href="manage_personal.php?type=projects" class="<?= $filter_type === 'projects' ? 'active' : '' ?>"><i class='bx bx-folder'></i> Personal Projects</a></li>
            <li><a href="manage_personal.php?type=habits" class="<?= $filter_type === 'habits' ? 'active' : '' ?>"><i class='bx bx-calendar'></i> Personal Habits</a></li>
            <li><a href="collaborative_management.php?section=groups"><i class='bx bx-group'></i> Collaborative Groups</a></li>
            <li><a href="collaborative_management.php?section=teammem"><i class='bx bx-user-plus'></i> Team Members</a></li>
            <li><a href="collaborative_management.php?section=projects"><i class='bx bx-folder'></i> Collaborative Projects</a></li>
            <li><a href="collaborative_management.php?section=tasks"><i class='bx bx-task'></i> Collaborative Tasks</a></li>
            <li><a href="backup_restore.php"><i class='bx bx-data'></i> Backup & Restore</a></li>
            <li><a href="deleted_entities.php"><i class='bx bx-trash'></i> Recycle Bin</a></li>
        </ul>
    </nav>

    <main class="admin-main">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="content-header">
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="document.getElementById('filter-modal').showModal()">
                    <i class='bx bx-filter'></i> Filter
                    <?php if ($filter_status || $filter_priority || $filter_search || $filter_day): ?>
                        <span class="filter-active">Active</span>
                    <?php endif; ?>
                </button>
            </div>
        </div>

        <dialog id="filter-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class='bx bx-filter'></i> Filter <?= ucfirst($filter_type) ?></h3>
                    <button class="btn btn-icon modal-close" onclick="document.getElementById('filter-modal').close()">
                        <i class='bx bx-x' style="font-size: 1.5rem;"></i>
                    </button>
                </div>
                <form action="manage_personal.php" method="GET" class="filter-form">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($filter_type) ?>">
                    <?php if ($filter_type === 'todos' || $filter_type === 'projects'): ?>
                        <div class="form-group">
                            <label for="filter_status" class="form-label">Status</label>
                            <div class="select-wrapper">
                                <select id="filter_status" name="status" class="form-control">
                                    <option value="" <?= !$filter_status ? 'selected' : '' ?>>All Statuses</option>
                                    <option value="not started" <?= ($filter_status === 'not started') ? 'selected' : '' ?>>Not Started</option>
                                    <option value="in progress" <?= ($filter_status === 'in progress') ? 'selected' : '' ?>>In Progress</option>
                                    <option value="on hold" <?= ($filter_status === 'on hold') ? 'selected' : '' ?>>On Hold</option>
                                    <option value="cancelled" <?= ($filter_status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="done" <?= ($filter_status === 'done') ? 'selected' : '' ?>>Done</option>
                                </select>
                                <i class='bx bx-chevron-down'></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="filter_priority" class="form-label">Priority</label>
                            <div class="select-wrapper">
                                <select id="filter_priority" name="priority" class="form-control">
                                    <option value="" <?= !$filter_priority ? 'selected' : '' ?>>All Priorities</option>
                                    <option value="low" <?= ($filter_priority === 'low') ? 'selected' : '' ?>>Low</option>
                                    <option value="medium" <?= ($filter_priority === 'medium') ? 'selected' : '' ?>>Medium</option>
                                    <option value="high" <?= ($filter_priority === 'high') ? 'selected' : '' ?>>High</option>
                                </select>
                                <i class='bx bx-chevron-down'></i>
                            </div>
                        </div>
                    <?php elseif ($filter_type === 'habits'): ?>
                        <div class="form-group">
                            <label for="filter_day" class="form-label">Day</label>
                            <div class="select-wrapper">
                                <select id="filter_day" name="day" class="form-control">
                                    <option value="" <?= !$filter_day ? 'selected' : '' ?>>All Days</option>
                                    <option value="Monday" <?= ($filter_day === 'Monday') ? 'selected' : '' ?>>Monday</option>
                                    <option value="Tuesday" <?= ($filter_day === 'Tuesday') ? 'selected' : '' ?>>Tuesday</option>
                                    <option value="Wednesday" <?= ($filter_day === 'Wednesday') ? 'selected' : '' ?>>Wednesday</option>
                                    <option value="Thursday" <?= ($filter_day === 'Thursday') ? 'selected' : '' ?>>Thursday</option>
                                    <option value="Friday" <?= ($filter_day === 'Friday') ? 'selected' : '' ?>>Friday</option>
                                    <option value="Saturday" <?= ($filter_day === 'Saturday') ? 'selected' : '' ?>>Saturday</option>
                                    <option value="Sunday" <?= ($filter_day === 'Sunday') ? 'selected' : '' ?>>Sunday</option>
                                </select>
                                <i class='bx bx-chevron-down'></i>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="filter_search" class="form-label">Search</label>
                        <input type="text" id="filter_search" name="search" class="form-control" placeholder="Name or User" value="<?= htmlspecialchars($filter_search) ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('filter-modal').close()">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class='bx bx-check'></i> Apply Filters</button>
                    </div>
                </form>
            </div>
        </dialog>

        <?php if ($filter_type === 'todos'): ?>
        <div class="dashboard-card">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Category</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_todos as $todo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($todo['username']); ?></td>
                                <td><?php echo htmlspecialchars($todo['todo_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?= str_replace(' ', '-', strtolower($todo['status'] ?? 'unknown')) ?>">
                                        <?= htmlspecialchars(ucfirst($todo['status'] ?? 'Unknown')) ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($todo['priority']); ?></td>
                                <td><?php echo htmlspecialchars($todo['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($todo['category']); ?></td>
                                <td><?php echo htmlspecialchars($todo['created_at'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <form method="POST" action="manage_personal.php?type=todos">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                            <button type="submit" name="delete_todo" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this todo?')">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($filter_type === 'projects'): ?>
        <div class="dashboard-card">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Project Name</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Start Date</th>
                            <th>Due Date</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['username']); ?></td>
                                <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?= str_replace(' ', '-', strtolower($project['status'] ?? 'unknown')) ?>">
                                        <?= htmlspecialchars(ucfirst($project['status'] ?? 'Unknown')) ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($project['priority']); ?></td>
                                <td><?php echo htmlspecialchars($project['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($project['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($project['created_at'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <form method="POST" action="manage_personal.php?type=projects">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                            <button type="submit" name="delete_project" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($filter_type === 'habits'): ?>
        <div class="dashboard-card">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Habit Name</th>
                            <th>Day</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_habits as $habit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($habit['username']); ?></td>
                                <td><?php echo htmlspecialchars($habit['habit_name']); ?></td>
                                <td><?php echo htmlspecialchars($habit['day']); ?></td>
                                <td><?php echo htmlspecialchars($habit['created_at'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <form method="POST" action="manage_personal.php?type=habits">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="habit_id" value="<?php echo $habit['habit_id']; ?>">
                                            <button type="submit" name="delete_habit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this habit?')">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer class="admin-footer">
        <div>DoTrack Database Admin Panel</div>
        <div>Version 1.0.0</div>
        <div><?= date('Y') ?> &copy; All Rights Reserved</div>
    </footer>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('status') || urlParams.has('priority') || urlParams.has('search') || urlParams.has('day')) {
            document.getElementById('filter-modal').showModal();
        }
    });
    </script>
</body>
</html>