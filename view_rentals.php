<!-- this page is only accessible by admin-->
<?php
session_start();
require 'database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$filter = $_GET['type'] ?? 'two';

if (isset($_POST['cancel_rental_id'])) {
    $cancelRentalId = $_POST['cancel_rental_id'];
    $stmt = $conn->prepare("UPDATE rentals SET status = 'canceled' WHERE rental_id = ?");
    $stmt->bind_param("i", $cancelRentalId);
    $stmt->execute();
}

if ($filter === 'four') {
    $rentals = $conn->query("
        SELECT r.*, u.name AS user_name, f.name AS vehicle_name, r.status AS rental_status
        FROM rentals r 
        JOIN users u ON r.UID = u.UID 
        JOIN four_wheeler f ON r.FWID = f.FWID
        ORDER BY r.rental_start ASC
    ");
} else {
    $rentals = $conn->query("
        SELECT r.*, u.name AS user_name, v.name AS vehicle_name, r.status AS rental_status
        FROM rentals r 
        JOIN users u ON r.UID = u.UID 
        JOIN two_wheeler v ON r.TWID = v.TWID
        ORDER BY r.rental_start ASC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Rentals</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="admin-common.css">
<style>
body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.sidebar {
    width: 250px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    background: #2c3e50;
    padding-top: 20px;
}

.sidebar h3 {
    color: #fff;
    text-align: center;
}

.sidebar .nav-link {
    color: #ecf0f1;
    padding: 10px 20px;
    display: block;
    border-bottom: 1px solid #34495e;
}

.sidebar .nav-link:hover {
    background: #34495e;
}

.content {
    margin-left: 250px; /* equal to sidebar width */
    padding: 20px 40px;
}

.filter-btns {
    margin-bottom: 20px;
}

.table-responsive {
    overflow-x: auto;
}

thead.thead-dark {
    background-color: #343a40;
    color: #fff;
}
</style>
</head>
<body>

<div class="sidebar">
    <h3>Admin Menu</h3>
    <nav class="nav flex-column">
         <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
        <a class="nav-link" href="manage_twoWheeler.php">Manage Two-Wheeler</a>
        <a class="nav-link" href="manage_fourWheeler.php">Manage Four-Wheeler</a>
        <a class="nav-link" href="manage_users.php">Manage Users</a>
        <a class="nav-link" href="view_rentals.php">View Rentals</a>
        <a class="nav-link" href="admin_logout.php">Logout</a>
    </nav>
</div>

<div class="content">
    <h1 class="text-center mb-4">View Rentals</h1>

    <div class="filter-btns text-center">
        <a href="?type=two" class="btn btn-outline-primary <?php echo ($filter === 'two') ? 'active' : ''; ?>">Two-Wheelers</a>
        <a href="?type=four" class="btn btn-outline-secondary <?php echo ($filter === 'four') ? 'active' : ''; ?>">Four-Wheelers</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Rental History - <?php echo ucfirst($filter); ?>-Wheeler</h5>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>User Name</th>
                            <th>Vehicle Name</th>
                            <th>Rental Start</th>
                            <th>Rental End</th>
                            <th>Rental Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($rental = $rentals->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rental['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($rental['vehicle_name']); ?></td>
                                <td><?php echo htmlspecialchars($rental['rental_start']); ?></td>
                                <td><?php echo htmlspecialchars($rental['rental_end']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($rental['rental_status'])); ?></td>
                                <td>
                                    <?php if ($rental['rental_status'] === 'active'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="disableButton(this);">
                                            <input type="hidden" name="cancel_rental_id" value="<?php echo htmlspecialchars($rental['rental_id']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Cancel Rental</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Canceled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    function disableButton(form) {
        form.querySelector('button[type="submit"]').disabled = true;
    }
</script>
</body>
</html>
