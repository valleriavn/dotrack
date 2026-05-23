<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - Help Center</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
  <link rel="shortcut icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
  <link rel="stylesheet" href="/dotrack/assets/css/contact.css">
</head>

<body>

  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
      <span class="site-title">Help Center</span>
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
    <button class="mobile-menu-btn d-md-none">☰</button>
  </header>

  <main>
    <div class="hero-section">
      <div class="container text-center py-5">
        <h1 class="hero-title">How can we help?</h1>
      </div>
    </div>

    <div class="categories-section">
      <div class="container">
        <div class="row g-4 justify-content-center">
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="category-card" onclick="showCategory('Getting Started')">
              <i class="fas fa-flag fa-3x mb-3"></i>
              <h3>Getting Started</h3>
              <p>Learn the basics to get up and running quickly.</p>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="category-card" onclick="showCategory('Workspace Settings')">
              <i class="fas fa-sliders-h fa-3x mb-3"></i>
              <h3>Workspace Settings</h3>
              <p>Assign and create a workspace for your productivity.</p>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="category-card" onclick="showCategory('Tasks & Projects')">
              <i class="fas fa-check-circle fa-3x mb-3"></i>
              <h3>Tasks & Projects</h3>
              <p>Organize your work and hit deadlines with ease.</p>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="category-card" onclick="showCategory('FAQs & Support')">
              <i class="fas fa-life-ring fa-3x mb-3"></i>
              <h3>FAQs & Support</h3>
              <p>Common questions and how to contact support.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalContent"></div>
      </div>
    </div>
  </div>

  <div class="chatbot-container">
    <button class="chatbot-toggle" id="chatbotToggle">
      <i class="fas fa-comment-dots"></i>
    </button>
    <div class="chatbot-window" id="chatbotWindow">
      <div class="chatbot-header">
        <span>DoTrack Support Assistant</span>
        <button class="chatbot-close" id="chatbotClose">×</button>
      </div>
      <div class="chatbot-body" id="chatBody">
        <div class="message bot">
          <img src="/dotrack/assets/img/dtlogo.png" alt="Bot" class="avatar">
          <div class="bubble">Hi there! I'm your DoTrack assistant. How can I help you today?</div>
        </div>
      </div>
      <div class="suggestions">
        <button class="suggestion-btn" data-suggestion="How to restore deleted content?">Restore Content</button>
        <button class="suggestion-btn" data-suggestion="How to share projects with my team?">Share Projects</button>
        <button class="suggestion-btn" data-suggestion="How to add team members to my workspace?">Add Members</button>
      </div>
      <div class="chatbot-input">
        <input type="text" id="userInput" placeholder="Type your message...">
        <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="footer-section footer-left">
      <span>DoTrack</span>
    </div>
    <div class="footer-section footer-center">
      <p>&copy; 2026 DoTrack. All rights reserved.</p>
    </div>
    <div class="footer-section footer-right">
      <a href="/dotrack/ourteam.php">Our Team</a>
      <a href="/dotrack/contact.php">Contact Us</a>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const categoryData = {
      "Getting Started": "To get started with DoTrack, simply create an account, then choose between Personal or Collaborative mode. Our interactive tutorial will guide you through creating your first task, setting deadlines, and organizing your workflow. You can also explore the dashboard to familiarize yourself with all features.",
      "Workspace Settings": "Workspace settings allow you to customize your experience. You can rename your workspace, set team member roles (Admin, Member), configure notification preferences, manage billing settings, and adjust privacy controls. Go to Settings > Workspace to access these options.",
      "Tasks & Projects": "Create tasks, assign deadlines, group tasks under projects, and monitor progress. Use our drag-and-drop interface to organize tasks, set dependencies, track time spent, and mark items as complete. Projects help you group related tasks and view overall progress at a glance.",
      "FAQs & Support": "For common questions, check our FAQ section. For technical support, email us at dotrack@gmail.com or use the chatbot below. Our support team typically responds within 24 hours. We're here to help with any issues or questions you may have!"
    };

    function showCategory(title) {
      document.getElementById('modalTitle').innerText = title;
      document.getElementById('modalContent').innerHTML = categoryData[title] || 'More details coming soon...';
      new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatBody = document.getElementById('chatBody');
    const userInput = document.getElementById('userInput');
    const sendBtn = document.getElementById('sendBtn');

    chatbotToggle.addEventListener('click', () => {
      chatbotWindow.classList.toggle('active');
    });

    chatbotClose.addEventListener('click', () => {
      chatbotWindow.classList.remove('active');
    });

    function addMessage(text, isUser) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
      if (isUser) {
        messageDiv.innerHTML = `
                <div class="bubble">${escapeHtml(text)}</div>
            `;
      } else {
        messageDiv.innerHTML = `
                <img src="/dotrack/assets/img/dtlogo.png" alt="Bot" class="avatar">
                <div class="bubble">${escapeHtml(text)}</div>
            `;
      }
      chatBody.appendChild(messageDiv);
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    function escapeHtml(str) {
      return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
      }).replace(/\n/g, '<br>');
    }

    function getBotResponse(message) {
      const msg = message.toLowerCase();
      if (msg.includes('restore') || msg.includes('deleted')) {
        return "You can restore deleted content from the Trash section within 30 days. Go to Settings > Trash, select the items you want to restore, and click 'Restore'. Items permanently delete after 30 days.";
      } else if (msg.includes('share') || msg.includes('project')) {
        return "To share a project: 1) Open the project, 2) Click the 'Share' button at the top, 3) Enter email addresses of team members, 4) Set permissions (View/Edit/Comment), 5) Click 'Send Invite'. Members will receive an email notification.";
      } else if (msg.includes('member') || msg.includes('team')) {
        return "To add team members: 1) Go to Workspace Settings, 2) Click 'Members' tab, 3) Click 'Add Member', 4) Enter their email address, 5) Assign a role (Admin/Member/Viewer), 6) Click 'Send Invite'. They'll need to accept the invitation to join.";
      } else if (msg.includes('task') || msg.includes('create')) {
        return "To create a task: Click the '+' button in your dashboard, enter task details, set a due date, assign priority (Low/Medium/High/Urgent), add subtasks if needed, and click 'Save'. You can also drag tasks to reorder them.";
      } else {
        return "Thanks for your message! You can find detailed information in our Help Center categories above. For urgent issues, please email us at dotrack@gmail.com and we'll respond within 24 hours.";
      }
    }

    function sendMessage() {
      const text = userInput.value.trim();
      if (!text) return;

      addMessage(text, true);
      userInput.value = '';

      setTimeout(() => {
        const reply = getBotResponse(text);
        addMessage(reply, false);
      }, 800);
    }

    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') sendMessage();
    });

    document.querySelectorAll('.suggestion-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const suggestion = btn.getAttribute('data-suggestion');
        userInput.value = suggestion;
        sendMessage();
      });
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