<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize session and authentication
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: /dotrack/auth/loginandsignup.php");
  exit();
}

$user_id = $_SESSION['user_id']; // User's ID from auth
$successMessage = '';
$errorMessage = '';

// Function to generate a unique group code
function generateGroupCode($conn, $length = 8)
{
  $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
  do {
    $code = '';
    for ($i = 0; $i < $length; $i++) {
      $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    // Check if code already exists
    $stmt = $conn->prepare("SELECT colgroup_id FROM collaborate_group WHERE group_code = ?");
    if ($stmt === false) {
      // Log and throw error for debugging
      error_log("Prepare failed in generateGroupCode: " . $conn->error);
      throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
  } while ($result->num_rows > 0);
  return $code;
}

// Handle group code generation for fetch request
if (isset($_GET['generate_code']) && $_GET['generate_code'] == 1) {
  $_SESSION['new_group_code'] = generateGroupCode($conn);
  echo $_SESSION['new_group_code'];
  exit();
}

// Generate group code on page load if not set
if (!isset($_SESSION['new_group_code'])) {
  $_SESSION['new_group_code'] = generateGroupCode($conn);
}

// Check if user is in a group
try {
  // Validate user_id
  if (!isset($user_id) || !is_numeric($user_id)) {
    $errorMessage = "Invalid user ID.";
    error_log(date('[Y-m-d H:i:s] ') . "Invalid user_id in createjoin.php: " . var_export($user_id, true) . PHP_EOL, 3, 'C:/xampp/htdocs/dotrack/logs/db_errors.log');
  } elseif (!isset($_SESSION['redirected_to_collaborate']) || !$_SESSION['redirected_to_collaborate']) {
    // Check group membership
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM collaborate_teammem WHERE user_id = ? AND deleted_at IS NULL");
    if ($stmt === false) {
      $errorMessage = "Failed to prepare group membership query: " . $conn->error;
      error_log(date('[Y-m-d H:i:s] ') . "Prepare failed: " . $conn->error . PHP_EOL, 3, 'C:/xampp/htdocs/dotrack/logs/db_errors.log');
    } else {
      $stmt->bind_param("i", $user_id);
      if (!$stmt->execute()) {
        $errorMessage = "Failed to execute group membership query: " . $stmt->error;
        error_log(date('[Y-m-d H:i:s] ') . "Execute failed: " . $stmt->error . PHP_EOL, 3, 'C:/xampp/htdocs/dotrack/logs/db_errors.log');
      } else {
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
          $_SESSION['redirected_to_collaborate'] = true;
          header("Location: /dotrack/users/collaborate.php");
          exit();
        }
      }
      $stmt->close();
    }
  }
} catch (Exception $e) {
  $errorMessage = "Error checking group membership: " . $e->getMessage();
  error_log(date('[Y-m-d H:i:s] ') . "Group membership check error in createjoin.php for user_id $user_id: " . $e->getMessage() . PHP_EOL, 3, 'C:/xampp/htdocs/dotrack/logs/db_errors.log');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['group_name'])) { // create
    $groupName = trim($_POST['group_name']);
    $groupCode = $_SESSION['new_group_code']; // Use the session-stored code

    $stmt = $conn->prepare("INSERT INTO collaborate_group (user_id, colgroup_name, group_code, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $groupName, $groupCode);
    if ($stmt->execute()) {
      $successMessage = "Group created successfully! Your group code is: $groupCode";
      // Generate new code for next creation
      $_SESSION['new_group_code'] = generateGroupCode($conn);
    } else {
      $errorMessage = "Failed to create group.";
    }
    $stmt->close();
  }
  if (isset($_POST['join_code'])) { // join
    $enteredCode = trim($_POST['join_code']);
    $stmt = $conn->prepare("SELECT colgroup_id FROM collaborate_group WHERE group_code = ? LIMIT 1");
    if ($stmt === false) {
      $errorMessage = "Failed to prepare join group query: " . $conn->error;
      error_log("Prepare failed for join group: " . $conn->error);
    } else {
      $stmt->bind_param("s", $enteredCode);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        $groupId = $result->fetch_assoc()['colgroup_id'];

        $stmt2 = $conn->prepare("INSERT INTO collaborate_teammem (colgroup_id, user_id) VALUES (?, ?)");
        if ($stmt2 === false) {
          $errorMessage = "Failed to prepare insert team member: " . $conn->error;
          error_log("Prepare failed for insert team member: " . $conn->error);
        } else {
          $stmt2->bind_param("ii", $groupId, $user_id);
          if ($stmt2->execute()) {
            $successMessage = "You've successfully joined the group.";
          } else {
            $errorMessage = "Failed to join the group.";
          }
          $stmt2->close();
        }
      } else {
        $errorMessage = "Invalid group code.";
      }
      $stmt->close();
    }
  }
}

// Retrieve groups you own
$ownGroups = [];
$stmt = $conn->prepare("SELECT * FROM collaborate_group WHERE user_id = ?");
if ($stmt === false) {
  $errorMessage = "Failed to prepare query for owned groups: " . $conn->error;
  error_log(date('[Y-m-d H:i:s] ') . "Prepare failed for owned groups: " . $conn->error . PHP_EOL, 3, 'C:/xampp/htdocs/dotrack/logs/db_errors.log');
} else {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $ownGroups = $stmt->get_result();
  $stmt->close();
}

// Retrieve groups you are a member of
$memberGroups = [];
$stmt = $conn->prepare("SELECT g.* FROM collaborate_teammem m JOIN collaborate_group g ON m.colgroup_id = g.colgroup_id WHERE m.user_id = ?");
if ($stmt === false) {
  $errorMessage = "Failed to prepare query for member groups: " . $conn->error;
  error_log(date('[Y-m-d H:i:s] ') . "Prepare failed for member groups: " . $conn->error . PHP_EOL, 3, 'C:/xampp/htdocs/dotrack/logs/db_errors.log');
} else {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $memberGroups = $stmt->get_result();
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - Create or Join Group</title>
  <link rel="stylesheet" href="/dotrack/assets/css/createjoin.css">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
  <link rel="shortcut icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
</head>

<body>
  <!-- Header -->
  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img" />
      <h1 class="site-title">Create/Join Group</h1>
    </div>
    <button class="mobile-menu-btn" style="display:none;">☰</button>
    <nav class="main-nav">
      <a href="/dotrack/home.php" class="home" title="Home">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-4H9v4a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2z" />
        </svg>
        Home
      </a>
      <a href="/dotrack/users/mode.php" class="dashboard" title="Mode">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="9" />
          <rect x="14" y="3" width="7" height="5" />
          <rect x="14" y="12" width="7" height="9" />
          <rect x="3" y="16" width="7" height="5" />
        </svg>
        Mode
      </a>
      <a href="/dotrack/account.php" class="account" title="Account">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10" />
          <path d="M12 14c2 0 4 1 4 3v2H8v-2c0-2 2-3 4-3z" />
          <circle cx="12" cy="8" r="3" />
        </svg>
        Account
      </a>
      <a href="about.php" class="about" title="About">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="16" x2="12" y2="12" />
          <line x1="12" y1="8" x2="12" y2="8" />
        </svg>
        About
      </a>
    </nav>
  </header>

  <div class="auth-center">
    <div class="button-container">
      <!-- Create Group Button -->
      <button class="action-button" id="createGroupBtn">
        <i class='bx bx-group'></i>
        Create a Group
      </button>

      <!-- Join Group Button -->
      <button class="action-button" id="joinGroupBtn">
        <i class='bx bx-link-alt'></i>
        Join a Group
      </button>
    </div>
  </div>

  <!-- Create Group Modal -->
  <div id="createGroupModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeCreate">×</span>
      <h2>Create a Group</h2>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="create_group" value="1">
        <div class="form-group">
          <label for="group_name">Group Name</label>
          <input type="text" id="group_name" name="group_name" required placeholder="Enter group name">
        </div>

        <div class="form-group">
          <label for="members">Add Members (comma-separated emails, non-admins only)</label>
          <input type="text" id="members" name="members" placeholder="member1@email.com, member2@email.com">
        </div>

        <div class="form-group">
          <label>Group Code</label>
          <div class="code-display" id="groupCodeDisplay">
            <?= htmlspecialchars($_SESSION['new_group_code'] ?? '') ?>
          </div>
          <input type="hidden" name="group_code" id="groupCodeInput" value="<?= htmlspecialchars($_SESSION['new_group_code'] ?? '') ?>">
        </div>

        <button type="submit" class="submit-btn">Create Group</button>
      </form>
    </div>
  </div>

  <!-- Join Group Modal -->
  <div id="joinGroupModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeJoin">×</span>
      <h2>Join a Group</h2>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="join_group" value="1">
        <div class="form-group">
          <label for="join_code">Enter Group Code</label>
          <input type="text" id="join_code" name="join_code" required placeholder="Enter the group code">
        </div>

        <button type="submit" class="submit-btn">Join Group</button>
      </form>
    </div>
  </div>

  <!-- Success/Error Message Modal -->
  <?php if (!empty($successMessage) || !empty($errorMessage)): ?>
    <div id="messageModal" class="modal" style="display: flex;">
      <div class="modal-content">
        <span class="close-btn" id="closeMessage">×</span>
        <h2 style="color: <?php echo !empty($successMessage) ? '#4CAF50' : '#f44336'; ?>">
          <?php echo !empty($successMessage) ? 'Success!' : 'Error!'; ?>
        </h2>
        <p style="text-align: center; font-size: 1.2rem; margin: 1.5rem 0;">
          <?php echo htmlspecialchars($successMessage ?: $errorMessage); ?>
        </p>
        <button id="okButton" class="submit-btn">OK</button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="footer-section footer-left">
      <a>DoTrack</a>
    </div>
    <div class="footer-section footer-center">
      <p>© 2026 DoTrack. All rights reserved.</p>
    </div>
    <div class="footer-section footer-right">
      <a href="/dotrack/ourteam.php">Our Team</a>
      <a href="/dotrack/contact.php">Contact Us</a>
    </div>
  </footer>

  <script>
    // Get DOM elements
    const createGroupBtn = document.getElementById('createGroupBtn');
    const joinGroupBtn = document.getElementById('joinGroupBtn');
    const createGroupModal = document.getElementById('createGroupModal');
    const joinGroupModal = document.getElementById('joinGroupModal');
    const messageModal = document.getElementById('messageModal');
    const closeCreate = document.getElementById('closeCreate');
    const closeJoin = document.getElementById('closeJoin');
    const closeMessage = document.getElementById('closeMessage');
    const okButton = document.getElementById('okButton');
    const groupCodeDisplay = document.getElementById('groupCodeDisplay');
    const groupCodeInput = document.getElementById('groupCodeInput');

    // Event listeners
    createGroupBtn.addEventListener('click', () => {
      createGroupModal.style.display = 'flex';
    });

    joinGroupBtn.addEventListener('click', () => {
      joinGroupModal.style.display = 'flex';
    });

    closeCreate.addEventListener('click', () => {
      createGroupModal.style.display = 'none';
      // Refresh group code on modal close
      fetch('createjoin.php?generate_code=1').then(response => response.text()).then(code => {
        groupCodeDisplay.textContent = code;
        groupCodeInput.value = code;
      });
    });

    closeJoin.addEventListener('click', () => {
      joinGroupModal.style.display = 'none';
    });

    if (closeMessage) {
      closeMessage.addEventListener('click', () => {
        messageModal.style.display = 'none';
        location.href = 'collaborate.php';
      });
    }

    if (okButton) {
      okButton.addEventListener('click', () => {
        messageModal.style.display = 'none';
        location.href = 'collaborate.php';
      });
    }

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === createGroupModal) {
        createGroupModal.style.display = 'none';
        // Refresh group code on modal close
        fetch('createjoin.php?generate_code=1').then(response => response.text()).then(code => {
          groupCodeDisplay.textContent = code;
          groupCodeInput.value = code;
        });
      }
      if (e.target === joinGroupModal) {
        joinGroupModal.style.display = 'none';
      }
      if (e.target === messageModal) {
        messageModal.style.display = 'none';
        location.href = 'collaborate.php';
      }
    });

    // Copy group code on click
    groupCodeDisplay.addEventListener('click', () => {
      const textArea = document.createElement('textarea');
      textArea.value = groupCodeDisplay.textContent.trim();
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);

      // Show copied feedback
      const originalText = groupCodeDisplay.textContent;
      groupCodeDisplay.textContent = 'Copied to clipboard!';
      groupCodeDisplay.style.color = '#4CAF50';

      setTimeout(() => {
        groupCodeDisplay.textContent = originalText;
        groupCodeDisplay.style.color = '';
      }, 1500);
    });
  </script>
</body>

</html>