<?php
include("session.php");
include("config.php"); 

$current_page = basename(__FILE__);
$success_message = $error_message = '';

// --- 1. Handle Profile Data Update (Name Change) ---
if (isset($_POST['save'])) {
    
    // Secure input sanitization
    $fname = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lname = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);

    if (empty($fname) || empty($lname)) {
        $error_message = "First Name and Last Name cannot be empty.";
    } else {
        // Secure UPDATE query using prepared statement (SSi = string, string, integer)
        $sql = "UPDATE users SET firstname = ?, lastname = ? WHERE user_id = ?";
        
        if ($stmt = mysqli_prepare($con, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $fname, $lname, $userid);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Profile updated successfully! Refreshing data...";
                // Redirect to refresh session data
                header('Refresh: 2; URL=profile.php'); 
                exit();
            } else {
                $error_message = "ERROR: Could not execute query. " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error preparing statement.";
        }
    }
}


// --- 2. Handle Profile Picture Update (Securely) ---
if (isset($_POST['but_upload'])) {

    // 🛡️ CRITICAL FIX: Check if a file was successfully uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK || empty($_FILES["file"]["tmp_name"])) {
        $error_message = "No file selected or a critical upload error occurred.";
    } else {

        $target_dir = "uploads/";
        $name = basename($_FILES['file']['name']);
        $target_file = $target_dir . $name;
        
        // Check if the file is an actual image (resolves Uncaught ValueError)
        if (@getimagesize($_FILES["file"]["tmp_name"]) === false) { 
            $error_message = "File is not a valid image.";
        } else {
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $extensions_arr = array("jpg", "jpeg", "png", "gif");

            if (in_array($imageFileType, $extensions_arr)) {
                
                // Create a unique filename
                $new_filename = $userid . '_' . time() . '.' . $imageFileType;
                $new_target_file = $target_dir . $new_filename;
                
                // Secure UPDATE query for profile path
                $query = "UPDATE users SET profile_path = ? WHERE user_id = ?";
                
                if ($stmt = mysqli_prepare($con, $query)) {
                    mysqli_stmt_bind_param($stmt, "si", $new_filename, $userid);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Upload file
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $new_target_file)) {
                            $success_message = "Profile picture updated successfully! Refreshing...";
                            // Redirect to ensure session data (userprofile) is updated
                            header('Refresh: 1; URL=profile.php');
                            exit();
                        } else {
                            $error_message = "File upload failed on the server. Check folder permissions.";
                        }
                    } else {
                        $error_message = "Database error updating profile path.";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                $error_message = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
            }
        }
    }
}

// Re-declare $current_page after all logic
$current_page = basename($_SERVER['PHP_SELF']); 

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Profile Settings - Daily Expense Tracker</title>

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
                <a href="profile.php" class="list-group-item list-group-item-action sidebar-active">
                    <span data-feather="user" class="mr-2"></span> Profile
                </a>
                 <a href="change_password.php" class="list-group-item list-group-item-action">
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
                        <h1 class="mt-4 mb-4 font-weight-light">Profile Settings</h1>
                        <hr class="mb-4">
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-camera mr-2"></i> Update Profile Picture
                            </div>
                            <div class="card-body">
                                <form class="form" method="post" action="" enctype='multipart/form-data'>
                                    <div class="text-center mb-3">
                                        <img src="<?php echo $userprofile; ?>" class="img img-fluid rounded-circle avatar shadow-sm" width="120" alt="Profile Picture">
                                    </div>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" name='file' class="custom-file-input" id="profilepic" required>
                                            <label class="custom-file-label" for="profilepic">Choose new photo</label>
                                        </div>
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit" name='but_upload'>
                                                <i class="fas fa-upload mr-1"></i> Upload
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-user-edit mr-2"></i> Personal Details
                            </div>
                            <div class="card-body">
                                <form class="form" action="" method="post" autocomplete="off">
                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="first_name">First Name</label>
                                                <input type="text" class="form-control" name="first_name" id="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($firstname); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="last_name">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" id="last_name" value="<?php echo htmlspecialchars($lastname); ?>" placeholder="Last Name" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email (Cannot be changed)</label>
                                        <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($useremail); ?>" disabled>
                                    </div>
                                    <div class="form-group mt-4">
                                        <button class="btn btn-block btn-success" name="save" type="submit">
                                            <i class="fas fa-save mr-2"></i> Save Changes
                                        </button>
                                        <a href="change_password.php" class="btn btn-block btn-outline-info mt-2">
                                            <i class="fas fa-key mr-2"></i> Change Password
                                        </a>
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
    
    <script type="text/javascript">
        $(document).ready(function() {
            // Function to preview the image selected by the user
            var readURL = function(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('.avatar').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Trigger the preview function when a file is selected
            $("#profilepic").on('change', function() {
                readURL(this);
            });
            
            // Display the filename in the custom-file-label (Bootstrap feature)
            $('#profilepic').on('change',function(){
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
        });
    </script>

</body>

</html>