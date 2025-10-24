<?php
// Include necessary files
include("session.php");
include("config.php"); 

$expense_data = null;
$expense_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$success_message = $error_message = '';
$current_page = basename($_SERVER['PHP_SELF']);

// Define categories (must match add_expense.php and manage_expense.php)
$categories = [
    "Food & Dining", 
    "Transportation", 
    "Bills & Utilities", 
    "Entertainment", 
    "Shopping", 
    "Health & Fitness", 
    "Personal Care", 
    "Education", 
    "Travel", 
    "Other"
];

// --- 1. Fetch Existing Data (GET Request or Initial Load) ---
if ($expense_id) {
    $sql_fetch = "SELECT * FROM expenses WHERE expense_id = ? AND user_id = ?";
    if ($stmt_fetch = mysqli_prepare($con, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "ii", $expense_id, $userid);
        mysqli_stmt_execute($stmt_fetch);
        $result = mysqli_stmt_get_result($stmt_fetch);
        
        if (mysqli_num_rows($result) == 1) {
            $expense_data = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Expense record not found or access denied.";
            $expense_id = null; // Invalidate ID
        }
        mysqli_stmt_close($stmt_fetch);
    } else {
        $error_message = "Database error preparing fetch statement.";
    }
} else {
    $error_message = "Invalid expense ID provided.";
}

// --- 2. Handle Update Submission (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $expense_id && !$error_message) {
    
    // Sanitize and validate update inputs
    $new_expense = filter_input(INPUT_POST, 'expense_amount', FILTER_VALIDATE_FLOAT);
    $new_category = filter_input(INPUT_POST, 'expense_category', FILTER_SANITIZE_STRING);
    $new_date     = filter_input(INPUT_POST, 'expense_date', FILTER_SANITIZE_STRING);
    $new_note     = filter_input(INPUT_POST, 'expense_note', FILTER_SANITIZE_STRING);

    if ($new_expense === false || $new_expense <= 0 || !in_array($new_category, $categories) || !$new_date) {
        $error_message = "Please provide valid amount, category, and date.";
    } else {
        // Use Prepared Statement for safe update
        // SQL has 6 placeholders: expense, category, date, note, id, user_id
        $sql_update = "UPDATE expenses SET expense = ?, expensecategory = ?, expensedate = ?, expensenote = ? WHERE expense_id = ? AND user_id = ?";
        
        if ($stmt_update = mysqli_prepare($con, $sql_update)) {
            // CRITICAL FIX: Binding must be "dsssii" for 6 variables.
            // d=double/float, s=string, s=string, s=string, i=integer (id), i=integer (user_id)
            mysqli_stmt_bind_param($stmt_update, "dsssii", $new_expense, $new_category, $new_date, $new_note, $expense_id, $userid);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $success_message = "Expense record updated successfully!";
                
                // Re-fetch updated data to populate the form fields
                $expense_data['expense'] = $new_expense;
                $expense_data['expensecategory'] = $new_category;
                $expense_data['expensedate'] = $new_date;
                $expense_data['expensenote'] = $new_note;
                
            } else {
                $error_message = "Error updating expense: " . mysqli_stmt_error($stmt_update);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error_message = "Database error preparing update statement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Expense - Daily Expense Tracker</title>

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
            <h1 class="mt-4 mb-4 font-weight-light">Edit Expense Record </h1>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0 rounded-lg mt-5">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-edit mr-2"></i> Update Expenditure Details
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
                            <?php elseif (!$expense_data && $expense_id): ?>
                                <div class="alert alert-warning">
                                    Unable to load expense data. Redirecting...
                                </div>
                                <script>setTimeout(function() { window.location.href = 'manage_expense.php'; }, 2000);</script>
                            <?php endif; ?>

                            <?php if ($expense_data): ?>
                            <form method="POST" action="edit_expense.php?id=<?php echo $expense_id; ?>">
                                
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expense_amount">Amount </label>
                                            <input type="number" step="0.01" class="form-control" id="expense_amount" name="expense_amount" value="<?php echo htmlspecialchars($expense_data['expense']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expense_date">Date</label>
                                            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo htmlspecialchars($expense_data['expensedate']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="expense_category">Category</label>
                                    <select class="form-control" id="expense_category" name="expense_category" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($expense_data['expensecategory'] === $cat) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="expense_note">Description/Note</label>
                                    <textarea class="form-control" id="expense_note" name="expense_note" rows="3" placeholder="What was this expense for?"><?php echo htmlspecialchars($expense_data['expensenote']); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-info btn-block mt-4"><i class="fas fa-save mr-2"></i> Save Changes</button>
                                <a href="manage_expense.php" class="btn btn-outline-secondary btn-block mt-2">Cancel and Go Back</a>
                            </form>
                            <?php endif; ?>
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