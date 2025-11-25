<?php
// template/footer.php

// 1. Setup variables
// Base path is required for scripts. Assumes this file is in 'template/'.
$base_path = '../'; 
// Retrieve session role safely for the footer display
$user_role = $_SESSION['role'] ?? 'Guest';

// NOTE: If your main script did not close the connection ($conn) 
// before including this footer, you should close it here:
/*
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
*/
?>

            </div> </div> </section> <footer class="bg-light py-5">
        <div class="container px-4 px-lg-5">
            <div class="small text-center text-muted">
                Copyright &copy; <?php echo date("Y"); ?> - Elegance Salon Management 
                <?php if ($user_role !== 'Guest'): ?>
                    | Logged in as: <?php echo htmlspecialchars(ucfirst($user_role)); ?>
                <?php endif; ?>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.1.0/simpleLightbox.min.js"></script>
    
    <script src="<?php echo $base_path; ?>assets/js/scripts.js"></script>
    
    </body>
</html>