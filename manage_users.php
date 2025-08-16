<!-- this page is only accessible by admin-->
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require 'database.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user'])) {
        // Single delete
        $uid = $_POST['uid'];

        // Delete rentals first to avoid foreign key issues
        $stmt = $conn->prepare("DELETE FROM rentals WHERE UID = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();

        // Then delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE UID = ?");
        $stmt->bind_param("i", $uid);
        if ($stmt->execute()) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Error deleting user: " . $stmt->error;
        }
    } elseif (isset($_POST['bulk_delete']) && !empty($_POST['selected_users'])) {
        // Bulk delete
        $ids = $_POST['selected_users']; // array of IDs

        // Prepare placeholders and types
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        // Delete rentals first
        $stmt = $conn->prepare("DELETE FROM rentals WHERE UID IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();

        // Then delete users
        $stmt = $conn->prepare("DELETE FROM users WHERE UID IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        if ($stmt->execute()) {
            $success_message = count($ids) . " users deleted successfully!";
        } else {
            $error_message = "Error deleting users: " . $stmt->error;
        }
    }
}

// Fetch users
$users = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Users with Filter & Bulk Actions</title>
<link rel="stylesheet" href="admin-common.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
<style>
    .table th, .table td { vertical-align: middle; }
</style>
</head>
<body>
<div class="sidebar">
    <h3 class="text-white text-center mb-4">Admin Menu</h3>
    <nav class="nav flex-column">
        <a class="nav-link" href="manage_twoWheeler.php">Manage Two-Wheeler</a>
        <a class="nav-link" href="manage_fourWheeler.php">Manage Four-Wheeler</a>
        <a class="nav-link active" href="manage_users.php">Manage Users</a>
        <a class="nav-link" href="view_rentals.php">View Rentals</a>
        <a class="nav-link" href="admin_logout.php">Logout</a>
    </nav>
</div>

<div class="content">
    <h1 class="mb-4 text-center">Manage Users</h1>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php elseif ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <input
          type="text"
          id="searchInput"
          class="form-control"
          placeholder=" 🔍 Search users by name, phone, or address..."
          onkeyup="filterTable()"
        />
    </div>

    <form method="POST" id="usersForm" onsubmit="return confirmBulkDelete();">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="usersTable">
                <thead class="thead-dark">
                    <tr>
                        <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" /></th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_users[]" value="<?php echo $user['UID']; ?>" /></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                        <td>
                            <button
                              type="submit"
                              name="delete_user"
                              value="1"
                              formaction=""
                              formmethod="POST"
                              onclick="return confirm('Delete this user?');"
                              class="btn btn-danger btn-sm"
                            >
                              Delete
                            </button>
                            <input type="hidden" name="uid" value="<?php echo $user['UID']; ?>" />
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" name="bulk_delete" class="btn btn-danger mt-3" id="bulkDeleteBtn" disabled>Delete Selected</button>
    </form>
</div>

<script>
function filterTable() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const table = document.getElementById('usersTable');
    const trs = table.tBodies[0].getElementsByTagName('tr');

    for (let row of trs) {
        const cells = row.getElementsByTagName('td');
        const name = cells[1].textContent.toLowerCase();
        const phone = cells[2].textContent.toLowerCase();
        const address = cells[3].textContent.toLowerCase();
        row.style.display = (name.includes(input) || phone.includes(input) || address.includes(input)) ? '' : 'none';
    }
}

function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
    toggleBulkDeleteBtn();
}

function toggleBulkDeleteBtn() {
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
    const bulkBtn = document.getElementById('bulkDeleteBtn');
    bulkBtn.disabled = !Array.from(checkboxes).some(cb => cb.checked);
}

function confirmBulkDelete() {
    return confirm('Are you sure you want to delete selected users?');
}

document.querySelectorAll('input[name="selected_users[]"]').forEach(cb => {
    cb.addEventListener('change', toggleBulkDeleteBtn);
});
</script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
