<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../auth/auth.php';
include __DIR__ . '/../includes/db.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

// Get all active users for activity tracking (EXCLUDING admin)
$users_list_result = $conn->query("SELECT user_id, username, email, status, created_at FROM users WHERE deleted_at IS NULL AND role = 'user' ORDER BY username");
$users_list = $users_list_result->fetch_all(MYSQLI_ASSOC);

// Function to get activity logs for a specific user
function getUserActivityLogs($conn, $user_id, $daysBack = 90) {
    $cutoff = date('Y-m-d H:i:s', strtotime("-$daysBack days"));
    $activities = [];
    
    // 1. Personal Todo Activities
    $todos_query = "
        SELECT 
            'task_complete' as activity_type,
            'personal_todo' as source,
            CONCAT('Completed task: ', t.todo_name) as action_description,
            t.created_at as activity_timestamp,
            t.todo_name as details
        FROM personal_todo t
        WHERE t.status = 'done' 
        AND t.created_at >= '$cutoff'
        AND t.deleted_at IS NULL
        AND t.user_id = $user_id
        
        UNION ALL
        
        SELECT 
            'task_create' as activity_type,
            'personal_todo' as source,
            CONCAT('Created task: ', t.todo_name) as action_description,
            t.created_at as activity_timestamp,
            t.todo_name as details
        FROM personal_todo t
        WHERE t.created_at >= '$cutoff'
        AND t.deleted_at IS NULL
        AND t.user_id = $user_id
    ";
    
    // 2. Personal Habit Activities
    $habits_activity_query = "
        SELECT 
            'habit_complete' as activity_type,
            'personal_habit' as source,
            CONCAT('Created habit: ', h.habit_name) as action_description,
            h.created_at as activity_timestamp,
            h.habit_name as details
        FROM personal_habit h
        WHERE h.created_at >= '$cutoff'
        AND h.deleted_at IS NULL
        AND h.user_id = $user_id
    ";
    
    // 3. Personal Project Activities
    $projects_activity_query = "
        SELECT 
            'project_update' as activity_type,
            'personal_project' as source,
            CONCAT('Updated project: ', p.project_name, ' to ', p.status) as action_description,
            p.created_at as activity_timestamp,
            p.project_name as details
        FROM personal_project p
        WHERE p.created_at >= '$cutoff'
        AND p.deleted_at IS NULL
        AND p.user_id = $user_id
    ";
    
    // 4. Collaborative Group Activities
    $groups_activity_query = "
        SELECT 
            'member_join' as activity_type,
            'collaborate_group' as source,
            CONCAT('Created group: ', g.colgroup_name) as action_description,
            g.created_at as activity_timestamp,
            g.colgroup_name as details
        FROM collaborate_group g
        WHERE g.created_at >= '$cutoff'
        AND g.deleted_at IS NULL
        AND g.user_id = $user_id
    ";
    
    // 5. Collaborative Project Activities
    $colprojects_activity_query = "
        SELECT 
            'project_update' as activity_type,
            'collaborate_project' as source,
            CONCAT('Updated collab project: ', cp.colproj_name, ' to ', cp.status) as action_description,
            cp.created_at as activity_timestamp,
            cp.colproj_name as details
        FROM collaborate_project cp
        WHERE cp.created_at >= '$cutoff'
        AND cp.deleted_at IS NULL
        AND cp.user_id = $user_id
    ";
    
    // 6. Collaborative Task Activities
    $coltasks_activity_query = "
        SELECT 
            'task_complete' as activity_type,
            'collaborate_task' as source,
            CONCAT('Completed collab task: ', ct.coltask_name) as action_description,
            ct.created_at as activity_timestamp,
            ct.coltask_name as details
        FROM collaborate_task ct
        WHERE ct.status = 'done'
        AND ct.created_at >= '$cutoff'
        AND ct.deleted_at IS NULL
        AND (ct.user_id = $user_id OR ct.assigned_user_id = $user_id)
        
        UNION ALL
        
        SELECT 
            'task_create' as activity_type,
            'collaborate_task' as source,
            CONCAT('Created collab task: ', ct.coltask_name) as action_description,
            ct.created_at as activity_timestamp,
            ct.coltask_name as details
        FROM collaborate_task ct
        WHERE ct.created_at >= '$cutoff'
        AND ct.deleted_at IS NULL
        AND ct.user_id = $user_id
    ";
    
    // 7. Team Member Activities
    $teammem_activity_query = "
        SELECT 
            'member_join' as activity_type,
            'collaborate_teammem' as source,
            CONCAT('Joined as team member') as action_description,
            tm.created_at as activity_timestamp,
            CONCAT('Team ID: ', tm.teammem_id) as details
        FROM collaborate_teammem tm
        WHERE tm.created_at >= '$cutoff'
        AND tm.deleted_at IS NULL
        AND tm.user_id = $user_id
    ";
    
    $queries = [
        $todos_query, 
        $habits_activity_query, 
        $projects_activity_query, 
        $groups_activity_query, 
        $colprojects_activity_query, 
        $coltasks_activity_query,
        $teammem_activity_query
    ];
    
    foreach ($queries as $query) {
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }
    }
    
    usort($activities, function($a, $b) {
        return strtotime($b['activity_timestamp']) - strtotime($a['activity_timestamp']);
    });
    
    return $activities;
}

// Get selected user activities if a user is clicked
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$selected_user = null;
$user_activities = [];

if ($selected_user_id > 0) {
    foreach ($users_list as $user) {
        if ($user['user_id'] == $selected_user_id) {
            $selected_user = $user;
            break;
        }
    }
    if ($selected_user) {
        $user_activities = getUserActivityLogs($conn, $selected_user_id, 90);
    }
}

// If no user selected, redirect back to admin dashboard
if (!$selected_user) {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Details | DoTrack Admin</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles specific to activity monitor */
        .activity-timeline {
            max-height: 500px;
            overflow-y: auto;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-content p {
            margin: 0 0 5px;
            font-size: 0.9rem;
        }

        .timeline-time {
            font-size: 0.7rem;
            color: #888;
        }

        .badge-activity {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .badge-task-create { background: #2196f3; color: white; }
        .badge-task-complete { background: var(--success-color); color: white; }
        .badge-habit-complete { background: #9c27b0; color: white; }
        .badge-project-update { background: #ff9800; color: white; }
        .badge-member-join { background: #00bcd4; color: white; }

        .source-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .source-personal { background: #e3f2fd; color: #1976d2; }
        .source-collab { background: #f3e5f5; color: #7b1fa2; }

        .user-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .user-stats {
            display: flex;
            gap: 30px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .user-stat-item {
            text-align: center;
            background: #f8f5f0;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .user-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
        }

        .user-stat-label {
            font-size: 12px;
            color: #7a6a5c;
        }

        .back-button {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: #5a6268;
        }
    </style>
</head>
<body class="admin-container">
    <header class="admin-header">
        <h1><i class='bx bx-user-detail'></i> User Activity Details</h1>
        <div class="user-menu">
            <span><i class='bx bx-user'></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="admin_dashboard.php"><i class='bx bx-grid-alt'></i> Dashboard</a>
            <a href="../auth/logout.php?redirect=index.php"><i class='bx bx-log-out'></i> Logout</a>
        </div>
    </header>

    <nav class="admin-sidebar">
        <div class="sidebar-brand">
            <h2><i class='bx bx-data'></i> DoTrack Admin</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class='bx bx-grid-alt'></i> Dashboard</a></li>
            <li><a href="manage_users.php"><i class='bx bx-user'></i> Users</a></li>
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

    <main class="admin-main">
        <div class="dashboard-card">
            <div class="user-detail-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class='bx bx-user'></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: var(--text-dark);"><?php echo htmlspecialchars($selected_user['username']); ?></h2>
                        <p style="margin: 5px 0 0; color: #7a6a5c;"><?php echo htmlspecialchars($selected_user['email']); ?> • Status: <?php echo ucfirst($selected_user['status']); ?></p>
                        <p style="margin: 5px 0 0; font-size: 12px; color: #7a6a5c;">Joined: <?php echo date('M d, Y', strtotime($selected_user['created_at'])); ?></p>
                    </div>
                </div>
                <a href="admin_dashboard.php" class="back-button">
                    <i class='bx bx-arrow-back'></i> Back to Dashboard
                </a>
            </div>
            
            <div class="user-stats">
                <div class="user-stat-item">
                    <div class="user-stat-number"><?php echo count($user_activities); ?></div>
                    <div class="user-stat-label">Total Activities</div>
                </div>
                <div class="user-stat-item">
                    <div class="user-stat-number"><?php echo count(array_filter($user_activities, function($a) { return $a['activity_type'] === 'task_complete'; })); ?></div>
                    <div class="user-stat-label">Tasks Completed</div>
                </div>
                <div class="user-stat-item">
                    <div class="user-stat-number"><?php echo count(array_filter($user_activities, function($a) { return $a['activity_type'] === 'habit_complete'; })); ?></div>
                    <div class="user-stat-label">Habits Created</div>
                </div>
                <div class="user-stat-item">
                    <div class="user-stat-number"><?php echo count(array_filter($user_activities, function($a) { return $a['activity_type'] === 'project_update'; })); ?></div>
                    <div class="user-stat-label">Projects Updated</div>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h3 style="margin-bottom: 20px;"><i class='bx bx-time-five'></i> User Activity History</h3>
            <div class="activity-timeline">
                <?php if (empty($user_activities)): ?>
                <p style="text-align: center; padding: 40px;">No activities found for this user</p>
                <?php else: ?>
                <?php foreach ($user_activities as $activity): ?>
                <?php
                    $icon_map = [
                        'task_create' => 'bx bx-task',
                        'task_complete' => 'bx bx-check-circle',
                        'habit_complete' => 'bx bx-calendar-check',
                        'project_update' => 'bx bx-folder',
                        'member_join' => 'bx bx-group'
                    ];
                    $badge_map = [
                        'task_create' => 'badge-task-create',
                        'task_complete' => 'badge-task-complete',
                        'habit_complete' => 'badge-habit-complete',
                        'project_update' => 'badge-project-update',
                        'member_join' => 'badge-member-join'
                    ];
                    $icon = $icon_map[$activity['activity_type']] ?? 'bx bx-task';
                    $badge = $badge_map[$activity['activity_type']] ?? 'badge-task-create';
                    $display_type = str_replace('_', ' ', $activity['activity_type']);
                    $source_class = ($activity['source'] === 'personal_todo' || $activity['source'] === 'personal_habit' || $activity['source'] === 'personal_project') ? 'source-personal' : 'source-collab';
                    $source_label = str_replace('_', ' ', $activity['source']);
                ?>
                <div class="timeline-item">
                    <div class="timeline-icon"><i class='<?php echo $icon; ?>'></i></div>
                    <div class="timeline-content">
                        <p>
                            <strong><?php echo htmlspecialchars($selected_user['username']); ?></strong> 
                            <?php echo htmlspecialchars($activity['action_description']); ?>
                            <span class="badge-activity <?php echo $badge; ?>"><?php echo $display_type; ?></span>
                            <span class="source-badge <?php echo $source_class; ?>"><?php echo $source_label; ?></span>
                        </p>
                        <div class="timeline-time"><i class='bx bx-time'></i> <?php echo date('M d, Y h:i A', strtotime($activity['activity_timestamp'])); ?></div>
                        <?php if (!empty($activity['details'])): ?>
                        <small><?php echo htmlspecialchars($activity['details']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="admin-footer">
        <div>DoTrack Database Admin Panel</div>
        <div>Version 1.0.0</div>
        <div><?= date('Y') ?> &copy; All Rights Reserved</div>
    </footer>
</body>
</html>