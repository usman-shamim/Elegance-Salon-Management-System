<?php
$page_title = "Book an Appointment";

// Remove error display settings for a public-facing page in a production environment
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once 'config/functions.php';
require_once 'db_connect.php';

// Check if a booking was attempted and process it
$message = '';

// Variables to retain form data on failure
$client_name_val = $_POST['client_name'] ?? '';
$client_phone_val = $_POST['client_phone'] ?? '';
$client_email_val = $_POST['client_email'] ?? '';
$service_id_val = $_POST['service_id'] ?? '';
$staff_id_val = $_POST['staff_id'] ?? '';
$app_date_val = $_POST['app_date'] ?? date('Y-m-d'); // Default to today
$app_time_val = $_POST['app_time'] ?? '';

// --- Handle New Client Creation & Booking Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['client_name']) && isset($_POST['service_id'])) {
    
    // 1. Sanitize and collect data
    $client_name = trim($_POST['client_name']);
    $client_phone = trim($_POST['client_phone']);
    $client_email = trim($_POST['client_email']);
    $service_id = (int)$_POST['service_id'];
    $staff_id = (int)$_POST['staff_id'];
    $app_date = $_POST['app_date'];
    $app_time = $_POST['app_time'];
    $start_datetime_str = $app_date . ' ' . $app_time; 

    // Basic validation
    if (empty($client_name) || empty($service_id) || empty($staff_id) || empty($app_date) || empty($app_time)) {
        $message = '<div class="alert alert-danger" role="alert"><i class="bi-exclamation-triangle-fill me-2"></i> Please fill out all required fields.</div>';
        goto end_booking_logic; // Skip to fetching data if validation fails
    }

    // --- A. Find or Create Client ---
    $client_id = null;
    
    // Try to find client by phone (simple check for existing users)
    $sql_find_client = "SELECT client_id FROM clients WHERE phone = ?";
    $stmt_find = $conn->prepare($sql_find_client);
    $stmt_find->bind_param("s", $client_phone);
    $stmt_find->execute();
    $result_find = $stmt_find->get_result();

    if ($result_find->num_rows > 0) {
        $client_id = $result_find->fetch_assoc()['client_id'];
        $message .= '<div class="alert alert-info alert-sm" role="alert"><i class="bi-person-check-fill me-1"></i> Welcome back, ' . htmlspecialchars($client_name) . '! Using existing client record.</div>';
    } else {
        // Create new client if not found
        $sql_insert_client = "INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_client);
        $stmt_insert->bind_param("sss", $client_name, $client_phone, $client_email);
        
        if ($stmt_insert->execute()) {
            $client_id = $stmt_insert->insert_id;
            $message .= '<div class="alert alert-success alert-sm" role="alert"><i class="bi-person-plus-fill me-1"></i> New client record created for ' . htmlspecialchars($client_name) . '.</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert"><i class="bi-x-octagon-fill me-2"></i> Error creating client record: ' . $conn->error . '</div>';
            goto end_booking_logic; // Stop if client creation fails
        }
        $stmt_insert->close();
    }
    $stmt_find->close();
    
    // --- B. Appointment Creation (Reuse Logic from admin page) ---
    
    if ($client_id) {
        // 1. Get Service Duration
        $sql_duration = "SELECT duration_minutes FROM services WHERE service_id = ?";
        $stmt_duration = $conn->prepare($sql_duration);
        $stmt_duration->bind_param("i", $service_id);
        $stmt_duration->execute();
        $result_duration = $stmt_duration->get_result();
        $service = $result_duration->fetch_assoc();
        $duration = $service['duration_minutes'];
        $stmt_duration->close();

        // 2. Calculate End Time
        $start_timestamp = strtotime($start_datetime_str);
        $end_timestamp = $start_timestamp + ($duration * 60); 
        $end_datetime_str = date('Y-m-d H:i:s', $end_timestamp);
        
        // 3. Run Conflict Check (Same as admin check)
        $sql_conflict = "
            SELECT app_id FROM appointments 
            WHERE staff_id = ?
            AND status != 'Cancelled'
            AND (
                (start_time < ? AND end_time > ?) OR 
                (start_time < ? AND end_time > ?) OR 
                (start_time = ?) 
            )
        ";
        $stmt_conflict = $conn->prepare($sql_conflict);
        $stmt_conflict->bind_param(
            "isssss", 
            $staff_id, 
            $end_datetime_str, 
            $start_datetime_str, 
            $end_datetime_str, 
            $start_datetime_str,
            $start_datetime_str
        );
        $stmt_conflict->execute();
        $conflict_result = $stmt_conflict->get_result();

        if ($conflict_result->num_rows > 0) {
            $message = '<div class="alert alert-warning" role="alert"><i class="bi-clock-history me-2"></i> CONFLICT: The selected stylist is already booked during this time slot. Please choose another time or stylist.</div>';
            $stmt_conflict->close();
        } else {
            // 4. Insert Appointment
            $stmt_conflict->close(); 

            $sql_insert = "INSERT INTO appointments (client_id, service_id, staff_id, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, 'Booked')";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiiss", $client_id, $service_id, $staff_id, $start_datetime_str, $end_datetime_str);

            if ($stmt_insert->execute()) {
                // Success message with dismiss button
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi-calendar-check me-2"></i> 🎉 Appointment successfully booked! You will receive a confirmation shortly.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                
                // Clear form persistence values on successful booking
                $client_name_val = $client_phone_val = $client_email_val = $service_id_val = $staff_id_val = $app_time_val = '';
                $app_date_val = date('Y-m-d');
            } else {
                $message = '<div class="alert alert-danger" role="alert"><i class="bi-x-octagon-fill me-2"></i> Database Error during booking: ' . $conn->error . '</div>';
            }
            $stmt_insert->close();
        }
    }
}

end_booking_logic: // Label for goto statement if needed

// --- Fetch Data for Booking Form ---
// Get all Services for the dropdown
$services = $conn->query("SELECT service_id, name, duration_minutes, price FROM services WHERE is_active = TRUE ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Get all Staff for the dropdown
$staff = [];
$sql_staff = "SELECT s.staff_id, u.username FROM staff s JOIN users u ON s.user_id = u.user_id";
$staff = $conn->query($sql_staff)->fetch_all(MYSQLI_ASSOC);


include 'template/header.php';
?>

<div class="container my-5">
    <h1 class="text-center mb-2 text-primary">Book Your Appointment</h1>
    <p class="text-center lead mb-4 text-muted">Select your service, preferred stylist, and time slot to secure your booking.</p>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php echo $message; ?>

            <div class="card shadow-lg p-4 border-0">
                <form method="POST" action="public_booking.php" id="bookingForm">
                    
                    <h4 class="mb-4 text-dark"><i class="bi-person-circle me-2"></i> 1. Your Contact Details</h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="client_name" name="client_name" class="form-control" value="<?php echo htmlspecialchars($client_name_val); ?>" required placeholder="John Doe">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="client_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" id="client_phone" name="client_phone" class="form-control" value="<?php echo htmlspecialchars($client_phone_val); ?>" required placeholder="555-123-4567">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="client_email" class="form-label">Email (Optional)</label>
                        <input type="email" id="client_email" name="client_email" class="form-control" value="<?php echo htmlspecialchars($client_email_val); ?>" placeholder="john@example.com">
                    </div>
                    
                    <hr class="my-4">
                    <h4 class="mb-4 text-dark"><i class="bi-scissors me-2"></i> 2. Service & Stylist</h4>

                    <div class="mb-3">
                        <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                        <select id="service_id" name="service_id" class="form-select" required>
                            <option value="">-- Select Service --</option>
                            <?php foreach ($services as $s): ?>
                                <option 
                                    value="<?php echo $s['service_id']; ?>" 
                                    data-duration="<?php echo $s['duration_minutes']; ?>"
                                    <?php echo ($service_id_val == $s['service_id']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($s['name']) . " ($" . number_format($s['price'], 2) . " - " . $s['duration_minutes'] . " min)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="staff_id" class="form-label">Preferred Stylist <span class="text-danger">*</span></label>
                        <select id="staff_id" name="staff_id" class="form-select" required>
                            <option value="">-- Select Stylist --</option>
                            <?php foreach ($staff as $st): ?>
                                <option 
                                    value="<?php echo $st['staff_id']; ?>"
                                    <?php echo ($staff_id_val == $st['staff_id']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($st['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <hr class="my-4">
                    <h4 class="mb-4 text-dark"><i class="bi-calendar-check me-2"></i> 3. Date & Time</h4>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="app_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" id="app_date" name="app_date" class="form-control" value="<?php echo htmlspecialchars($app_date_val); ?>" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="app_time" class="form-label">Time (HH:MM) <span class="text-danger">*</span></label>
                            <input type="time" id="app_time" name="app_time" class="form-control" value="<?php echo htmlspecialchars($app_time_val); ?>" required step="1800">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                        <i class="bi-check-circle me-2"></i> Confirm and Book Appointment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'template/footer.php';
?>