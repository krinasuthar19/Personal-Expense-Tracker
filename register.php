<?php
// CRITICAL SECURITY NOTE: This uses password_hash() for secure storage.
require('config.php');

$error_message = "";

// Ensure $_POST variables are initialized for htmlspecialchars in input fields
if (!isset($_POST['firstname'])) $_POST['firstname'] = '';
if (!isset($_POST['lastname'])) $_POST['lastname'] = '';
if (!isset($_POST['email'])) $_POST['email'] = '';


if (isset($_REQUEST['firstname'])) {
    
    // 1. Sanitize input
    $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
    $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);
    
    // 2. Validate input
    if ($password !== $confirm_password) {
        $error_message = "ERROR: Password and Confirmation password do not match.";
    } elseif (empty($firstname) || empty($email) || empty($password)) {
        $error_message = "All fields must be filled.";
    } else {
        
        // 3. Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt_check = mysqli_prepare($con, $check_sql);
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error_message = "This email is already registered. Please login or use a different email.";
        } else {
            // 4. Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $trn_date = date("Y-m-d H:i:s");
            
            // 5. Secure Query: INSERT user data using prepared statement
            $query = "INSERT INTO users (firstname, lastname, password, email, trn_date) VALUES (?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($con, $query)) {
                // Binding: s, s, s, s, s
                mysqli_stmt_bind_param($stmt, "sssss", $firstname, $lastname, $hashed_password, $email, $trn_date);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Successful registration
                    // In a live system, this should likely redirect to index.php after setting a session
                    header("Location: login.php?registration=success");
                    exit();
                } else {
                    $error_message = "Database error: Could not register user. " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error_message = "System error during registration process.";
            }
        }
        mysqli_stmt_close($stmt_check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Register - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            /* Clean, appealing background gradient */
            background: linear-gradient(135deg, #f4f6f9 0%, #e9ecef 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .signup-form {
            width: 100%;
            max-width: 420px; /* Reduced width for focus */
            font-size: 15px;
        }

        .signup-form form {
            color: #555;
            border-radius: 12px; /* Smoother rounding */
            background: #fff;
            /* Stronger, modern shadow */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); 
            padding: 40px;
        }

        .signup-form h2 {
            color: #17a2b8; /* Info/Primary color for title */
            margin: 0 0 15px;
            text-align: center;
            font-weight: 600;
        }
        
        .signup-form .hint-text {
            color: #999;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-control {
            min-height: 48px; /* Slightly taller inputs */
            border-radius: 8px;
            border: 1px solid #ced4da;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
            padding: 10px 15px;
        }
        
        .form-control:focus {
            border-color: #17a2b8;
            box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px); /* Slight lift on hover */
        }
        
        .btn {
            min-height: 48px;
            font-size: 17px;
            font-weight: 700;
            border-radius: 8px;
        }
        
        .text-primary {
            color: #17a2b8 !important; /* Ensuring consistency */
        }
        .text-primary:hover {
            color: #138496 !important;
            text-decoration: underline;
        }
        
        /* Centering the container content for vertical alignment */
        .login-link {
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="signup-form">
        <form action="" method="POST" autocomplete="off">
            <h2>Register Account</h2>
            <p class="hint-text">Create your account to start tracking your expenses.</p>
            
            <?php if ($error_message !== ""): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <div class="row gx-2">
                    <div class="col pb-3">
                        <input type="text" class="form-control" name="firstname" placeholder="First Name" required="required" value="<?php echo htmlspecialchars($_POST['firstname']); ?>">
                    </div>
                    <div class="col pb-3">
                        <input type="text" class="form-control" name="lastname" placeholder="Last Name" required="required" value="<?php echo htmlspecialchars($_POST['lastname']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group pb-3">
                <input type="email" class="form-control" name="email" placeholder="Email Address" required="required" value="<?php echo htmlspecialchars($_POST['email']); ?>">
            </div>
            
            <div class="form-group pb-3">
                <input type="password" class="form-control" name="password" placeholder="Password (Min 6 characters)" required="required">
            </div>
            
            <div class="form-group pb-3">
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required="required">
            </div>
            
            
            <div class="form-group">
                <button type="submit" class="btn btn-danger btn-block">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </button>
            </div>
        </form>
        <div class="text-center login-link">Already have an account? <a class="text-primary font-weight-bold" href="login.php">Login Here</a></div>
    </div>
</body>
<script src="js/jquery.slim.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>

</html>
