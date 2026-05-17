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

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==============================================
// DATABASE QUERIES - Following admin_dashboard.php patterns
// ==============================================

// Total Users (active, not deleted) - Excluding admin
$user_result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE deleted_at IS NULL AND role = 'user'");
$total_users = $user_result->fetch_assoc()['total'] ?? 0;

// Total Personal Projects
$personal_projects_result = $conn->query("SELECT COUNT(*) AS total FROM personal_project WHERE deleted_at IS NULL");
$total_personal_projects = $personal_projects_result->fetch_assoc()['total'] ?? 0;

// Get all groups
$groups_result = $conn->query("
    SELECT g.*, u.username as created_by 
    FROM collaborate_group g
    JOIN users u ON g.user_id = u.user_id
    WHERE g.deleted_at IS NULL
    ORDER BY g.created_at DESC
");
$all_groups = $groups_result->fetch_all(MYSQLI_ASSOC);

// Get all projects
$projects_result = $conn->query("
    SELECT 
        p.colproj_id, p.colproj_name, p.description, p.status, p.created_at,
        g.colgroup_name,
        u.username AS created_by
    FROM collaborate_project p
    LEFT JOIN collaborate_group g ON p.colgroup_id = g.colgroup_id AND g.deleted_at IS NULL
    JOIN users u ON p.user_id = u.user_id
    WHERE p.deleted_at IS NULL
    ORDER BY p.created_at DESC
");
$all_projects = $projects_result->fetch_all(MYSQLI_ASSOC);

// Get all user todos
$todos_result = $conn->query("
    SELECT t.*, u.username 
    FROM personal_todo t
    JOIN users u ON t.user_id = u.user_id
    WHERE t.deleted_at IS NULL
    ORDER BY t.due_date ASC
");
$all_todos = $todos_result->fetch_all(MYSQLI_ASSOC);

// Get all user habits
$habits_result = $conn->query("
    SELECT h.*, u.username 
    FROM personal_habit h
    JOIN users u ON h.user_id = u.user_id
    WHERE h.deleted_at IS NULL
    ORDER BY h.day, h.habit_name
");
$all_habits = $habits_result->fetch_all(MYSQLI_ASSOC);

// Get all team members
$teammem_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM collaborate_teammem 
    WHERE deleted_at IS NULL
");
$total_teammem = $teammem_result->fetch_assoc()['total'] ?? 0;

// Get all collaborative tasks
$coltasks_result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM collaborate_task 
    WHERE deleted_at IS NULL
");
$total_coltasks = $coltasks_result->fetch_assoc()['total'] ?? 0;

// ==============================================
// ACTIVITY MONITORING QUERIES - Excluding admin user
// ==============================================

// Get all active users for activity tracking (EXCLUDING admin)
$users_list_result = $conn->query("SELECT user_id, username, email, status, created_at FROM users WHERE deleted_at IS NULL AND role = 'user' ORDER BY username");
$users_list = $users_list_result->fetch_all(MYSQLI_ASSOC);

// Function to get activity logs from all tables for a specific user
function getUserActivityLogs($conn, $user_id, $daysBack = 90)
{
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

    usort($activities, function ($a, $b) {
        return strtotime($b['activity_timestamp']) - strtotime($a['activity_timestamp']);
    });

    return $activities;
}

// Function to get all activity logs from all tables (excluding admin)
function getActivityLogsFromDB($conn, $daysBack = 90)
{
    $cutoff = date('Y-m-d H:i:s', strtotime("-$daysBack days"));
    $activities = [];

    $todos_query = "
        SELECT 
            'task_complete' as activity_type,
            u.username,
            CONCAT('Completed task: ', t.todo_name) as action_description,
            t.created_at as activity_timestamp,
            t.todo_name as details
        FROM personal_todo t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.status = 'done' 
        AND t.created_at >= '$cutoff'
        AND t.deleted_at IS NULL
        AND u.role = 'user'
        
        UNION ALL
        
        SELECT 
            'task_create' as activity_type,
            u.username,
            CONCAT('Created task: ', t.todo_name) as action_description,
            t.created_at as activity_timestamp,
            t.todo_name as details
        FROM personal_todo t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.created_at >= '$cutoff'
        AND t.deleted_at IS NULL
        AND u.role = 'user'
    ";

    $habits_activity_query = "
        SELECT 
            'habit_complete' as activity_type,
            u.username,
            CONCAT('Created habit: ', h.habit_name) as action_description,
            h.created_at as activity_timestamp,
            h.habit_name as details
        FROM personal_habit h
        JOIN users u ON h.user_id = u.user_id
        WHERE h.created_at >= '$cutoff'
        AND h.deleted_at IS NULL
        AND u.role = 'user'
    ";

    $projects_activity_query = "
        SELECT 
            'project_update' as activity_type,
            u.username,
            CONCAT('Updated project: ', p.project_name, ' to ', p.status) as action_description,
            p.created_at as activity_timestamp,
            p.project_name as details
        FROM personal_project p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.created_at >= '$cutoff'
        AND p.deleted_at IS NULL
        AND u.role = 'user'
    ";

    $groups_activity_query = "
        SELECT 
            'member_join' as activity_type,
            u.username,
            CONCAT('Created group: ', g.colgroup_name) as action_description,
            g.created_at as activity_timestamp,
            g.colgroup_name as details
        FROM collaborate_group g
        JOIN users u ON g.user_id = u.user_id
        WHERE g.created_at >= '$cutoff'
        AND g.deleted_at IS NULL
        AND u.role = 'user'
    ";

    $colprojects_activity_query = "
        SELECT 
            'project_update' as activity_type,
            u.username,
            CONCAT('Updated collab project: ', cp.colproj_name, ' to ', cp.status) as action_description,
            cp.created_at as activity_timestamp,
            cp.colproj_name as details
        FROM collaborate_project cp
        JOIN users u ON cp.user_id = u.user_id
        WHERE cp.created_at >= '$cutoff'
        AND cp.deleted_at IS NULL
        AND u.role = 'user'
    ";

    $coltasks_activity_query = "
        SELECT 
            'task_complete' as activity_type,
            u.username,
            CONCAT('Completed collab task: ', ct.coltask_name) as action_description,
            ct.created_at as activity_timestamp,
            ct.coltask_name as details
        FROM collaborate_task ct
        JOIN users u ON ct.assigned_user_id = u.user_id
        WHERE ct.status = 'done'
        AND ct.created_at >= '$cutoff'
        AND ct.deleted_at IS NULL
        AND u.role = 'user'
        
        UNION ALL
        
        SELECT 
            'task_create' as activity_type,
            u.username,
            CONCAT('Created collab task: ', ct.coltask_name) as action_description,
            ct.created_at as activity_timestamp,
            ct.coltask_name as details
        FROM collaborate_task ct
        JOIN users u ON ct.user_id = u.user_id
        WHERE ct.created_at >= '$cutoff'
        AND ct.deleted_at IS NULL
        AND u.role = 'user'
    ";

    $teammem_activity_query = "
        SELECT 
            'member_join' as activity_type,
            u.username,
            CONCAT('Joined as team member') as action_description,
            tm.created_at as activity_timestamp,
            CONCAT('Team ID: ', tm.teammem_id) as details
        FROM collaborate_teammem tm
        JOIN users u ON tm.user_id = u.user_id
        WHERE tm.created_at >= '$cutoff'
        AND tm.deleted_at IS NULL
        AND u.role = 'user'
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

    usort($activities, function ($a, $b) {
        return strtotime($b['activity_timestamp']) - strtotime($a['activity_timestamp']);
    });

    return $activities;
}

// Get all activities (excluding admin)
$all_activities = getActivityLogsFromDB($conn, 90);

// Calculate activity statistics for current period
function calculateActivityStats($activities, $period_days)
{
    $cutoff = date('Y-m-d H:i:s', strtotime("-$period_days days"));
    $filtered = array_filter($activities, function ($activity) use ($cutoff) {
        return $activity['activity_timestamp'] >= $cutoff;
    });

    $unique_users = array_unique(array_column($filtered, 'username'));
    $total_actions = count($filtered);
    $tasks_completed = count(array_filter($filtered, function ($a) {
        return $a['activity_type'] === 'task_complete';
    }));
    $habits_completed = count(array_filter($filtered, function ($a) {
        return $a['activity_type'] === 'habit_complete';
    }));
    $collab_events = count(array_filter($filtered, function ($a) {
        return $a['activity_type'] === 'member_join';
    }));

    return [
        'unique_users' => count($unique_users),
        'total_actions' => $total_actions,
        'tasks_completed' => $tasks_completed,
        'habits_completed' => $habits_completed,
        'collab_events' => $collab_events
    ];
}

// Get user engagement ranking (excluding admin)
function getUserEngagementRanking($activities, $users, $period_days)
{
    $cutoff = date('Y-m-d H:i:s', strtotime("-$period_days days"));
    $filtered = array_filter($activities, function ($a) use ($cutoff) {
        return $a['activity_timestamp'] >= $cutoff;
    });

    $user_stats = [];
    foreach ($users as $user) {
        $user_stats[$user['username']] = [
            'user_id' => $user['user_id'],
            'total_actions' => 0,
            'tasks_completed' => 0,
            'habits_completed' => 0
        ];
    }

    foreach ($filtered as $act) {
        $username = $act['username'];
        if (isset($user_stats[$username])) {
            $user_stats[$username]['total_actions']++;
            if ($act['activity_type'] === 'task_complete') {
                $user_stats[$username]['tasks_completed']++;
            }
            if ($act['activity_type'] === 'habit_complete') {
                $user_stats[$username]['habits_completed']++;
            }
        }
    }

    $ranking = [];
    foreach ($user_stats as $username => $stats) {
        $engagement_score = 0;
        if ($stats['total_actions'] > 0) {
            $engagement_score = min(100, round(($stats['total_actions'] / 20) * 100));
        }
        $ranking[] = [
            'user_id' => $stats['user_id'],
            'username' => $username,
            'total_actions' => $stats['total_actions'],
            'tasks_completed' => $stats['tasks_completed'],
            'habits_completed' => $stats['habits_completed'],
            'engagement_score' => $engagement_score
        ];
    }

    usort($ranking, function ($a, $b) {
        return $b['total_actions'] - $a['total_actions'];
    });

    return $ranking;
}

// Get recent activities for timeline
function getRecentActivities($activities, $limit = 20)
{
    return array_slice($activities, 0, $limit);
}

// Default period (week)
$period_days = 7;
$activity_stats = calculateActivityStats($all_activities, $period_days);
$user_ranking = getUserEngagementRanking($all_activities, $users_list, $period_days);
$recent_activities = getRecentActivities($all_activities, 20);

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Monitor | DoTrack Admin</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #fffdf9 100%);
            border-radius: 20px;
            padding: 25px 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(224, 224, 224, 0.6);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 139, 92, 0.15);
        }

        .stat-card .stat-icon {
            font-size: 2.2rem;
            color: #ff8b5c;
            margin-bottom: 15px;
        }

        .stat-card .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #4a2c24;
            line-height: 1.2;
        }

        .stat-card .stat-text {
            font-size: 0.9rem;
            color: #7a6a5c;
            margin-top: 8px;
            letter-spacing: 0.5px;
        }

        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
        }

        .chart-card h3 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #4a2c24;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #ff8b5c;
            padding-left: 15px;
        }

        canvas {
            max-height: 300px;
            width: 100%;
        }

        .activity-timeline {
            max-height: 400px;
            overflow-y: auto;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ff8b5c, #ff7043);
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

        .badge-task-create {
            background: #2196f3;
            color: white;
        }

        .badge-task-complete {
            background: #4caf50;
            color: white;
        }

        .badge-habit-complete {
            background: #9c27b0;
            color: white;
        }

        .badge-project-update {
            background: #ff9800;
            color: white;
        }

        .badge-member-join {
            background: #00bcd4;
            color: white;
        }

        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .period-btn {
            padding: 8px 20px;
            border: 1px solid #e0e0e0;
            background: white;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .period-btn.active {
            background: #ff8b5c;
            color: white;
            border-color: #ff8b5c;
        }

        .engagement-table {
            width: 100%;
            border-collapse: collapse;
        }

        .engagement-table th,
        .engagement-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .engagement-table th {
            background: #ff8b5c;
            color: white;
            font-weight: 500;
        }

        .engagement-table tbody tr {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .engagement-table tbody tr:hover {
            background-color: #fff3e0;
        }

        .progress-bar {
            width: 100%;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            height: 8px;
        }

        .progress-fill {
            background: #4caf50;
            height: 100%;
            border-radius: 10px;
        }

        .dashboard-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
            margin-bottom: 30px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff8b5c, #ff7043);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 139, 92, 0.3);
        }

        .report-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .alert.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .user-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff8b5c;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ff8b5c, #ff7043);
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
            color: #ff8b5c;
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
        }

        .back-button:hover {
            background: #5a6268;
        }

        .source-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .source-personal {
            background: #e3f2fd;
            color: #1976d2;
        }

        .source-collab {
            background: #f3e5f5;
            color: #7b1fa2;
        }
    </style>
</head>

<body class="admin-container">
    <header class="admin-header">
        <h1><i class='bx bx-line-chart'></i> Activity Monitor</h1>
        <div class="user-menu">
            <span><i class='bx bx-user'></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../auth/logout.php?redirect=index.php"><i class='bx bx-log-out'></i> Logout</a>
        </div>
    </header>

    <nav class="admin-sidebar">
        <div class="sidebar-brand">
            <h2><i class='bx bx-data'></i> DoTrack Admin</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active"><i class='bx bx-grid-alt'></i> Dashboard</a></li>
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

    <main class="admin-main" style="padding: 30px; background: linear-gradient(135deg, #f5f7fa 0%, #fffdf9 100%);">

        <?php if (empty($users_list)): ?>
            <div class="alert info">
                <div><i class='bx bx-info-circle'></i> No regular users found in the system. Only admin accounts exist.</div>
            </div>
        <?php endif; ?>

        <?php if ($selected_user): ?>
            <!-- User Detail View -->
            <div class="dashboard-card">
                <div class="user-detail-header">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class='bx bx-user'></i>
                        </div>
                        <div>
                            <h2 style="margin: 0; color: #4a2c24;"><?php echo htmlspecialchars($selected_user['username']); ?></h2>
                            <p style="margin: 5px 0 0; color: #7a6a5c;"><?php echo htmlspecialchars($selected_user['email']); ?> • Status: <?php echo ucfirst($selected_user['status']); ?></p>
                            <p style="margin: 5px 0 0; font-size: 12px; color: #7a6a5c;">Joined: <?php echo date('M d, Y', strtotime($selected_user['created_at'])); ?></p>
                        </div>
                    </div>
                    <button class="back-button" onclick="window.location.href='activity_monitor.php'">
                        <i class='bx bx-arrow-back'></i> Back to Overview
                    </button>
                </div>

                <div class="user-stats">
                    <div class="user-stat-item">
                        <div class="user-stat-number"><?php echo count($user_activities); ?></div>
                        <div class="user-stat-label">Total Activities</div>
                    </div>
                    <div class="user-stat-item">
                        <div class="user-stat-number"><?php echo count(array_filter($user_activities, function ($a) {
                                                            return $a['activity_type'] === 'task_complete';
                                                        })); ?></div>
                        <div class="user-stat-label">Tasks Completed</div>
                    </div>
                    <div class="user-stat-item">
                        <div class="user-stat-number"><?php echo count(array_filter($user_activities, function ($a) {
                                                            return $a['activity_type'] === 'habit_complete';
                                                        })); ?></div>
                        <div class="user-stat-label">Habits Created</div>
                    </div>
                    <div class="user-stat-item">
                        <div class="user-stat-number"><?php echo count(array_filter($user_activities, function ($a) {
                                                            return $a['activity_type'] === 'project_update';
                                                        })); ?></div>
                        <div class="user-stat-label">Projects Updated</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3 style="margin-bottom: 20px;"><i class='bx bx-time-five'></i> User Activity History</h3>
                <div class="activity-timeline" style="max-height: 500px;">
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

        <?php else: ?>
            <!-- Overview View -->

            <div class="period-selector">
                <button class="period-btn active" data-period="week">This Week</button>
                <button class="period-btn" data-period="month">This Month</button>
                <button class="period-btn" data-period="quarter">Last 3 Months</button>
            </div>

            <div class="stats-grid" id="statsOverview">
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-user'></i></div>
                    <div class="stat-number" id="statActiveUsers"><?php echo $activity_stats['unique_users']; ?></div>
                    <div class="stat-text">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-task'></i></div>
                    <div class="stat-number" id="statTotalActions"><?php echo $activity_stats['total_actions']; ?></div>
                    <div class="stat-text">Total Actions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-check-circle'></i></div>
                    <div class="stat-number" id="statTasksCompleted"><?php echo $activity_stats['tasks_completed']; ?></div>
                    <div class="stat-text">Tasks Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-calendar-check'></i></div>
                    <div class="stat-number" id="statHabitsCompleted"><?php echo $activity_stats['habits_completed']; ?></div>
                    <div class="stat-text">Habits Created</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-group'></i></div>
                    <div class="stat-number" id="statCollabEvents"><?php echo $activity_stats['collab_events']; ?></div>
                    <div class="stat-text">Groups/Teams Created</div>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-card">
                    <h3><i class='bx bx-pie-chart'></i> Activity Distribution</h3>
                    <canvas id="activityPieChart" width="400" height="300"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class='bx bx-trending-up'></i> Productivity Trends (Last 7 days)</h3>
                    <canvas id="trendBarChart" width="400" height="300"></canvas>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-card">
                    <h3><i class='bx bx-group'></i> User Engagement Ranking <small style="font-weight: normal;">(Click on a user to view details)</small></h3>
                    <div style="max-height: 320px; overflow-y: auto;">
                        <table class="engagement-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Actions</th>
                                    <th>Tasks</th>
                                    <th>Habits</th>
                                    <th>Engagement</th>
                                </tr>
                            </thead>
                            <tbody id="engagementBody">
                                <?php foreach ($user_ranking as $user): ?>
                                    <tr onclick="window.location.href='activity_monitor.php?user_id=<?php echo $user['user_id']; ?>'" style="cursor: pointer;">
                                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                        <td><?php echo $user['total_actions']; ?></td>
                                        <td><?php echo $user['tasks_completed']; ?></td>
                                        <td><?php echo $user['habits_completed']; ?></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $user['engagement_score']; ?>%;"></div>
                                            </div>
                                            <span style="font-size:0.7rem;"><?php echo $user['engagement_score']; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($user_ranking)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No activity data available for regular users</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="chart-card">
                    <h3><i class='bx bx-time-five'></i> Recent Activity Timeline</h3>
                    <div class="activity-timeline" id="activityTimeline">
                        <?php foreach ($recent_activities as $activity): ?>
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
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-icon"><i class='<?php echo $icon; ?>'></i></div>
                                <div class="timeline-content">
                                    <p><strong><?php echo htmlspecialchars($activity['username']); ?></strong> <?php echo htmlspecialchars($activity['action_description']); ?> <span class="badge-activity <?php echo $badge; ?>"><?php echo $display_type; ?></span></p>
                                    <div class="timeline-time"><i class='bx bx-time'></i> <?php echo date('M d, Y h:i A', strtotime($activity['activity_timestamp'])); ?></div>
                                    <?php if (!empty($activity['details'])): ?>
                                        <small><?php echo htmlspecialchars($activity['details']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($recent_activities)): ?>
                            <p style="text-align: center; padding: 20px;">No recent activities found for regular users</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3 style="margin-bottom: 20px;"><i class='bx bx-file'></i> Generate Reports</h3>
                <div class="report-buttons">
                    <button class="btn btn-primary" id="downloadWeeklyReport"><i class='bx bx-download'></i> Download Weekly Report (CSV)</button>
                    <button class="btn btn-primary" id="downloadMonthlyReport"><i class='bx bx-download'></i> Download Monthly Report (CSV)</button>
                </div>
                <div id="reportPreview" style="margin-top: 15px; padding: 15px; background: #f9f6f0; border-radius: 12px; font-size: 0.9rem;">
                    <p><strong>Report Summary:</strong> Select a period to view insights.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="admin-footer">
        <div>DoTrack Database Admin Panel</div>
        <div>Version 1.0.0</div>
        <div><?= date('Y') ?> &copy; All Rights Reserved</div>
    </footer>

    <?php if (!$selected_user): ?>
        <script>
            // Only run chart-related code in overview mode
            const activitiesData = <?php echo json_encode($all_activities); ?>;
            const usersData = <?php echo json_encode($users_list); ?>;

            let pieChart, barChart;

            function filterActivitiesByPeriod(period) {
                let days = 7;
                if (period === 'month') days = 30;
                if (period === 'quarter') days = 90;

                const cutoff = new Date();
                cutoff.setDate(cutoff.getDate() - days);

                return activitiesData.filter(activity => {
                    return new Date(activity.activity_timestamp) >= cutoff;
                });
            }

            function getActivityTypeCounts(activities) {
                const counts = {
                    'Task Creation': 0,
                    'Task Completion': 0,
                    'Habit Creation': 0,
                    'Project Update': 0,
                    'Group Creation': 0
                };

                activities.forEach(act => {
                    switch (act.activity_type) {
                        case 'task_create':
                            counts['Task Creation']++;
                            break;
                        case 'task_complete':
                            counts['Task Completion']++;
                            break;
                        case 'habit_complete':
                            counts['Habit Creation']++;
                            break;
                        case 'project_update':
                            counts['Project Update']++;
                            break;
                        case 'member_join':
                            counts['Group Creation']++;
                            break;
                    }
                });
                return counts;
            }

            function getDailyTrend(activities) {
                const last7Days = [];
                for (let i = 6; i >= 0; i--) {
                    const d = new Date();
                    d.setDate(d.getDate() - i);
                    d.setHours(0, 0, 0, 0);
                    last7Days.push(d);
                }

                const labels = last7Days.map(d => d.toLocaleDateString('en-US', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric'
                }));
                const taskCompletions = new Array(7).fill(0);
                const habitCompletions = new Array(7).fill(0);

                activities.forEach(act => {
                    if (act.activity_type === 'task_complete' || act.activity_type === 'habit_complete') {
                        const actDate = new Date(act.activity_timestamp);
                        actDate.setHours(0, 0, 0, 0);
                        const idx = last7Days.findIndex(d => d.getTime() === actDate.getTime());
                        if (idx !== -1) {
                            if (act.activity_type === 'task_complete') taskCompletions[idx]++;
                            else habitCompletions[idx]++;
                        }
                    }
                });

                return {
                    labels,
                    taskCompletions,
                    habitCompletions
                };
            }

            function getStatsOverview(activities, period) {
                let days = 7;
                if (period === 'month') days = 30;
                if (period === 'quarter') days = 90;

                const cutoff = new Date();
                cutoff.setDate(cutoff.getDate() - days);
                const filtered = activities.filter(a => new Date(a.activity_timestamp) >= cutoff);

                const uniqueUsers = new Set(filtered.map(a => a.username)).size;
                const totalActions = filtered.length;
                const tasksCompleted = filtered.filter(a => a.activity_type === 'task_complete').length;
                const habitsCompleted = filtered.filter(a => a.activity_type === 'habit_complete').length;
                const collabEvents = filtered.filter(a => a.activity_type === 'member_join').length;

                return {
                    uniqueUsers,
                    totalActions,
                    tasksCompleted,
                    habitsCompleted,
                    collabEvents
                };
            }

            function updatePeriod(period) {
                const filteredActivities = filterActivitiesByPeriod(period);

                // Update stats overview
                const stats = getStatsOverview(activitiesData, period);
                document.getElementById('statActiveUsers').innerText = stats.uniqueUsers;
                document.getElementById('statTotalActions').innerText = stats.totalActions;
                document.getElementById('statTasksCompleted').innerText = stats.tasksCompleted;
                document.getElementById('statHabitsCompleted').innerText = stats.habitsCompleted;
                document.getElementById('statCollabEvents').innerText = stats.collabEvents;

                // Update pie chart
                const typeCounts = getActivityTypeCounts(filteredActivities);
                if (pieChart) pieChart.destroy();
                const pieCtx = document.getElementById('activityPieChart').getContext('2d');
                pieChart = new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: Object.keys(typeCounts),
                        datasets: [{
                            data: Object.values(typeCounts),
                            backgroundColor: ['#ff8b5c', '#ffb74d', '#66bb6a', '#42a5f5', '#ab47bc'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });

                // Update bar chart
                const trend = getDailyTrend(filteredActivities);
                if (barChart) barChart.destroy();
                const barCtx = document.getElementById('trendBarChart').getContext('2d');
                barChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: trend.labels,
                        datasets: [{
                                label: 'Tasks Completed',
                                data: trend.taskCompletions,
                                backgroundColor: '#ff8b5c',
                                borderRadius: 6
                            },
                            {
                                label: 'Habits Created',
                                data: trend.habitCompletions,
                                backgroundColor: '#9ec1a3',
                                borderRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        }
                    }
                });

                // Update report preview
                const totalUnique = stats.uniqueUsers;
                const avgActions = stats.totalActions / (totalUnique || 1);
                const topPerformer = document.querySelector('.engagement-table tbody tr:first-child td:first-child strong')?.innerText || 'N/A';
                document.getElementById('reportPreview').innerHTML = `
                <p><strong>${period.toUpperCase()} Summary:</strong> ${stats.totalActions} total actions | ${stats.tasksCompleted} tasks done | ${stats.habitsCompleted} habits | ${totalUnique} active users | Avg ${avgActions.toFixed(1)} actions/user</p>
                <p><strong>Top performer:</strong> ${topPerformer}</p>
            `;
            }

            function downloadReport(periodType) {
                let days = periodType === 'weekly' ? 7 : 30;
                const cutoff = new Date();
                cutoff.setDate(cutoff.getDate() - days);
                const filtered = activitiesData.filter(a => new Date(a.activity_timestamp) >= cutoff);

                // Calculate engagement stats for report
                const userStats = {};
                usersData.forEach(user => {
                    userStats[user.username] = {
                        total: 0,
                        tasksDone: 0,
                        habits: 0
                    };
                });
                filtered.forEach(act => {
                    if (userStats[act.username]) {
                        userStats[act.username].total++;
                        if (act.activity_type === 'task_complete') userStats[act.username].tasksDone++;
                        if (act.activity_type === 'habit_complete') userStats[act.username].habits++;
                    }
                });
                const engagement = [];
                for (const [username, stats] of Object.entries(userStats)) {
                    const engagementScore = stats.total > 0 ? Math.min(100, Math.round((stats.total / 20) * 100)) : 0;
                    engagement.push({
                        username: username,
                        totalActions: stats.total,
                        tasksDone: stats.tasksDone,
                        habitsCompleted: stats.habits,
                        engagementScore: engagementScore
                    });
                }
                engagement.sort((a, b) => b.totalActions - a.totalActions);

                let csvRows = [
                    ["Report Type", periodType.toUpperCase()],
                    ["Generated", new Date().toLocaleString()],
                    []
                ];
                csvRows.push(["User", "Total Actions", "Tasks Completed", "Habits", "Engagement Score"]);
                engagement.forEach(e => {
                    csvRows.push([e.username, e.totalActions, e.tasksDone, e.habitsCompleted, e.engagementScore + '%']);
                });
                csvRows.push([], ["Detailed Activity Log"]);
                csvRows.push(["User", "Activity Type", "Description", "Timestamp"]);
                filtered.slice(0, 200).forEach(act => {
                    csvRows.push([act.username, act.activity_type, act.action_description, new Date(act.activity_timestamp).toLocaleString()]);
                });

                const csvContent = csvRows.map(row => row.join(",")).join("\n");
                const blob = new Blob([csvContent], {
                    type: "text/csv"
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `dotrack_${periodType}_report_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            // Event listeners
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const period = btn.dataset.period;
                    updatePeriod(period);
                });
            });

            document.getElementById('downloadWeeklyReport').addEventListener('click', () => downloadReport('weekly'));
            document.getElementById('downloadMonthlyReport').addEventListener('click', () => downloadReport('monthly'));

            // Initialize with week data
            updatePeriod('week');
        </script>
    <?php endif; ?>
</body>

</html>