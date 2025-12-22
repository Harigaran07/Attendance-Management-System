<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark custom-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-school"></i> Attendance System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <header class="hero-section text-center py-5">
        <div class="container hero-content">
            <h1 class="display-4">Welcome To Online Attendance Management System</h1>
            <p class="lead">Manage and monitor student attendance with ease.</p>
            <a href="login.php" class="btn btn-primary btn-lg mt-3"><i class="fas fa-sign-in-alt"></i> Get Started</a>
        </div>
    </header>
    
    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container text-center">
            <h2 class="mb-4">Our Features</h2>
            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <div class="feature-box p-4 shadow rounded">
                        <i class="fas fa-user-check fa-3x text-primary"></i>
                        <h4 class="mt-3">Quick Attendance</h4>
                        <p>Mark attendance seamlessly with just one click.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="feature-box p-4 shadow rounded">
                        <i class="fas fa-envelope fa-3x text-success"></i>
                        <h4 class="mt-3">Real time Dashboard</h4>
                        <p>Update the real time Dashboard based on attendance.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="feature-box p-4 shadow rounded">
                        <i class="fas fa-chart-bar fa-3x text-danger"></i>
                        <h4 class="mt-3">Analytics & Reports</h4>
                        <p>Generate and analyze attendance reports easily.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer text-center py-3">
        <div class="container">
            <p>&copy; 2025 Student Attendance System. All rights reserved. <br><small>Developed by GCT Coimbatore from the Department of Electronics and Communication  Engineering.</small></p>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
