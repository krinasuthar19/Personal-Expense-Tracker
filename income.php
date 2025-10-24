<?php
// Include necessary files
include("session.php");
include("config.php"); 

// --- Process Income Form Submission ---
$success_message = $error_message = '';
$current_page = basename($_SERVER['PHP_SELF']);

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize and validate input
    $source = filter_input(INPUT_POST, 'income_source', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'income_amount', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'income_description', FILTER_SANITIZE_STRING);
    
    // FIX: Get the date input
    $raw_date = filter_input(INPUT_POST, 'income_date', FILTER_SANITIZE_STRING);
    
    // CRITICAL FIX: Ensure date is valid and format it explicitly for MySQL
    $date_object = DateTime::createFromFormat('Y-m-d', $raw_date);
    $date = $date_object ? $date_object->format('Y-m-d') : null;

    // Basic validation
    if (!$source || $amount === false || $amount <= 0 || !$date) {
        $error_message = "Please provide a valid Income Source, Amount, and Date.";
    } else {
        // Use Prepared Statement for Security 🛡️
        $sql = "INSERT INTO income (user_id, income_source, income_amount, income_date, income_description) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($con, $sql)) {
            // Binding: i=integer, s=string, d=double, s=string, s=string
            mysqli_stmt_bind_param($stmt, "isdss", $userid, $source, $amount, $date, $description);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Income of $".$amount." from ".$source." recorded successfully! Date: ".$date;
            } else {
                $error_message = "Error recording income (DB Error): " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error preparing statement: " . mysqli_error($con);
        }
    }
}
// --- Fetch Recent Income for Display ---
$recent_income_query = mysqli_query($con, "SELECT income_date, income_source, income_amount FROM income WHERE user_id = '$userid' ORDER BY income_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Income - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/feather.min.js"></script>
</head>

<body>
    <div class="d-flex" id="wrapper">
        
        <?php 
            // In a real refactor, this would be include('header.php');
            // Since you didn't want to restructure, copy the sidebar/nav section from index.php here:
        ?>
        
        <div id="sidebar-wrapper">
            <div class="user">
                <img class="img img-fluid rounded-circle shadow-sm" src="<?php echo $userprofile ?>" width="80">
                <h5 class="mt-2 mb-0"><?php echo $username ?></h5>
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
                <a href="profile.php" class="list-group-item list-group-item-action">
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
                <h1 class="mt-4 mb-4 font-weight-light">Income Management</h1>
                
                <div class="row">
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-plus-circle mr-2"></i> Record New Income
                            </div>
                            <div class="card-body">
                                
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

                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label for="income_source">Income Source/Category</label>
                                        <input type="text" class="form-control" id="income_source" name="income_source" placeholder="e.g., Salary, Freelance, Gift" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="income_amount">Amount </label>
                                        <input type="number" step="0.01" class="form-control" id="income_amount" name="income_amount" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="income_date">Date</label>
                                        <input type="date" class="form-control" id="income_date" name="income_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="income_description">Description (Optional)</label>
                                        <textarea class="form-control" id="income_description" name="income_description" rows="3" placeholder="Brief details about the income"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success btn-block mt-4">Record Income</button>
                                    <a href="manage_income.php" class="btn btn-outline-secondary btn-block mt-2">Manage All Income</a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-history mr-2"></i> Recent Income Records
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Source</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($recent_income_query) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($recent_income_query)): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($row['income_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($row['income_source']); ?></td>
                                                        <td class="text-success font-weight-bold"><?php echo number_format($row['income_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No income records found for this user.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="manage_income.php" class="btn btn-sm btn-outline-info float-right">View All &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        </div>
    <script src="js/jquery.slim.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/Chart.min.js"></script>
    
    <script>
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        feather.replace()
    </script>
</body>
</html>