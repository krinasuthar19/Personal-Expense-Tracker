<?php
// Ensure configuration and session are loaded first
include("session.php");
include("config.php");

$success_message = $error_message = $delete_message = '';
$current_page = basename($_SERVER['PHP_SELF']);

$user_id_int = (int) $userid;
$selected_month = $_SESSION['selected_month'] ?? date('n');
$selected_year = $_SESSION['selected_year'] ?? date('Y');
$base_url_params = "?month={$selected_month}&year={$selected_year}";

// --- A. Handle Form Submission (ADD Category) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim(filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_STRING));

    if (empty($category_name)) {
        $error_message = "Category name cannot be empty.";
    } else {
        // SQL: Insert new category
        $sql = "INSERT INTO expense_categories (user_id, category_name) VALUES (?, ?)";

        if ($stmt = mysqli_prepare($con, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $user_id_int, $category_name);

            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Category '{$category_name}' added successfully!";
            } else {
                if (mysqli_errno($con) == 1062) { // MySQL error code for duplicate entry
                    $error_message = "Error: Category '{$category_name}' already exists.";
                } else {
                    $error_message = "Error adding category: " . mysqli_error($con);
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error preparing statement.";
        }
    }
}

// --- B. Handle Archive Operation (Set status to 'inactive') ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $category_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($category_id_to_delete) {
        $sql = "UPDATE expense_categories SET category_status = 'inactive' WHERE category_id = ? AND user_id = ?";

        if ($stmt = mysqli_prepare($con, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $category_id_to_delete, $user_id_int);

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $delete_message = "Category marked as inactive (archived). Historical expenses are preserved.";
                } else {
                    $error_message = "Category not found or failed to update.";
                }
            } else {
                $error_message = "Error archiving category: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- C. Handle Reactivate Operation (Set status back to 'active') ---
if (isset($_GET['action']) && $_GET['action'] == 'reactivate' && isset($_GET['id'])) {
    $category_id_to_reactivate = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($category_id_to_reactivate) {
        $sql = "UPDATE expense_categories SET category_status = 'active' WHERE category_id = ? AND user_id = ?";

        if ($stmt = mysqli_prepare($con, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $category_id_to_reactivate, $user_id_int);

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $success_message = "Category successfully reactivated and is now available for new expenses!";
                } else {
                    $error_message = "Category not found or failed to reactivate.";
                }
            } else {
                $error_message = "Error reactivating category: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- D. Fetch All Categories for Display (Active & Inactive) ---
$categories_list = [];
// This query fetches both active and inactive categories to display the full list
$sql_fetch = "SELECT category_id, category_name, category_status FROM expense_categories WHERE user_id = ? ORDER BY category_name ASC";

if ($stmt_fetch = mysqli_prepare($con, $sql_fetch)) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $user_id_int);
    mysqli_stmt_execute($stmt_fetch);
    $result_fetch = mysqli_stmt_get_result($stmt_fetch);
    while ($row = mysqli_fetch_assoc($result_fetch)) {
        $categories_list[] = $row;
    }
    mysqli_stmt_close($stmt_fetch);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Categories - Daily Expense Tracker</title>
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
                <a href="index.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="home" class="mr-2"></span> Dashboard
                </a>
                <a href="add_expense.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="plus-square" class="mr-2"></span> Add Expense
                </a>
                <a href="manage_expense.php<?php echo $base_url_params; ?>"
                    class="list-group-item list-group-item-action">
                    <span data-feather="list" class="mr-2"></span> Manage Expenses
                </a>
                <a href="income.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="dollar-sign" class="mr-2"></span> Income Management
                </a>
                <a href="budget.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="check-square" class="mr-2"></span> Budgeting
                </a>
                <a href="reports.php<?php echo $base_url_params; ?>" class="list-group-item list-group-item-action">
                    <span data-feather="bar-chart-2" class="mr-2"></span> Reports & Analytics
                </a>
                <a href="manage_categories.php<?php echo $base_url_params; ?>"
                    class="list-group-item list-group-item-action sidebar-active">
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
                <h1 class="mt-4 mb-4 font-weight-light">Expense Category Management</h1>

                <?php if ($success_message || $delete_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message ?: $delete_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-plus-circle mr-2"></i> Add New Category
                            </div>
                            <div class="card-body">
                                <form method="POST" action="manage_categories.php<?php echo $base_url_params; ?>">
                                    <input type="hidden" name="add_category" value="1">
                                    <div class="form-group">
                                        <label for="category_name">Category Name</label>
                                        <input type="text" class="form-control" id="category_name" name="category_name"
                                            required maxlength="100">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block mt-3">Add Category</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-list mr-2"></i> Existing Categories
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">Archived categories are hidden from future expense forms but
                                    their data is preserved.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Category Name</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($categories_list)): ?>
                                                <?php foreach ($categories_list as $category): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                                        <td>
                                                            <span
                                                                class="badge bg-<?php echo ($category['category_status'] == 'active' ? 'success' : 'secondary'); ?>">
                                                                <?php echo ucfirst($category['category_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($category['category_status'] == 'active'): ?>
                                                                <a href="javascript:void(0);"
                                                                    onclick="confirmArchive(<?php echo $category['category_id']; ?>)"
                                                                    class="btn btn-sm btn-warning" title="Archive">
                                                                    <span data-feather="archive"></span> Archive
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="manage_categories.php<?php echo $base_url_params; ?>&action=reactivate&id=<?php echo $category['category_id']; ?>"
                                                                    class="btn btn-sm btn-info" title="Reactivate">
                                                                    <span data-feather="check-circle"></span> Reactivate
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>s
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No custom categories found.</td>
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
    <script>
        $("#menu-toggle").click(function (e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
        feather.replace();

        function confirmArchive(id) {
            if (confirm("Archiving this category will hide it from future expense forms. Existing expenses under this category will NOT be deleted. Proceed?")) {
                // Pass category_id and persistent parameters back to the current page
                window.location.href = 'manage_categories.php<?php echo $base_url_params; ?>&action=delete&id=' + id;
            }
        }
    </script>
</body>

</html>