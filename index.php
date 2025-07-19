<?php
// index.php - Main Bot Logic and Webhook Handler

// Include necessary files
require_once 'config.php';
require_once 'functions.php';

// Get the input from Telegram
$update = json_decode(file_get_contents('php://input'), true);

// --- Error Logging ---
if (!$update) {
    error_log("Failed to decode JSON input from Telegram.");
    exit();
}
error_log(print_r($update, true)); // Log every update for debugging

// --- Process Update ---
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $text = $message['text'];
    $first_name = $message['from']['first_name'];
    
    // Check for referral code in /start command
    if (strpos($text, '/start') === 0) {
        $parts = explode(' ', $text);
        if (count($parts) > 1) {
            $referrer_id = $parts[1];
            // Pass the first name of the new user to the referral handler
            handleReferral($user_id, $referrer_id, $first_name);
        }
    }

} elseif (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];
    $chat_id = $callback_query['message']['chat']['id'];
    $user_id = $callback_query['from']['id'];
    $data = $callback_query['data'];
    $message_id = $callback_query['message']['message_id'];
    $first_name = $callback_query['from']['first_name'];
    $text = $data; // Treat callback data as text command for simplicity
    
    // Answer callback query to remove the "loading" state on the button
    answerCallbackQuery($callback_query['id']);
} else {
    // Ignore other update types
    exit();
}

// --- User Management ---
$user = getUser($user_id);
if (!$user) {
    // If the user was referred, the referral handler would have already created the user.
    // This check ensures we don't overwrite it.
    if (!getUser($user_id)) {
        $user = initializeUser($user_id, $first_name);
    } else {
        $user = getUser($user_id);
    }
}

// --- Bot Logic based on Flowchart ---

// 1. Onboarding Flow
if ($user['state'] === 'new') {
    if ($text === '/start') {
        $welcome_text = "✨ Earn real money daily\n⚖️ Unlock premium tasks\n✨ Spin & win daily bonuses\n💼 Withdraw easily to Paytm/UPI/PayPal\n\n❓ *Where are you from?*";
        $keyboard = getCountrySelectorKeyboard();
        sendMessage($chat_id, $welcome_text, $keyboard);
        $user['state'] = 'selecting_country';
        saveUser($user);
    }
} elseif ($user['state'] === 'selecting_country') {
    if (in_array($text, ['country_in', 'country_us', 'country_other'])) {
        $country = str_replace('country_', '', $text);
        $user['country'] = $country;
        $user['state'] = 'subscription_check';
        saveUser($user);
        
        $sub_text = "Great! To get started, please subscribe to our channels. This helps us bring you more offers!";
        $keyboard = getSubscriptionKeyboard();
        sendMessage($chat_id, $sub_text, $keyboard);
    } else {
        // Re-prompt if they don't select a country
        $welcome_text = "Please select your country to continue.";
        $keyboard = getCountrySelectorKeyboard();
        sendMessage($chat_id, $welcome_text, $keyboard);
    }
} elseif ($user['state'] === 'subscription_check') {
    if ($text === 'verify_subscription') {
        // --- Subscription Verification Logic ---
        // For this example, we assume verification is successful.
        // In a real bot, you would use `getChatMember` to check each channel.
        $is_subscribed = true; // Placeholder
        
        if ($is_subscribed) {
            $user['state'] = 'main_dashboard';
            $user['is_verified'] = true;
            saveUser($user);
            sendDashboard($chat_id, $user_id);
        } else {
            $sub_text = "❌ You haven't subscribed to all channels yet. Please subscribe and try again.";
            $keyboard = getSubscriptionKeyboard();
            sendMessage($chat_id, $sub_text, $keyboard);
        }
    }
} else {
    // --- Main Bot Logic for Verified Users ---
    switch ($text) {
        case '/start':
        case 'back_to_main':
            sendDashboard($chat_id, $user_id);
            break;

        case 'spin_win':
            handleSpin($chat_id, $user_id, $update['callback_query']['id']);
            break;

        case 'app_installs':
            sendTaskMenu($chat_id, $user_id, 'app_installs');
            break;
            
        case 'surveys':
            sendTaskMenu($chat_id, $user_id, 'surveys');
            break;

        case 'refer_earn':
            sendReferralInfo($chat_id, $user_id);
            break;

        case 'premium_unlock':
            sendPremiumInfo($chat_id, $user_id);
            break;
            
        case 'wallet':
            sendWalletInfo($chat_id, $user_id);
            break;

        default:
            // Handle cases where user might be in a sub-menu or sends random text
            sendMessage($chat_id, "🤔 Unknown command. Please use the buttons below.");
            sendDashboard($chat_id, $user_id);
            break;
    }
}

?>
