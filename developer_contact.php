<?php
$page_title = "Contact Application Developer";

// === PHPMailer Includes (Paths corrected to start from root) ===
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; 

require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/SMTP.php';
// === END PHPMailer Includes ===


// Include necessary files (Path corrected to 'config/' as it's in the root)
require_once 'config/functions.php'; 

// Ensure session is started for access control
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Optional: If you want to require staff login to view this page, uncomment the following:
/*
check_login();
// If you only want Admins to see dev contact info:
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php?error=Unauthorized");
    exit();
}
*/

// ===================================================
// === PHPMailer Submission Logic (Integrated Form Handler) ===
// ===================================================

$success_message = '';
$error_message = '';

if (isset($_POST['sendBtn'])){
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message_body = htmlspecialchars(trim($_POST['message'])); 

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // IMPORTANT: Use your actual Gmail and App Password here
        $mail->Username   = 'usman2007.ap@gmail.com'; 
        $mail->Password   = 'ojtqfkvjxusvhmjw'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom($email, $username);
        $mail->addAddress('usman2007.ap@gmail.com', 'Developer Contact'); 

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Message: $subject";
        $mail->Body = "
            <h2>New Contact Form Message</h2>
            <p><b>Name:</b> $username</p>
            <p><b>Email:</b> $email</p>
            <p><b>Subject:</b> $subject</p>
            <p><b>Message:</b><br>$message_body</p>
        ";
        
        if($mail->send()){
            $success_message = 'Message sent successfully!';
        }
    } catch (Exception $e){
        $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// === TEMPLATE INCLUDES (Paths corrected to start from root) ===
include 'template/header.php'; 
?>

<div class="container mt-5">
    <h1 class="mb-4 text-center text-primary">Contact Us</h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success text-center"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">

            <p class="lead text-muted text-center">Thank you for using our system! For technical support or inquiries regarding the software application itself, please refer to the developer information below.</p>
            
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

            <hr class="my-5">

            <h2 class="text-center display-5 fw-bolder mb-4">Send Technical Feedback</h2>
            <p class="text-center text-muted mb-4">Use this form for application-related issues (bugs, suggestions, features).</p>
            
            <div class="card shadow-sm p-4 mb-5">
                 <form action="developer_contact.php" method="post"> 
                    <input type="text" name="username" placeholder="Enter your name" required class="form-control mb-3">
                    <input type="email" name="email" placeholder="Enter your email" required class="form-control mb-3">
                    <select name="subject" class="form-control mb-3" required>
                        <option value="">Select Subject</option>
                        <option value="Bug Report">Bug Report (Urgent)</option>
                        <option value="Suggestion">Suggestion</option>
                        <option value="Feedback">General Feedback</option>
                        <option value="Other Inquiry">Other Inquiry</option>
                    </select>
                    <textarea name="message" placeholder="Enter your detailed message..." required cols="30" rows="6" class="form-control mb-3"></textarea>
                    <div class="d-grid gap-2">
                        <button type="submit" name="sendBtn" class="btn btn-dark btn-lg">Send Technical Mail</button>
                    </div>
                </form>
            </div>


            <p class="text-center mt-5">
                <i class="bi-scissors me-1 text-success"></i> Note: For Elegance Salon service appointments (booking, cancellation, pricing), please contact the salon directly.
            </p>

        </div>
    </div>
</div>

<?php 
include 'template/footer.php';
?>