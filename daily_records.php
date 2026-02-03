<?php
session_start();

// 1. AUTHENTICATION CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "shuttle");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// 3. FETCH RECORDS WITH FILTERING
$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';

// Base Query
$query = "SELECT * FROM ew_daily_records";

// Apply Date Filters if set
if (!empty($fromDate) && !empty($toDate)) {
    $f = $conn->real_escape_string($fromDate);
    $t = $conn->real_escape_string($toDate);
    $query .= " WHERE date BETWEEN '$f' AND '$t'";
}

// Order by newest first
$query .= " ORDER BY date DESC, time DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EASTWEST Shuttle - Records</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <link rel="icon" href="styles/logo.webp" type="image/webp">
</head>

<body class="dashboard">

<aside class="sidebar">
    <div class="logo">EW Shuttle System</div>

    <ul class="menu">
        <li><a href="dashboard.php"><strong>Dashboard</strong></a></li>
        
        <?php if ($role !== 'Dispatcher'): ?>
        <li id="accountsMenuItem"><a href="Account.php">Accounts</a></li>
        <?php endif; ?>

        <li>
            <div class="dropdown-btn" onclick="toggleDropdown('shuttleMenu')">
                Shuttle ▾
            </div>
            <ul class="dropdown-menu" id="shuttleMenu">
                <li><a href="shuttle.php?id=1">Shuttle 1</a></li>
                <li><a href="shuttle.php?id=2">Shuttle 2</a></li>
                <li><a href="shuttle.php?id=3">Shuttle 3</a></li>
            </ul>
        </li>

        <li class="active"><a href="daily_records.php">Records</a></li>
    </ul>
    
    <div class="dashboard-sidebar-footer">
        <img src="styles/sidebarlogo.jpg" alt="EastWest BPO Logo">
    </div>
</aside>

<div class="main">

    <header class="topbar">
        <div class="user-dropdown">
            <button class="user-btn" onclick="toggleUserMenu()">
                <span id="currentUserDisplay"><?php echo htmlspecialchars($username) . " ($role)"; ?></span> ▾
            </button>
            <ul class="user-menu" id="userMenu">
                <li><a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
            </ul>
        </div>
    </header>

    <main class="content">

        <div class="top-controls">
            <div class="filter-bar">
                <form method="GET" style="display: flex; gap: 10px; align-items: flex-end;">
                    <div class="filter-group">
                        <label>From</label>
                        <input type="date" name="fromDate" id="fromDate" value="<?php echo htmlspecialchars($fromDate); ?>">
                    </div>

                    <div class="filter-group">
                        <label>To</label>
                        <input type="date" name="toDate" id="toDate" value="<?php echo htmlspecialchars($toDate); ?>">
                    </div>

                    <button type="submit" class="filter-btn">Filter</button>
                    <button type="button" class="secondary-btn clear-btn" onclick="window.location.href='daily_records.php'">Clear</button>
                </form>
            </div>

            <?php if ($role === 'Super Admin' || $role === 'Admin'): ?>
            <button class="primary-btn export-btn" id="exportBtn" onclick="exportExcel()">Export</button>
            <?php endif; ?>
        </div>

        <section class="chart-box3">
            <h4>Shuttle Trip Logs — History</h4>

            <table id="dailyRecordsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Shuttle No.</th>
                        <th>Employee ID</th>
                    </tr>
                </thead>

                <tbody id="recordsBody">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date("m/d/Y", strtotime($row['date'])); ?></td>
                                <td><?php echo date("h:i A", strtotime($row['time'])); ?></td>
                                <td>Shuttle <?php echo htmlspecialchars($row['shuttle_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#999; padding: 20px;">
                                No records found in the selected date range.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>
</div>

<script>
// UI INTERACTION SCRIPTS
function toggleDropdown(id) {
    const menu = document.getElementById(id);
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}

function toggleUserMenu() {
    const menu = document.getElementById("userMenu");
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}

window.addEventListener("click", e => {
    const btn = document.querySelector(".user-btn");
    const menu = document.getElementById("userMenu");
    if (btn && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = "none";
    }
});

// EXCEL EXPORT FUNCTION
function exportExcel() {
    const table = document.getElementById("dailyRecordsTable");
    const wb = XLSX.utils.table_to_book(table, { sheet: "Shuttle Logs" });
    const today = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, `shuttle_logs_exported_${today}.xlsx`);
}

// AUTO-SET DEFAULT DATES (Last 30 Days)
if (!document.getElementById("fromDate").value) {
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    document.getElementById("fromDate").valueAsDate = thirtyDaysAgo;
}
if (!document.getElementById("toDate").value) {
    document.getElementById("toDate").valueAsDate = new Date();
}
</script>

</body>
</html>
