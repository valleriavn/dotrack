<?php
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../auth/auth.php';
include __DIR__ . '/../includes/db.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

// Directory for backups
$backup_dir = __DIR__ . '/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Ensure backup directory is writable
if (!is_writable($backup_dir)) {
    error_log(date('[Y-m-d H:i:s] ') . "Backup directory is not writable: $backup_dir" . PHP_EOL, 3, '/logs/db.log');
    $_SESSION['error'] = "Backup directory is not writable. Please check server permissions.";
    header('Location: backup_restore.php');
    exit;
}

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $backup_file = $backup_dir . 'dotrackdb_backup_' . date('Ymd_His') . '.sql';
    // Escape shell arguments for security
    $db_user = escapeshellarg(DB_USER);
    $db_name = escapeshellarg(DB_NAME);
    $db_host = escapeshellarg(DB_HOST);
    $command = "\"C:\\xampp\\mysql\\bin\\mysqldump\" --user=$db_user " . (DB_PASS ? "--password=" . escapeshellarg(DB_PASS) : "") . " --host=$db_host $db_name > " . escapeshellarg($backup_file);
    
    exec($command . " 2>&1", $output, $return_var);
    if ($return_var === 0) {
        $_SESSION['success'] = "Backup created successfully!";
    } else {
        $error_message = implode("\n", $output);
        error_log(date('[Y-m-d H:i:s] ') . "Backup failed: $error_message" . PHP_EOL, 3, '/logs/db.log');
        $_SESSION['error'] = "Failed to create backup. Check server logs for details.";
    }
    header('Location: backup_restore.php');
    exit;
}

// Handle backup restore
if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['restore_file']['tmp_name'];
    $ext = pathinfo($_FILES['restore_file']['name'], PATHINFO_EXTENSION);
    
    if ($ext === 'sql') {
        $db_user = escapeshellarg(DB_USER);
        $db_name = escapeshellarg(DB_NAME);
        $db_host = escapeshellarg(DB_HOST);
        $command = "\"C:\\xampp\\mysql\\bin\\mysqldump\" --user=$db_user " . (DB_PASS ? "--password=" . escapeshellarg(DB_PASS) : "") . " --host=$db_host $db_name > " . escapeshellarg($backup_file);
        exec($command . " 2>&1", $output, $return_var);
        if ($return_var === 0) {
            $_SESSION['success'] = "Database restored successfully!";
        } else {
            $error_message = implode("\n", $output);
            error_log(date('[Y-m-d H:i:s] ') . "Restore failed: $error_message" . PHP_EOL, 3, '/logs/db.log');
            $_SESSION['error'] = "Failed to restore database. Check server logs for details.";
        }
    } else {
        $_SESSION['error'] = "Invalid file format. Please upload a .sql file.";
    }
    header('Location: backup_restore.php');
    exit;
}

// Handle backup deletion
if (isset($_POST['delete_backup'])) {
    $file = $backup_dir . basename($_POST['delete_backup']);
    if (file_exists($file) && is_writable($file)) {
        if (unlink($file)) {
            $_SESSION['success'] = "Backup deleted successfully!";
        } else {
            error_log(date('[Y-m-d H:i:s] ') . "Failed to delete backup: $file" . PHP_EOL, 3, '/logs/db.log');
            $_SESSION['error'] = "Failed to delete backup.";
        }
    } else {
        $_SESSION['error'] = "Backup file not found or not writable.";
    }
    header('Location: backup_restore.php');
    exit;
}

// Get list of backup files
$backups = array_filter(glob($backup_dir . '*.sql'), 'is_file');
usort($backups, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore | DoTrack</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/backup_restore.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <h1>Backup & Restore</h1>
        <div class="user-menu">
            <span><i class='bx bx-user'></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
            <li><a href="manage_users.php"><i class='bx bx-user'></i> Users</a></li>
            <li><a href="manage_personal.php?type=todos" class="<?= $filter_type === 'todos' ? 'active' : '' ?>"><i class='bx bx-task'></i> Personal Todos</a></li>
            <li><a href="manage_personal.php?type=projects" class="<?= $filter_type === 'projects' ? 'active' : '' ?>"><i class='bx bx-folder'></i> Personal Projects</a></li>
            <li><a href="manage_personal.php?type=habits" class="<?= $filter_type === 'habits' ? 'active' : '' ?>"><i class='bx bx-calendar'></i> Personal Habits</a></li>
            <li><a href="collaborative_management.php?section=groups"><i class='bx bx-group'></i> Collaborative Groups</a></li>
            <li><a href="collaborative_management.php?section=teammem"><i class='bx bx-user-plus'></i> Team Members</a></li>
            <li><a href="collaborative_management.php?section=projects"><i class='bx bx-folder'></i> Collaborative Projects</a></li>
            <li><a href="collaborative_management.php?section=tasks"><i class='bx bx-task'></i> Collaborative Tasks</a></li>
            <li><a href="backup_restore.php" class="active"><i class='bx bx-data'></i> Backup & Restore</a></li>
            <li><a href="deleted_entities.php"><i class='bx bx-trash'></i> Recycle Bin</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="admin-main">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Backup Section -->
        <div class="dashboard-card">
            <div class="content-header">
                <h2><i class='bx bx-data'></i> Database Backup</h2>
            </div>
            <form method="POST" class="backup-form">
                <p>Create a new backup of the DoTrack database.</p>
                <button type="submit" name="create_backup" class="btn btn-primary">
                    <i class='bx bx-download'></i> Create Backup
                </button>
            </form>
        </div>

        <!-- Restore Section -->
        <div class="dashboard-card">
            <div class="content-header">
                <h2><i class='bx bx-upload'></i> Database Restore</h2>
            </div>
            <form method="POST" enctype="multipart/form-data" class="restore-form">
                <p>Upload a .sql backup file to restore the database. Warning: This will overwrite existing data.</p>
                <div class="form-group">
                    <input type="file" name="restore_file" accept=".sql" required>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to restore this backup? Current data will be overwritten.')">
                    <i class='bx bx-upload'></i> Restore Database
                </button>
            </form>
        </div>

        <!-- Backup List Section -->
        <div class="user-data-section">
            <div class="content-header">
                <h2><i class='bx bx-file'></i> Backup Files</h2>
            </div>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(basename($backup)); ?></td>
                                <td><?php echo round(filesize($backup) / 1024, 2); ?> KB</td>
                                <td><?php echo date('M j, Y g:i A', filemtime($backup)); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="backups/<?php echo htmlspecialchars(basename($backup)); ?>" download class="action-btn">
                                            <i class='bx bx-download'></i>
                                        </a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_backup" value="<?php echo htmlspecialchars(basename($backup)); ?>">
                                            <button type="submit" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this backup?')">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="4">No backups found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <div>DoTrack Database Admin Panel</div>
        <div>Version 1.0.0</div>
        <div><?= date('Y') ?> © All Rights Reserved</div>
    </footer>
</body>
</html>