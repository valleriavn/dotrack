<?php
session_start();
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: /dotrack/auth/loginandsignup.php");
  exit();
}

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : '';
$successMessage = '';
$errorMessage = '';
$user_id = $_SESSION['user_id'];

try {
  $stmt = $conn->prepare("SELECT colgroup_id FROM collaborate_teammem WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 0 && (!isset($_GET['tab']) || $_GET['tab'] !== 'create_join')) {
    header("Location: /dotrack/users/createjoin.php");
    exit();
  }
  $stmt->close();
} catch (Exception $e) {
  $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
  try {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      throw new Exception("Invalid CSRF token");
    }
    $del_index = (int)$_POST['delete_project_index'];
    $stmt = $conn->prepare("UPDATE collaborate_project SET deleted_at = NOW() WHERE colproj_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_index, $user_id);
    $stmt->execute();
    $successMessage = "Project deleted successfully";
  } catch (Exception $e) {
    $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
  }
  header("Location: /dotrack/users/collaborate.php?tab=group_projects");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_project'])) {
  try {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      throw new Exception("Invalid CSRF token");
    }
    $conn->begin_transaction();

    $edit_index = (int)$_POST['edit_project_index'];
    $name = trim($_POST['edit_project_name']);
    $description = trim($_POST['edit_project_description']);
    $leader_id = (int)$_POST['edit_project_leader'];
    $start = date('Y-m-d', strtotime($_POST['edit_project_start']));
    $due = date('Y-m-d', strtotime($_POST['edit_project_due']));
    $group_id = (int)$_POST['edit_group_id'];
    $status = $_POST['edit_project_status'];

    if (empty($name) || empty($description) || empty($leader_id) || empty($group_id)) {
      throw new Exception("All fields are required");
    }

    $stmt = $conn->prepare("UPDATE collaborate_project SET colproj_name = ?, description = ?, colgroup_id = ?, start_date = ?, due_date = ?, status = ? WHERE colproj_id = ? AND user_id = ?");
    $stmt->bind_param("ssisssii", $name, $description, $group_id, $start, $due, $status, $edit_index, $user_id);
    $stmt->execute();

    $teammem_stmt = $conn->prepare("UPDATE collaborate_teammem SET user_id = ? WHERE colproj_id = ? AND colgroup_id = ?");
    $teammem_stmt->bind_param("iii", $leader_id, $edit_index, $group_id);
    $teammem_stmt->execute();

    $task_ids = $_POST['task_id'] ?? [];
    $task_titles = $_POST['task_title'] ?? [];
    $task_starts = $_POST['task_start'] ?? [];
    $task_dues = $_POST['task_due'] ?? [];
    $task_members = $_POST['task_member'] ?? [];
    $task_statuses = $_POST['task_status'] ?? [];
    $subtask_titles = $_POST['subtask_title'] ?? [];
    $existing_task_ids = [];

    $insert_task_stmt = $conn->prepare("INSERT INTO collaborate_task (user_id, coltask_name, colproj_id, start_date, due_date, assigned_user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $update_task_stmt = $conn->prepare("UPDATE collaborate_task SET coltask_name = ?, start_date = ?, due_date = ?, assigned_user_id = ?, status = ? WHERE coltask_id = ?");
    $subtask_stmt = $conn->prepare("UPDATE collaborate_task SET colsubtask_name = ? WHERE coltask_id = ?");

    foreach ($task_titles as $i => $task_title) {
      $task_title = trim($task_title);
      if (empty($task_title)) continue;

      $task_id = isset($task_ids[$i]) && !empty($task_ids[$i]) ? (int)$task_ids[$i] : null;
      $task_start = date('Y-m-d', strtotime($task_starts[$i]));
      $task_due = date('Y-m-d', strtotime($task_dues[$i]));
      $assigned_user_id = !empty($task_members[$i]) ? (int)$task_members[$i] : null;
      $task_status = $task_statuses[$i] ?? 'not started';

      if ($task_id) {
        $update_task_stmt->bind_param("sssisi", $task_title, $task_start, $task_due, $assigned_user_id, $task_status, $task_id);
        $update_task_stmt->execute();
      } else {
        $insert_task_stmt->bind_param("isissis", $user_id, $task_title, $edit_index, $task_start, $task_due, $assigned_user_id, $task_status);
        $insert_task_stmt->execute();
        $task_id = $conn->insert_id;
      }

      $existing_task_ids[] = $task_id;

      $subtask_list = '';
      if (isset($subtask_titles[$i]) && is_array($subtask_titles[$i])) {
        $subtasks = array_filter(array_map('trim', $subtask_titles[$i]));
        if (!empty($subtasks)) {
          $subtask_list = implode(',', $subtasks);
        }
      }
      $subtask_stmt->bind_param("si", $subtask_list, $task_id);
      $subtask_stmt->execute();
    }

    if (!empty($existing_task_ids)) {
      $placeholders = implode(',', array_fill(0, count($existing_task_ids), '?'));
      $delete_stmt = $conn->prepare("UPDATE collaborate_task SET deleted_at = NOW() WHERE colproj_id = ? AND coltask_id NOT IN ($placeholders)");
      $types = str_repeat('i', count($existing_task_ids) + 1);
      $params = array_merge([$edit_index], $existing_task_ids);
      $delete_stmt->bind_param($types, ...$params);
      $delete_stmt->execute();
    } else {
      $delete_stmt = $conn->prepare("UPDATE collaborate_task SET deleted_at = NOW() WHERE colproj_id = ?");
      $delete_stmt->bind_param("i", $edit_index);
      $delete_stmt->execute();
    }

    $conn->commit();
    $successMessage = "Project updated successfully";
  } catch (Exception $e) {
    $conn->rollback();
    $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
  }
  header("Location: collaborate.php?tab=group_projects");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $errorMessage = "Invalid CSRF token";
  } elseif (isset($_POST['add_project'])) {
    try {
      $conn->begin_transaction();

      $name = trim($_POST['project_name']);
      $description = trim($_POST['project_description']);
      $leader_id = (int)$_POST['project_leader'];
      $start = date('Y-m-d', strtotime($_POST['project_start']));
      $due = date('Y-m-d', strtotime($_POST['project_due']));
      $group_id = (int)$_POST['group_id'];
      $status = $_POST['project_status'];

      if (empty($name) || empty($description) || empty($leader_id) || empty($group_id)) {
        throw new Exception("All fields required");
      }

      $stmt = $conn->prepare("INSERT INTO collaborate_project (user_id, colproj_name, description, colgroup_id, start_date, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
      $stmt->bind_param("ississs", $user_id, $name, $description, $group_id, $start, $due, $status);
      $stmt->execute();
      $project_id = $conn->insert_id;

      $teammem_stmt = $conn->prepare("INSERT INTO collaborate_teammem (user_id, colproj_id, colgroup_id, created_at) VALUES (?, ?, ?, NOW())");
      $teammem_stmt->bind_param("iii", $user_id, $project_id, $group_id);
      $teammem_stmt->execute();

      if (!empty($_POST['task_title'])) {
        $task_stmt = $conn->prepare("INSERT INTO collaborate_task (user_id, coltask_name, colproj_id, start_date, due_date, assigned_user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $subtask_stmt = $conn->prepare("UPDATE collaborate_task SET colsubtask_name = ? WHERE coltask_id = ?");

        foreach ($_POST['task_title'] as $i => $task_title) {
          $task_title = trim($task_title);
          if (empty($task_title)) continue;

          $task_start = date('Y-m-d', strtotime($_POST['task_start'][$i]));
          $task_due = date('Y-m-d', strtotime($_POST['task_due'][$i]));
          $assigned_user_id = !empty($_POST['task_member'][$i]) ? (int)$_POST['task_member'][$i] : null;
          $task_status = $_POST['task_status'][$i] ?? 'not started';

          $task_stmt->bind_param("isissis", $user_id, $task_title, $project_id, $task_start, $task_due, $assigned_user_id, $task_status);
          $task_stmt->execute();
          $task_id = $conn->insert_id;

          $subtask_list = '';
          if (!empty($_POST['subtask_title'][$i]) && is_array($_POST['subtask_title'][$i])) {
            $subtasks = array_filter(array_map('trim', $_POST['subtask_title'][$i]));
            if (!empty($subtasks)) {
              $subtask_list = implode(',', $subtasks);
            }
          }
          $subtask_stmt->bind_param("si", $subtask_list, $task_id);
          $subtask_stmt->execute();
        }
      }

      $conn->commit();
      $successMessage = "Project created successfully";
    } catch (Exception $e) {
      $conn->rollback();
      $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
    }
    header("Location: /dotrack/users/collaborate.php?tab=group_projects");
    exit;
  } elseif (isset($_POST['add_member'])) {
    try {
      $email = trim($_POST['member_email']);
      $project_id = (int)$_POST['project_id'];

      $user_stmt = $conn->prepare("SELECT user_id, username FROM users WHERE email = ? AND role != 'admin'");
      $user_stmt->bind_param("s", $email);
      $user_stmt->execute();
      $result = $user_stmt->get_result();

      if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        $member_id = $member['user_id'];

        $check_stmt = $conn->prepare("SELECT teammem_id FROM collaborate_teammem WHERE user_id = ? AND colproj_id = ? AND deleted_at IS NULL");
        $check_stmt->bind_param("ii", $member_id, $project_id);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
          $errorMessage = "User is already a member";
        } else {
          $proj_stmt = $conn->prepare("SELECT colgroup_id FROM collaborate_project WHERE colproj_id = ?");
          $proj_stmt->bind_param("i", $project_id);
          $proj_stmt->execute();
          $group_id = $proj_stmt->get_result()->fetch_assoc()['colgroup_id'];

          $stmt = $conn->prepare("INSERT INTO collaborate_teammem (user_id, colproj_id, colgroup_id, created_at) VALUES (?, ?, ?, NOW())");
          $stmt->bind_param("iii", $member_id, $project_id, $group_id);
          $stmt->execute();
          $successMessage = "Team member added successfully";
        }
      } else {
        $errorMessage = "Email not found";
      }
    } catch (Exception $e) {
      $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
    }
    header("Location: /dotrack/users/collaborate.php?tab=team_members");
    exit;
  } elseif (isset($_POST['delete_member'])) {
    try {
      $member_id = (int)$_POST['delete_member_index'];
      $stmt = $conn->prepare("UPDATE collaborate_teammem SET deleted_at = NOW() WHERE teammem_id = ?");
      $stmt->bind_param("i", $member_id);
      $stmt->execute();
      $successMessage = "Team member deleted successfully";
    } catch (Exception $e) {
      $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
    }
    header("Location: /dotrack/users/collaborate.php?tab=team_members");
    exit;
  }
}

$users = [];
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role != 'admin'");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$groups = [];
$stmt = $conn->prepare("SELECT DISTINCT g.colgroup_id, g.colgroup_name FROM collaborate_group g LEFT JOIN collaborate_teammem tm ON g.colgroup_id = tm.colgroup_id WHERE (g.user_id = ? OR tm.user_id = ?) AND g.deleted_at IS NULL");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$team_members = [];
$stmt = $conn->prepare("SELECT tm.teammem_id, tm.colproj_id, tm.user_id, p.colproj_name, u.username, u.email FROM collaborate_teammem tm LEFT JOIN collaborate_project p ON tm.colproj_id = p.colproj_id LEFT JOIN users u ON tm.user_id = u.user_id WHERE tm.deleted_at IS NULL AND p.colgroup_id IN (SELECT colgroup_id FROM collaborate_teammem WHERE user_id = ?)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$team_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$projects = [];
$stmt = $conn->prepare("SELECT p.colproj_id, p.colproj_name, p.description, p.start_date, p.due_date, p.colgroup_id, p.status, u.username AS leader_name, u.user_id AS leader_id FROM collaborate_project p LEFT JOIN users u ON p.user_id = u.user_id WHERE p.colgroup_id IN (SELECT colgroup_id FROM collaborate_teammem WHERE user_id = ?) AND p.deleted_at IS NULL ORDER BY p.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$task_stmt = $conn->prepare("SELECT t.coltask_id, t.coltask_name, t.start_date, t.due_date, t.colsubtask_name, u.username AS assigned_username, t.assigned_user_id, t.status FROM collaborate_task t LEFT JOIN users u ON t.assigned_user_id = u.user_id WHERE t.colproj_id = ? AND t.deleted_at IS NULL ORDER BY t.created_at DESC");
foreach ($projects as &$project) {
  $task_stmt->bind_param("i", $project['colproj_id']);
  $task_stmt->execute();
  $tasks = $task_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($tasks as &$task) {
    if ($task['colsubtask_name']) {
      $subtask_names = explode(',', $task['colsubtask_name']);
      $task['subtasks'] = [];
      foreach ($subtask_names as $name) {
        $task['subtasks'][] = ['title' => trim($name)];
      }
    } else {
      $task['subtasks'] = [];
    }
  }
  $project['tasks'] = $tasks;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - Collaborate Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/dotrack/assets/css/collaborate.css">
</head>

<body>

  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
      <span class="site-title">Collaborate</span>
    </div>
    <nav class="main-nav">
      <a href="/dotrack/home.php" class="nav-link">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-4H9v4a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2z" />
        </svg>
        Home
      </a>
      <a href="/dotrack/users/mode.php" class="nav-link">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="9" />
          <rect x="14" y="3" width="7" height="5" />
          <rect x="14" y="12" width="7" height="9" />
          <rect x="3" y="16" width="7" height="5" />
        </svg>
        Mode
      </a>
      <a href="/dotrack/account.php" class="nav-link">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10" />
          <path d="M12 14c2 0 4 1 4 3v2H8v-2c0-2 2-3 4-3z" />
          <circle cx="12" cy="8" r="3" />
        </svg>
        Account
      </a>
      <a href="/dotrack/about.php" class="nav-link">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
            <a href="?tab=group_projects" class="card-button d-block text-center">
              <i class='bx bxs-group display-1 mb-3'></i>
              <h2>Group Projects</h2>
              <p class="mb-0">Manage and track team projects</p>
            </a>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <a href="?tab=team_members" class="card-button d-block text-center">
              <i class='bx bxs-user-account display-1 mb-3'></i>
              <h2>Team Members</h2>
              <p class="mb-0">Manage your team</p>
            </a>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <a href="/dotrack/users/createjoin.php" class="card-button d-block text-center">
              <i class='bx bx-group display-1 mb-3'></i>
              <h2>Create/Join Group</h2>
              <p class="mb-0">Start or join a group</p>
            </a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="container-fluid px-4">
        <div class="row">
          <div class="col-auto px-0 position-relative">
            <div class="sidebar">
              <a href="?tab=group_projects" class="tab-btn <?php echo $tab == 'group_projects' ? 'active' : ''; ?>">
                <i class='bx bxs-group'></i>
                <span class="label">Group Projects</span>
              </a>
              <a href="?tab=team_members" class="tab-btn <?php echo $tab == 'team_members' ? 'active' : ''; ?>">
                <i class='bx bxs-user-account'></i>
                <span class="label">Team Members</span>
              </a>
              <a href="/dotrack/users/createjoin.php" class="tab-btn">
                <i class='bx bx-group'></i>
                <span class="label">Create/Join Group</span>
              </a>
            </div>
          </div>
          <div class="col">
            <div class="content">
              <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($successMessage); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
              <?php endif; ?>
              <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($errorMessage); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
              <?php endif; ?>

              <?php if ($tab == 'group_projects'): ?>
                <h2 class="mb-4">Group Projects</h2>

                <div class="card bg-dark mb-4">
                  <div class="card-body">
                    <h5 class="card-title text-white">Add New Project</h5>
                    <form method="POST" id="addProjectForm">
                      <input type="hidden" name="add_project" value="1">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                      <div class="row g-3">
                        <div class="col-md-6"><input type="text" name="project_name" class="form-control" placeholder="Project Name" required></div>
                        <div class="col-md-6"><input type="text" name="project_description" class="form-control" placeholder="Description" required></div>
                        <div class="col-md-3">
                          <select name="project_leader" class="form-select" required>
                            <option value="">Select Leader</option>
                            <?php foreach ($users as $user): ?>
                              <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <select name="group_id" class="form-select" required>
                            <option value="">Select Group</option>
                            <?php foreach ($groups as $group): ?>
                              <option value="<?php echo $group['colgroup_id']; ?>"><?php echo htmlspecialchars($group['colgroup_name']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <select name="project_status" class="form-select" required>
                            <option value="not started">Not Started</option>
                            <option value="in progress">In Progress</option>
                            <option value="on hold">On Hold</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="done">Done</option>
                          </select>
                        </div>
                        <div class="col-md-3"><input type="date" name="project_start" class="form-control" required></div>
                        <div class="col-md-3"><input type="date" name="project_due" class="form-control" required></div>
                      </div>
                      <div class="mt-3">
                        <h6 class="text-white">Tasks</h6>
                        <div id="tasks-container"></div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addTask()">+ Add Task</button>
                      </div>
                      <button type="submit" class="btn btn-primary mt-3">Create Project</button>
                    </form>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Project</th>
                        <th>Description</th>
                        <th>Leader</th>
                        <th>Start</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Tasks</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($projects as $project): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($project['colproj_name']); ?></td>
                          <td><?php echo htmlspecialchars($project['description']); ?></td>
                          <td><?php echo htmlspecialchars($project['leader_name']); ?></td>
                          <td><?php echo htmlspecialchars($project['start_date']); ?></td>
                          <td><?php echo htmlspecialchars($project['due_date']); ?></td>
                          <td><span class="badge bg-<?php echo $project['status'] == 'done' ? 'success' : ($project['status'] == 'in progress' ? 'primary' : 'secondary'); ?>"><?php echo ucfirst($project['status']); ?></span></td>
                          <td>
                            <?php if (!empty($project['tasks'])): ?>
                              <ul class="list-unstyled mb-0">
                                <?php foreach ($project['tasks'] as $task): ?>
                                  <li><small><?php echo htmlspecialchars($task['coltask_name']); ?> - <?php echo htmlspecialchars($task['assigned_username'] ?: 'Unassigned'); ?></small></li>
                                <?php endforeach; ?>
                              </ul>
                              <?php else: ?>No tasks<?php endif; ?>
                          </td>
                          <td>
                            <a href="?tab=group_projects&edit_project=<?php echo $project['colproj_id']; ?>" class="btn btn-sm btn-warning"><i class='bx bx-edit'></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this project?');">
                              <input type="hidden" name="delete_project" value="1">
                              <input type="hidden" name="delete_project_index" value="<?php echo $project['colproj_id']; ?>">
                              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                              <button type="submit" class="btn btn-sm btn-danger"><i class='bx bx-trash'></i></button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

              <?php elseif ($tab == 'team_members'): ?>
                <h2 class="mb-4">Team Members</h2>

                <div class="card bg-dark mb-4">
                  <div class="card-body">
                    <h5 class="card-title text-white">Add Team Member</h5>
                    <form method="POST" class="row g-3">
                      <input type="hidden" name="add_member" value="1">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                      <div class="col-md-6">
                        <input type="email" name="member_email" class="form-control" placeholder="Member Email" required>
                      </div>
                      <div class="col-md-4">
                        <select name="project_id" class="form-select" required>
                          <option value="">Select Project</option>
                          <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['colproj_id']; ?>"><?php echo htmlspecialchars($project['colproj_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Add</button>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="row g-4">
                  <?php foreach ($team_members as $member): ?>
                    <div class="col-md-6 col-lg-4">
                      <div class="team-member-card">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($member['username'] ?? 'Member'); ?>&background=ff8b5c&color=fff" alt="Avatar">
                        <h4><?php echo htmlspecialchars($member['username'] ?? 'Member'); ?></h4>
                        <p><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></p>
                        <p><i class='bx bx-folder'></i> <?php echo htmlspecialchars($member['colproj_name'] ?? 'No Project'); ?></p>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Remove this member?');">
                          <input type="hidden" name="delete_member" value="1">
                          <input type="hidden" name="delete_member_index" value="<?php echo $member['teammem_id']; ?>">
                          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                          <button type="submit" class="btn btn-danger btn-sm"><i class='bx bx-trash'></i> Remove</button>
                        </form>
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
    <div class="footer-section footer-left"><span>DoTrack</span></div>
    <div class="footer-section footer-center">
      <p>&copy; 2025 DoTrack. All rights reserved.</p>
    </div>
    <div class="footer-section footer-right">
      <a href="/dotrack/ourteam.php">Our Team</a>
      <a href="/dotrack/contact.php">Contact Us</a>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let taskCounter = 0;
    const users = <?php echo json_encode(array_map(function ($u) {
                    return ['id' => $u['user_id'], 'name' => $u['username']];
                  }, $users)); ?>;

    function addTask() {
      const container = document.getElementById('tasks-container');
      const idx = taskCounter++;
      const memberOptions = '<option value="">Select Member</option>' + users.map(u => `<option value="${u.id}">${u.name.replace(/</g, '&lt;')}</option>`).join('');
      const html = `<div class="task-block mb-3 p-3 border rounded bg-light" data-idx="${idx}">
        <input type="hidden" name="task_id[]" value="">
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="task_title[]" class="form-control form-control-sm" placeholder="Task Title" required></div>
            <div class="col-md-3"><select name="task_member[]" class="form-select form-select-sm">${memberOptions}</select></div>
            <div class="col-md-2"><input type="date" name="task_start[]" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><input type="date" name="task_due[]" class="form-control form-control-sm" required></div>
            <div class="col-md-3"><select name="task_status[]" class="form-select form-select-sm"><option value="not started">Not Started</option><option value="in progress">In Progress</option><option value="on hold">On Hold</option><option value="cancelled">Cancelled</option><option value="done">Done</option></select></div>
        </div>
        <div class="subtasks-container mt-2" data-task-idx="${idx}"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addSubtask(${idx})">+ Add Subtask</button>
        <button type="button" class="btn btn-sm btn-outline-danger mt-2 ms-2" onclick="this.closest('.task-block').remove()">Remove Task</button>
    </div>`;
      container.insertAdjacentHTML('beforeend', html);
    }

    function addSubtask(taskIdx) {
      const container = document.querySelector(`.task-block[data-idx="${taskIdx}"] .subtasks-container`);
      if (!container) return;
      const subIdx = container.children.length;
      const html = `<div class="subtask-row input-group mb-1">
        <input type="text" name="subtask_title[${taskIdx}][]" class="form-control form-control-sm" placeholder="Subtask">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.subtask-row').remove()">×</button>
    </div>`;
      container.insertAdjacentHTML('beforeend', html);
    }

    if (document.getElementById('tasks-container') && document.getElementById('tasks-container').children.length === 0) {
      addTask();
    }
  </script>
</body>

</html>