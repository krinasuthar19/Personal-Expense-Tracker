<?php
// Ensure configuration and session are loaded first
include("session.php");
include("config.php"); 

$current_page = basename($_SERVER['PHP_SELF']);

// --- 0. PERSISTENCE & REPORT FILTERING ---

// 0a. Retrieve selected month/year from SESSION/GET for consistent sidebar navigation
$selected_month = $_SESSION['selected_month'] ?? date('n');
$selected_year = $_SESSION['selected_year'] ?? date('Y');
$base_url_params = "?month={$selected_month}&year={$selected_year}";

// 0b. Determine the main reporting period filter type (default to current year)
$report_type = $_GET['report_type'] ?? 'year'; 
$filter_year = isset($_GET['filter_year']) ? (int)$_GET['filter_year'] : date('Y');
$filter_month = isset($_GET['filter_month']) ? (int)$_GET['filter_month'] : date('n');

// Set the time context for the filter charts
$time_context_title = "Year " . $filter_year;
$where_clause = "user_id = {$userid} AND YEAR(expensedate) = {$filter_year}";
$where_clause_income = "user_id = {$userid} AND YEAR(income_date) = {$filter_year}";

switch ($report_type) {
    case 'month':
        $time_context_title = date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year));
        $where_clause .= " AND MONTH(expensedate) = {$filter_month}";
        $where_clause_income .= " AND MONTH(income_date) = {$filter_month}";
        break;
    case 'all':
        $time_context_title = "All Time";
        $where_clause = "user_id = {$userid}"; // No date filter
        $where_clause_income = "user_id = {$userid}"; // No date filter
        break;
    case 'year':
    default:
        // Already set above: filter_year, which defaults to current year
        break;
}


// --- 1. Data Fetching for 12-Month Trends (Always last 12 months) ---
$months_labels = [];
$monthly_expenses = [];
$monthly_income = [];

for ($i = 11; $i >= 0; $i--) {
    $month_ts = strtotime("-$i months");
    $month_num = date('n', $month_ts);
    $year_num = date('Y', $month_ts);
    
    // FIX 1: Change label format to YYYY-MM-01 for Chart.js Time Scale interpretation
    $months_labels[] = date('Y-m-01', $month_ts);
    
    // Fetch Monthly Expenses (using Prepared Statements)
    $sql_exp = "SELECT SUM(expense) AS total FROM expenses WHERE user_id = ? AND MONTH(expensedate) = ? AND YEAR(expensedate) = ?";
    if ($stmt_exp = mysqli_prepare($con, $sql_exp)) {
        mysqli_stmt_bind_param($stmt_exp, "iii", $userid, $month_num, $year_num);
        mysqli_stmt_execute($stmt_exp);
        $result_exp = mysqli_stmt_get_result($stmt_exp);
        $exp = mysqli_fetch_assoc($result_exp)['total'] ?? 0;
        $monthly_expenses[] = round($exp, 2);
        mysqli_stmt_close($stmt_exp);
    }
    
    // Fetch Monthly Income (using Prepared Statements)
    $sql_inc = "SELECT SUM(income_amount) AS total FROM income WHERE user_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?";
    if ($stmt_inc = mysqli_prepare($con, $sql_inc)) {
        mysqli_stmt_bind_param($stmt_inc, "iii", $userid, $month_num, $year_num);
        mysqli_stmt_execute($stmt_inc);
        $result_inc = mysqli_stmt_get_result($stmt_inc);
        $inc = mysqli_fetch_assoc($result_inc)['total'] ?? 0;
        $monthly_income[] = round($inc, 2);
        mysqli_stmt_close($stmt_inc);
    }
}

// Convert PHP arrays to JSON strings for Chart.js
$json_months = json_encode($months_labels);
$json_expenses = json_encode($monthly_expenses);
$json_income = json_encode($monthly_income);


// --- 2. Data Fetching for Category Breakdown (DYNAMICALLY FILTERED) ---

// Expense Categories Breakdown (DYNAMIC PERIOD)
$exp_cat_query = "SELECT expensecategory, SUM(expense) AS total_spent 
                  FROM expenses 
                  WHERE {$where_clause}
                  GROUP BY expensecategory ORDER BY total_spent DESC";
$exp_cat_labels = [];
$exp_cat_data = [];

$result_exp_cat = mysqli_query($con, $exp_cat_query);
while ($row = mysqli_fetch_assoc($result_exp_cat)) {
    $exp_cat_labels[] = $row['expensecategory'];
    $exp_cat_data[] = round($row['total_spent'], 2);
}

$json_exp_cat_labels = json_encode($exp_cat_labels);
$json_exp_cat_data = json_encode($exp_cat_data);

// Income Source Breakdown (DYNAMIC PERIOD)
$inc_src_query = "SELECT income_source, SUM(income_amount) AS total_received 
                  FROM income 
                  WHERE {$where_clause_income}
                  GROUP BY income_source ORDER BY total_received DESC";
$inc_src_labels = [];
$inc_src_data = [];

$result_inc_src = mysqli_query($con, $inc_src_query);
while ($row = mysqli_fetch_assoc($result_inc_src)) {
    $inc_src_labels[] = $row['income_source'];
    $inc_src_data[] = round($row['total_received'], 2);
}

$json_inc_src_labels = json_encode($inc_src_labels);
$json_inc_src_data = json_encode($inc_src_data);

// Generate colors (helper function remains the same)
function generate_colors($count) {
    $colors = [];
    $base_colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#95a5a6', '#d35400', '#c0392b', '#7f8c8d', '#27ae60'];
    for ($i = 0; $i < $count; $i++) {
        $colors[] = $base_colors[$i % count($base_colors)];
    }
    return json_encode($colors);
}
$json_colors = generate_colors(12);

// Utility for year dropdowns
function createYearOptions($selected_year, $current_filter) {
    $html = '';
    $currentYear = date('Y');
    $startYear = $currentYear - 5; 
    for ($y = $currentYear + 1; $y >= $startYear; $y--) {
        $selected = ($y == $current_filter) ? 'selected' : '';
        $html .= "<option value=\"{$y}\" {$selected}>{$y}</option>";
    }
    return $html;
}

// Utility for month dropdowns
function createMonthOptions($selected_month, $current_filter) {
    $html = '';
    for ($m = 1; $m <= 12; $m++) {
        $monthName = date('F', mktime(0, 0, 0, $m, 10));
        $selected = ($m == $current_filter) ? 'selected' : '';
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
    <title>Reports & Analytics - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/feather.min.js"></script>
    <script src="js/Chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <style>
        .card-chart { min-height: 450px; }
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
            <h1 class="mt-4 mb-4 font-weight-light">Reports & Analytics</h1>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-search mr-2"></i> Customize Breakdown Period
                </div>
                <div class="card-body">
                    <form method="GET" action="reports.php" class="row align-items-end">
                        <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                        <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                        
                        <div class="col-md-3 form-group">
                            <label for="report_type">Report Scope</label>
                            <select name="report_type" id="report_type" class="form-control" onchange="toggleDatePickers(this.value)">
                                <option value="year" <?php echo ($report_type == 'year') ? 'selected' : ''; ?>>By Year</option>
                                <option value="month" <?php echo ($report_type == 'month') ? 'selected' : ''; ?>>By Month</option>
                                <option value="all" <?php echo ($report_type == 'all') ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group" id="month_picker_group" style="display: <?php echo ($report_type == 'month') ? 'block' : 'none'; ?>;">
                            <label for="filter_month">Month</label>
                            <select name="filter_month" id="filter_month" class="form-control">
                                <?php echo createMonthOptions($filter_month, $filter_month); ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 form-group" id="year_picker_group" style="display: <?php echo ($report_type == 'all') ? 'none' : 'block'; ?>;">
                            <label for="filter_year">Year</label>
                            <select name="filter_year" id="filter_year" class="form-control">
                                <?php echo createYearOptions($filter_year, $filter_year); ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 form-group">
                            <button type="submit" class="btn btn-secondary btn-block">Apply Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            
            
            <h2 class="mt-4 mb-3">Breakdown Analysis: <span class="text-primary"><?php echo $time_context_title; ?></span></h2>
            <hr>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-chart-pie mr-2"></i> Expense Breakdown 
                        </div>
                        <div class="card-body card-chart">
                            <canvas id="expenseCategoryPie"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-chart-bar mr-2"></i> Income Source Analysis 
                        </div>
                        <div class="card-body card-chart">
                            <canvas id="incomeSourceBar"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-chart-line mr-2"></i> Monthly Cash Flow Trend (Last 12 Months)
                </div>
                <div class="card-body card-chart">
                    <canvas id="monthlyCashFlowChart"></canvas>
                </div>
            </div>

        </div>
    </div>
    </div>
    <script src="js/jquery.slim.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/Chart.min.js"></script>
    
    <script>
        var MONTH_LABELS = <?php echo $json_months; ?>;
        var MONTHLY_EXPENSES = <?php echo $json_expenses; ?>;
        var MONTHLY_INCOME = <?php echo $json_income; ?>;
        
        var EXP_CAT_LABELS = <?php echo $json_exp_cat_labels; ?>;
        var EXP_CAT_DATA = <?php echo $json_exp_cat_data; ?>;
        
        var INC_SRC_LABELS = <?php echo $json_inc_src_labels; ?>;
        var INC_SRC_DATA = <?php echo $json_inc_src_data; ?>;

        var COLORS = <?php echo $json_colors; ?>;

        // Menu Toggle Script
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        
        feather.replace();

        // JS function to toggle month/year pickers based on report type
        function toggleDatePickers(type) {
            const monthGroup = document.getElementById('month_picker_group');
            const yearGroup = document.getElementById('year_picker_group');

            if (type === 'month') {
                monthGroup.style.display = 'block';
                yearGroup.style.display = 'block';
            } else if (type === 'year') {
                monthGroup.style.display = 'none';
                yearGroup.style.display = 'block';
            } else if (type === 'all') {
                monthGroup.style.display = 'none';
                yearGroup.style.display = 'none';
            }
        }
        
        // --- Chart.js Initialization ---
        
        // 1. Monthly Cash Flow Line Chart
        var ctxLine = document.getElementById('monthlyCashFlowChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: MONTH_LABELS,
                datasets: [
                    {
                        label: 'Total Expenses',
                        data: MONTHLY_EXPENSES,
                        borderColor: '#e74c3c', // Red
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Total Income',
                        data: MONTHLY_INCOME,
                        borderColor: '#2ecc71', // Green
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    // FIX 2: Define X-Axis as time scale
                    xAxes: [{ 
                        type: 'time',
                        time: {
                            unit: 'month', // Treat each label as a month
                            tooltipFormat: 'MMM YYYY',
                            displayFormats: {
                                month: 'MMM YYYY'
                            }
                        },
                        distribution: 'series', // Ensures continuous display of 12 months
                        ticks: {
                            source: 'labels'
                        }
                    }],
                    yAxes: [{
                        ticks: { beginAtZero: true, callback: function(value, index, values) { return '$' + value; } }
                    }]
                }
            }
        });
        
        // 2. Expense Category Pie Chart
        var ctxPie = document.getElementById('expenseCategoryPie').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut', 
            data: {
                labels: EXP_CAT_LABELS,
                datasets: [{
                    data: EXP_CAT_DATA,
                    backgroundColor: COLORS,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' }
            }
        });
        
        // 3. Income Source Bar Chart
        var ctxBar = document.getElementById('incomeSourceBar').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: INC_SRC_LABELS,
                datasets: [{
                    label: 'Income Received ($)',
                    data: INC_SRC_DATA,
                    backgroundColor: '#3498db', // Blue
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: { beginAtZero: true, callback: function(value, index, values) { return '$' + value; } }
                    }]
                }
            }
        });
    </script>
</body>
</html>