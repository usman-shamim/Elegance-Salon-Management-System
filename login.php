<?php
// index.php (Login Page)

// Start session to store user data
session_start();

// If the user is already logged in, redirect them to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Prepare the SELECT statement (securely prevents SQL injection)
    // NOTE: The 'is_active = TRUE' check is a good security measure.
    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE username = ? AND is_active = TRUE");
    $stmt->bind_param("s", $username); // 's' means the parameter is a string

    // 2. Execute the statement
    if ($stmt->execute()) {
        // 3. Get the result
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // 4. Fetch the data
            $user = $result->fetch_assoc();
            
            // 5. Verify the password hash
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, store session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $username;
                
                // Redirect to the dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Database error. Please try again later.";
    }
    
    $stmt->close();
}

// Note: Ensure $conn is closed only after all operations, including any error handling.
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Elegance Salon Staff Login</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    
    <style>
        /* Use a light gray background like the Creative Theme, but center content */
        body { 
            background-color: #f4f6f9; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        /* Define the primary color to match the Creative theme's aesthetic */
        .btn-theme {
            background-color: #f4623a; /* Creative Theme's orange */
            border-color: #f4623a;
            color: white;
        }
        .btn-theme:hover {
            background-color: #e05d38; 
            border-color: #e05d38;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header bg-success text-white">
                        <h3 class="text-center font-weight-light my-2">
                            <i class="bi-scissors me-2"></i> Elegance Salon Staff Login
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger text-center" role="alert">
                                <i class="bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-person"></i></span>
                                    <input type="text" id="username" name="username" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-lock"></i></span>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-theme btn-lg">
                                    <i class="bi-box-arrow-in-right me-2"></i> Log In
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <div class="small text-muted">&copy; <?php echo date("Y"); ?> Salon Management System</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>