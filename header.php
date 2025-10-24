<?php
// We assume session.php handles session start and authentication check
// You should include 'session.php' here or at the top of every page.
// For now, we only focus on the HTML structure.

// Function to get the current page name for active link highlighting
function get_current_page() {
    return basename($_SERVER['PHP_SELF']);
}

$current_page = get_current_page();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Expense Manager | Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css"> 

</head>
<body>

    <div class="d-flex" id="wrapper">

        <div class="bg-dark border-right" id="sidebar-wrapper">
            <div class="sidebar-heading text-white p-3 border-bottom border-secondary fs-5 fw-bold">
                <i class="bi bi-wallet2 me-2"></i> Daily Tracker
            </div>
            <div class="list-group list-group-flush">
                
                <a href="index.php" class="list-group-item list-group-item-action bg-dark text-light border-bottom border-secondary 
                    <?php echo ($current_page == 'index.php') ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                
                <a href="add_expense.php" class="list-group-item list-group-item-action bg-dark text-light border-bottom border-secondary
                    <?php echo ($current_page == 'add_expense.php' || $current_page == 'manage_expense.php') ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-cash-stack me-2"></i> Expenses
                </a>

                <a href="income.php" class="list-group-item list-group-item-action bg-dark text-light border-bottom border-secondary
                    <?php echo ($current_page == 'income.php' || $current_page == 'manage_income.php') ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-wallet me-2"></i> Income
                </a>

                <a href="budget.php" class="list-group-item list-group-item-action bg-dark text-light border-bottom border-secondary
                    <?php echo ($current_page == 'budget.php') ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-clipboard-check me-2"></i> Budgeting
                </a>
                
                <a href="reports.php" class="list-group-item list-group-item-action bg-dark text-light border-bottom border-secondary
                    <?php echo ($current_page == 'reports.php') ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-graph-up me-2"></i> Reports
                </a>

                <a href="profile.php" class="list-group-item list-group-item-action bg-dark text-light border-bottom border-secondary
                    <?php echo ($current_page == 'profile.php') ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-person-circle me-2"></i> Profile
                </a>

            </div>
        </div>
        <div id="page-content-wrapper" class="flex-grow-1">

            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    
                    <button class="btn btn-primary" id="sidebarToggle"><i class="bi bi-list"></i></button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i> User Name 
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a>
                                    <a class="dropdown-item" href="change_password.php"><i class="bi bi-lock me-2"></i>Change Password</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-4">
                