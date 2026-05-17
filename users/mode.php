<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header('Location: /dotrack/auth/loginandsignup.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoTrack - Choose Mode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Averia+Serif+Libre:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/dotrack/assets/css/mode.css">
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
            <a href="/dotrack/account.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 14c2 0 4 1 4 3v2H8v-2c0-2 2-3 4-3z" />
                    <circle cx="12" cy="8" r="3" />
                </svg>
                Account
            </a>
            <a href="/dotrack/about.php" class="nav-link">
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
        <div class="row justify-content-center w-100">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <div class="mode-card text-center p-4 p-md-5">
                    <h2 class="mode-title mb-4">Choose Your Mode</h2>
                    <div class="d-flex flex-column flex-sm-row gap-4 justify-content-center">
                        <a href="personal.php" class="mode-btn personal-btn">
                            <i class='bx bxs-user-circle'></i>
                            <span>Personal</span>
                        </a>
                        <a href="createjoin.php" class="mode-btn collaborate-btn">
                            <i class='bx bxs-group'></i>
                            <span>Collaborate</span>
                        </a>
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
</body>

</html>