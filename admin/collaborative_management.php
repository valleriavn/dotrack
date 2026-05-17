<?php
session_start();
require_once '../auth/auth.php';
require_once '../includes/db.php';

// Verify admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$successMessage = '';
$current_section = isset($_GET['section']) ? $_GET['section'] : 'groups';

// Initialize filter variables
$filter_created_by = isset($_GET['created_by']) ? trim($_GET['created_by']) : '';
$filter_group_name = isset($_GET['group_name']) ? trim($_GET['group_name']) : '';
$filter_group = isset($_GET['group']) ? (int)$_GET['group'] : 0;
$filter_project = isset($_GET['project']) ? (int)$_GET['project'] : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch all projects for filter modals
$all_projects = [];
try {
    $projects_query = $conn->query("SELECT colproj_id, colproj_name FROM collaborate_project WHERE deleted_at IS NULL ORDER BY colproj_name");
    if ($projects_query === false) {
        throw new Exception("Failed to fetch projects: " . $conn->error);
    }
    $all_projects = $projects_query->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $errorMessage = "Error loading projects: " . htmlspecialchars($e->getMessage());
}

// Handle actions based on section
switch ($current_section) {
    case 'groups':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
            try {
                if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
                    throw new Exception("Invalid CSRF token");
                }
                $group_id = (int)$_GET['delete'];
                $conn->begin_transaction();
                $stmt = $conn->prepare("UPDATE collaborate_group SET deleted_at = NOW() WHERE colgroup_id = ?");
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $stmt = $conn->prepare("UPDATE collaborate_project SET deleted_at = NOW() WHERE colgroup_id = ?");
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $conn->commit();
                $successMessage = "Group deleted successfully";
            } catch (Exception $e) {
                $conn->rollback();
                $errorMessage = "Delete failed: " . htmlspecialchars($e->getMessage());
            }
            header("Location: ?section=groups");
            exit;
        }
        $groups = [];
        $all_usernames = [];
        try {
            $query = "SELECT g.colgroup_id, g.colgroup_name, g.group_code, g.created_at, u.username as created_by 
                     FROM collaborate_group g
                     JOIN users u ON g.user_id = u.user_id 
                     WHERE g.deleted_at IS NULL";
            $params = [];
            $types = '';
            if (!empty($filter_created_by)) {
                $query .= " AND u.username LIKE ?";
                $params[] = '%' . $filter_created_by . '%';
                $types .= 's';
            }
            if (!empty($filter_group_name)) {
                $query .= " AND g.colgroup_name LIKE ?";
                $params[] = '%' . $filter_group_name . '%';
                $types .= 's';
            }
            $query .= " ORDER BY g.created_at DESC";
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $result = $conn->query("SELECT DISTINCT u.username FROM users u 
                                   JOIN collaborate_group g ON u.user_id = g.user_id 
                                   WHERE g.deleted_at IS NULL ORDER BY u.username");
            $all_usernames = $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            $errorMessage = "Error loading groups: " . htmlspecialchars($e->getMessage());
        }
        break;

    case 'projects':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
            try {
                if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
                    throw new Exception("Invalid CSRF token");
                }
                $project_id = (int)$_GET['delete'];
                $conn->begin_transaction();
                $stmt = $conn->prepare("UPDATE collaborate_project SET status = 'cancelled', deleted_at = NOW() WHERE colproj_id = ?");
                $stmt->bind_param("i", $project_id);
                $stmt->execute();
                $conn->commit();
                $successMessage = "Project deactivated successfully";
            } catch (Exception $e) {
                $conn->rollback();
                $errorMessage = "Deactivation failed: " . htmlspecialchars($e->getMessage());
            }
            header("Location: ?section=projects");
            exit;
        }
        $projects = [];
        $all_groups = [];
        try {
            $groups_query = $conn->query("SELECT colgroup_id, colgroup_name FROM collaborate_group WHERE deleted_at IS NULL ORDER BY colgroup_name");
            $all_groups = $groups_query->fetch_all(MYSQLI_ASSOC);

            $query = "SELECT p.colproj_id, p.colproj_name, p.description, p.status, p.created_at, 
                            g.colgroup_name, g.colgroup_id, u.username AS created_by
                     FROM collaborate_project p
                     LEFT JOIN collaborate_group g ON p.colgroup_id = g.colgroup_id AND g.deleted_at IS NULL
                     JOIN users u ON p.user_id = u.user_id
                     WHERE p.deleted_at IS NULL";
            $params = [];
            $types = '';
            if ($filter_group > 0) {
                $query .= " AND p.colgroup_id = ?";
                $params[] = $filter_group;
                $types .= 'i';
            }
            if (!empty($filter_status)) {
                $query .= " AND p.status = ?";
                $params[] = $filter_status;
                $types .= 's';
            }
            $query .= " ORDER BY p.created_at DESC";
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            $errorMessage = "Error loading projects: " . htmlspecialchars($e->getMessage());
        }
        break;

    case 'teammem':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
            try {
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    throw new Exception("Invalid CSRF token");
                }
                $teammem_id = (int)$_POST['teammem_id'];
                $stmt = $conn->prepare("UPDATE collaborate_teammem SET deleted_at = NOW() WHERE teammem_id = ?");
                $stmt->bind_param("i", $teammem_id);
                $stmt->execute();
                $successMessage = "Team member removed successfully";
            } catch (Exception $e) {
                $errorMessage = "Removal failed: " . htmlspecialchars($e->getMessage());
            }
            header("Location: ?section=teammem");
            exit;
        }
        $all_teammem = [];
        $all_groups = [];
        try {
            $groups_query = $conn->query("SELECT colgroup_id, colgroup_name FROM collaborate_group WHERE deleted_at IS NULL ORDER BY colgroup_name");
            $all_groups = $groups_query->fetch_all(MYSQLI_ASSOC);

            $query = "SELECT tm.teammem_id, tm.user_id, tm.colproj_id, tm.colgroup_id, tm.created_at,
                            u.username, u.email, p.colproj_name, g.colgroup_name
                     FROM collaborate_teammem tm
                     JOIN users u ON tm.user_id = u.user_id
                     LEFT JOIN collaborate_project p ON tm.colproj_id = p.colproj_id
                     LEFT JOIN collaborate_group g ON tm.colgroup_id = g.colgroup_id
                     WHERE tm.deleted_at IS NULL";
            $params = [];
            $types = '';
            if ($filter_project > 0) {
                $query .= " AND tm.colproj_id = ?";
                $params[] = $filter_project;
                $types .= 'i';
            }
            if ($filter_group > 0) {
                $query .= " AND tm.colgroup_id = ?";
                $params[] = $filter_group;
                $types .= 'i';
            }
            $query .= " ORDER BY tm.created_at DESC";
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $all_teammem = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            $errorMessage = "Error loading team members: " . htmlspecialchars($e->getMessage());
        }
        break;

    case 'tasks':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
            try {
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    throw new Exception("Invalid CSRF token");
                }
                $task_id = (int)$_POST['task_id'];
                $stmt = $conn->prepare("UPDATE collaborate_task SET deleted_at = NOW() WHERE coltask_id = ?");
                $stmt->bind_param("i", $task_id);
                $stmt->execute();
                $successMessage = "Task deleted successfully";
            } catch (Exception $e) {
                $errorMessage = "Deletion failed: " . htmlspecialchars($e->getMessage());
            }
            header("Location: ?section=tasks");
            exit;
        }
        $all_tasks = [];
        $statuses = ['not started', 'in progress', 'on hold', 'cancelled', 'done'];
        try {
            $query = "SELECT t.coltask_id, t.coltask_name, t.start_date, t.due_date, t.status, t.colsubtask_name,
                            p.colproj_name, p.colproj_id, u.username AS assigned_to, creator.username AS created_by,
                            g.colgroup_name
                     FROM collaborate_task t
                     LEFT JOIN collaborate_project p ON t.colproj_id = p.colproj_id
                     LEFT JOIN users u ON t.assigned_user_id = u.user_id
                     LEFT JOIN users creator ON t.user_id = creator.user_id
                     LEFT JOIN collaborate_group g ON p.colgroup_id = g.colgroup_id
                     WHERE t.deleted_at IS NULL";
            $params = [];
            $types = '';
            if ($filter_project > 0) {
                $query .= " AND t.colproj_id = ?";
                $params[] = $filter_project;
                $types .= 'i';
            }
            if (!empty($filter_status)) {
                $query .= " AND t.status = ?";
                $params[] = $filter_status;
                $types .= 's';
            }
            $query .= " ORDER BY t.due_date ASC";
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $all_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            $errorMessage = "Error loading tasks: " . htmlspecialchars($e->getMessage());
        }
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaborative Management | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
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
        .modal-header h2 {
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
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.92em;
            font-weight: 600;
            color: #333;
            min-width: 80px;
            text-align: center;
        }
        .status-not-started { background: #b8bcc2; }
        .status-in-progress { background: #7bb8e8; }
        .status-on-hold     { background: #ffdea0; }
        .status-cancelled   { background: #e48787; }
        .status-done        { background: #94c496; }
        .status-unknown     { background: #c8c8c8; }
        .data-table th, .data-table td {
            padding: 12px;
            white-space: nowrap;
        }
        .data-table .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .filter-active {
            background: #ffc107;
            color: #333;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 6px;
        }
    </style>
</head>
<body class="admin-container">
    <header class="admin-header">
        <h1>
            <?php
            $titles = [
                'groups' => 'Manage Collaborative Groups',
                'teammem' => 'Manage Team Members',
                'projects' => 'Manage Collaborative Projects',
                'tasks' => 'Manage Collaborative Tasks'
            ];
            echo htmlspecialchars($titles[$current_section] ?? 'Collaborative Management');
            ?>
        </h1>
        <div class="user-menu">
            <a href="admin_dashboard.php"><i class='bx bx-grid-alt'></i> Dashboard</a>
            <a href="../auth/logout.php?redirect=/dotrack/index.php"><i class='bx bx-log-out'></i> Logout</a>
        </div>
    </header>

    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <h2><i class='bx bxs-widget'></i> <span>DoTrack</span></h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class='bx bx-grid-alt'></i> Dashboard</a></li>
            <li><a href="manage_users.php"><i class='bx bx-user'></i> Users</a></li>
            <li><a href="manage_personal.php?type=todos"><i class='bx bx-task'></i> Personal Todos</a></li>
            <li><a href="manage_personal.php?type=projects"><i class='bx bx-folder'></i> Personal Projects</a></li>
            <li><a href="manage_personal.php?type=habits"><i class='bx bx-calendar'></i> Personal Habits</a></li>
            <li><a href="?section=groups" class="<?= $current_section === 'groups' ? 'active' : '' ?>"><i class='bx bx-group'></i> Collaborative Groups</a></li>
            <li><a href="?section=teammem" class="<?= $current_section === 'teammem' ? 'active' : '' ?>"><i class='bx bx-user-plus'></i> Team Members</a></li>
            <li><a href="?section=projects" class="<?= $current_section === 'projects' ? 'active' : '' ?>"><i class='bx bx-folder'></i> Collaborative Projects</a></li>
            <li><a href="?section=tasks" class="<?= $current_section === 'tasks' ? 'active' : '' ?>"><i class='bx bx-task'></i> Collaborative Tasks</a></li>
            <li><a href="backup_restore.php"><i class='bx bx-data'></i> Backup & Restore</a></li>
            <li><a href="deleted_entities.php"><i class='bx bx-trash'></i> Recycle Bin</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="content-header">
            <div class="action-buttons">
                <?php if (in_array($current_section, ['groups', 'projects', 'teammem', 'tasks'])): ?>
                    <button class="btn btn-primary" onclick="document.getElementById('filter-modal').showModal()">
                        <i class='bx bx-filter'></i> Filter
                        <?php if ($filter_created_by || $filter_group_name || $filter_group || $filter_project || $filter_status): ?>
                            <span class="filter-active">Active</span>
                        <?php endif; ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($errorMessage): ?>
            <div class="alert error">
                <i class='bx bx-error-circle'></i> <?= htmlspecialchars($errorMessage) ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert success">
                <i class='bx bx-check-circle'></i> <?= htmlspecialchars($successMessage) ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <div class="data-table-container">
                <?php switch ($current_section): 
                    case 'groups': ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Group Name</th>
                                    <th>Code</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($groups)): ?>
                                    <tr>
                                        <td colspan="5" class="no-data">
                                            <i class='bx bx-info-circle'></i> No groups found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($groups as $group): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($group['colgroup_name']) ?></td>
                                            <td><?= htmlspecialchars($group['group_code'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($group['created_by']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($group['created_at'])) ?></td>
                                            <td>
                                                <a href="?section=groups&delete=<?= $group['colgroup_id'] ?>&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                   onclick="return confirm('Are you sure you want to delete this group?')"
                                                   class="btn btn-danger">
                                                   <i class='bx bx-trash'></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php break; ?>
                    <?php case 'projects': ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Group</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($projects)): ?>
                                    <tr>
                                        <td colspan="6" class="no-data">
                                            <i class='bx bx-info-circle'></i> No projects found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($project['colproj_name']) ?></td>
                                            <td><?= htmlspecialchars($project['colgroup_name'] ?? 'No Group') ?></td>
                                            <td><span class="status-badge status-<?= htmlspecialchars(str_replace(' ', '-', strtolower($project['status'] ?? 'unknown'))) ?>">
                                                <?= htmlspecialchars(ucfirst($project['status'] ?? 'Unknown')) ?>
                                            </span></td>
                                            <td><?= htmlspecialchars($project['created_by']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($project['created_at'])) ?></td>
                                            <td>
                                                <a href="?section=projects&delete=<?= $project['colproj_id'] ?>&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                   onclick="return confirm('Are you sure you want to deactivate this project?')"
                                                   class="btn btn-danger">
                                                   <i class='bx bx-trash'></i> Deactivate
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php break; ?>
                    <?php case 'teammem': ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Project</th>
                                    <th>Group</th>
                                    <th>Joined On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_teammem)): ?>
                                    <tr>
                                        <td colspan="7" class="no-data">
                                            <i class='bx bx-info-circle'></i> No team members found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_teammem as $member): ?>
                                        <tr>
                                            <td><?= $member['teammem_id'] ?></td>
                                            <td><?= htmlspecialchars($member['username']) ?></td>
                                            <td><?= htmlspecialchars($member['email']) ?></td>
                                            <td><?= htmlspecialchars($member['colproj_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($member['colgroup_name'] ?? 'N/A') ?></td>
                                            <td><?= date('M j, Y', strtotime($member['created_at'])) ?></td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="teammem_id" value="<?= $member['teammem_id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <button type="submit" name="delete" class="btn btn-danger"
                                                            onclick="return confirm('Are you sure you want to remove this team member?')">
                                                        <i class='bx bx-trash'></i> Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php break; ?>
                    <?php case 'tasks': ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Task Name</th>
                                    <th>Project</th>
                                    <th>Group</th>
                                    <th>Assigned To</th>
                                    <th>Start Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Subtasks</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_tasks)): ?>
                                    <tr>
                                        <td colspan="11" class="no-data">
                                            <i class='bx bx-info-circle'></i> No tasks found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_tasks as $task): ?>
                                        <tr>
                                            <td><?= $task['coltask_id'] ?></td>
                                            <td><?= htmlspecialchars($task['coltask_name']) ?></td>
                                            <td><?= htmlspecialchars($task['colproj_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($task['colgroup_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($task['assigned_to'] ?? 'Unassigned') ?></td>
                                            <td><?= $task['start_date'] ? date('M j, Y', strtotime($task['start_date'])) : '-' ?></td>
                                            <td><?= $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : '-' ?></td>
                                            <td><span class="status-badge status-<?= htmlspecialchars(str_replace(' ', '-', strtolower($task['status'] ?? 'unknown'))) ?>">
                                                <?= htmlspecialchars(ucfirst($task['status'] ?? 'Unknown')) ?>
                                            </span></td>
                                            <td><?= $task['colsubtask_name'] ? count(explode(',', $task['colsubtask_name'])) . ' subtasks' : 'None' ?></td>
                                            <td><?= htmlspecialchars($task['created_by']) ?></td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="task_id" value="<?= $task['coltask_id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <button type="submit" name="delete" class="btn btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this task?')">
                                                        <i class='bx bx-trash'></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php break; ?>
                <?php endswitch; ?>
            </div>
        </div>

        <?php if (in_array($current_section, ['groups', 'projects', 'teammem', 'tasks'])): ?>
            <dialog id="filter-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class='bx bx-filter'></i> Filter <?= htmlspecialchars(ucfirst($current_section)) ?></h2>
                        <button class="btn btn-icon modal-close" onclick="document.getElementById('filter-modal').close()">
                            <i class='bx bx-x' style="font-size: 1.5rem;"></i>
                        </button>
                    </div>
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="section" value="<?= htmlspecialchars($current_section) ?>">
                        <?php if ($current_section === 'groups'): ?>
                            <div class="form-group">
                                <label for="created_by" class="form-label">Created By</label>
                                <div class="select-wrapper">
                                    <select id="created_by" name="created_by" class="form-control">
                                        <option value="">All Users</option>
                                        <?php foreach ($all_usernames as $user): ?>
                                            <option value="<?= htmlspecialchars($user['username']) ?>" <?= $filter_created_by === $user['username'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['username']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="group_name" class="form-label">Group Name</label>
                                <input type="text" id="group_name" name="group_name" class="form-control" value="<?= htmlspecialchars($filter_group_name) ?>" placeholder="Enter group name">
                            </div>
                        <?php elseif ($current_section === 'projects'): ?>
                            <div class="form-group">
                                <label for="group" class="form-label">Group</label>
                                <div class="select-wrapper">
                                    <select id="group" name="group" class="form-control">
                                        <option value="">All Groups</option>
                                        <?php foreach ($all_groups as $group): ?>
                                            <option value="<?= $group['colgroup_id'] ?>" <?= $filter_group == $group['colgroup_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($group['colgroup_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <div class="select-wrapper">
                                    <select id="status" name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <?php foreach (['not started', 'in progress', 'on hold', 'cancelled', 'done'] as $status): ?>
                                            <option value="<?= $status ?>" <?= $filter_status === $status ? 'selected' : '' ?>>
                                                <?= ucfirst($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>
                        <?php elseif ($current_section === 'teammem'): ?>
                            <div class="form-group">
                                <label for="project" class="form-label">Project</label>
                                <div class="select-wrapper">
                                    <select id="project" name="project" class="form-control">
                                        <option value="">All Projects</option>
                                        <?php foreach ($all_projects as $project): ?>
                                            <option value="<?= $project['colproj_id'] ?>" <?= $filter_project == $project['colproj_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($project['colproj_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="group" class="form-label">Group</label>
                                <div class="select-wrapper">
                                    <select id="group" name="group" class="form-control">
                                        <option value="">All Groups</option>
                                        <?php foreach ($all_groups as $group): ?>
                                            <option value="<?= $group['colgroup_id'] ?>" <?= $filter_group == $group['colgroup_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($group['colgroup_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>
                        <?php elseif ($current_section === 'tasks'): ?>
                            <div class="form-group">
                                <label for="project" class="form-label">Project</label>
                                <div class="select-wrapper">
                                    <select id="project" name="project" class="form-control">
                                        <option value="">All Projects</option>
                                        <?php foreach ($all_projects as $project): ?>
                                            <option value="<?= $project['colproj_id'] ?>" <?= $filter_project == $project['colproj_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($project['colproj_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <div class="select-wrapper">
                                    <select id="status" name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status ?>" <?= $filter_status === $status ? 'selected' : '' ?>>
                                                <?= ucfirst($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('filter-modal').close()">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class='bx bx-check'></i> Apply Filters</button>
                        </div>
                    </form>
                </div>
            </dialog>
        <?php endif; ?>
    </main>

    <footer class="admin-footer">
        <div>DoTrack Database Admin Panel</div>
        <div>Version 1.0.0</div>
        <div><?= date('Y') ?> &copy; All Rights Reserved</div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('project') || urlParams.has('group') || urlParams.has('status') || 
                urlParams.has('created_by') || urlParams.has('group_name')) {
                document.getElementById('filter-modal')?.showModal();
            }

            document.querySelector('.filter-form')?.addEventListener('submit', (e) => {
                const createdBy = document.getElementById('created_by')?.value;
                const groupName = document.getElementById('group_name')?.value;
                if ((createdBy && createdBy.length < 2) || (groupName && groupName.length < 2)) {
                    alert('Text filters must be at least 2 characters long.');
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>