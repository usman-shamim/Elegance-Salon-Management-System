<?php
$page_title = "Client Management";
// Include necessary files and enforce access control
require_once '../config/functions.php';
require_once '../db_connect.php';

// Check if user is logged in and has access (Admin OR Receptionist)
check_login();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist') {
    header("Location: ../dashboard.php?error=Unauthorized Access");
    exit();
}

$message = '';
$client_to_edit = null; // Used to pre-fill the form for editing

// --- I. Handle Form Submission (Add or Edit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_id = $_POST['client_id'] ?? null;
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $preferences = trim($_POST['preferences']);

    // Simple Validation
    if (empty($name) || empty($phone)) {
        // Updated error message markup
        $message = '<div class="alert alert-danger" role="alert">Client Name and Phone are required fields.</div>';
    } else {
        if ($client_id) {
            // UPDATE Operation (Edit Client)
            $sql = "UPDATE clients SET name=?, phone=?, email=?, preferences=? WHERE client_id=?";
            $stmt = $conn->prepare($sql);
            // 'sssi' means String, String, String, Integer
            $stmt->bind_param("ssssi", $name, $phone, $email, $preferences, $client_id);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">Client updated successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger" role="alert">Error updating client: ' . $conn->error . '</div>';
            }
        } else {
            // INSERT Operation (Add New Client)
            $sql = "INSERT INTO clients (name, phone, email, preferences) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // 'ssss' means String, String, String, String
            $stmt->bind_param("ssss", $name, $phone, $email, $preferences);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">New client added successfully.</div>';
            } else {
                // Check for duplicate key error (e.g., duplicate phone/email)
                if ($conn->errno == 1062) {
                    $message = '<div class="alert alert-warning" role="alert">Error adding client: Phone or Email may already exist.</div>';
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Error adding client: ' . $conn->error . '</div>';
                }
            }
        }
        $stmt->close();
    }
}

// --- II. Handle Edit Request ---
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT client_id, name, phone, email, preferences FROM clients WHERE client_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $client_to_edit = $result->fetch_assoc();
    } else {
        $message = '<div class="alert alert-danger" role="alert">Client not found.</div>';
    }
    $stmt->close();
}

// --- III. Handle Redirect Status Message (from client_delete.php) ---
if (isset($_GET['status'])) {
    $status_message = htmlspecialchars(urldecode($_GET['status']));
    
    // Determine the alert type based on the message content
    $alert_class = 'alert-info';
    if (strpos($status_message, 'successfully') !== false) {
        $alert_class = 'alert-success';
    } elseif (strpos($status_message, 'Cannot delete client') !== false || strpos($status_message, 'error') !== false) {
        $alert_class = 'alert-danger';
    } elseif (strpos($status_message, 'not found') !== false) {
        $alert_class = 'alert-warning';
    }
    
    $message .= '<div class="alert ' . $alert_class . '" role="alert">' . $status_message . '</div>';
}

// --- IV. Fetch All Clients for List View ---
$clients = [];
$sql = "SELECT * FROM clients ORDER BY name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
} else {
    $message = '<div class="alert alert-danger" role="alert">Error fetching clients: ' . $conn->error . '</div>';
}

include '../template/header.php'; // Start HTML output
?>

<h1 class="mb-4">Client Management</h1>

<?php echo $message; ?>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-primary text-white">
        <h2 class="h5 mb-0"><?php echo $client_to_edit ? 'Edit Client: ' . htmlspecialchars($client_to_edit['name']) : 'Add New Client'; ?></h2>
    </div>
    <div class="card-body">
        <form method="POST" action="clients_manage.php">
            <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client_to_edit['client_id'] ?? ''); ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($client_to_edit['name'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone:</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($client_to_edit['phone'] ?? ''); ?>" required>
                </div>

                <div class="col-md-12 mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($client_to_edit['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="preferences" class="form-label">Preferences / Notes:</label>
                <textarea id="preferences" name="preferences" class="form-control" rows="3"><?php echo htmlspecialchars($client_to_edit['preferences'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-success me-2">
                <i class="bi-check-circle me-1"></i> <?php echo $client_to_edit ? 'Update Client' : 'Add Client'; ?>
            </button>
            <?php if ($client_to_edit): ?>
                <a href="clients_manage.php" class="btn btn-danger">
                    <i class="bi-x-circle me-1"></i> Cancel Edit
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<h2 class="mt-5 mb-3">Registered Client List</h2>

<?php if (empty($clients)): ?>
    <div class="alert alert-info">
        <i class="bi-info-circle me-1"></i> No clients found in the database.
    </div>
<?php else: ?>
    <div class="table-responsive shadow-sm">
        <table class="table table-hover table-striped border">
            <thead class="bg-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Preferences</th>
                    <th>Member Since</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?php echo htmlspecialchars($client['client_id']); ?></td>
                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                    <td><?php echo htmlspecialchars($client['phone']); ?></td>
                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                    <td><?php echo htmlspecialchars(substr($client['preferences'], 0, 50)) . (strlen($client['preferences']) > 50 ? '...' : ''); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($client['created_at'])); ?></td>
                    <td class="text-nowrap">
                        <a href="clients_manage.php?edit_id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi-pencil"></i> Edit
                        </a>
                        <a href="client_delete.php?id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete <?php echo addslashes($client['name']); ?>? This cannot be undone and will fail if they have appointments.');">
                            <i class="bi-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php 
$conn->close();
include '../template/footer.php'; // End HTML output
?>