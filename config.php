<?php
// config.php - Environment Configuration

// --- Telegram Bot Settings ---
// Load from Environment Variables for security and flexibility
// The '?:' provides a fallback value if the environment variable is not set (useful for local testing)
define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '7838096541:AAHvAQfuf9oTwlSSguNtqtMBKkJno0sFXAQ');
define('BOT_USERNAME', getenv('BOT_USERNAME') ?: 'EarnVerseBot');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// --- File Paths ---
// Defines the names for your data and log files.
define('USERS_FILE', 'users.json');
define('TASKS_FILE', 'tasks.json');
define('ERROR_LOG_FILE', 'error.log');

// --- Bot Logic & Rewards ---
// These values are based on your flowchart and can be adjusted here.
define('REFERRAL_BONUS', 5.00); // Amount earned per referral
define('REFERRAL_MILESTONE_COUNT', 10); // Number of referrals for a bonus
define('REFERRAL_MILESTONE_BONUS', 50.00); // Bonus amount
define('PREMIUM_UNLOCK_REFERRALS', 5); // Referrals needed to unlock premium for free
define('MIN_WITHDRAWAL_AMOUNT', 50.00); // Minimum balance to request a withdrawal


// --- Error Reporting ---
// This setup ensures that any PHP errors are written to your error.log file
// and are not displayed to the user.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_FILE);

?>
