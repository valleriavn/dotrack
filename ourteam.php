<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - Meet Our Team</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/dotrack/assets/css/ourteam.css">
</head>

<body>

  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
      <span class="site-title">Our Team</span>
    </div>
    <nav class="main-nav">
      <a href="/dotrack/index.php" class="nav-link">
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
    <div class="team-section">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="team-title">Meet Our Exceptional Team</h2>
          <p class="team-subtitle">The passionate minds driving innovation and success</p>
        </div>
        <div class="row g-4 justify-content-center">
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="team-card">
              <img src="/dotrack/assets/img/nei.png" alt="Neilyn Bobadilla">
              <h3 class="name">Neilyn Bobadilla</h3>
              <div class="social-icons">
                <a href="https://www.instagram.com/neiloveyoun/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                <a href="https://www.facebook.com/nlynbbdll" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="team-card">
              <img src="/dotrack/assets/img/sarah.png" alt="Sarah Macalilong">
              <h3 class="name">Sarah Macalilong</h3>
              <div class="social-icons">
                <a href="https://www.instagram.com/cassandramacalilong/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                <a href="https://www.facebook.com/ja.ren.104" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="team-card">
              <img src="/dotrack/assets/img/rhen.png" alt="Rhenalyn Mecha">
              <h3 class="name">Rhenalyn Mecha</h3>
              <div class="social-icons">
                <a href="https://www.instagram.com/rhennnnnalyyyn/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                <a href="https://www.facebook.com/crwnlss" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="team-card">
              <img src="/dotrack/assets/img/kim.png" alt="Kim Verlie Napolitano">
              <h3 class="name">Kim Verlie Napolitano</h3>
              <div class="social-icons">
                <a href="https://www.instagram.com/valleriavn/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                <a href="https://www.facebook.com/valleriavn/" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-container">
      <p>&copy; 2025 DoTrack. All rights reserved. | <a href="mailto:dotrack@gmail.com">dotrack@gmail.com</a></p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
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