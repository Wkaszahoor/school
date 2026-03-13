<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function inventory_active(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'active' : '';
}
function inventory_tab_active(string $tab, string $activeTab): string
{
    return $tab === $activeTab ? 'active' : '';
}
$inventoryTabs = ['all', 'categories', 'items', 'stock_entry', 'issues', 'current_stock', 'reports', 'ledger'];
$inventoryTab = (string)($_GET['tab'] ?? 'all');
if (!in_array($inventoryTab, $inventoryTabs, true)) {
    $inventoryTab = 'all';
}
$inventoryAvatar = trim((string)($_SESSION['auth_avatar'] ?? ''));
if ($inventoryAvatar === '') {
    $inventoryAvatar = 'https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KORT - Inventory Panel</title>
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <link href="../admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="../admin/css/sb-admin-2.css" rel="stylesheet">
    <style>
        .topbar .navbar-search.global-search {
            width: 100%;
            max-width: 640px;
        }
        .topbar .navbar-search.global-search .input-group {
            border: 1px solid #d8dee9;
            border-radius: 999px;
            background: #f8f9fc;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        .topbar .navbar-search.global-search .form-control {
            border: 0;
            background: transparent !important;
            font-size: 0.9rem;
            height: 40px;
            padding-left: 0.95rem;
        }
        .topbar .navbar-search.global-search .form-control:focus {
            box-shadow: none;
        }
        .topbar .navbar-search.global-search .btn {
            border: 0;
            border-left: 1px solid #d8dee9;
            background: #2e59d9;
            min-width: 44px;
            color: #fff;
        }
        .topbar .navbar-search.global-search .btn:hover {
            background: #2653d4;
            color: #fff;
        }
        @media (max-width: 991.98px) {
            .topbar .navbar-search.global-search {
                max-width: 460px;
            }
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <ul class="navbar-nav babar sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
            <div class="sidebar-brand-icon" style="transform: rotate(0deg);">
                <img src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo" style="width: 63px; height: auto;">
            </div>
            <div class="sidebar-brand-text mx-3">Inventory</div>
        </a>
        <hr class="sidebar-divider my-0">

        <li class="nav-item <?php echo ($currentPage === 'dashboard.php' && $inventoryTab === 'all') ? 'active' : ''; ?>">
            <a class="nav-link" href="dashboard.php?tab=all"><i class="fas fa-fw fa-home"></i><span>Dashboard</span></a>
        </li>
        <li class="nav-item <?php echo inventory_tab_active('categories', $inventoryTab); ?>">
            <a class="nav-link" href="dashboard.php?tab=categories"><i class="fas fa-tags"></i><span>Categories</span></a>
        </li>
        <li class="nav-item <?php echo inventory_tab_active('items', $inventoryTab); ?>">
            <a class="nav-link" href="dashboard.php?tab=items"><i class="fas fa-box-open"></i><span>Create Item</span></a>
        </li>
        <li class="nav-item <?php echo inventory_tab_active('stock_entry', $inventoryTab); ?>">
            <a class="nav-link" href="dashboard.php?tab=stock_entry"><i class="fas fa-random"></i><span>Stock In/Out</span></a>
        </li>
        <li class="nav-item <?php echo inventory_tab_active('issues', $inventoryTab); ?>">
            <a class="nav-link" href="dashboard.php?tab=issues"><i class="fas fa-share-square"></i><span>Issue Records</span></a>
        </li>
        <li class="nav-item <?php echo inventory_tab_active('current_stock', $inventoryTab); ?>">
            <a class="nav-link" href="dashboard.php?tab=current_stock"><i class="fas fa-warehouse"></i><span>Current Stock</span></a>
        </li>
        <li class="nav-item <?php echo inventory_tab_active('reports', $inventoryTab); ?>">
            <a class="nav-link" href="dashboard.php?tab=reports"><i class="fas fa-chart-bar"></i><span>Monthly Report</span></a>
        </li>
        <li class="nav-item <?php echo inventory_tab_active('ledger', $inventoryTab); ?>">
            <a class="nav-link" href="dashboard.php?tab=ledger"><i class="fas fa-book"></i><span>Ledger</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </li>

        <hr class="sidebar-divider d-none d-md-block">
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search global-search" onsubmit="return false;">
                    <div class="input-group">
                        <input
                            type="text"
                            id="inventoryGlobalSearch"
                            class="form-control border-0 small"
                            placeholder="Search inventory tables..."
                            aria-label="Search"
                            autocomplete="off"
                        >
                        <div class="input-group-append">
                            <button class="btn" id="inventoryGlobalSearchBtn" type="button" aria-label="Search">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars((string)($_SESSION['auth_name'] ?? 'Inventory Manager'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <img class="img-profile rounded-circle" src="<?php echo htmlspecialchars($inventoryAvatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Inventory Avatar">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="index.php?logout=1">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
