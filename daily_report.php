<?php
// Ensure configuration and session are loaded first
include("session.php");
include("config.php");

$current_page = basename($_SERVER['PHP_SELF']);
$user_id_int = (int) $userid;

// --- 1. PERSISTENCE & DATE FILTERING ---

// Retrieve selected month/year from SESSION/GET for consistent sidebar navigation
$selected_month = $_SESSION['selected_month'] ?? date('n');
$selected_year = $_SESSION['selected_year'] ?? date('Y');
$base_url_params = "?month={$selected_month}&year={$selected_year}";

// Determine the date to display, defaulting to today
$selected_date = isset($_GET['date']) ? filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING) : date('Y-m-d');
$display_date = date('F d, Y', strtotime($selected_date));
$date_filter_sql = $selected_date; // Safe to use in prepared statement

// --- 2. Fetch Expenses and Chart Data for Selected Date ---
$daily_expenses = [];
$total_day_expense = 0.00;
$exp_cat_labels = [];
$exp_cat_data = [];

// A. Fetch all individual expenses for the selected date
$sql_expenses = "SELECT expense, expensecategory, expensenote FROM expenses WHERE user_id = ? AND DATE(expensedate) = ? ORDER BY expensedate DESC";

if ($stmt_exp = mysqli_prepare($con, $sql_expenses)) {
    mysqli_stmt_bind_param($stmt_exp, "is", $user_id_int, $date_filter_sql);
    mysqli_stmt_execute($stmt_exp);
    $result_exp = mysqli_stmt_get_result($stmt_exp);
    while ($row = mysqli_fetch_assoc($result_exp)) {
        $daily_expenses[] = $row;
        $total_day_expense += $row['expense'];
    }
    mysqli_stmt_close($stmt_exp);
}

// B. Fetch aggregated expense data for the chart
$sql_chart = "SELECT expensecategory, SUM(expense) AS total_spent 
              FROM expenses 
              WHERE user_id = ? AND DATE(expensedate) = ? 
              GROUP BY expensecategory ORDER BY total_spent DESC";

if ($stmt_chart = mysqli_prepare($con, $sql_chart)) {
    mysqli_stmt_bind_param($stmt_chart, "is", $user_id_int, $date_filter_sql);
    mysqli_stmt_execute($stmt_chart);
    $result_chart = mysqli_stmt_get_result($stmt_chart);
    while ($row = mysqli_fetch_assoc($result_chart)) {
        $exp_cat_labels[] = $row['expensecategory'] . " ($" . number_format($row['total_spent'], 2) . ")";
        $exp_cat_data[] = round($row['total_spent'], 2);
    }
    mysqli_stmt_close($stmt_chart);
}

// Convert PHP arrays to JSON strings for Chart.js
$json_exp_cat_labels = json_encode($exp_cat_labels);
$json_exp_cat_data = json_encode($exp_cat_data);

// Generate 12 dynamic colors for charts
function generate_colors($count)
{
    $colors = [];
    $base_colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#95a5a6', '#d35400', '#c0392b', '#7f8c8d', '#27ae60'];
    for ($i = 0; $i < $count; $i++) {
        $colors[] = $base_colors[$i % count($base_colors)];
    }
    return json_encode($colors);
}
$json_colors = generate_colors(count($exp_cat_labels));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Daily Expense Report - <?php echo $display_date; ?></title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/feather.min.js"></script>
    <style>
    .card-chart-daily {
        /* Keep min-height for structure, but define height for better control */
        min-height: 350px; 
        height: 350px; /* New: Define a fixed height for the canvas's container */
        display: flex; /* New: Use flex to center the canvas */
        justify-content: center;
        align-items: center;
    }
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
            </nav>

            <div class="container-fluid p-4">
                <h1 class="mt-4 mb-4 font-weight-light">Daily Expense Details</h1>

                <!-- Date Selector -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="daily_report.php" class="d-flex align-items-center">
                            <h5 class="mb-0 me-3 text-muted">Select Date:</h5>
                            <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                            <input type="hidden" name="year" value="<?php echo $selected_year; ?>">

                            <input type="date" name="date" class="form-control me-3" style="max-width: 200px;"
                                value="<?php echo htmlspecialchars($selected_date); ?>" required>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-1"></i> View Report
                            </button>
                            <a href="daily_report.php" class="btn btn-outline-secondary ms-2">Today</a>
                        </form>
                    </div>
                </div>

                <!-- Expense Summary and Chart -->
                <div class="row">

                    <div class="col-lg-5 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-chart-pie me-1"></i> Spending Breakdown for
                                **<?php echo $display_date; ?>**
                            </div>
                            <div class="card-body card-chart-daily">
                                <?php if ($total_day_expense > 0): ?>
                                    <canvas id="dailyExpensePie"></canvas>
                                <?php else: ?>
                                    <div class="alert alert-info text-center mt-5">
                                        No expenses recorded for **<?php echo $display_date; ?>**.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-center bg-light">
                                <h4 class="mb-0">Total Spent: <span
                                        class="text-danger"><?php echo number_format($total_day_expense, 2); ?></span>
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Transaction List -->
                    <div class="col-lg-7 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-list-alt me-1"></i> Transactions on **<?php echo $display_date; ?>**
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($daily_expenses)): ?>
                                                <?php foreach ($daily_expenses as $expense): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($expense['expensecategory']); ?></td>
                                                        <td><?php echo htmlspecialchars($expense['expensenote']); ?></td>
                                                        <td class="text-right text-danger">
                                                            <?php echo number_format($expense['expense'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No transactions recorded
                                                        for this date.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
    <script src="js/Chart.min.js"></script>

    <script>
        var EXP_CAT_LABELS = <?php echo $json_exp_cat_labels; ?>;
        var EXP_CAT_DATA = <?php echo $json_exp_cat_data; ?>;
        var COLORS = <?php echo $json_colors; ?>;

        $("#menu-toggle").click(function (e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        feather.replace();

        // --- Pie Chart Initialization ---
        <?php if ($total_day_expense > 0): ?>
            var ctxPie = document.getElementById('dailyExpensePie').getContext('2d');
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
                    legend: { position: 'bottom' },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var label = data.labels[tooltipItem.index] || '';
                                if (label) {
                                    label = label.split(' (')[0]; // Remove the amount from the label if present
                                }
                                var value = data.datasets[0].data[tooltipItem.index];
                                var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                var percentage = ((value / total) * 100).toFixed(1) + '%';
                                return label + ': $' + value.toFixed(2) + ' (' + percentage + ')';
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>