<?php
$page_title = "Submit Feedback";
// Note: We don't require check_login() for a public feedback form.
require_once 'db_connect.php'; 

$feedback_status = '';
// Variables to retain form data on error
$name_val = '';
$email_val = '';
$comments_val = '';
$rating_val = 5;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $comments = trim($_POST['comments']);
    $rating = (int)($_POST['rating'] ?? 5);

    // Retain values for re-display
    $name_val = $name;
    $email_val = $email;
    $comments_val = $comments;
    $rating_val = $rating;

    if (empty($comments)) {
        // Updated error message markup
        $feedback_status = '<div class="alert alert-danger" role="alert"><i class="bi-exclamation-triangle-fill me-2"></i> Comments field cannot be empty.</div>';
    } else {
        // Secure INSERT using prepared statements
        $sql = "INSERT INTO feedback (name, email, rating, comments) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // 'ssis' means String, String, Integer, String
        $stmt->bind_param("ssis", $name, $email, $rating, $comments);

        if ($stmt->execute()) {
            // Updated success message markup
            $feedback_status = '<div class="alert alert-success" role="alert"><i class="bi-check-circle-fill me-2"></i> Thank you for your feedback! It has been submitted successfully.</div>';
            // Clear retained values after successful submission
            $name_val = $email_val = $comments_val = ''; 
            $rating_val = 5;
        } else {
            // Updated error message markup
            $feedback_status = '<div class="alert alert-danger" role="alert"><i class="bi-x-octagon-fill me-2"></i> Error submitting feedback: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}
// IMPORTANT: Close the connection before including the header, as the header will re-open it.
if (isset($conn)) {
    $conn->close();
}

// Since this page might be public, it won't use the staff header/footer unless 
// we assume it is included within a staff dashboard container page. 
// Assuming it's a standalone page that needs the main site's look.
// We must include a header and footer now. If this is *only* accessed by staff, 
// using '../template/header.php' and '../template/footer.php' is correct.

// Assuming the file is included from the root (index.php?page=feedback)
include 'template/header.php'; 
?>

<h1 class="mb-4 text-center text-primary">Application Feedback</h1>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <?php echo $feedback_status; ?>

        <div class="card shadow-lg border-0">
            <div class="card-header bg-dark text-white">
                <h2 class="h5 mb-0"><i class="bi-chat-left-text me-2"></i> Share Your Thoughts</h2>
            </div>
            <div class="card-body">

                <form method="POST" action="feedback.php">
                    <input type="hidden" name="submit_feedback" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Your Name (Optional):</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name_val); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Your Email (Optional):</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email_val); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="rating" class="form-label">Application Rating (1=Poor, 5=Excellent):</label>
                        <select id="rating" name="rating" class="form-select">
                            <?php
                            $ratings = [5 => 'Excellent', 4 => 'Good', 3 => 'Fair', 2 => 'Poor', 1 => 'Very Poor'];
                            foreach ($ratings as $value => $label) {
                                $selected = ($rating_val == $value) ? 'selected' : '';
                                echo "<option value='{$value}' {$selected}>{$value} - {$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="comments" class="form-label">Comments (Required):</label>
                        <textarea id="comments" name="comments" rows="5" class="form-control" required><?php echo htmlspecialchars($comments_val); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi-send me-2"></i> Submit Feedback
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php 
// We must close the connection if the header did not, but since the header re-includes db_connect, 
// we rely on the logic in header/footer to close $conn if they need to. If this file is a direct include 
// *without* a staff template structure, we would need to manually include the footer and close $conn here.

// Since the file structure implies the use of the staff templates:
// The connection was closed after processing, but the header re-opened it. 
// We rely on the footer to close it now.
include 'template/footer.php'; 
?>