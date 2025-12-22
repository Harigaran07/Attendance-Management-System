<?php
session_start();

// Display error message if available
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : "";
unset($_SESSION['error']); // Clear error after displaying it
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/logins.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-box d-flex flex-column flex-md-row">
                    <div class="login-left d-none d-md-block w-50 bg-primary p-5 text-white text-center"></div>
                    <div class="login-right w-100 w-md-50 p-4">
                        <h2 class="text-center">Login</h2>

                        <!-- Display error message -->
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form action="login_user.php" method="POST" id="loginForm">
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" placeholder="Email" name="email" required>
                            </div>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" placeholder="Password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                            <div class="text-center mt-3">
                                 <a href="forgot_password.php">Forgot Password?</a> | <a href="register.php">Register</a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
