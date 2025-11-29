<?php 
// ... (PHP logic remains unchanged above this line) ...

// --- IV. HTML Output ---
include '../template/header.php';

// Style the message with Bootstrap alerts
if (!empty($message)) {
    // Determine alert type based on message content
    $alert_type = (strpos($message, 'success-message') !== false) ? 'alert-success' : 'alert-danger';
    
    // Clean up the inner message text for the alert box
    $clean_message = str_replace(['<p class="success-message">', '<p class="error-message">', '</p>', '', '*'], '', $message);
    
    echo '<div class="alert ' . $alert_type . '" role="alert">' . $clean_message . '</div>';
}
?>

<h2 class="mb-4">Appointment Management</h2>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-primary text-white">
        <h3 class="h5 mb-0">Book New Appointment</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="appointments_manage.php">
            <div class="row">
                
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label">Client:</label>
                    <select id="client_id" name="client_id" class="form-select" required>
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['client_id']; ?>"><?php echo htmlspecialchars($c['name']) . " (" . htmlspecialchars($c['phone']) . ")"; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted"><a href="clients_manage.php">Add New Client</a> if not listed.</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="staff_id" class="form-label">Stylist:</label>
                    <select id="staff_id" name="staff_id" class="form-select" required>
                        <option value="">-- Select Stylist --</option>
                        <?php foreach ($staff as $st): ?>
                            <option value="<?php echo $st['staff_id']; ?>"><?php echo htmlspecialchars($st['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="service_id" class="form-label">Service:</label>
                    <select id="service_id" name="service_id" class="form-select" required>
                        <option value="">-- Select Service --</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?php echo $s['service_id']; ?>">
                                <?php echo htmlspecialchars($s['name']) . " ($" . number_format($s['price'], 2) . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="app_date" class="form-label">Date:</label>
                    <input type="date" id="app_date" name="app_date" class="form-control" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="app_time" class="form-label">Time (HH:MM):</label>
                    <input type="time" id="app_time" name="app_time" class="form-control" required step="1800">
                </div>
            </div>
            
            <button type="submit" class="btn btn-success mt-2"><i class="bi-calendar-plus"></i> Book Appointment</button>
        </form>
    </div>
</div>
<hr>

<h2 class="mb-4">Recent & Upcoming Appointments</h2>

<?php if (empty($appointments)): ?>
    <div class="alert alert-info">No recent or upcoming appointments found.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Client</th>
                    <th>Stylist</th>
                    <th>Service (Price)</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app): 
                    $is_paid = $app['payment_id'] !== null;
                    $is_completed = $app['status'] === 'Completed';
                    
                    // --- ROBUST TIME COMPARISON using DateTime objects ---
                    $is_in_past = false;
                    try {
                        $app_end_datetime = new DateTime($app['end_time']);
                        $current_datetime = new DateTime();
                        $is_in_past = $app_end_datetime < $current_datetime; 
                    } catch (Exception $e) {
                        error_log("Date parsing error for appointment {$app['app_id']}: " . $e->getMessage());
                        $is_in_past = false;
                    }
                    // --- END TIME COMPARISON ---

                    // Determine status badge color
                    $status_badge_class = 'badge ';
                    if ($app['status'] === 'Booked') {
                        $status_badge_class .= 'bg-primary';
                    } elseif ($app['status'] === 'Completed') {
                        $status_badge_class .= 'bg-success';
                    } elseif ($app['status'] === 'Cancelled') {
                        $status_badge_class .= 'bg-danger';
                    } else {
                        $status_badge_class .= 'bg-secondary';
                    }
                ?>
                <tr>
                    <td>
                        <?php echo date('M d, Y', strtotime($app['start_time'])); ?><br>
                        <small class="text-muted"><?php echo date('H:i', strtotime($app['start_time'])); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($app['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['staff_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['service_name']); ?> ($<?php echo number_format($app['price'], 2); ?>)</td>
                    <td><span class="<?php echo $status_badge_class; ?>"><?php echo htmlspecialchars($app['status']); ?></span></td>
                    <td>
                        <?php if ($is_paid): ?>
                            <span class="badge bg-success"><i class="bi-check-circle-fill"></i> PAID</span>
                            <small class="d-block text-muted">Inv: <?php echo htmlspecialchars($app['invoice_number']); ?></small>
                        <?php elseif ($is_completed): ?>
                            <span class="badge bg-danger"><i class="bi-exclamation-triangle-fill"></i> UNPAID</span>
                        <?php else: ?>
                            <span class="text-muted">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $has_action = false;

                        // Action 1: Mark as Completed (Only if status is 'Booked' AND end time is in the past)
                        if ($app['status'] === 'Booked' && $is_in_past) {
                            echo '<a href="?action=complete&id=' . $app['app_id'] . '" class="btn btn-warning btn-sm mb-1"><i class="bi-check2"></i> Complete</a>';
                            $has_action = true;
                        }

                        // Action 2: Process Payment (Only if Completed and Unpaid)
                        if ($is_completed && !$is_paid) {
                            // Using Bootstrap collapse utility for the payment form
                            echo '
                            <button class="btn btn-success btn-sm mb-1" type="button" data-bs-toggle="collapse" data-bs-target="#pay-' . $app['app_id'] . '" aria-expanded="false" aria-controls="pay-' . $app['app_id'] . '">
                                <i class="bi-cash-coin"></i> Take Payment
                            </button>
                            
                            <div class="collapse mt-2" id="pay-' . $app['app_id'] . '">
                                <div class="card card-body p-2 border-success">
                                    <form method="POST" action="appointments_manage.php">
                                        <input type="hidden" name="form_type" value="payment">
                                        <input type="hidden" name="app_id" value="' . $app['app_id'] . '">
                                        <input type="hidden" name="amount_paid" value="' . $app['price'] . '">
                                        
                                        <p class="mb-2">Due: <strong>$' . number_format($app['price'], 2) . '</strong></p>
                                        
                                        <select name="payment_method" class="form-select form-select-sm mb-2" required>
                                            <option value="">Select Method</option>
                                            <option value="Card">Card</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Mobile Pay">Mobile Pay</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">Confirm</button>
                                    </form>
                                </div>
                            </div>';
                            $has_action = true;
                        }

                        // Display N/A if no relevant action is available
                        if (!$has_action) {
                            echo '<span class="text-muted small">No actions needed</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php 
$conn->close();
include '../template/footer.php';
?>