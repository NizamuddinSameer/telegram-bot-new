<?php
// debug.php - A temporary file to view the error log without shell access.

// Set the content type to plain text for clean output
header('Content-Type: text/plain');

// Define the path to the error log file
$logFile = __DIR__ . '/error.log';

echo "--- Reading Error Log ---\n\n";

// Check if the log file exists
if (file_exists($logFile)) {
    // Read the contents of the file
    $logContent = file_get_contents($logFile);

    // Check if the file is empty
    if (empty(trim($logContent))) {
        echo "The error.log file is empty. This is good!\n";
        echo "It might mean the error is happening before PHP can write to the log.\n";
        echo "Try sending /start to your bot again to trigger an error, then refresh this page.";
    } else {
        // Display the contents of the log file
        echo "Contents of error.log:\n";
        echo "========================\n";
        echo htmlspecialchars($logContent);
        echo "\n========================";
    }
} else {
    echo "The error.log file does not exist.\n";
    echo "This could be a file permissions issue or it hasn't been created yet.";
}

echo "\n\n--- End of Log ---";

?>
