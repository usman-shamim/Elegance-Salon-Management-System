<?php
$page_title = "Contact Application Developer";
// Include necessary files and enforce access control (if this page requires login, otherwise omit)
require_once '../config/functions.php';
// Note: db_connect is generally not needed for a static contact page, but we'll include the header/footer.

// Optional: If you want to require staff login to view this page, uncomment the following:
/*
check_login();
// If you only want Admins to see dev contact info:
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard.php?error=Unauthorized");
    exit();
}
*/

include '../template/header.php'; 
?>

<h1 class="mb-4 text-center text-primary">🤝 Application Developer Contact</h1>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">

        <p class="lead text-muted text-center">Thank you for using our system! For **technical support** or inquiries regarding the software application itself, please refer to the developer information below.</p>
        
        <div class="card shadow-lg mb-5 border-0">
            <div class="card-header bg-dark text-white text-center py-3">
                <h2 class="h5 mb-0"><i class="bi-code-slash me-2"></i> Technical Solutions Dev</h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <p class="fw-bold mb-1"><i class="bi-building me-2 text-primary"></i> Organization:</p>
                        <p class="ms-4">Tech Solutions Dev</p>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-bold mb-1"><i class="bi-envelope me-2 text-primary"></i> Email:</p>
                        <p class="ms-4"><a href="mailto:support@techsolutions.dev">support@techsolutions.dev</a></p>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-bold mb-1"><i class="bi-phone me-2 text-primary"></i> Contact Number:</p>
                        <p class="ms-4"><a href="tel:+15559012345">+1 (555) 901-2345</a></p>
                    </div>
                    <div class="col-12">
                        <p class="fw-bold mb-1"><i class="bi-geo-alt me-2 text-primary"></i> Address:</p>
                        <p class="ms-4">101 Innovation Blvd, Suite 200, Dev City, CA 90001</p>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-5">
            <i class="bi-scissors me-1 text-success"></i> **For Elegance Salon service appointments (booking, cancellation, pricing), please contact the salon directly.**
        </p>

    </div>
</div>

<?php 
// $conn is not needed here, but ensure you clean up if you included db_connect.php
// if (isset($conn)) { $conn->close(); } 
include '../template/footer.php';
?>