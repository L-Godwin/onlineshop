<?php
// Database connection settings.
// Values are read from Azure App Service "Environment variables" (App settings) if set,
// otherwise they fall back to the workshop defaults below.
$serverName = getenv('DB_SERVER') ?: "tcp:st20299012-wk5.database.windows.net,1433";
$connectionOptions = array(
    "Database" => getenv('DB_NAME') ?: "myDatabase",
    "Uid"      => getenv('DB_USER') ?: "SystemAdmin",
    "PWD"      => getenv('DB_PASSWORD') ?: "1nV3nTi0n!",
    "Encrypt"  => 1,
    "TrustServerCertificate" => 0
);

// Helper: send the user back to the form with an error message
function redirect_with_error($message)
{
    header("Location: register.php?error=" . urlencode($message));
    exit();
}

// Only accept form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if ($name === '' || $email === '' || $password === '') {
    redirect_with_error("Please fill all fields");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_error("Please enter a valid email address");
}

// Connect to database
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    $conn_error_message = "Database connection failed: ";
    $connection_errors = sqlsrv_errors();
    if ($connection_errors !== null) {
        foreach ($connection_errors as $error) {
            $conn_error_message .= $error['message'] . " ";
        }
    }
    redirect_with_error($conn_error_message);
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into database using a parameterised query
$sql = "INSERT INTO shopusers (name, email, password) VALUES (?, ?, ?)";
$params = array($name, $email, $hashed_password);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    // Build detailed error information
    $error_message = "Database error: ";
    $errors = sqlsrv_errors();
    if ($errors !== null) {
        foreach ($errors as $error) {
            $error_message .= "SQLSTATE: " . $error['SQLSTATE'] . ", ";
            $error_message .= "Code: " . $error['code'] . ", ";
            $error_message .= "Message: " . $error['message'] . " ";
        }
    }
    sqlsrv_close($conn);
    redirect_with_error($error_message);
}

// Clean up and go to the success page
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

header("Location: success.php");
exit();
