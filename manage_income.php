<?php
// Include necessary files
include("session.php");
include("config.php"); 

$delete_message = $error_message = '';
$current_page = basename($_SERVER['PHP_SELF']);

// --- 1. Handle Delete Operation ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $income_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($income_id_to_delete) {
        // Use Prepared Statement for safe deletion
        $sql = "DELETE FROM income WHERE income_id = ? AND user_id = ?";
        
        if ($stmt = mysqli_prepare($con, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $income_id_to_delete, $userid);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $delete_message = "Income record deleted successfully!";
                } else {
                    $error_message = "Income record not found or you do not have permission to delete it.";
                }
            } else {
                $error_message = "Error deleting income record: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error preparing delete statement.";
        }
    }
}

// --- 2. Build Query for Display/Search ---
$default_end_date = date('Y-m-d');
$default_start_date = date('Y-m-01', strtotime($default_end_date)); 

// Get filter inputs
$start_date = isset($_POST['start_date']) ? filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING) : $default_start_date;
$end_date   = isset($_POST['end_date']) ? filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING) : $default_end_date;
$source     = isset($_POST['source_filter']) ? filter_input(INPUT_POST, 'source_filter', FILTER_SANITIZE_STRING) : '';

$where_clauses = ["user_id = '$userid'"];

// Date range filter
if (!empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "income_date BETWEEN '$start_date' AND '$end_date'"; 
}

// Source filter (assuming income_source is used for category)
if (!empty($source)) {
    $where_clauses[] = "income_source = '$source'";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);
$order_sql = "ORDER BY income_date DESC, income_id DESC";

$full_query = "SELECT * FROM income $where_sql $order_sql";
$income_result = mysqli_query($con, $full_query);

// Fetch all unique income sources for the filter dropdown
$sources_query = "SELECT DISTINCT income_source FROM income WHERE user_id = '$userid' ORDER BY income_source ASC";
$sources_result = mysqli_query($con, $sources_query);
$income_sources = [];
while ($row = mysqli_fetch_assoc($sources_result)) {
    $income_sources[] = $row['income_source'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Income - Daily Expense Tracker</title>

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
                <h1 class="mt-4 mb-4 font-weight-light">Income History & Management</h1>
                
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
                        <i class="fas fa-filter mr-2"></i> Filter Income
                    </div>
                    <div class="card-body">
                        <form method="POST" action="manage_income.php" class="row">
                            <div class="col-md-4 form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="source_filter">Income Source</label>
                                <select class="form-control" id="source_filter" name="source_filter">
                                    <option value="">All Sources</option>
                                    <?php foreach ($income_sources as $src): ?>
                                        <option value="<?php echo htmlspecialchars($src); ?>" <?php echo ($source === $src) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($src); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mt-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-2"></i> Search/Filter</button>
                                <a href="manage_income.php" class="btn btn-outline-secondary">Reset Filters</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-table mr-2"></i> Income Records
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="incomeTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Source</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Source</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php 
                                    $total_filtered_income = 0;
                                    if (mysqli_num_rows($income_result) > 0): 
                                        while ($row = mysqli_fetch_assoc($income_result)): 
                                            $total_filtered_income += $row['income_amount'];
                                    ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($row['income_date'])); ?></td>
                                            <td class="font-weight-bold text-success"><?php echo number_format($row['income_amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($row['income_source']); ?></td>
                                            <td><?php echo htmlspecialchars($row['income_description']); ?></td>
                                            <td>
                                                <a href="edit_income.php?id=<?php echo $row['income_id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                                    <span data-feather="edit"></span>
                                                </a>
                                                <a href="javascript:void(0);" onclick="confirmIncomeDelete(<?php echo $row['income_id']; ?>)" class="btn btn-sm btn-danger" title="Delete">
                                                    <span data-feather="trash-2"></span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No income records found for the selected criteria.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-right">
                             <h4 class="font-weight-bold">Total Filtered Income: <span class="text-success"><?php echo number_format($total_filtered_income, 2); ?></span></h4>
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
            $('#incomeTable').DataTable({
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

        // Delete Confirmation
        function confirmIncomeDelete(id) {
            if (confirm("Are you sure you want to permanently delete this income record?")) {
                window.location.href = 'manage_income.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>