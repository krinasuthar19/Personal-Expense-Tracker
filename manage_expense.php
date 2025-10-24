<?php
// Include necessary files
include("session.php");
include("config.php"); 

$delete_message = $error_message = '';
$current_page = basename($_SERVER['PHP_SELF']);

// --- 1. CONSISTENCY: Retrieve selected month/year from SESSION or default ---
// We retrieve the session variables set by index.php
$selected_month = $_SESSION['selected_month'] ?? date('m');
$selected_year = $_SESSION['selected_year'] ?? date('Y');

// Construct the base URL parameters for persistent month/year tracking in navigation
$base_url_params = "?month={$selected_month}&year={$selected_year}";

// Generate default dates based on the *selected* month/year
$default_end_date = date('Y-m-t', strtotime("{$selected_year}-{$selected_month}-01"));
$default_start_date = date('Y-m-01', strtotime("{$selected_year}-{$selected_month}-01"));


// --- 2. Handle Delete Operation ---
// We must preserve the month/year context after deletion, so we pass the parameters.
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $expense_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    // ... (rest of delete logic remains the same) ...
    if ($expense_id_to_delete) {
        $sql = "DELETE FROM expenses WHERE expense_id = ? AND user_id = ?";
        
        if ($stmt = mysqli_prepare($con, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $expense_id_to_delete, $userid);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $delete_message = "Expense deleted successfully!";
                } else {
                    $error_message = "Expense not found or you do not have permission to delete it.";
                }
            } else {
                $error_message = "Error deleting expense: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error preparing delete statement.";
        }
    }
}

// --- 3. Build Query for Display/Search ---

// Get filter inputs: POST overrides session defaults
$start_date = isset($_POST['start_date']) ? filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING) : $default_start_date;
$end_date   = isset($_POST['end_date']) ? filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING) : $default_end_date;
$category   = isset($_POST['category_filter']) ? filter_input(INPUT_POST, 'category_filter', FILTER_SANITIZE_STRING) : '';

// --- NOTE: When the page loads via sidebar link, $_POST will be empty. 
// --- Therefore, $start_date and $end_date will default to the SESSION-based dates.

$where_clauses = ["user_id = '$userid'"];
$category_filter_applied = false;

// Date range filter
if (!empty($start_date) && !empty($end_date)) {
    // MySQL BETWEEN includes both endpoints. We use the exact dates.
    $where_clauses[] = "expensedate BETWEEN '$start_date' AND '$end_date'"; 
}

// Category filter
if (!empty($category)) {
    $where_clauses[] = "expensecategory = '$category'";
    $category_filter_applied = true;
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);
$order_sql = "ORDER BY expensedate DESC, expense_id DESC";

$full_query = "SELECT expense_id, expense, expensecategory, expensedate, expensenote FROM expenses $where_sql $order_sql";
$expenses_result = mysqli_query($con, $full_query);

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

// Utility function to merge URL parameters
function append_params($url, $params) {
    return $url . (strpos($url, '?') === false ? '?' : '&') . ltrim($params, '?');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Expenses - Daily Expense Tracker</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css"/>
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
            <h1 class="mt-4 mb-4 font-weight-light">Expense History & Management</h1>
                
                <?php if ($delete_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $delete_message; ?>
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
                    <div class="card-header bg-light">
                        <i class="fas fa-filter mr-2"></i> Filter Expenses (Defaulting to <?php echo date('F Y', strtotime("{$selected_year}-{$selected_month}-01")); ?>)
                    </div>
                    <div class="card-body">
                        <form method="POST" action="manage_expense.php<?php echo $base_url_params; ?>" class="row">
                            <div class="col-md-4 form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="category_filter">Category</label>
                                <select class="form-control" id="category_filter" name="category_filter">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mt-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-2"></i> Search/Filter</button>
                                <a href="manage_expense.php<?php echo $base_url_params; ?>" class="btn btn-outline-secondary">Reset Filters for This Month</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-table mr-2"></i> Expense Records (<?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?>)
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="expensesTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php 
                                    $total_filtered_expense = 0;
                                    if (mysqli_num_rows($expenses_result) > 0): 
                                        while ($row = mysqli_fetch_assoc($expenses_result)): 
                                            $total_filtered_expense += $row['expense'];
                                    ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($row['expensedate'])); ?></td>
                                            <td class="font-weight-bold text-danger"><?php echo number_format($row['expense'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($row['expensecategory']); ?></td>
                                            <td><?php echo htmlspecialchars($row['expensenote']); ?></td>
                                            <td>
                                                <a href="edit_expense.php<?php echo append_params($base_url_params, 'id=' . $row['expense_id']); ?>" class="btn btn-sm btn-info" title="Edit">
                                                    <span data-feather="edit"></span>
                                                </a>
                                                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $row['expense_id']; ?>, '<?php echo $base_url_params; ?>')" class="btn btn-sm btn-danger" title="Delete">
                                                    <span data-feather="trash-2"></span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No expenses found for the selected criteria.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-right">
                             <h4 class="font-weight-bold">Total Filtered Expenses: <span class="text-danger"><?php echo number_format($total_filtered_expense, 2); ?></span></h4>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        </div>
    <script src="js/jquery.slim.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        // DataTables Initialization (Optional but highly recommended for UX)
        $(document).ready(function() {
            $('#expensesTable').DataTable({
                "paging": true,
                "searching": false, // Disable default search since we have a custom filter
                "ordering": true,
                "info": true
            });
        });

        // Menu Toggle Script
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        
        // Feather Icons
        feather.replace();

        // FIX 10: Update Delete Confirmation to preserve month/year context
        function confirmDelete(id, params) {
            if (confirm("Are you sure you want to permanently delete this expense record?")) {
                // Redirect back to manage_expense.php with delete action AND persistent params
                window.location.href = 'manage_expense.php' + params + '&action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>