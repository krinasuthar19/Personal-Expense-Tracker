<?php
// Note: If config.php is not included in session.php, you must include it here.
include("session.php");
include("config.php"); 

$current_page = basename(__FILE__);
$success_message = $error_message = '';

// --- Password Change Logic ---
if (isset($_POST['updatepassword'])) {
    
    // Sanitize input
    $curr_password = filter_input(INPUT_POST, 'curr_password', FILTER_SANITIZE_STRING);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING);
    $confirm_new_password = filter_input(INPUT_POST, 'confirm_new_password', FILTER_SANITIZE_STRING);

    if (empty($curr_password) || empty($new_password) || empty($confirm_new_password)) {
        $error_message = "All fields are required.";
    } elseif ($new_password !== $confirm_new_password) {
        $error_message = "New Password and Confirm New Password do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long.";
    } else {
        
        // 1. Fetch the currently stored password hash (secure)
        $sql_fetch = "SELECT password FROM users WHERE user_id = ?";
        if ($stmt_fetch = mysqli_prepare($con, $sql_fetch)) {
            mysqli_stmt_bind_param($stmt_fetch, "i", $userid);
            mysqli_stmt_execute($stmt_fetch);
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            $user = mysqli_fetch_assoc($result_fetch);
            mysqli_stmt_close($stmt_fetch);
            
            $stored_hash = $user['password'] ?? '';
            
            // 2. Verify current password against stored hash
            // ASSUMPTION: Your existing login/registration uses password_hash() for storage.
            if (!password_verify($curr_password, $stored_hash)) {
                $error_message = "Current password is incorrect.";
            } else {
                
                // 3. Hash the new password securely
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // 4. Update the password in the database (secure)
                $sql_update = "UPDATE users SET password = ? WHERE user_id = ?";
                if ($stmt_update = mysqli_prepare($con, $sql_update)) {
                    mysqli_stmt_bind_param($stmt_update, "si", $new_password_hash, $userid);
                    
                    if (mysqli_stmt_execute($stmt_update)) {
                        $success_message = "Password updated successfully! Please log in again with your new password.";
                        // Log user out for security immediately after successful change
                        // header('Refresh: 3; URL=logout.php'); 
                        // For development, just show success:
                    } else {
                        $error_message = "Database error updating password: " . mysqli_stmt_error($stmt_update);
                    }
                    mysqli_stmt_close($stmt_update);
                }
            }
        } else {
            $error_message = "Error fetching user data.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Change Password - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/feather.min.js"></script>
</head>

<body>

    <div class="d-flex" id="wrapper">

        <div id="sidebar-wrapper">
            <div class="user">
                <img class="img img-fluid rounded-circle shadow-sm" src="<?php echo $userprofile ?>" width="80">
                <h5 class="mt-2 mb-0 text-white"><?php echo $username ?></h5>
                <p class="small text-muted"><?php echo $useremail ?></p>
            </div>
            
            <div class="sidebar-heading">Core</div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'index.php') ? 'sidebar-active' : ''; ?>">
                    <span data-feather="home" class="mr-2"></span> Dashboard
                </a>
                <a href="daily_report.php" class="list-group-item list-group-item-action">
                    <span data-feather="calendar" class="mr-2"></span> Daily Report
                </a>
                
                <a href="add_expense.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'add_expense.php') ? 'sidebar-active' : ''; ?>">
                    <span data-feather="plus-square" class="mr-2"></span> Add Expense
                </a>
                <a href="manage_expense.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'manage_expense.php') ? 'sidebar-active' : ''; ?>">
                    <span data-feather="list" class="mr-2"></span> Manage Expenses
                </a>

                <a href="income.php" class="list-group-item list-group-item-action">
                    <span data-feather="dollar-sign" class="mr-2"></span> Income Management
                </a>

                <a href="budget.php" class="list-group-item list-group-item-action">
                    <span data-feather="check-square" class="mr-2"></span> Budgeting
                </a>

                <a href="reports.php" class="list-group-item list-group-item-action">
                    <span data-feather="bar-chart-2" class="mr-2"></span> Reports & Analytics
                </a>
                <a href="manage_categories.php" class="list-group-item list-group-item-action">
                    <span data-feather="layers" class="mr-2"></span> Manage Categories
                </a>

            </div>
            
            <div class="sidebar-heading">Settings</div>
            <div class="list-group list-group-flush">
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <span data-feather="user" class="mr-2"></span> Profile
                </a>
                 <a href="change_password.php" class="list-group-item list-group-item-action sidebar-active">
                    <span data-feather="key" class="mr-2"></span> Change Password
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                    <span data-feather="power" class="mr-2"></span> Logout
                </a>
            </div>
        </div>
        <div id="page-content-wrapper">

            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <button class="btn btn-outline-primary" type="button" id="menu-toggle">
                    <span data-feather="menu"></span>
                </button>

                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ml-auto mt-2 mt-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img class="img img-fluid rounded-circle" src="<?php echo $userprofile ?>" width="30">
                                <span class="ml-1 d-none d-lg-inline-block"><?php echo $username ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="profile.php"><span data-feather="user" class="mr-2"></span>Your Profile</a>
                                <a class="dropdown-item" href="change_password.php"><span data-feather="key" class="mr-2"></span>Change Password</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php"><span data-feather="power" class="mr-2"></span>Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="container-fluid p-4">
                <div class="row justify-content-center">
                    <div class="col-md-7">
                        <h1 class="mt-4 mb-4 font-weight-light">Change Password</h1>
                        <hr class="mb-4">
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow-sm">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-lock mr-2"></i> Update Your Security Credentials
                            </div>
                            <div class="card-body">
                                <form class="form" action="" method="post" autocomplete="off">
                                    <div class="form-group">
                                        <label for="curr_password">Enter Current Password</label>
                                        <input type="password" class="form-control" name="curr_password" id="curr_password" placeholder="Current Password" required>
                                    </div>
                                    <hr>
                                    <div class="form-group">
                                        <label for="new_password">Enter New Password</label>
                                        <input type="password" class="form-control" name="new_password" id="new_password" placeholder="New Password (min 6 characters)" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_new_password">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_new_password" id="confirm_new_password" placeholder="Confirm New Password" required>
                                    </div>
                                    
                                    <div class="form-group mt-4">
                                        <button class="btn btn-block btn-primary" name="updatepassword" type="submit">
                                            <i class="fas fa-sync-alt mr-2"></i> Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        </div>
    <script src="js/jquery.slim.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    
    <script>
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        feather.replace()
    </script>
</body>

</html>