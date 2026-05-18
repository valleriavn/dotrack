<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - About Us</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
  <link rel="shortcut icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
  <link rel="stylesheet" href="/dotrack/assets/css/about.css">
</head>

<body>

  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
    </div>
    <nav class="main-nav">
      <a href="/dotrack/home.php" class="nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-4H9v4a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2z" />
        </svg>
        Home
      </a>
    </nav>
  </header>

  <main>
    <div class="hero-section">
      <div class="container py-5">
        <div class="row justify-content-center">
          <div class="col-lg-8 text-center">
            <h1 class="hero-title mb-4">About Us</h1>
            <p class="hero-text mx-auto">
              A productivity-focused task management system designed for individuals and teams to manage daily habits,
              to-do lists, project tasks, and schedules in one platform.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="features-section py-5">
      <div class="container">
        <h2 class="section-title text-center mb-5">Key Features</h2>
        <div class="row g-4 justify-content-center">
          <div class="col-md-6 col-lg-5">
            <div class="feature-card text-center p-4">
              <div class="feature-icon mx-auto mb-3">
                <i class='bx bx-task'></i>
              </div>
              <h3 class="feature-title">Habit Daily Tracking</h3>
              <p class="feature-text">Track and build better habits daily with visual progress feedback.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-5">
            <div class="feature-card text-center p-4">
              <div class="feature-icon mx-auto mb-3">
                <i class='bx bx-calendar'></i>
              </div>
              <h3 class="feature-title">Projects Tracker</h3>
              <p class="feature-text">Monitor progress on all your projects and key deadlines in one view.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-5">
            <div class="feature-card text-center p-4">
              <div class="feature-icon mx-auto mb-3">
                <i class='bx bx-group'></i>
              </div>
              <h3 class="feature-title">To-Do List Management</h3>
              <p class="feature-text">Manage personal and shared task lists efficiently and collaboratively.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-5">
            <div class="feature-card text-center p-4">
              <div class="feature-icon mx-auto mb-3">
                <i class='bx bx-time-five'></i>
              </div>
              <h3 class="feature-title">Schedule</h3>
              <p class="feature-text">Plan your week, set priorities, and stay on track with daily reminders.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-section footer-left">
      <span>DoTrack</span>
    </div>
    <div class="footer-section footer-center">
      <p>&copy; 2025 DoTrack. All rights reserved.</p>
    </div>
    <div class="footer-section footer-right">
      <a href="/dotrack/ourteam.php">Our Team</a>
      <a href="/dotrack/contact.php">Contact Us</a>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>