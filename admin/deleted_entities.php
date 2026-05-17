<?php
require_once '../auth/auth.php';
include '../includes/db.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit;
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

// Restore functionality
if (isset($_GET['restore'])) {
    $id = (int)$_GET['restore'];
    $type = $_GET['type'];
    
    // Validate type
    $valid_types = ['user', 'group', 'project', 'todo', 'personal_project', 'habit', 'teammem', 'collaborate_project', 'collaborate_task'];
    if (!in_array($type, $valid_types)) {
        $_SESSION['error'] = "Invalid item type";
        header("Location: deleted_entities.php");
        exit;
    }

    try {
        // Determine the correct table and fields based on type
        switch ($type) {
            case 'user':
                $table = 'users';
                $id_field = 'user_id';
                $update_fields = "deleted_at = NULL, status = 'active'";
                break;
            case 'group':
                $table = 'collaborate_group';
                $id_field = 'colgroup_id';
                $update_fields = "deleted_at = NULL";
                break;
            case 'collaborate_project':
                $table = 'collaborate_project';
                $id_field = 'colproj_id';
                $update_fields = "deleted_at = NULL, status = 'not_started'";
                break;
            case 'collaborate_task':
                $table = 'collaborate_task';
                $id_field = 'coltask_id';
                $update_fields = "deleted_at = NULL, status = 'not_started'";
                break;
            case 'teammem':
                $table = 'collaborate_teammem';
                $id_field = 'teammem_id';
                $update_fields = "deleted_at = NULL";
                break;
            case 'todo':
                $table = 'personal_todo';
                $id_field = 'todo_id';
                $update_fields = "deleted_at = NULL, status = 'Not Started'";
                break;
            case 'personal_project':
                $table = 'personal_project';
                $id_field = 'project_id';
                $update_fields = "deleted_at = NULL, status = 'Not Started'";
                break;
            case 'habit':
                $table = 'personal_habit';
                $id_field = 'habit_id';
                $update_fields = "deleted_at = NULL";
                break;
            default:
                throw new Exception("Invalid type specified");
        }

        $stmt = $conn->prepare("UPDATE $table SET $update_fields WHERE $id_field = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = ucfirst(str_replace('_', ' ', $type)) . " restored successfully";
        } else {
            $_SESSION['error'] = ucfirst(str_replace('_', ' ', $type)) . " not found or already restored";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Restoration failed: " . $e->getMessage();
    }
    header("Location: deleted_entities.php");
    exit;
}

// Fetch all types of deleted items
$deleted_items = [
    'users' => $conn->query("SELECT user_id as id, username, email, deleted_at FROM users WHERE deleted_at IS NOT NULL"),
    'collaborate_groups' => $conn->query("SELECT colgroup_id as id, colgroup_name as name, deleted_at FROM collaborate_group WHERE deleted_at IS NOT NULL"),
    'collaborate_projects' => $conn->query("SELECT colproj_id as id, colproj_name as name, deleted_at FROM collaborate_project WHERE deleted_at IS NOT NULL"),
    'collaborate_tasks' => $conn->query("SELECT coltask_id as id, coltask_name as name, deleted_at FROM collaborate_task WHERE deleted_at IS NOT NULL"),
    'collaborate_teammems' => $conn->query("SELECT teammem_id as id, user_id, colproj_id, deleted_at FROM collaborate_teammem WHERE deleted_at IS NOT NULL"),
    'personal_todos' => $conn->query("SELECT todo_id as id, todo_name as name, deleted_at FROM personal_todo WHERE deleted_at IS NOT NULL"),
    'personal_projects' => $conn->query("SELECT project_id as id, project_name as name, deleted_at FROM personal_project WHERE deleted_at IS NOT NULL"),
    'personal_habits' => $conn->query("SELECT habit_id as id, habit_name as name, deleted_at FROM personal_habit WHERE deleted_at IS NOT NULL")
];

// Display names for each type
$display_names = [
    'users' => 'Users',
    'collaborate_groups' => 'Collaboration Groups',
    'collaborate_projects' => 'Collaboration Projects',
    'collaborate_tasks' => 'Collaboration Tasks',
    'collaborate_teammems' => 'Team Members',
    'personal_todos' => 'Personal Todos',
    'personal_projects' => 'Personal Projects',
    'personal_habits' => 'Personal Habits'
];

// Type mapping for restore action
$type_mapping = [
    'users' => 'user',
    'collaborate_groups' => 'group',
    'collaborate_projects' => 'collaborate_project',
    'collaborate_tasks' => 'collaborate_task',
    'collaborate_teammems' => 'teammem',
    'personal_todos' => 'todo',
    'personal_projects' => 'personal_project',
    'personal_habits' => 'habit'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recycle Bin | DoTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/deleted_entities.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Deleted Items</h1>
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
            <li><a href="manage_personal.php?type=todos" class="<?= $filter_type === 'todos' ? 'active' : '' ?>"><i class='bx bx-task'></i> Personal Todos</a></li>
            <li><a href="manage_personal.php?type=projects" class="<?= $filter_type === 'projects' ? 'active' : '' ?>"><i class='bx bx-folder'></i> Personal Projects</a></li>
            <li><a href="manage_personal.php?type=habits" class="<?= $filter_type === 'habits' ? 'active' : '' ?>"><i class='bx bx-calendar'></i> Personal Habits</a></li>
            <li><a href="collaborative_management.php?section=groups"><i class='bx bx-group'></i> Collaborative Groups</a></li>
            <li><a href="collaborative_management.php?section=teammem"><i class='bx bx-user-plus'></i> Team Members</a></li>
            <li><a href="collaborative_management.php?section=projects"><i class='bx bx-folder'></i> Collaborative Projects</a></li>
            <li><a href="collaborative_management.php?section=tasks"><i class='bx bx-task'></i> Collaborative Tasks</a></li>
            <li><a href="backup_restore.php"><i class='bx bx-data'></i> Backup & Restore</a></li>
            <li><a href="deleted_entities.php" class="active"><i class='bx bx-trash'></i> Recycle Bin</a></li>
        </ul>
        </aside>

        <main class="admin-main">

            <div class="recycle-bin-container">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php 
                $has_items = false;
                
                foreach ($deleted_items as $type => $items): 
                    if ($items->num_rows > 0): 
                        $has_items = true;
                ?>
                        <h3 class="section-title"><?= htmlspecialchars($display_names[$type]) ?></h3>
                        <div class="data-table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <?php if ($type === 'collaborate_teammems'): ?>
                                            <th>User ID</th>
                                            <th>Project ID</th>
                                        <?php endif; ?>
                                        <th>Deleted On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['name'] ?? $item['username'] ?? 'Unknown') ?></td>
                                        <?php if ($type === 'collaborate_teammems'): ?>
                                            <td><?= htmlspecialchars($item['user_id']) ?></td>
                                            <td><?= htmlspecialchars($item['colproj_id']) ?></td>
                                        <?php endif; ?>
                                        <td><?= date('M j, Y g:i A', strtotime($item['deleted_at'])) ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="?restore=<?= $item['id'] ?>&type=<?= $type_mapping[$type] ?>" 
                                                   class="btn-restore"
                                                   onclick="return confirm('Restore this <?= str_replace('_', ' ', $type_mapping[$type]) ?>?')">
                                                   <i class='bx bx-undo'></i> Restore
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if (!$has_items): ?>
                    <div class="empty-state">
                        <i class='bx bx-trash'></i>
                        <h3>Recycle Bin is Empty</h3>
                        <p>No deleted items found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

    <footer class="admin-footer">
        <div>DoTrack Database Admin Panel</div>
        <div>Version 1.0.0</div>
        <div><?= date('Y') ?> © All Rights Reserved</div>
    </footer>
    </div>
</body>
</html>