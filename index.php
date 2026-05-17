<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - Welcome Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:ital,wght@0,300;0,400;1,300&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <link rel="stylesheet" href="/dotrack/assets/css/index.css">
</head>

<body>

  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
    </div>
    <nav class="main-nav">
      <a href="/dotrack/about.php" class="about" title="About">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="16" x2="12" y2="12" />
          <line x1="12" y1="8" x2="12" y2="8" />
        </svg>
        About
      </a>
    </nav>
  </header>

  <h1 class="page-title" id="animated-title">
    <span>Welcome</span>
    <span>to</span>
    <span>DoTrack!</span>
  </h1>

  <div class="container video-section-container">
    <div class="row justify-content-lg-end">
      <div class="col-12 col-lg-8 col-xl-7">
        <div class="feature-video-card">
          <div class="video-container">
            <video autoplay loop muted playsinline>
              <source src="/dotrack/assets/vid/ponyo.mp4" type="video/mp4">
              Your browser does not support the video tag.
            </video>
            <div class="video-overlay"></div>
          </div>
          <div class="get-started-content">
            <h1>Get Organized, Stay Productive</h1>
            <p class="animated-text">The Smarter Way to Organize and Collaborate.</p>
            <form method="post" action="home.php">
              <button type="submit" class="btn pulse">Do More, Track Better</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

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
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const title = document.getElementById('animated-title');
      const text = title.textContent;
      title.textContent = '';
      text.split('').forEach(char => {
        const span = document.createElement('span');
        span.textContent = char;
        span.style.display = 'inline-block';
        span.style.opacity = 0;
        span.style.transform = 'translateY(30px)';
        title.appendChild(span);
      });
      gsap.to('#animated-title span', {
        opacity: 1,
        y: 0,
        ease: 'power3.out',
        duration: 0.6,
        stagger: 0.08
      });
    });
  </script>
</body>

</html>