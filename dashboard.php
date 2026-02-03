<?php
session_start();

// 1. AUTHENTICATION CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "shuttle");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. GET DATA WITH ERROR CHECKING
function getCount($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) return 0; // Return 0 if table/query fails instead of crashing
    $data = $res->fetch_assoc();
    return $data['total'] ?? 0;
}

$totalUsers = getCount($conn, "SELECT COUNT(*) as total FROM accounts");
$adminUsers = getCount($conn, "SELECT COUNT(*) as total FROM accounts WHERE role LIKE '%Admin%'");

// Get counts for Shuttles (Today only)
$today = date('Y-m-d');
$shuttle1 = getCount($conn, "SELECT COUNT(*) as total FROM ew_daily_records WHERE shuttle_no = 1 AND date = '$today'");
$shuttle2 = getCount($conn, "SELECT COUNT(*) as total FROM ew_daily_records WHERE shuttle_no = 2 AND date = '$today'");
$shuttle3 = getCount($conn, "SELECT COUNT(*) as total FROM ew_daily_records WHERE shuttle_no = 3 AND date = '$today'");

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<head>
    <meta charset="UTF-8">
    <title>EASTWEST Shuttle - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="styles/logo.webp" type="image/webp">
</head>

<body class="dashboard">

    <aside class="sidebar">
        <div class="logo">EW Shuttle System</div>

        <ul class="menu">
            <li class="active">
                <a href="dashboard.php">
                    <strong>Dashboard</strong>
                </a>
            </li>

            <?php if ($role !== 'Dispatcher'): ?>
            <li id="accountsMenuItem">
                <a href="Account.php">Accounts</a>
            </li>
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

            <li>
                <a href="daily_records.php">Records</a>
            </li>
        </ul>

        <div class="dashboard-sidebar-footer">
            <img src="styles/sidebarlogo.jpg" alt="EastWest BPO Logo">
        </div>
    </aside>

    <div class="main">

        <header class="topbar">
            <div class="user-dropdown">
                <button class="user-btn" onclick="toggleUserMenu()">
                    <span id="currentUserDisplay"><?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</span> ▾
                </button>
                <ul class="user-menu" id="userMenu">
                    <li>
                        <a href="logout.php" onclick="return confirm('Logout?')">Logout</a>
                    </li>
                </ul>
            </div>
        </header>

        <main class="content">

            <section class="cards">
                <div class="card">
                    <h4>Shuttle 1</h4>
                    <h2 id="bus1Count"><?php echo $shuttle1; ?></h2>
                </div>

                <div class="card">
                    <h4>Shuttle 2</h4>
                    <h2 id="bus2Count"><?php echo $shuttle2; ?></h2>
                </div>

                <div class="card">
                    <h4>Shuttle 3</h4>
                    <h2 id="bus3Count"><?php echo $shuttle3; ?></h2>
                </div>
            </section>

            <section class="cards2">
                <div class="card">
                    <h4>Shuttle 1 Status</h4>
                    <h2 id="shuttle1Status" class="<?php echo $shuttle1 > 0 ? 'status-online' : 'status-offline'; ?>">
                        <?php echo $shuttle1 > 0 ? 'Active' : 'Offline'; ?>
                    </h2>
                </div>

                <div class="card">
                    <h4>Shuttle 2 Status</h4>
                    <h2 id="shuttle2Status" class="<?php echo $shuttle2 > 0 ? 'status-online' : 'status-offline'; ?>">
                         <?php echo $shuttle2 > 0 ? 'Active' : 'Offline'; ?>
                    </h2>
                </div>

                <div class="card">
                    <h4>Shuttle 3 Status</h4>
                    <h2 id="shuttle3Status" class="<?php echo $shuttle3 > 0 ? 'status-online' : 'status-offline'; ?>">
                         <?php echo $shuttle3 > 0 ? 'Active' : 'Offline'; ?>
                    </h2>
                </div>
            </section>

            <section class="chart-box">
                <h4>Today's Records — All Shuttles</h4>

                <table class="lamesa">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Shuttle No.</th>
                            <th>Employee ID</th>
                        </tr>
                    </thead>
                    <tbody id="todayPassengerTable">
                        <?php
                        $records = $conn->query("SELECT * FROM ew_daily_records WHERE date = '$today' ORDER BY time DESC");
                        if ($records->num_rows > 0):
                            while($row = $records->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo date("h:i A", strtotime($row['time'])); ?></td>
                            <td>Shuttle <?php echo $row['shuttle_no']; ?></td>
                            <td><?php echo $row['employee_id']; ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="4" style="text-align:center;color:#999;">No records for today</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        </main>
    </div>

<script>
function toggleDropdown(id) {
    const menu = document.getElementById(id);
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}

function toggleUserMenu() {
    const menu = document.getElementById("userMenu");
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}

window.addEventListener("click", function(e) {
    const userBtn = document.querySelector(".user-btn");
    const userMenu = document.getElementById("userMenu");
    if (userBtn && userMenu && !userBtn.contains(e.target) && !userMenu.contains(e.target)) {
        userMenu.style.display = "none";
    }
});
</script>

</body>
</html>
