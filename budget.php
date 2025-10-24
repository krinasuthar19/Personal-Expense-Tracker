<?php
// Include necessary files
include("session.php");
include("config.php"); 

$success_message = $error_message = '';
$current_page = basename($_SERVER['PHP_SELF']);

// --- 1. PERSISTENCE: Retrieve selected month/year from SESSION or GET ---
$selected_month = $_SESSION['selected_month'] ?? date('n'); // Use 'n' for month without leading zero (1 to 12)
$selected_year = $_SESSION['selected_year'] ?? date('Y');

// Allow GET to override session/default if navigating directly (though usually handled by index.php)
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : $selected_month;
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $selected_year;

// Ensure session reflects the current view
$_SESSION['selected_month'] = $selected_month;
$_SESSION['selected_year'] = $selected_year;

// Construct the base URL parameters for persistent month/year tracking in navigation
$base_url_params = "?month={$selected_month}&year={$selected_year}";

// For displaying the month title
$display_month_year = date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year));

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

// --- 2. FETCH TOTAL INCOME FOR VALIDATION ---
$total_income_for_month = 0.00;
$first_day_of_month = date("Y-m-01", strtotime("{$selected_year}-{$selected_month}-01"));
$last_day_of_month = date("Y-m-t", strtotime("{$selected_year}-{$selected_month}-01")); 

$sql_total_inc = "SELECT SUM(income_amount) AS total_inc FROM income WHERE user_id = ? AND income_date BETWEEN ? AND ?";
if (isset($con) && $stmt_total_inc = mysqli_prepare($con, $sql_total_inc)) {
    mysqli_stmt_bind_param($stmt_total_inc, "iss", $user_id_int, $first_day_of_month, $last_day_of_month);
    mysqli_stmt_execute($stmt_total_inc);
    $result_total_inc = mysqli_stmt_get_result($stmt_total_inc);
    $total_income_for_month = mysqli_fetch_assoc($result_total_inc)['total_inc'] ?? 0.00;
    mysqli_stmt_close($stmt_total_inc);
}


// --- 3. Handle Budget Form Submission (SET/UPDATE BUDGET) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_budget'])) {
    
    // Sanitize and validate input
    $budget_category = filter_input(INPUT_POST, 'budget_category', FILTER_SANITIZE_STRING);
    $budget_limit    = filter_input(INPUT_POST, 'budget_limit', FILTER_VALIDATE_FLOAT);
    $budget_month    = filter_input(INPUT_POST, 'budget_month', FILTER_VALIDATE_INT);
    $budget_year     = filter_input(INPUT_POST, 'budget_year', FILTER_VALIDATE_INT);
    
    // Initial validation
    if ($budget_limit === false || $budget_limit < 0 || !in_array($budget_category, $categories) || $budget_month < 1 || $budget_month > 12 || $budget_year < 2000) {
        $error_message = "Please provide a valid category, budget limit, month, and year.";
    } 
    // Income Check Logic
    else {
        
        // 3a. Get the CURRENT budget limit for the category being modified (if it exists)
        $current_category_budget = 0.00;
        $sql_current = "SELECT budget_limit FROM budgets WHERE user_id = ? AND budget_month = ? AND budget_year = ? AND category = ?";
        
        if ($stmt_current = mysqli_prepare($con, $sql_current)) {
            mysqli_stmt_bind_param($stmt_current, "iiis", $user_id_int, $budget_month, $budget_year, $budget_category);
            mysqli_stmt_execute($stmt_current);
            $result_current = mysqli_stmt_get_result($stmt_current);
            $current_category_budget = mysqli_fetch_assoc($result_current)['budget_limit'] ?? 0.00;
            mysqli_stmt_close($stmt_current);
        }

        // 3b. Get the SUM of ALL existing budgets for the month
        $total_allocated_budgets = 0.00;
        $sql_total_allocated = "SELECT SUM(budget_limit) AS total_sum FROM budgets WHERE user_id = ? AND budget_month = ? AND budget_year = ?";
        
        if ($stmt_total_allocated = mysqli_prepare($con, $sql_total_allocated)) {
            mysqli_stmt_bind_param($stmt_total_allocated, "iii", $user_id_int, $budget_month, $budget_year);
            mysqli_stmt_execute($stmt_total_allocated);
            $result_total_allocated = mysqli_stmt_get_result($stmt_total_allocated);
            $total_allocated_budgets = mysqli_fetch_assoc($result_total_allocated)['total_sum'] ?? 0.00;
            mysqli_stmt_close($stmt_total_allocated);
        }
        
        // 3c. Calculate the NEW Total Committed: 
        $new_total_committed = $total_allocated_budgets - $current_category_budget + $budget_limit;
        
        // 3d. Check if the new total budget exceeds total income
        if ($new_total_committed > $total_income_for_month) {
            
            $remaining_income = $total_income_for_month - ($total_allocated_budgets - $current_category_budget);
            
            // Provide a clear error message
            $error_message = "Your total budget allocation for {$display_month_year} cannot exceed your total income of " . number_format($total_income_for_month, 2) . ". ";
            
            if ($remaining_income > 0) {
                $error_message .= "You can allocate a maximum of " . number_format($remaining_income, 2) . " to '{$budget_category}'.";
            } else {
                $error_message .= "All your income is currently allocated. You must reduce other budgets before increasing '{$budget_category}'.";
            }
            
        } else {
            // 3e. Proceed with UPSERT if validation passes (Allows setting or updating)
            $sql = "INSERT INTO budgets (user_id, category, budget_limit, budget_month, budget_year) 
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    budget_limit = VALUES(budget_limit)";
            
            if ($stmt = mysqli_prepare($con, $sql)) {
                mysqli_stmt_bind_param($stmt, "isdii", $userid, $budget_category, $budget_limit, $budget_month, $budget_year);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Check if it was an insert (new row) or update (existing row)
                    if (mysqli_stmt_affected_rows($stmt) == 1) {
                         $action = "set";
                    } elseif (mysqli_stmt_affected_rows($stmt) == 2) {
                         $action = "updated"; // 2 means 1 row changed, plus one "duplicate" match count
                    } else {
                         $action = "set/updated";
                    }
                    
                    $success_message = "Budget for " . $budget_category . " successfully {$action} to " . number_format($budget_limit, 2) . " for " . date("F Y", mktime(0, 0, 0, $budget_month, 10, $budget_year)) . ".";
                } else {
                    $error_message = "Error setting budget: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error_message = "Database error preparing statement: " . mysqli_error($con);
            }
        }
    }
}

// --- 4. Fetch Budgets and Expenses for SELECTED Month (Display Logic) ---
// Note: This logic remains the same as before to load the data for the report table
$user_id_int = (int)$userid;

// Fetch all budgets for the SELECTED month/year
$budgets_query = "SELECT * FROM budgets WHERE user_id = ? AND budget_month = ? AND budget_year = ?";
$budgets_data = [];
$current_total_allocated = 0.00; // Initialize total allocated amount

if ($stmt_budgets = mysqli_prepare($con, $budgets_query)) {
    mysqli_stmt_bind_param($stmt_budgets, "iii", $user_id_int, $selected_month, $selected_year);
    mysqli_stmt_execute($stmt_budgets);
    $result_budgets = mysqli_stmt_get_result($stmt_budgets);
    while ($row = mysqli_fetch_assoc($result_budgets)) {
        $budgets_data[$row['category']] = $row;
        $current_total_allocated += $row['budget_limit']; // Calculate current allocated total
    }
    mysqli_stmt_close($stmt_budgets);
}

// --- CALCULATION FOR REMAINING BUDGET AMOUNT ---
$remaining_for_allocation = $total_income_for_month - $current_total_allocated;
// ----------------------------------------------------


// Fetch total spent per category for the SELECTED month
$expenses_query = "SELECT expensecategory, SUM(expense) AS total_spent 
                   FROM expenses 
                   WHERE user_id = ? 
                   AND MONTH(expensedate) = ? 
                   AND YEAR(expensedate) = ? 
                   GROUP BY expensecategory";
$expenses_spent = [];

if ($stmt_expenses = mysqli_prepare($con, $expenses_query)) {
    mysqli_stmt_bind_param($stmt_expenses, "iii", $user_id_int, $selected_month, $selected_year);
    mysqli_stmt_execute($stmt_expenses);
    $result_expenses = mysqli_stmt_get_result($stmt_expenses);
    while ($row = mysqli_fetch_assoc($result_expenses)) {
        $expenses_spent[$row['expensecategory']] = $row['total_spent'];
    }
    mysqli_stmt_close($stmt_expenses);
}

// Combine data for display
$budget_report = [];
$has_active_budget = false; 
foreach ($categories as $cat) {
    $limit = $budgets_data[$cat]['budget_limit'] ?? 0.00;
    $spent = $expenses_spent[$cat] ?? 0.00;
    $remaining = $limit - $spent;
    $percentage = ($limit > 0) ? round(($spent / $limit) * 100, 1) : 0;

    if ($limit > 0) {
        $has_active_budget = true;
    }

    // Determine status color
    $status_class = 'success';
    if ($percentage >= 100) {
        $status_class = 'danger';
    } elseif ($percentage >= 75) {
        $status_class = 'warning';
    }

    $budget_report[] = [
        'category' => $cat,
        'limit' => $limit,
        'spent' => $spent,
        'remaining' => $remaining,
        'percentage' => $percentage,
        'status_class' => $status_class
    ];
}

// Utility function for year selection dropdown (reused from index.php)
function createYearOptions($selected_year) {
    $html = '';
    $currentYear = date('Y');
    $startYear = $currentYear - 5; // Go back 5 years
    for ($y = $currentYear + 1; $y >= $startYear; $y--) {
        $selected = ($y == $selected_year) ? 'selected' : '';
        $html .= "<option value=\"{$y}\" {$selected}>{$y}</option>";
    }
    return $html;
}

// Utility function for month selection dropdown (reused from index.php)
function createMonthOptions($selected_month) {
    $html = '';
    for ($m = 1; $m <= 12; $m++) {
        $monthName = date('F', mktime(0, 0, 0, $m, 10));
        $selected = ($m == $selected_month) ? 'selected' : '';
        $html .= "<option value=\"{$m}\" {$selected}>{$monthName}</option>";
    }
    return $html;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Budget Management - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/feather.min.js"></script>
    <style>
        .progress-container { margin-bottom: 0.5rem; }
        .budget-card { transition: all 0.2s; }
        .budget-card:hover { border-color: #007bff; box-shadow: 0 0 10px rgba(0, 123, 255, 0.1); }
        .remaining-amount { font-size: 1.25rem; font-weight: bold; }
    </style>
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
                <a href="index.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="home" class="mr-2"></span> Dashboard
                </a>
                
                <a href="add_expense.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="plus-square" class="mr-2"></span> Add Expense
                </a>
                <a href="manage_expense.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="list" class="mr-2"></span> Manage Expenses
                </a>

                <a href="income.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="dollar-sign" class="mr-2"></span> Income Management
                </a>

                <a href="budget.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action sidebar-active">
                    <span data-feather="check-square" class="mr-2"></span> Budgeting
                </a>

                <a href="reports.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="bar-chart-2" class="mr-2"></span> Reports & Analytics
                </a>
                <a href="manage_categories.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
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
            <h1 class="mt-4 mb-4 font-weight-light">Budgeting: <?php echo $display_month_year; ?></h1>
                
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

                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-money-bill-alt mr-2"></i> Set / Update Budget for <?php echo $display_month_year; ?>
                            </div>
                            <div class="card-body">
                                <p class="text-info small text-center mb-2 p-1 border rounded">
                                    Total Income for <?php echo date('M Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)); ?>: 
                                    <strong><?php echo number_format($total_income_for_month, 2); ?></strong>
                                </p>
                                
                                <div class="alert alert-<?php echo ($remaining_for_allocation >= 0 ? 'success' : 'danger'); ?> text-center remaining-amount">
                                    Remaining Allocation:
                                    <br>
                                    <?php echo number_format(abs($remaining_for_allocation), 2); ?> 
                                    <span class="small font-weight-normal text-dark"><?php echo ($remaining_for_allocation >= 0 ? 'LEFT' : 'OVER'); ?></span>
                                </div>

                                <form method="POST" action="budget.php<?php echo $base_url_params; ?>">
                                    <input type="hidden" name="set_budget" value="1">
                                    <input type="hidden" name="budget_month" value="<?php echo $selected_month; ?>">
                                    <input type="hidden" name="budget_year" value="<?php echo $selected_year; ?>">

                                    <div class="form-group">
                                        <label for="budget_category">Category</label>
                                        <select class="form-control" id="budget_category" name="budget_category" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="budget_limit">Monthly Budget Limit </label>
                                        <input type="number" step="0.01" class="form-control" id="budget_limit" name="budget_limit" placeholder="0.00" required>
                                        <small class="form-text text-muted">Total allocated budget must not exceed income.</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-block mt-4">Save Budget Limit</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-chart-pie mr-2"></i> Budget Progress Report for <?php echo $display_month_year; ?>
                            </div>
                            <div class="card-body">
                                
                                <?php if (!$has_active_budget && empty($expenses_spent)): ?>
                                    <div class="alert alert-warning text-center">
                                        No budgets set or expenses recorded for <?php echo $display_month_year; ?>. Start by setting a limit!
                                    </div>
                                <?php endif; ?>

                                <div class="row">
                                    <?php foreach ($budget_report as $item): ?>
                                    <?php if ($item['limit'] > 0 || $item['spent'] > 0): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card budget-card h-100 border-<?php echo $item['status_class']; ?>">
                                            <div class="card-body p-3">
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($item['category']); ?></h5>
                                                
                                                <p class="card-text small mb-1">
                                                    Limit: <?php echo number_format($item['limit'], 2); ?> | 
                                                    Spent: <?php echo number_format($item['spent'], 2); ?>
                                                </p>
                                                
                                                <?php if ($item['limit'] > 0): ?>
                                                    <div class="progress progress-container" style="height: 15px;">
                                                        <div class="progress-bar bg-<?php echo $item['status_class']; ?>" 
                                                            role="progressbar" 
                                                            style="width: <?php echo min($item['percentage'], 100); ?>%;" 
                                                            aria-valuenow="<?php echo $item['percentage']; ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            <?php echo $item['percentage']; ?>%
                                                        </div>
                                                    </div>
                                                    <p class="small text-muted">
                                                        <?php if ($item['remaining'] >= 0): ?>
                                                            <?php echo number_format($item['remaining'], 2); ?> remaining
                                                        <?php else: ?>
                                                            <span class="text-danger"><?php echo number_format(abs($item['remaining']), 2); ?> OVER BUDGET!</span>
                                                        <?php endif; ?>
                                                    </p>
                                                <?php else: ?>
                                                     <p class="small text-muted text-danger"><?php echo number_format($item['spent'], 2); ?> spent without a budget!</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
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