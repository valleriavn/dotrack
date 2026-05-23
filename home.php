<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - Discover Productivity</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:ital,wght@0,300;0,400;0,700;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
  <link rel="shortcut icon" type="image/png" href="/dotrack/assets/img/tablogo.png">
  <link rel="stylesheet" href="/dotrack/assets/css/home.css">
</head>

<body>

  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
    </div>
    <nav class="main-nav">
      <a href="index.php" class="nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-4H9v4a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2z" />
        </svg>
        Home
      </a>
      <a href="about.php" class="nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="16" x2="12" y2="12" />
          <line x1="12" y1="8" x2="12" y2="8" />
        </svg>
        About
      </a>
    </nav>
  </header>

  <section class="hero">
    <div class="container">
      <h1 class="display-4 fw-bold mb-4">Unlock Your <span class="highlight">Productivity</span> with DoTrack</h1>
      <p class="lead mb-5 mx-auto">All-in-one task manager, collaboration hub, and progress tracker—designed to fuel your creativity and maximize your efficiency.</p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="/dotrack/auth/loginandsignup.php" class="btn btn-primary btn-lg">Start Your Journey</a>
        <a href="about.php" class="btn btn-secondary btn-lg">Learn More</a>
      </div>
    </div>
  </section>

  <section class="visual-showcase py-5">
    <div class="container">
      <h2 class="section-title text-center mb-5">Powerful Features for Everyone</h2>
      <div class="row g-4 justify-content-center">
        <div class="col-md-6">
          <div class="image-block h-100">
            <img src="/dotrack/assets/img/per.png" alt="Personal Productivity" class="img-fluid">
            <div class="image-block-content p-4">
              <h3>Personal Productivity</h3>
              <p>Manage your own tasks, set deadlines, and monitor progress in one clean dashboard.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="image-block h-100">
            <img src="/dotrack/assets/img/collab.png" alt="Team Collaboration" class="img-fluid">
            <div class="image-block-content p-4">
              <h3>Team Collaboration</h3>
              <p>Join teams, assign tasks, communicate clearly, and get work done together.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="features-overview py-5">
    <div class="container">
      <h2 class="section-title text-center mb-5">Everything You Need in Handling Tasks</h2>
      <div class="row g-4">
        <div class="col-sm-6 col-lg-4">
          <div class="feature-box text-center p-4">
            <i class="fas fa-user-lock fa-3x mb-3"></i>
            <h4>User Authentication</h4>
            <p>Login, registration, and secure sessions built for peace of mind.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="feature-box text-center p-4">
            <i class="fas fa-tasks fa-3x mb-3"></i>
            <h4>Task Management</h4>
            <p>Create, assign, track status and subtasks — all organized and visual.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="feature-box text-center p-4">
            <i class="fas fa-users-cog fa-3x mb-3"></i>
            <h4>Team Collaboration</h4>
            <p>Manage teams, assign roles, and track shared project milestones.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="feature-box text-center p-4">
            <i class="fas fa-chart-line fa-3x mb-3"></i>
            <h4>Visual Dashboards</h4>
            <p>Track progress with badges, color-coded indicators, and charts.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="feature-box text-center p-4">
            <i class="fas fa-calendar-alt fa-3x mb-3"></i>
            <h4>Calendar Integration</h4>
            <p>Calendars to monitor and stay on schedule.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="feature-box text-center p-4">
            <i class="fas fa-headset fa-3x mb-3"></i>
            <h4>Help Center</h4>
            <p>Built-in chatbot, FAQs, and guides to walk you through every feature.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="cta py-5 text-center">
    <div class="container">
      <h2 class="display-5 fw-bold mb-4">Ready to transform how you work?</h2>
      <p class="lead mb-4">Join DoTrack to accomplish more every day.</p>
      <a href="/dotrack/auth/loginandsignup.php" class="btn cta-btn btn-lg">Try DoTrack!</a>
    </div>
  </section>

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
    document.addEventListener('DOMContentLoaded', function() {
      const animateElements = document.querySelectorAll('.image-block, .feature-box');
      const animateOnScroll = () => {
        const windowHeight = window.innerHeight;
        animateElements.forEach(el => {
          const rect = el.getBoundingClientRect();
          if (rect.top < windowHeight - 100) {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
          }
        });
      };
      animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
      });
      window.addEventListener('scroll', animateOnScroll);
      animateOnScroll();
    });
  </script>
</body>

</html>