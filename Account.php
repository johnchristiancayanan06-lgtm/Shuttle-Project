<?php
session_start();

// 1. AUTHENTICATION & PERMISSION CHECK
// Dispatchers are automatically redirected to dashboard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Super Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 2. DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "shuttle");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$username = $_SESSION['username'];
$role = $_SESSION['role']; // Current user's role

// 3. HANDLE OPERATIONS
$message = "";

// DELETE ACCOUNT (Super Admin Only)
if (isset($_GET['delete'])) {
    if ($role === 'Super Admin') {
        $id = intval($_GET['delete']);
        if ($id == $_SESSION['user_id']) {
            $message = "You cannot delete your own account.";
        } else {
            $conn->query("DELETE FROM accounts WHERE id=$id");
            header("Location: Account.php?msg=Deleted");
            exit();
        }
    } else {
        $message = "Access Denied: Only Super Admins can delete accounts.";
    }
}

// ADD ACCOUNT (Super Admin Only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    if ($role === 'Super Admin') {
        $u = trim($_POST['username']);
        $r = $_POST['role'];
        $p = $_POST['password'];
        $cp = $_POST['confirm_password'];

        if ($p !== $cp) {
            $message = "Passwords do not match!";
        } else {
            $stmt = $conn->prepare("INSERT INTO accounts (username, role, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $u, $r, $p);
            $stmt->execute();
            header("Location: Account.php?msg=Added");
            exit();
        }
    } else {
        $message = "Access Denied: Only Super Admins can add accounts.";
    }
}

// UPDATE ACCOUNT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = intval($_POST['account_id']);
    $u = trim($_POST['username']);
    $p = $_POST['password'];

    if ($role === 'Super Admin') {
        // Super Admin can also update the Role
        $r = $_POST['role'];
        if (!empty($p)) {
            $stmt = $conn->prepare("UPDATE accounts SET username=?, role=?, password=? WHERE id=?");
            $stmt->bind_param("sssi", $u, $r, $p, $id);
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET username=?, role=? WHERE id=?");
            $stmt->bind_param("ssi", $u, $r, $id);
        }
    } else {
        // Admin can ONLY update username and password (role is ignored)
        if (!empty($p)) {
            $stmt = $conn->prepare("UPDATE accounts SET username=?, password=? WHERE id=?");
            $stmt->bind_param("ssi", $u, $p, $id);
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET username=? WHERE id=?");
            $stmt->bind_param("si", $u, $id);
        }
    }
    $stmt->execute();
    header("Location: Account.php?msg=Updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EASTWEST Shuttle - Accounts</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="styles/logo.webp" type="image/webp">
    <style>
        .form-group { position: relative; margin-bottom: 15px; }
        .add-account-modal { 
            display: none; position: fixed; top:0; left:0; width:100%; height:100%; 
            background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;
        }
        .update-btn { background: #3498db; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .delete-btn { background: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; margin-left: 5px; text-decoration: none; font-size: 13.3px; }
        /* Style for disabled role dropdown */
        .locked-input { background-color: #f5f5f5; cursor: not-allowed; }
    </style>
</head>

<body class="dashboard">

<aside class="sidebar">
    <div class="logo">EW Shuttle System</div>
    <ul class="menu">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li class="active"><a href="Account.php">Accounts</a></li>
        <li>
            <div class="dropdown-btn" onclick="toggleDropdown('shuttleMenu')">Shuttle ▾</div>
            <ul class="dropdown-menu" id="shuttleMenu">
                <li><a href="shuttle.php?id=1">Shuttle 1</a></li>
                <li><a href="shuttle.php?id=2">Shuttle 2</a></li>
                <li><a href="shuttle.php?id=3">Shuttle 3</a></li>
            </ul>
        </li>
        <li><a href="daily_records.php">Records</a></li>
    </ul>
    <div class="dashboard-sidebar-footer"><img src="styles/sidebarlogo.jpg" alt="Logo"></div>
</aside>

<div class="main">
    <header class="topbar">
        <div class="user-dropdown">
            <button class="user-btn" onclick="toggleUserMenu()">
                <span><?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</span> ▾
            </button>
            <ul class="user-menu" id="userMenu">
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <main class="content">
        <?php if($message): ?>
            <div style="background: #ffcccc; color: #d32f2f; padding: 10px; margin-bottom: 10px; border-radius: 5px;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="actions-bar">
            <?php if($role === 'Super Admin'): ?>
                <button class="primary-btn" onclick="openAddModal()">Add Account</button>
            <?php endif; ?>
        </div>

        <section class="chart-box2">
            <h4>All Accounts</h4>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM accounts ORDER BY id DESC");
                    while($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td>
                            <button class="update-btn" onclick="openUpdateModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo $row['role']; ?>')">Update</button>
                            
                            <?php if($role === 'Super Admin'): ?>
                                <a href="Account.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this account?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<div class="add-account-modal" id="addAccountModal">
    <div class="auth-card">
        <h1>Add Account</h1>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="Dispatcher">Dispatcher</option>
                    <option value="Admin">Admin</option>
                    <option value="Super Admin">Super Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="prime-btn">Create Account</button>
            <button type="button" class="secondary-btn" onclick="closeAddModal()">Cancel</button>
        </form>
    </div>
</div>

<div class="add-account-modal" id="updateAccountModal">
    <div class="auth-card">
        <h1>Update Account</h1>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="account_id" id="upId">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="upUser" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="upRole" required <?php if($role === 'Admin') echo 'disabled class="locked-input"'; ?>>
                    <option value="Dispatcher">Dispatcher</option>
                    <option value="Admin">Admin</option>
                    <option value="Super Admin">Super Admin</option>
                </select>
                <?php if($role === 'Admin'): ?>
                    <input type="hidden" name="role" id="upRoleHidden">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>New Password (blank to keep current)</label>
                <input type="password" name="password">
            </div>
            <button type="submit" class="prime-btn">Update Account</button>
            <button type="button" class="secondary-btn" onclick="closeUpdateModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function toggleDropdown(id) {
    const menu = document.getElementById(id);
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}
function toggleUserMenu() {
    const menu = document.getElementById("userMenu");
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}
function openAddModal() { document.getElementById("addAccountModal").style.display = "flex"; }
function closeAddModal() { document.getElementById("addAccountModal").style.display = "none"; }

function openUpdateModal(id, user, role) {
    document.getElementById("upId").value = id;
    document.getElementById("upUser").value = user;
    document.getElementById("upRole").value = role;
    
    // For Admin: ensure the hidden role field is also set
    if(document.getElementById("upRoleHidden")) {
        document.getElementById("upRoleHidden").value = role;
    }
    
    document.getElementById("updateAccountModal").style.display = "flex";
}
function closeUpdateModal() { document.getElementById("updateAccountModal").style.display = "none"; }
</script>

</body>
</html>
