<?php
// CRITICAL SECURITY NOTE: Your original code used MD5 for password storage, 
// which is insecure. This updated code assumes you are now using password_hash() 
// for new user registrations and verifies against that modern hash using password_verify().

require('config.php');
session_start();

$errormsg = "";
$success_redirect = false; // Flag to prevent headers when errors occur

// Initialize $_POST['email'] for htmlspecialchars on page load
if (!isset($_POST['email'])) $_POST['email'] = '';

if (isset($_POST['email']) && isset($_POST['password'])) {
    
    // 1. Sanitize input (Prepared Statements handle escaping, but we clean input type)
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if (empty($email) || empty($password)) {
       $errormsg = "Email and password are required.";
    } else {
        
        // 2. Secure Query: Select hash based on email (prevent SQL injection)
        $query = "SELECT user_id, password, firstname FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($con, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            // 3. Verification
            if ($user && password_verify($password, $user['password'])) {
                
                // Password is correct, set session variables
                $_SESSION['email'] = $email;
                $_SESSION['userid'] = $user['user_id'];
                $_SESSION['username'] = $user['firstname'];
                
                $success_redirect = true;
                header("Location: index.php");
                exit();
                
            } else {
                // Invalid email or password
                $errormsg = "Invalid email or password.";
            }
        } else {
            $errormsg = "Database error during login preparation.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            /* Consistent background with register.php */
            background: linear-gradient(135deg, #f4f6f9 0%, #e9ecef 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .login-form {
            width: 100%;
            max-width: 400px; /* Consistent sizing */
            font-size: 15px;
        }

        .login-form form {
            margin-bottom: 15px;
            background: #fff;
            /* Consistent shadow and rounding */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); 
            padding: 40px;
            border-radius: 12px; 
        }

        .login-form h2 {
            color: #17a2b8; 
            margin: 0 0 15px;
            text-align: center;
            font-weight: 600;
        }
        
        .login-form .hint-text {
            color: #999;
            margin-bottom: 25px;
            text-align: center;
        }

        /* Modern input styling consistent with register.php */
        .form-control {
            min-height: 48px; 
            border-radius: 8px;
            border: 1px solid #ced4da;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
            padding: 10px 15px;
        }
        
        .form-control:focus {
            border-color: #17a2b8;
            box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
        }

        /* Input Group styling for icons */
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group .form-control {
            border-radius: 0 8px 8px 0;
        }

        .btn-primary {
            background-color: #17a2b8;
            border-color: #17a2b8;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #138496;
            border-color: #117a8b;
            transform: translateY(-2px); /* Slight lift on hover */
        }
        
        .btn {
            min-height: 48px;
            font-size: 17px;
            font-weight: 700;
            border-radius: 8px;
        }
        
        .text-primary {
            color: #17a2b8 !important; 
            font-weight: bold;
        }
        .text-primary:hover {
            color: #138496 !important;
            text-decoration: underline;
        }
        
        .form-check-label {
            font-weight: normal;
        }
    </style>
</head>

<body>
    <div class="login-form">
        <form action="" method="POST" autocomplete="off">
            <h2 class="text-center">D.E.T. Login</h2>
            <p class="hint-text">Welcome back to your Daily Expense Tracker.</p>
            
            <?php if ($errormsg !== ""): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $errormsg; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registration']) && $_GET['registration'] == 'success'): ?>
                <div class="alert alert-success text-center" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> Registration successful! Please log in.
                </div>
            <?php endif; ?>

            <div class="form-group pb-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                    </div>
                    <input type="email" name="email" class="form-control" placeholder="Email Address" required="required" value="<?php echo htmlspecialchars($_POST['email']); ?>">
                </div>
            </div>
            
            <div class="form-group pb-4">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    </div>
                    <input type="password" name="password" class="form-control" placeholder="Password" required="required">
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt mr-2"></i> Log in
                </button>
            </div>
            
            <div class="clearfix small text-center pt-2">
                <label class="form-check-label"><input type="checkbox"> Remember me</label>
            </div>
            
        </form>
        <p class="text-center">Don't have an account? <a href="register.php" class="text-primary">Register Here</a></p>
    </div>
</body>
<script src="js/jquery.slim.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script> </html>