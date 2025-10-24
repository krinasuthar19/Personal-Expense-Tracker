<?php
// Include necessary files
include("session.php");
include("config.php"); 

// --- Configuration and Variable Setup ---
$success_message = $error_message = '';
$current_page = basename($_SERVER['PHP_SELF']);

// --- 1. CONSISTENCY: Retrieve selected month/year from SESSION or default ---
// We assume index.php (dashboard) will set $_SESSION['selected_month'] and $_SESSION['selected_year'].
// If not set, default to current month/year.
$selected_month = $_SESSION['selected_month'] ?? date('m');
$selected_year = $_SESSION['selected_year'] ?? date('Y');

// Construct the base URL parameters for persistent month/year tracking in navigation
$base_url_params = "?month={$selected_month}&year={$selected_year}";


// Define default categories (You should ideally fetch this from a configuration table later)
// --- Dynamic Category Fetching ---
$categories = [];
$user_id_int = (int)$userid; 

$sql_fetch_cats = "SELECT category_name FROM expense_categories WHERE user_id = ? AND category_status = 'active' ORDER BY category_name ASC";

if (isset($con) && $stmt_cats = mysqli_prepare($con, $sql_fetch_cats)) {
    mysqli_stmt_bind_param($stmt_cats, "i", $user_id_int);
    mysqli_stmt_execute($stmt_cats);
    $result_cats = mysqli_stmt_get_result($stmt_cats);
    
    while ($row = mysqli_fetch_assoc($result_cats)) {
        $categories[] = $row['category_name'];
    }
    mysqli_stmt_close($stmt_cats);
}


// --- Process Expense Form Submission (NO CHANGE NEEDED HERE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize and validate input
    $expense = filter_input(INPUT_POST, 'expense_amount', FILTER_VALIDATE_FLOAT);
    $category = filter_input(INPUT_POST, 'expense_category', FILTER_SANITIZE_STRING);
    $date     = filter_input(INPUT_POST, 'expense_date', FILTER_SANITIZE_STRING);
    $note     = filter_input(INPUT_POST, 'expense_note', FILTER_SANITIZE_STRING);
    
    // Basic validation
    if ($expense === false || $expense <= 0 || !in_array($category, $categories) || !$date) {
        $error_message = "Please ensure the amount is valid and a category is selected.";
    } else {
        // 2. Use Prepared Statement for Security 🛡️
        $sql = "INSERT INTO expenses (user_id, expense, expensecategory, expensedate, expensenote) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($con, $sql)) {
            // Bind parameters: (i=integer, d=double/float, s=string, s=string, s=string)
            mysqli_stmt_bind_param($stmt, "idsss", $userid, $expense, $category, $date, $note);
            
            // 3. Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Expense of $".$expense." for ".$category." recorded successfully!";
            } else {
                $error_message = "Error recording expense: " . mysqli_stmt_error($stmt);
            }
            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error preparing statement: " . mysqli_error($con);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Expense - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/feather.min.js"></script>
</head>

<body>
    <div class="d-flex" id="wrapper">
        
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
            <h1 class="mt-4 mb-4 font-weight-light">Add New Expense</h1>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-lg border-0 rounded-lg mt-5">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-hand-holding-usd mr-2"></i> Record Your Expenditure
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
                                    
                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="expense_amount">Amount </label>
                                                <input type="number" step="0.01" class="form-control" id="expense_amount" name="expense_amount" placeholder="0.00" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="expense_date">Date</label>
                                                <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="expense_category">Category</label>
                                        <select class="form-control" id="expense_category" name="expense_category" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="expense_note">Description/Note</label>
                                        <textarea class="form-control" id="expense_note" name="expense_note" rows="3" placeholder="What was this expense for? (e.g., Groceries at Walmart)"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-danger btn-block mt-4">Record Expense</button>
                                    <a href="manage_expense.php<?php echo $base_url_params; ?>" class="btn btn-outline-secondary btn-block mt-2">Go to Expense History</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        </div>
    <script src="js/jquery.slim.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    
    <script>
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        feather.replace()
    </script>
</body>
</html>