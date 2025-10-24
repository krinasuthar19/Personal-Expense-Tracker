<?php
// Ensure configuration and session are loaded first
include("session.php");
include("config.php"); 

// 1. DEFINE TIMEFRAME VARIABLES & HANDLE USER INPUT
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// *** ADD THESE TWO LINES TO SAVE TO SESSION ***
$_SESSION['selected_month'] = $selected_month;
$_SESSION['selected_year'] = $selected_year;
// **********************************************

// Ensure $selected_month is valid (1-12)
if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = date('m');
}
// Generate the first and last day of the SELECTED month
$first_day_of_month = date("Y-m-01", strtotime("{$selected_year}-{$selected_month}-01"));
$last_day_of_month = date("Y-m-t", strtotime("{$selected_year}-{$selected_month}-01")); 

// For displaying the month title
$display_month_year = date("F Y", strtotime("{$selected_year}-{$selected_month}-01"));


// 2. INITIALIZE FINANCIAL VARIABLES
$total_expense = 0.00;
$total_income = 0.00;
$budget_limit = 0.00; 

// Ensure $userid is an integer type just in case it's a string from session
$user_id_int = (int)$userid;

// --- 3. Fetch Total Expense for SELECTED Month (Securely) ---
$sql_exp = "SELECT SUM(expense) AS total_exp FROM expenses WHERE user_id = ? AND MONTH(expensedate) = ? AND YEAR(expensedate) = ?";
if (isset($con) && $stmt_exp = mysqli_prepare($con, $sql_exp)) {
    // *** MODIFICATION: Use $selected_month and $selected_year ***
    mysqli_stmt_bind_param($stmt_exp, "iii", $user_id_int, $selected_month, $selected_year);
    mysqli_stmt_execute($stmt_exp);
    $result_exp = mysqli_stmt_get_result($stmt_exp);
    $total_expense = mysqli_fetch_assoc($result_exp)['total_exp'] ?? 0.00;
    mysqli_stmt_close($stmt_exp);
}


// --- 4. Fetch Total Income for SELECTED Month (Using Date Range) ---
$sql_inc = "SELECT SUM(income_amount) AS total_inc FROM income WHERE user_id = ? AND income_date BETWEEN ? AND ?";
if (isset($con) && $stmt_inc = mysqli_prepare($con, $sql_inc)) {
    // *** MODIFICATION: Use date range for $selected_month ***
    mysqli_stmt_bind_param($stmt_inc, "iss", $user_id_int, $first_day_of_month, $last_day_of_month);
    mysqli_stmt_execute($stmt_inc);
    $result_inc = mysqli_stmt_get_result($stmt_inc);
    $total_income = mysqli_fetch_assoc($result_inc)['total_inc'] ?? 0.00;
    mysqli_stmt_close($stmt_inc);
}

// --- 5. Calculate Net Cash Flow (Profit/Debt Status) ---
$net_cash_flow = $total_income - $total_expense;
$net_status = ($net_cash_flow >= 0) ? 'Positive' : 'Negative (Debt)';
$net_class = ($net_cash_flow >= 0) ? 'success' : 'danger';


// --- 6. Calculate Total Budget Limit for SELECTED Month ---
$sql_budget = "SELECT SUM(budget_limit) AS total_budget FROM budgets WHERE user_id = ? AND budget_month = ? AND budget_year = ?";
if (isset($con) && $stmt_budget = mysqli_prepare($con, $sql_budget)) {
    // *** MODIFICATION: Use $selected_month and $selected_year ***
    mysqli_stmt_bind_param($stmt_budget, "iii", $user_id_int, $selected_month, $selected_year);
    mysqli_stmt_execute($stmt_budget);
    $result_budget = mysqli_stmt_get_result($stmt_budget);
    $budget_limit = mysqli_fetch_assoc($result_budget)['total_budget'] ?? 0.00;
    mysqli_stmt_close($stmt_budget);
}
$remaining_budget = $budget_limit - $total_expense;


// --- 7. Existing Chart Data Queries (Must use $con and $userid) ---
// IMPORTANT: For charts to reflect the selected month, these queries MUST be updated. 
// However, since they were previously running for ALL time, I'll update them to 
// include a month/year filter for consistency.

// Expense Category Breakdown for SELECTED Month
$exp_category_dc = mysqli_query($con, 
    "SELECT expensecategory, SUM(expense) AS total_expense 
    FROM expenses 
    WHERE user_id = '{$user_id_int}' 
    AND MONTH(expensedate) = '{$selected_month}' 
    AND YEAR(expensedate) = '{$selected_year}'
    GROUP BY expensecategory 
    ORDER BY total_expense DESC");

// Daily Expense Line Data for SELECTED Month
$exp_date_line = mysqli_query($con, 
    "SELECT DATE(expensedate) AS expense_date_only 
    FROM expenses 
    WHERE user_id = '{$user_id_int}'
    AND MONTH(expensedate) = '{$selected_month}' 
    AND YEAR(expensedate) = '{$selected_year}'
    GROUP BY expense_date_only 
    ORDER BY expense_date_only ASC");

$exp_amt_line = mysqli_query($con, 
    "SELECT SUM(expense) AS daily_total 
    FROM expenses 
    WHERE user_id = '{$user_id_int}'
    AND MONTH(expensedate) = '{$selected_month}' 
    AND YEAR(expensedate) = '{$selected_year}'
    GROUP BY DATE(expensedate) 
    ORDER BY DATE(expensedate) ASC");


// Function to get the current page name for active link highlighting
function get_current_page() {
    return basename($_SERVER['PHP_SELF']);
}
$current_page = get_current_page();

// Utility function for month selection dropdown (moved down for clarity)
function createMonthOptions($selected_month) {
    $html = '';
    for ($m = 1; $m <= 12; $m++) {
        $monthName = date('F', mktime(0, 0, 0, $m, 10));
        $selected = ($m == $selected_month) ? 'selected' : '';
        $html .= "<option value=\"{$m}\" {$selected}>{$monthName}</option>";
    }
    return $html;
}

// Utility function for year selection dropdown
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Daily Expense Tracker - Dashboard</title>

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
                <a href="profile.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'profile.php') ? 'sidebar-active' : ''; ?>">
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
                
                <h1 class="mt-4 mb-4 font-weight-light">Dashboard</h1>

                <div class="row mb-4">
                    <div class="col-12">
                        <form method="GET" action="index.php" class="d-flex align-items-center bg-light p-3 rounded shadow-sm">
                            <h5 class="mb-0 me-3 text-muted">Viewing:</h5>
                            <select name="month" class="form-select form-select-sm me-2" style="max-width: 150px;">
                                <?php echo createMonthOptions($selected_month); ?>
                            </select>
                            <select name="year" class="form-select form-select-sm me-3" style="max-width: 100px;">
                                <?php echo createYearOptions($selected_year); ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <span class="ms-auto me-2 text-primary font-weight-bold">
                                <?php echo $display_month_year; ?>
                            </span>
                        </form>
                    </div>
                </div>
                <div class="row g-4 mb-4">
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-gradient-1 shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                            Expenses (<?php echo date('M', mktime(0, 0, 0, $selected_month, 10)); ?>)
                        </div>
                        <div class="h3 mb-0 font-weight-bold text-white">
                            <?php echo number_format($total_expense, 2); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-check-alt fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                <a href="manage_expense.php" class="text-white small font-weight-bold">View Expenses</a>
                <i class="fas fa-arrow-circle-right text-white-50"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-gradient-2 shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                            Income (<?php echo date('M', mktime(0, 0, 0, $selected_month, 10)); ?>)
                        </div>
                        <div class="h3 mb-0 font-weight-bold text-white">
                            <?php echo number_format($total_income, 2); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-wallet fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                <a href="income.php" class="text-white small font-weight-bold">Add Income</a>
                   <i class="fas fa-arrow-circle-right text-white-50"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow-sm h-100 py-2 border-left border-<?php echo $net_class; ?> dashboard-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-<?php echo $net_class; ?> text-uppercase mb-1">
                            NET CASH FLOW (<?php echo $net_status; ?>)
                        </div>
                        <div class="h3 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format(abs($net_cash_flow), 2); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exchange-alt fa-2x text-<?php echo $net_class; ?>"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Income - Expense</span>
                <i class="fas fa-info-circle text-muted"></i>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-gradient-3 shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                            Remaining Budget
                        </div>
                        <div class="h3 mb-0 font-weight-bold text-white">
                            <?php echo number_format($remaining_budget, 2); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-check fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                <a href="budget.php" class="text-white small font-weight-bold">Manage Budget</a>
                   <i class="fas fa-arrow-circle-right text-white-50"></i>
            </div>
        </div>
    </div>
</div>

                <h3 class="mt-4 mb-3">Spending Trends</h3>
                <div class="row">
                    
                    <div class="col-lg-7 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Daily Expense Trend (<?php echo $display_month_year; ?>)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="expense_line" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-5 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Expense Category Breakdown (<?php echo date('M', mktime(0, 0, 0, $selected_month, 10)); ?>)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="expense_category_pie" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h3 class="mt-4 mb-3">Recent Transactions</h3>
                   <div class="card shadow-sm mb-4">
                     <div class="card-body">
                          <p class="text-muted">Currently viewing data for the month of **<?php echo $display_month_year; ?>**. The dashboard totals (Expense, Income, Budget) and charts above will automatically reset to zero when you select a month with no recorded data.</p>
                          <a href="manage_expense.php" class="btn btn-sm btn-outline-primary">Go to Transactions</a>
                     </div>
                 </div>


            </div>
        </div>
        </div>
    <script src="js/jquery.slim.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script> <script src="js/Chart.min.js"></script>
    
    <script>
        // Toggle the sidebar functionality (Mobile/Desktop)
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        
        // Initialize Feather Icons
        feather.replace()
    </script>
    
    <script>
        // --- Expense Category Chart (Bar Chart) ---
        var ctxBar = document.getElementById('expense_category_pie').getContext('2d');
        var myBarChart = new Chart(ctxBar, {
            type: 'bar', 
            data: {
                labels: [<?php 
                    // Reset pointer and output labels
                    mysqli_data_seek($exp_category_dc, 0); 
                    while ($a = mysqli_fetch_array($exp_category_dc)) {
                        echo '"' . $a['expensecategory'] . '",';
                    } 
                ?>],
                datasets: [{
                    label: 'Expense by Category ',
                    data: [<?php 
                        // Reset pointer and output data
                        mysqli_data_seek($exp_category_dc, 0); 
                        while ($a = mysqli_fetch_array($exp_category_dc)) {
                            echo '"' . $a['total_expense'] . '",';
                        }
                    ?>],
                    backgroundColor: [
                        '#3498db', 
                        '#e74c3c', 
                        '#2ecc71', 
                        '#f39c12', 
                        '#9b59b6', 
                        '#1abc9c', 
                        '#34495e', 
                        '#95a5a6', 
                        '#d35400', 
                        '#c0392b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        // --- Daily/Monthly Expense Line Chart (Added to the layout) ---
        var ctxLine = document.getElementById('expense_line').getContext('2d');
        var myLineChart = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: [<?php 
                    // Reset pointer and output labels
                    mysqli_data_seek($exp_date_line, 0); 
                    while ($c = mysqli_fetch_array($exp_date_line)) {
                        echo '"' . $c['expense_date_only'] . '",';
                    } 
                ?>],
                datasets: [{
                    label: 'Daily Expenses ',
                    data: [<?php 
                        // Reset pointer and output data
                        mysqli_data_seek($exp_amt_line, 0); 
                        while ($d = mysqli_fetch_array($exp_amt_line)) {
                            echo '"' . $d['daily_total'] . '",';
                        } 
                    ?>],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.2)', // Light fill
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            unit: 'day'
                        },
                        distribution: 'linear'
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    </script>
</body>

</html>