<?php
session_start();

$showSignUp = isset($_GET['signup']) ? true : false;
$error = isset($_GET['error']) ? $_GET['error'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['loginSubmit'])) {
    require_once __DIR__ . '/../auth/login_process.php';
    exit;
  } elseif (isset($_POST['signUpSubmit'])) {
    require_once __DIR__ . '/../auth/signup_process.php';
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DoTrack - Login & Sign Up</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/loginsignup.css" />
</head>

<body>

  <header class="header">
    <div class="header-left">
      <img src="/dotrack/assets/img/dtlogo.png" alt="DoTrack Logo" class="logo-img">
    </div>
    <nav class="main-nav">
      <a href="../about.php" class="about">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="16" x2="12" y2="12" />
          <line x1="12" y1="8" x2="12" y2="8" />
        </svg>
        About
      </a>
    </nav>
  </header>

  <div class="container d-flex align-items-center justify-content-center" style="min-height: calc(100vh - var(--header-height) - var(--footer-height));">
    <div class="col-12 col-md-8 col-lg-6 col-xl-5">
      <div class="auth-card p-4 p-md-5">

        <form method="post" action="" id="loginForm" style="display: <?php echo $showSignUp ? 'none' : 'block'; ?>">
          <h2 class="text-center mb-4">Login</h2>
          <div class="auth-input mb-3">
            <i class='bx bxs-user'></i>
            <input type="email" name="email" placeholder="Email address" required>
          </div>
          <div class="auth-input mb-4">
            <i class='bx bxs-lock-alt'></i>
            <input type="password" name="password" placeholder="Password" required>
          </div>
          <button type="submit" name="loginSubmit" class="auth-btn mb-3">Login</button>
          <p class="text-center mb-2">Don't have an account? <a href="?signup=1" class="auth-link">Sign Up</a></p>
          <p class="text-center mb-0"><a href="../index.php" class="auth-link"><i class='bx bx-left-arrow-alt'></i> Back to Home</a></p>
        </form>

        <form method="post" action="" id="signupForm" style="display: <?php echo $showSignUp ? 'block' : 'none'; ?>">
          <h2 class="text-center mb-4">Sign Up</h2>
          <div class="auth-input mb-3">
            <i class='bx bxs-user'></i>
            <input type="text" name="signUpUsername" placeholder="Username" required>
          </div>
          <div class="auth-input mb-3">
            <i class='bx bxs-envelope'></i>
            <input type="email" name="signUpEmail" placeholder="Email address" required>
          </div>
          <div class="auth-input mb-3">
            <i class='bx bxs-lock-alt'></i>
            <input type="password" name="signUpPassword" placeholder="Password" required>
          </div>
          <div class="auth-input mb-3">
            <i class='bx bxs-lock-alt'></i>
            <input type="password" name="signUpConfirmPassword" placeholder="Confirm Password" required>
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="termsCheckbox" required>
              <label class="form-check-label" for="termsCheckbox">
                I agree to the <a href="#" id="termsLink" class="auth-link">terms and conditions</a>
              </label>
            </div>
          </div>
          <button type="submit" name="signUpSubmit" class="auth-btn mb-3">Sign Up</button>
          <p class="text-center mb-2">Already have an account? <a href="?" class="auth-link">Login</a></p>
          <p class="text-center mb-0"><a href="../index.php" class="auth-link"><i class='bx bx-left-arrow-alt'></i> Back to Home</a></p>
        </form>

      </div>
    </div>
  </div>

  <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title text-danger">Error</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="errorMessage"></div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Terms and Conditions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="terms-container" id="termsText">
            <h5 class="mb-3">Terms and Conditions for DoTrack</h5>
            <p><strong>Last Updated: June 2025</strong></p>

            <h6 class="mt-3">1. Acceptance of Terms</h6>
            <p>By accessing or using DoTrack ("the Service"), you agree to be bound by these Terms and Conditions ("Terms"). If you do not agree to all of these Terms, do not use the Service.</p>

            <h6 class="mt-3">2. Description of Service</h6>
            <p>DoTrack is a productivity application designed to help users manage tasks, projects, and time. The Service may change from time to time without prior notice.</p>

            <h6 class="mt-3">3. User Accounts</h6>
            <p>To use certain features of the Service, you must register for an account. You agree to provide accurate, current, and complete information, maintain the security of your password, accept all risks of unauthorized access, and be responsible for all activities under your account.</p>

            <h6 class="mt-3">4. User Responsibilities</h6>
            <p>You agree not to use the Service for any illegal purpose, upload or transmit viruses, attempt to gain unauthorized access to other accounts, interfere with the Service's operation, or harass other users.</p>

            <h6 class="mt-3">5. Privacy Policy</h6>
            <p>Your use of the Service is subject to our Privacy Policy, which explains how we collect, use, and protect your personal information.</p>

            <h6 class="mt-3">6. Intellectual Property</h6>
            <p>All content and materials available on DoTrack are the property of DoTrack and are protected by intellectual property laws.</p>

            <h6 class="mt-3">7. Limitation of Liability</h6>
            <p>DoTrack shall not be liable for any indirect, incidental, special, or consequential damages resulting from your use or inability to use the Service, unauthorized access to your data, or any third-party conduct on the Service.</p>

            <h6 class="mt-3">8. Service Modifications</h6>
            <p>DoTrack reserves the right to modify or discontinue the Service at any time without notice.</p>

            <h6 class="mt-3">9. Termination</h6>
            <p>We may terminate or suspend your account immediately for any reason, including if you breach these Terms.</p>

            <h6 class="mt-3">10. Governing Law</h6>
            <p>These Terms shall be governed by the laws of the Philippines.</p>

            <h6 class="mt-3">11. Changes to Terms</h6>
            <p>DoTrack reserves the right to modify these Terms at any time. Continued use constitutes acceptance of the new Terms.</p>

            <h6 class="mt-3">12. Contact Information</h6>
            <p>For questions about these Terms, please contact us at support@dotrack.com.</p>
          </div>
          <div class="form-check mt-3">
            <input type="checkbox" class="form-check-input" id="agreeCheckbox" disabled>
            <label class="form-check-label" for="agreeCheckbox">I have read and agree to the terms and conditions</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="agreeBtn" disabled>I Agree</button>
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
      <a href="../ourteam.php">Our Team</a>
      <a href="../contact.php">Contact Us</a>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
      const termsModal = new bootstrap.Modal(document.getElementById('termsModal'));
      const termsLink = document.getElementById('termsLink');
      const termsCheckbox = document.getElementById('termsCheckbox');
      const agreeCheckbox = document.getElementById('agreeCheckbox');
      const agreeBtn = document.getElementById('agreeBtn');
      const termsText = document.getElementById('termsText');
      const loginForm = document.getElementById('loginForm');
      const signupForm = document.getElementById('signupForm');

      <?php if (!empty($error)): ?>
        document.getElementById('errorMessage').innerHTML = '<?php echo addslashes($error); ?>';
        errorModal.show();
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        document.getElementById('errorMessage').innerHTML = '<?php echo addslashes($_SESSION['error']); ?>';
        errorModal.show();
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      if (termsLink) {
        termsLink.addEventListener('click', function(e) {
          e.preventDefault();
          termsModal.show();
        });
      }

      if (termsText) {
        termsText.addEventListener('scroll', function() {
          const scrollPosition = this.scrollTop + this.clientHeight;
          const scrollHeight = this.scrollHeight;
          if (scrollHeight - scrollPosition < 10) {
            agreeCheckbox.disabled = false;
          }
        });
      }

      if (agreeCheckbox) {
        agreeCheckbox.addEventListener('change', function() {
          agreeBtn.disabled = !this.checked;
        });
      }

      if (agreeBtn) {
        agreeBtn.addEventListener('click', function() {
          if (termsCheckbox) {
            termsCheckbox.checked = true;
          }
          termsModal.hide();
        });
      }

      if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
          if (termsCheckbox && !termsCheckbox.checked) {
            e.preventDefault();
            termsModal.show();
          }
        });
      }
    });
  </script>
</body>

</html>