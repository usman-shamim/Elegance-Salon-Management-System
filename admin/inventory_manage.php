<?php
$page_title = "Inventory Management";
require_once '../config/functions.php';
require_once '../db_connect.php';


// Check if user is logged in and is an Admin
check_login();
check_access('admin');

$message = '';
$item_to_edit = null;

// --- I. Handle Inventory Form Submission (Add, Edit, or Restock) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'inventory') {
    $item_id = $_POST['item_id'] ?? null;
    $name = trim($_POST['name']);
    $supplier_id = (int)$_POST['supplier_id'];
    $stock_level = (int)$_POST['stock_level'];
    $threshold = (int)$_POST['low_stock_threshold'];
    $cost = (float)$_POST['unit_cost'];

    if (empty($name) || $stock_level < 0 || $threshold < 0 || $cost < 0) {
        // Updated class to Bootstrap alert
        $message = '<div class="alert alert-danger" role="alert">All fields must contain valid, non-negative values.</div>';
    } else {
        $last_restock = date('Y-m-d'); // Set restock date on any stock level change

        if ($item_id) {
            // UPDATE Operation (Edit Item)
            $sql = "UPDATE inventory SET name=?, supplier_id=?, stock_level=?, low_stock_threshold=?, unit_cost=?, last_restock_date=? WHERE item_id=?";
            $stmt = $conn->prepare($sql);
            // 'siiidsi' means String, Integer, Integer, Integer, Double, String, Integer
            $stmt->bind_param("siiidsi", $name, $supplier_id, $stock_level, $threshold, $cost, $last_restock, $item_id);
        } else {
            // INSERT Operation (Add New Item)
            $sql = "INSERT INTO inventory (name, supplier_id, stock_level, low_stock_threshold, unit_cost, last_restock_date) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // 'siiids' means String, Integer, Integer, Integer, Double, String
            $stmt->bind_param("siiids", $name, $supplier_id, $stock_level, $threshold, $cost, $last_restock);
        }

        if ($stmt->execute()) {
            // Updated class to Bootstrap alert
            $message = '<div class="alert alert-success" role="alert">Inventory item ' . ($item_id ? 'updated' : 'added') . ' successfully.</div>';
            
            // --- NEW: Low Stock Notification Check ---
            if ($stock_level <= $threshold) {
                $item_name = $name; // Use the current item name
                $notif_message = "LOW STOCK ALERT: $item_name is at $stock_level units (Threshold: $threshold). Order immediately.";

                // Use a prepared statement to insert the notification
                $stmt_n = $conn->prepare("INSERT INTO notifications (type, message, related_id) VALUES ('Inventory', ?, ?)");
                // Since we don't have the item_id for a new insert yet, we'll pass NULL for related_id here for simplicity
                // Note: For an EDIT, you would pass the item_id here. For simplicity, we keep it as NULL.
                $null_id = NULL;
                $stmt_n->bind_param("si", $notif_message, $null_id);
                $stmt_n->execute();
                $stmt_n->close();
            }
        } else {
            // Updated class to Bootstrap alert
            $message = '<div class="alert alert-danger" role="alert">Error: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// --- II. Handle Edit or Delete Request ---
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? null;

    if ($id && is_numeric($id)) {
        if ($action === 'edit') {
            // Fetch item data for editing
            $sql = "SELECT item_id, name, supplier_id, stock_level, low_stock_threshold, unit_cost FROM inventory WHERE item_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $item_to_edit = $result->fetch_assoc();
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            // DELETE Operation
            $sql = "DELETE FROM inventory WHERE item_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">Inventory item deleted successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger" role="alert">Error deleting item: ' . $conn->error . '</div>';
            }
            $stmt->close();
            // Redirect to clear the GET parameters
            header("Location: inventory_manage.php?status=" . urlencode(strip_tags($message)));
            exit();
        }
    }
}

// --- III. Fetch Suppliers for Dropdown ---
$suppliers = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// --- IV. Fetch All Inventory Items for List View ---
$inventory_list = [];
$sql = "
    SELECT 
        i.item_id, i.name, i.stock_level, i.low_stock_threshold, i.unit_cost, i.last_restock_date,
        s.name AS supplier_name
    FROM inventory i
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    ORDER BY i.name ASC
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inventory_list[] = $row;
    }
}


include '../template/header.php';
?>

<div class="container mt-5">

    <?php
    if (isset($_GET['status'])) {
        // Use Bootstrap alert classes for redirected status
        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($_GET['status']) . '</div>';
    }
    echo $message;
    ?>

    <h1 class="mb-4 text-primary"><i class="bi bi-box-seam me-2"></i> Inventory Management</h1>

    <div class="card shadow mb-5">
        <div class="card-header bg-light">
            <h3 class="mb-0 text-dark"><?php echo $item_to_edit ? 'Edit Item: ' . htmlspecialchars($item_to_edit['name']) : 'Add New Item'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" action="inventory_manage.php">
                <input type="hidden" name="form_type" value="inventory">
                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_to_edit['item_id'] ?? ''); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Item Name:</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($item_to_edit['name'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Supplier:</label>
                    <select id="supplier_id" name="supplier_id" class="form-select">
                        <option value="0">-- No Supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>"
                                <?php echo (isset($item_to_edit['supplier_id']) && $item_to_edit['supplier_id'] == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="stock_level" class="form-label">Current Stock:</label>
                        <input type="number" id="stock_level" name="stock_level" class="form-control" value="<?php echo htmlspecialchars($item_to_edit['stock_level'] ?? 0); ?>" required min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="low_stock_threshold" class="form-label">Low Stock Threshold:</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" value="<?php echo htmlspecialchars($item_to_edit['low_stock_threshold'] ?? 5); ?>" required min="1">
                    </div>
                    <div class="col-md-4">
                        <label for="unit_cost" class="form-label">Unit Cost ($):</label>
                        <input type="number" step="0.01" id="unit_cost" name="unit_cost" class="form-control" value="<?php echo htmlspecialchars($item_to_edit['unit_cost'] ?? '0.00'); ?>" required min="0">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2"><i class="bi bi-floppy me-1"></i> <?php echo $item_to_edit ? 'Update Item' : 'Add Item'; ?></button>
                <?php if ($item_to_edit): ?>
                    <a href="inventory_manage.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <hr class="my-5">

    <h2><i class="bi bi-list-check me-2"></i> Stock Level Overview</h2>

    <?php
    // --- V. Low Inventory Alert and Report Generation (Simple) ---
    $low_stock_items = array_filter($inventory_list, function ($item) {
        return $item['stock_level'] <= $item['low_stock_threshold'];
    });

    if (!empty($low_stock_items)):
    ?>
        <div class="alert alert-danger d-flex align-items-center mb-3">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
            <div>
                <strong>🚨 LOW STOCK ALERT:</strong> <?php echo count($low_stock_items); ?> Items are at or below the threshold. Order immediately.
            </div>
        </div>

        <div class="mb-4">
            <a href="?action=generate_report" class="btn btn-warning"><i class="bi bi-file-earmark-bar-graph me-1"></i> Generate Purchase Order List</a>
        </div>

        <?php
        // Simple code to "generate report"
        if (isset($_GET['action']) && $_GET['action'] === 'generate_report'): ?>
            <div class="card p-3 mb-4 bg-light">
                <h4 class="card-title text-warning"><i class="bi bi-truck me-2"></i> Purchase Order Summary:</h4>
                <ul class="list-group list-group-flush">
                    <?php foreach ($low_stock_items as $item):
                        // Calculate quantity to order (e.g., reorder up to 3x threshold)
                        $qty_to_order = $item['low_stock_threshold'] * 3;
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            **<?php echo htmlspecialchars($item['name']); ?>** (Current: <span class="badge bg-danger"><?php echo $item['stock_level']; ?></span>)
                            <span class="text-nowrap">
                                Order **<?php echo $qty_to_order; ?>** from <strong class="text-primary"><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></strong>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Item Name</th>
                    <th>Supplier</th>
                    <th>Stock Level</th>
                    <th>Cost (Unit)</th>
                    <th>Value (Total)</th>
                    <th>Last Restock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory_list as $item): ?>
                    <tr class="<?php echo ($item['stock_level'] <= $item['low_stock_threshold']) ? 'table-danger' : ''; ?>">
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['stock_level']); ?> / T:<?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($item['unit_cost'], 2)); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($item['stock_level'] * $item['unit_cost'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($item['last_restock_date'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="inventory_manage.php?action=edit&id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-info text-white me-2"><i class="bi bi-pencil"></i> Edit</a>
                            <a href="inventory_manage.php?action=delete&id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirm deletion of <?php echo addslashes($item['name']); ?>?');"><i class="bi bi-trash"></i> Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Ensure the connection is closed ONLY HERE
if (isset($conn)) {
    $conn->close();
}
include '../template/footer.php';
?>