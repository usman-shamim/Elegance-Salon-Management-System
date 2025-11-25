<?php
// db_connect.php - Configuration and Database Connection

// Define connection constants
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // WARNING: CHANGE THIS IN A PRODUCTION ENVIRONMENT
define('DB_PASSWORD', '');    // WARNING: CHANGE THIS!
define('DB_NAME', 'salon_management'); 

// Initialize the connection variable
$conn = null;

try {
    // Attempt to create connection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection failure 
    if ($conn->connect_error) {
        // Log the detailed error internally (for admin review)
        error_log("Database Connection Failed: " . $conn->connect_error);
        
        // Use a generic, non-informative message for the user (Security Best Practice)
        die("<h1>Application Error</h1><p>We are currently experiencing technical difficulties. Please try again shortly.</p>");
    }

    // Set character set for proper data handling
    // utf8mb4 supports the full range of Unicode, including emojis.
    $conn->set_charset("utf8mb4"); 

} catch (Exception $e) {
    // Handle general errors during connection setup
    error_log("General Connection Setup Error: " . $e->getMessage());
    die("<h1>Application Error</h1><p>A fatal error occurred during setup.</p>");
}

// $conn is now available for use in other files.

/*
// Original code block that was causing issues due to output after the closing ?> tag:

//db_connect.php

// Define connection constants
//define('DB_SERVER', 'localhost');
//define('DB_USERNAME', 'root'); // WARNING: CHANGE THIS IN A PRODUCTION ENVIRONMENT
//define('DB_PASSWORD', '');    // WARNING: CHANGE THIS!
//define('DB_NAME', 'salon_management'); 

// Initialize the connection variable
//$conn = null;

//try {
    // Attempt to create connection
    //$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection failure (mysqli constructor doesn't throw exceptions by default, 
    // so we check the connect_error property immediately)
    //if ($conn->connect_error) {
        // Log the detailed error internally and display a generic message to the user
        //error_log("Database Connection Failed: " . $conn->connect_error);
        
        // Use a generic, non-informative message for the user
        //die("<h1>Application Error</h1><p>We are currently experiencing technical difficulties. Please try again shortly.</p>");
    //}

    // Set character set for proper data handling
    //$conn->set_charset("utf8mb4"); // Using utf8mb4 is safer for modern emojis and full Unicode support

//} catch (Exception $e) {
    // This catch block would primarily handle non-database-specific fatal errors during connection setup
    //error_log("General Connection Setup Error: " . $e->getMessage());
    //die("<h1>Application Error</h1><p>A fatal error occurred during setup.</p>");
//}
*/
// We omit the closing tag to prevent accidental whitespace output.?> 