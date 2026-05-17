<?php
require_once '../auth/auth.php';
include '../includes/db.php';

// Handle filter parameters
$where_clause = "WHERE deleted_at IS NULL";
if (isset($_GET['filter_role']) && $_GET['filter_role'] !== 'all') {
    $filter_role = $conn->real_escape_string($_GET['filter_role']);
    $where_clause .= " AND role = '$filter_role'";
}
if (isset($_GET['filter_status']) && $_GET['filter_status'] !== 'all') {
    $filter_status = $conn->real_escape_string($_GET['filter_status']);
    $where_clause .= " AND status = '$filter_status'";
}

// Fetch users with filters
$users = $conn->query("SELECT * FROM users $where_clause ORDER BY created_at DESC");

// SOFT DELETE User
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Prevent self-deletion
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot deactivate your own account";
        header("Location: manage_users.php");
        exit;
    }

    try {
        // 1. Mark user as deleted
        $stmt = $conn->prepare("UPDATE users SET 
            status = 'inactive',
            deleted_at = NOW(),
            email = CONCAT(email, '_deleted_', UUID())
            WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // 2. Transfer group ownerships to first available admin
        $admin_id = $conn->query("SELECT user_id FROM users WHERE role = 'admin' AND deleted_at IS NULL LIMIT 1")->fetch_assoc()['user_id'] ?? null;
        if ($admin_id) {
            $conn->query("UPDATE groups SET owner_id = $admin_id WHERE owner_id = $user_id");
        }

        $_SESSION['message'] = "User deactivated successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Deactivation failed: " . $e->getMessage();
    }
    
    header("Location: manage_users.php");
    exit;
}

// TOGGLE USER STATUS
if (isset($_GET['toggle_status'])) {
    $user_id = $_GET['toggle_status'];
    
    // Prevent self-status change
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot change your own status";
        header("Location: manage_users.php");
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE users SET 
            status = CASE 
                WHEN status = 'active' THEN 'inactive' 
                ELSE 'active' 
            END
            WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $_SESSION['message'] = "User status updated successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Status update failed: " . $e->getMessage();
    }
    
    header("Location: manage_users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Database Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <h1>User Records</h1>
            <div class="user-menu">
                <span><i class='bx bx-user'></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                <a href="../auth/logout.php?redirect=/dotrack/index.php"><i class='bx bx-log-out'></i> Logout</a>
            </div>
        </header>

        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-brand">
                <h2><i class='bx bx-data'></i> DoTrack Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class='bx bx-grid-alt'></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="active"><i class='bx bx-user'></i> Users</a></li>
                <li><a href="manage_personal.php?type=todos"><i class='bx bx-task'></i> Personal Todos</a></li>
                <li><a href="manage_personal.php?type=projects"><i class='bx bx-folder'></i> Personal Projects</a></li>
                <li><a href="manage_personal.php?type=habits"><i class='bx bx-calendar'></i> Personal Habits</a></li>
                <li><a href="collaborative_management.php?section=groups"><i class='bx bx-group'></i> Collaborative Groups</a></li>
                <li><a href="collaborative_management.php?section=teammem"><i class='bx bx-user-plus'></i> Team Members</a></li>
                <li><a href="collaborative_management.php?section=projects"><i class='bx bx-folder'></i> Collaborative Projects</a></li>
                <li><a href="collaborative_management.php?section=tasks"><i class='bx bx-task'></i> Collaborative Tasks</a></li>
                <li><a href="backup_restore.php"><i class='bx bx-data'></i> Backup & Restore</a></li>
                <li><a href="deleted_entities.php"><i class='bx bx-trash'></i> Recycle Bin</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="content-header">
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="document.getElementById('filter-modal').showModal()">
                        <i class='bx bx-filter'></i> Filter
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-card">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Last Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="status-<?= htmlspecialchars($user['status']) ?>">
                                <td>#<?= htmlspecialchars($user['user_id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                                <td class="status-<?= htmlspecialchars($user['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($user['status'])) ?>
                                </td>
                                <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($user['created_at']))) ?></td>
                                <td><?= $user['last_active'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($user['last_active']))) : 'Never' ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="manage_users.php?toggle_status=<?= $user['user_id'] ?>" 
                                           class="action-btn status-toggle" 
                                           title="Toggle Status"
                                           onclick="return confirm('Are you sure you want to toggle this user\'s status?')">
                                            <i class='bx bx-power-off'></i>
                                        </a>
                                        <a href="manage_users.php?delete=<?= $user['user_id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to deactivate this user?')">
                                            <i class='bx bx-trash'></i> Deactivate
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Enhanced Filter Modal -->
            <dialog id="filter-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class='bx bx-filter'></i> Filter Users</h2>
                        <button class="btn btn-icon modal-close" onclick="document.getElementById('filter-modal').close()">
                            <i class='bx bx-x' style="font-size: 1.5rem;"></i>
                        </button>
                    </div>
                    <form action="manage_users.php" method="GET" class="filter-form">
                        <div class="form-group">
                            <label for="filter_role" class="form-label">Role</label>
                            <div class="select-wrapper">
                                <select id="filter_role" name="filter_role" class="form-control">
                                    <option value="all" <?= !isset($_GET['filter_role']) || $_GET['filter_role'] === 'all' ? 'selected' : '' ?>>All Roles</option>
                                    <option value="admin" <?= isset($_GET['filter_role']) && $_GET['filter_role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="user" <?= isset($_GET['filter_role']) && $_GET['filter_role'] === 'user' ? 'selected' : '' ?>>User</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="filter_status" class="form-label">Status</label>
                            <div class="select-wrapper">
                                <select id="filter_status" name="filter_status" class="form-control">
                                    <option value="all" <?= !isset($_GET['filter_status']) || $_GET['filter_status'] === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                    <option value="active" <?= isset($_GET['filter_status']) && $_GET['filter_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= isset($_GET['filter_status']) && $_GET['filter_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('filter-modal').close()">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class='bx bx-check'></i> Apply Filters</button>
                        </div>
                    </form>
                </div>
            </dialog>
        </main>

        <!-- Footer -->
        <footer class="admin-footer">
            <div>DoTrack Database Admin Panel</div>
            <div>Version 1.0.0</div>
            <div><?= date('Y') ?> © All Rights Reserved</div>
        </footer>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Open modal if filters are present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('filter_role') || urlParams.has('filter_status')) {
        document.getElementById('filter-modal').showModal();
    }
});
</script>
</body>
</html>