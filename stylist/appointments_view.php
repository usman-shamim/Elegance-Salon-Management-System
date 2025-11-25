<?php
$page_title = "My Schedule";
require_once '../config/functions.php';
require_once '../db_connect.php';

check_login();

// 1. Ensure the user is a 'stylist'
if ($_SESSION['role'] !== 'stylist') {
    // Redirect if not a stylist
    header("Location: ../dashboard.php");
    exit();
}

$message = '';
$stylist_user_id = $_SESSION['user_id'];
$stylist_staff_id = null;


// 2. Fetch the logged-in Stylist's staff_id
// This is crucial to filter the appointments.
$sql_staff_id = "SELECT staff_id FROM staff WHERE user_id = ?";
$stmt_staff_id = $conn->prepare($sql_staff_id);
$stmt_staff_id->bind_param("i", $stylist_user_id);
$stmt_staff_id->execute();
$result_staff_id = $stmt_staff_id->get_result();
if ($row = $result_staff_id->fetch_assoc()) {
    $stylist_staff_id = $row['staff_id'];
}
$stmt_staff_id->close();


// --- III. Fetch Appointments for Stylist ---
$appointments = [];

if ($stylist_staff_id !== null) {
    $sql_app = "
        SELECT 
            a.app_id, a.start_time, a.end_time, a.status, a.client_id,
            c.name AS client_name, c.phone AS client_phone, c.email AS client_email, c.preferences AS client_preferences,
            s.name AS service_name, s.price
        FROM appointments a
        JOIN clients c ON a.client_id = c.client_id
        JOIN services s ON a.service_id = s.service_id
        WHERE a.staff_id = ? 
        AND a.start_time >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) -- Show today and future
        ORDER BY a.start_time ASC
    ";

    $stmt_app = $conn->prepare($sql_app);
    $stmt_app->bind_param("i", $stylist_staff_id);
    $stmt_app->execute();
    $result_app = $stmt_app->get_result();
    
    while ($row = $result_app->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt_app->close();
}


// --- IV. HTML Output ---
include '../template/header.php';
echo $message;
?>

<h1 class="mb-4">My Schedule</h1>
<p class="lead text-muted">Welcome, **<?php echo htmlspecialchars($_SESSION['username']); ?>**! Below are your appointments starting today.</p>

<?php if ($stylist_staff_id === null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi-exclamation-triangle-fill"></i> Error: Stylist staff profile not linked. Please contact administration.
    </div>
<?php elseif (empty($appointments)): ?>
    <div class="alert alert-info" role="alert">
        <i class="bi-calendar-check"></i> You have no appointments scheduled for today or in the future. Enjoy the free time!
    </div>
<?php else: ?>
    <div class="table-responsive shadow-sm">
        <table class="table table-hover table-striped border">
            <thead class="bg-light">
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Client Name</th>
                    <th>Service (Price)</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app): 
                    $start = strtotime($app['start_time']);
                    $end = strtotime($app['end_time']);
                    $duration_minutes = round(abs($end - $start) / 60);

                    // Determine status badge color
                    $status_class = match ($app['status']) {
                        'Booked' => 'bg-primary',
                        'Confirmed' => 'bg-success',
                        'Completed' => 'bg-secondary',
                        'Cancelled' => 'bg-danger',
                        default => 'bg-info',
                    };
                ?>
                <tr>
                    <td><?php echo date('Y-m-d', $start); ?></td>
                    <td><?php echo date('H:i', $start); ?> - <?php echo date('H:i', $end); ?></td>
                    <td><?php echo htmlspecialchars($app['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['service_name']); ?> ($<?php echo number_format($app['price'], 2); ?>)</td>
                    <td><?php echo $duration_minutes; ?> min</td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($app['status']); ?></span></td>
                    <td class="text-nowrap">
                        <?php if ($app['status'] !== 'Cancelled'): ?>
                            <!-- Button to trigger the Bootstrap Modal -->
                            <button 
                                type="button" 
                                class="btn btn-sm btn-outline-info" 
                                data-bs-toggle="modal" 
                                data-bs-target="#clientDetailsModal"
                                
                                data-client-name="<?php echo htmlspecialchars($app['client_name']); ?>"
                                data-client-phone="<?php echo htmlspecialchars($app['client_phone']); ?>"
                                data-client-email="<?php echo htmlspecialchars($app['client_email']); ?>"
                                data-client-preferences="<?php echo htmlspecialchars($app['client_preferences']); ?>"
                                data-service-name="<?php echo htmlspecialchars($app['service_name']); ?>"
                                data-app-time="<?php echo date('H:i', $start); ?> - <?php echo date('H:i', $end); ?>"
                            >
                                <i class="bi-eye"></i> Details
                            </button>
                        <?php else: ?>
                            <span class="text-danger"><i class="bi-x-octagon"></i> Cancelled</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Bootstrap Modal Structure for Details -->
<div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="clientDetailsModalLabel">Appointment & Client Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Client:</strong> <span id="modal-client-name"></span></p>
                <p><strong>Phone:</strong> <span id="modal-client-phone"></span></p>
                <p><strong>Email:</strong> <span id="modal-client-email"></span></p>
                <p><strong>Service:</strong> <span id="modal-service-name" class="badge bg-success"></span></p>
                <p><strong>Time Slot:</strong> <span id="modal-app-time"></span></p>
                <hr>
                <h6>Client Preferences/Notes:</h6>
                <p id="modal-client-preferences" class="alert alert-light border"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * JavaScript to populate the Bootstrap Modal with appointment data 
 * when the 'Details' button is clicked.
 */
document.addEventListener('DOMContentLoaded', function() {
    const detailsModal = document.getElementById('clientDetailsModal');
    if (detailsModal) {
        detailsModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data-bs-* attributes
            const clientName = button.getAttribute('data-client-name');
            const clientPhone = button.getAttribute('data-client-phone');
            const clientEmail = button.getAttribute('data-client-email');
            const clientPreferences = button.getAttribute('data-client-preferences');
            const serviceName = button.getAttribute('data-service-name');
            const appTime = button.getAttribute('data-app-time');

            // Update the modal's content
            detailsModal.querySelector('#modal-client-name').textContent = clientName;
            detailsModal.querySelector('#modal-client-phone').textContent = clientPhone;
            detailsModal.querySelector('#modal-client-email').textContent = clientEmail;
            detailsModal.querySelector('#modal-service-name').textContent = serviceName;
            detailsModal.querySelector('#modal-app-time').textContent = appTime;
            detailsModal.querySelector('#modal-client-preferences').textContent = clientPreferences || 'No specific preferences recorded.';
        });
    }
});
// The old showClientDetails function is now removed, as the modal handles the display.
</script>

<?php 
$conn->close();
include '../template/footer.php';
?>