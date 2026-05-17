<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /dotrack/auth/loginandsignup.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = '';
$email = '';

try {
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $email = $user['email'];
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

$tasks_by_date = [];
try {
    $stmt = $conn->prepare("SELECT todo_name as name, due_date, 'personal' as type, category, status 
                          FROM personal_todo 
                          WHERE user_id = ? AND deleted_at IS NULL AND due_date IS NOT NULL
                          UNION ALL
                          SELECT t.coltask_name as name, t.due_date, 'collaborative' as type, p.colproj_name as category, 'Pending' as status
                          FROM collaborate_task t
                          JOIN collaborate_project p ON t.colproj_id = p.colproj_id
                          JOIN collaborate_teammem tm ON p.colgroup_id = tm.colgroup_id
                          WHERE tm.user_id = ? AND t.deleted_at IS NULL AND t.due_date IS NOT NULL");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $date = new DateTime($row['due_date'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('Asia/Manila'));
        $dateStr = $date->format('Y-m-d');
        if (!isset($tasks_by_date[$dateStr])) {
            $tasks_by_date[$dateStr] = [];
        }
        $tasks_by_date[$dateStr][] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching tasks by date: " . $e->getMessage());
}

$personal_tasks = [];
try {
    $stmt = $conn->prepare("SELECT * FROM personal_todo WHERE user_id = ? AND deleted_at IS NULL ORDER BY due_date ASC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $personal_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching personal tasks: " . $e->getMessage());
}

$collab_tasks = [];
try {
    $stmt = $conn->prepare("SELECT t.coltask_name, t.due_date, p.colproj_name 
                        FROM collaborate_task t
                        JOIN collaborate_project p ON t.colproj_id = p.colproj_id
                        JOIN collaborate_teammem tm ON p.colgroup_id = tm.colgroup_id
                        WHERE tm.user_id = ? AND t.deleted_at IS NULL
                        ORDER BY t.due_date ASC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $collab_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching collaborative tasks: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoTrack - My Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/dotrack/assets/css/account.css">
</head>

<body>

    <header class="header">
        <div class="header-left">
            <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
            <span class="site-title">My Account</span>
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
            <a href="/dotrack/about.php" class="nav-link">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="16" x2="12" y2="12" />
                    <line x1="12" y1="8" x2="12" y2="8" />
                </svg>
                About
            </a>
        </nav>
        <button class="mobile-menu-btn d-md-none">☰</button>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="row g-4">
                <div class="col-12">
                    <div class="profile-card">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                            <h2 class="profile-title">User Profile</h2>
                            <button class="btn-edit" id="editProfileBtn">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>
                        <div class="text-center">
                            <div class="username-wrapper mb-3">
                                <input type="text" class="username-field" id="usernameInput" value="<?php echo htmlspecialchars($username); ?>" readonly>
                            </div>
                            <div class="email-wrapper">
                                <p><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($email); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="task-card">
                        <h3><i class='bx bx-user'></i> Personal Tasks</h3>
                        <div class="task-list">
                            <?php foreach ($personal_tasks as $task): ?>
                                <div class="task-item">
                                    <input type="checkbox" class="task-checkbox" <?php echo $task['status'] === 'Done' ? 'checked' : ''; ?>>
                                    <div class="task-info">
                                        <div class="task-title"><?php echo htmlspecialchars($task['todo_name']); ?></div>
                                        <div class="task-meta">
                                            <?php if ($task['due_date']): ?>
                                                <span><i class="fas fa-calendar"></i> <?php echo date('M j', strtotime($task['due_date'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ($task['category']): ?>
                                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($task['category']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="task-badge personal">Personal</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($personal_tasks)): ?>
                                <p class="text-muted text-center">No personal tasks yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="task-card">
                        <h3><i class='bx bx-group'></i> Collaborative Tasks</h3>
                        <div class="task-list">
                            <?php foreach ($collab_tasks as $task): ?>
                                <div class="task-item">
                                    <input type="checkbox" class="task-checkbox">
                                    <div class="task-info">
                                        <div class="task-title"><?php echo htmlspecialchars($task['coltask_name']); ?></div>
                                        <div class="task-meta">
                                            <?php if ($task['due_date']): ?>
                                                <span><i class="fas fa-calendar"></i> <?php echo date('M j', strtotime($task['due_date'])); ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-project-diagram"></i> <?php echo htmlspecialchars($task['colproj_name']); ?></span>
                                        </div>
                                        <span class="task-badge collaborative">Collaborative</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($collab_tasks)): ?>
                                <p class="text-muted text-center">No collaborative tasks yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="calendar-card">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                            <h3><i class="fas fa-calendar-alt"></i> Task Calendar</h3>
                            <div class="calendar-nav">
                                <button id="prevMonth" class="btn-cal-nav"><i class="fas fa-chevron-left"></i></button>
                                <span class="current-month mx-3"><?php echo date('F Y'); ?></span>
                                <button id="nextMonth" class="btn-cal-nav"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                        <div class="calendar-grid" id="calendarGrid">
                            <div class="calendar-day">Sun</div>
                            <div class="calendar-day">Mon</div>
                            <div class="calendar-day">Tue</div>
                            <div class="calendar-day">Wed</div>
                            <div class="calendar-day">Thu</div>
                            <div class="calendar-day">Fri</div>
                            <div class="calendar-day">Sat</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-center mt-3">
                    <a href="/dotrack/auth/logout.php?redirect=/dotrack/index.php" class="btn-logout" id="logoutBtn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tasks for <span id="modalDate"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalTasks"></div>
            </div>
        </div>
    </div>

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
        const tasksByDate = <?php echo json_encode($tasks_by_date); ?>;
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();

        function buildCalendar() {
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            const prevMonth = currentMonth - 1;
            const prevYear = currentYear;
            const prevMonthDays = new Date(prevYear, prevMonth + 1, 0).getDate();

            let nextMonth = currentMonth + 1;
            let nextYear = currentYear;
            if (nextMonth > 11) {
                nextMonth = 0;
                nextYear++;
            }

            document.querySelector('.current-month').textContent = new Date(currentYear, currentMonth).toLocaleDateString('en-US', {
                month: 'long',
                year: 'numeric'
            });

            const calendarGrid = document.getElementById('calendarGrid');
            const dayHeaders = Array.from(calendarGrid.children).slice(0, 7);
            calendarGrid.innerHTML = '';
            dayHeaders.forEach(header => calendarGrid.appendChild(header));

            for (let i = 0; i < firstDay; i++) {
                const day = prevMonthDays - firstDay + i + 1;
                const dateStr = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dateDiv = document.createElement('div');
                dateDiv.className = 'calendar-date other-month';
                if (tasksByDate[dateStr]) dateDiv.classList.add('has-event');
                dateDiv.textContent = day;
                dateDiv.setAttribute('data-date', dateStr);
                calendarGrid.appendChild(dateDiv);
            }

            const today = new Date();
            for (let i = 1; i <= daysInMonth; i++) {
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const dateDiv = document.createElement('div');
                dateDiv.className = 'calendar-date';
                if (i === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
                    dateDiv.classList.add('today');
                }
                if (tasksByDate[dateStr]) dateDiv.classList.add('has-event');
                dateDiv.textContent = i;
                dateDiv.setAttribute('data-date', dateStr);
                calendarGrid.appendChild(dateDiv);
            }

            const totalSlots = 42;
            const daysDisplayed = firstDay + daysInMonth;
            const remaining = totalSlots - daysDisplayed;
            for (let i = 1; i <= remaining; i++) {
                const dateStr = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const dateDiv = document.createElement('div');
                dateDiv.className = 'calendar-date other-month';
                if (tasksByDate[dateStr]) dateDiv.classList.add('has-event');
                dateDiv.textContent = i;
                dateDiv.setAttribute('data-date', dateStr);
                calendarGrid.appendChild(dateDiv);
            }

            attachDateClickHandlers();
        }

        function attachDateClickHandlers() {
            document.querySelectorAll('.calendar-date').forEach(el => {
                el.removeEventListener('click', handleDateClick);
                el.addEventListener('click', handleDateClick);
            });
        }

        function handleDateClick() {
            const date = this.getAttribute('data-date');
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('modalDate').textContent = formattedDate;
            const modalTasks = document.getElementById('modalTasks');
            modalTasks.innerHTML = '';

            if (tasksByDate[date] && tasksByDate[date].length > 0) {
                tasksByDate[date].forEach(task => {
                    const taskDiv = document.createElement('div');
                    taskDiv.className = 'modal-task-item';
                    taskDiv.innerHTML = `
                <div class="modal-task-type ${task.type}">${task.type.charAt(0).toUpperCase() + task.type.slice(1)}</div>
                <div class="modal-task-name">${escapeHtml(task.name)}</div>
                ${task.category ? `<div class="modal-task-category"><i class="fas fa-tag"></i> ${escapeHtml(task.category)}</div>` : ''}
                ${task.status === 'Done' ? '<div class="modal-task-status done"><i class="fas fa-check"></i> Done</div>' : ''}
            `;
                    modalTasks.appendChild(taskDiv);
                });
            } else {
                modalTasks.innerHTML = '<p class="text-muted text-center">No tasks scheduled for this day.</p>';
            }

            new bootstrap.Modal(document.getElementById('taskModal')).show();
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            buildCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            buildCalendar();
        });

        buildCalendar();

        document.querySelectorAll('.task-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const taskItem = this.closest('.task-item');
                if (this.checked) {
                    taskItem.style.opacity = '0.6';
                } else {
                    taskItem.style.opacity = '1';
                }
            });
        });

        const editProfileBtn = document.getElementById('editProfileBtn');
        const usernameInput = document.getElementById('usernameInput');
        let isEditing = false;

        editProfileBtn.addEventListener('click', () => {
            isEditing = !isEditing;
            if (isEditing) {
                usernameInput.removeAttribute('readonly');
                usernameInput.focus();
                usernameInput.style.borderColor = '#ff8b5c';
                editProfileBtn.innerHTML = '<i class="fas fa-save"></i> Save';
            } else {
                usernameInput.setAttribute('readonly', true);
                usernameInput.style.borderColor = '#e6cfc2';
                editProfileBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';

                const newUsername = usernameInput.value;
                fetch('/dotrack/users/update_profile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `username=${encodeURIComponent(newUsername)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            usernameInput.value = '<?php echo htmlspecialchars($username); ?>';
                        }
                    })
                    .catch(() => {
                        usernameInput.value = '<?php echo htmlspecialchars($username); ?>';
                    });
            }
        });

        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
            }
        });

        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const mainNav = document.querySelector('.main-nav');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                mainNav.classList.toggle('active');
            });
        }
    </script>
</body>

</html>