<?php
$page_title = "Staff Dashboard";
require_once 'config/functions.php';

// Ensure user is logged in
check_login(); 

$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Determine the correct dashboard link based on the user role
if ($role === 'admin') {
    $dashboard_link = "admin/reports_analytics.php"; // Suggested more informative admin landing
} elseif ($role === 'receptionist') {
    $dashboard_link = "receptionist/appointments_manage.php";
} elseif ($role === 'stylist') {
    $dashboard_link = "stylist/appointments_view.php";
} else {
    // Fallback for unexpected roles
    $dashboard_link = "index.php"; 
}

// --- IMMEDIATE SERVER-SIDE REDIRECT ---
// Use a server-side redirect for reliability. This file will no longer output HTML.
header("Location: " . $dashboard_link);
exit();
// --- END REDIRECT ---


// --- HTML Fallback (In case headers are already sent, or for initial styling) ---
// Note: If the redirect above works, the code below is never executed. 
// However, we include it styled for best practice.

include 'template/header.php'; 

$welcome_message = "Welcome, " . htmlspecialchars(ucfirst($role)) . " " . htmlspecialchars($username) . "!";
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="text-center py-5">
            <h1 class="display-4 text-primary mb-3"><?php echo $welcome_message; ?></h1>
            <p class="lead mb-4">Your current role is: <strong><?php echo htmlspecialchars(ucfirst($role)); ?></strong></p>
            
            <p class="mb-4">You are being redirected to your main control panel automatically.</p>
            
            <p class="mb-5">
                <a href="<?php echo $dashboard_link; ?>" class="btn btn-xl btn-primary">
                    <i class="bi-arrow-right-circle me-2"></i> Click Here to Continue
                </a>
            </p>

            <p class="mt-5"><a href="logout.php" class="text-muted"><i class="bi-box-arrow-right"></i> Logout</a></p>
        </div>
    </div>
</div>

<script>
    // Keeping a client-side redirect fallback in case the header() fails (e.g., if output buffering is off)
    setTimeout(function() {
        window.location.href = '<?php echo $dashboard_link; ?>';
    }, 1500); // Wait 1.5 seconds
</script>

<?php 
include 'template/footer.php';
?>