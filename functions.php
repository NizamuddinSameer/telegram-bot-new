<?php
// functions.php - Reusable Bot Functions

/**
 * Sends a message to a specific chat.
 * @param int $chat_id The chat ID.
 * @param string $text The message text.
 * @param array|null $keyboard The inline keyboard markup.
 */
function sendMessage($chat_id, $text, $keyboard = null) {
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true,
    ];
    if ($keyboard) {
        $params['reply_markup'] = json_encode($keyboard);
    }
    apiRequest('sendMessage', $params);
}

/**
 * Answers a callback query.
 * @param string $callback_query_id The callback query ID.
 * @param string|null $text The text to show as a notification.
 */
function answerCallbackQuery($callback_query_id, $text = null) {
    $params = ['callback_query_id' => $callback_query_id];
    if ($text) {
        $params['text'] = $text;
        $params['show_alert'] = true;
    }
    apiRequest('answerCallbackQuery', $params);
}

/**
 * Makes a request to the Telegram Bot API.
 * @param string $method The API method to call.
 * @param array $data The data to send with the request.
 * @return mixed The API response.
 */
function apiRequest($method, $data = []) {
    $url = API_URL . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        error_log(curl_error($ch));
    }
    curl_close($ch);
    return json_decode($res, true);
}

// --- User Data Functions ---

/**
 * Initializes a new user profile.
 * @param int $user_id The user's Telegram ID.
 * @param string $first_name The user's first name.
 * @return array The new user object.
 */
function initializeUser($user_id, $first_name) {
    $newUser = [
        'id' => $user_id,
        'first_name' => $first_name,
        'state' => 'new',
        'country' => null,
        'balance' => 0.00,
        'is_premium' => false,
        'premium_expiry' => null,
        'referrals' => 0,
        'referred_by' => null,
        'last_spin_date' => null,
        'is_verified' => false,
    ];
    saveUser($newUser);
    return $newUser;
}

/**
 * Retrieves a user's data from the JSON file.
 * @param int $user_id The user's Telegram ID.
 * @return array|null The user's data or null if not found.
 */
function getUser($user_id) {
    $users = loadJson(USERS_FILE);
    return $users[$user_id] ?? null;
}

/**
 * Saves a user's data to the JSON file.
 * @param array $userData The user's data.
 */
function saveUser($userData) {
    $users = loadJson(USERS_FILE);
    $users[$userData['id']] = $userData;
    saveJson(USERS_FILE, $users);
}

// --- JSON Helper Functions ---

/**
 * Loads data from a JSON file.
 * @param string $filename The file to load.
 * @return array The decoded JSON data.
 */
function loadJson($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    $json = file_get_contents($filename);
    return json_decode($json, true) ?? [];
}

/**
 * Saves data to a JSON file.
 * @param string $filename The file to save to.
 * @param array $data The data to encode and save.
 */
function saveJson($filename, $data) {
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// --- Keyboard Layouts ---

function getCountrySelectorKeyboard() {
    return [
        'inline_keyboard' => [
            [['text' => '🇮🇳 India', 'callback_data' => 'country_in']],
            [['text' => '🇺🇸 USA', 'callback_data' => 'country_us']],
            [['text' => '🌎 Other', 'callback_data' => 'country_other']],
        ]
    ];
}

function getSubscriptionKeyboard() {
    $tasks = loadJson(TASKS_FILE);
    $sub_tasks = $tasks['subscriptions']; 
    
    $keyboard_buttons = [];
    foreach ($sub_tasks as $task) {
        $keyboard_buttons[] = [['text' => $task['text'], 'url' => $task['link']]];
    }
    
    $keyboard_buttons[] = [['text' => '✅ I Subscribed All', 'callback_data' => 'verify_subscription']];
    
    return ['inline_keyboard' => $keyboard_buttons];
}

function getDashboardKeyboard() {
    return [
        'inline_keyboard' => [
            [['text' => '🎲 Spin Daily to Win', 'callback_data' => 'spin_win']],
            [['text' => '📱 App Install Offers', 'callback_data' => 'app_installs']],
            [['text' => '📍 Survey Completion Tasks', 'callback_data' => 'surveys']],
            [['text' => '👥 Refer & Earn', 'callback_data' => 'refer_earn']],
            [['text' => '💸 Premium Unlock', 'callback_data' => 'premium_unlock']],
            [['text' => '💳 Wallet & Withdraw', 'callback_data' => 'wallet']],
        ]
    ];
}

function getBackKeyboard() {
    return ['inline_keyboard' => [[['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_to_main']]]];
}

// --- Feature Handlers ---

function sendDashboard($chat_id, $user_id) {
    $user = getUser($user_id);
    $balance = number_format($user['balance'], 2);
    $referrals = $user['referrals'];
    $text = "📊 *Main Dashboard*\n\nWelcome back, {$user['first_name']}!\n\n💰 *Balance:* ₹{$balance}\n👥 *Referrals:* {$referrals}\n\nWhat would you like to do today?";
    $keyboard = getDashboardKeyboard();
    sendMessage($chat_id, $text, $keyboard);
}

// UPDATED to correctly handle new user creation
function handleReferral($user_id, $referrer_id, $first_name) {
    $user = getUser($user_id);
    
    // Prevent user from referring themselves or being referred twice
    if ($user_id == $referrer_id || ($user && $user['referred_by'] !== null)) {
        return;
    }

    // Initialize the new user if they don't exist
    if (!$user) {
        $user = initializeUser($user_id, $first_name);
    }

    $referrer = getUser($referrer_id);
    if ($referrer) {
        $user['referred_by'] = $referrer_id;
        $referrer['referrals'] += 1;
        $referrer['balance'] += REFERRAL_BONUS;

        // Check for milestone bonus
        if ($referrer['referrals'] > 0 && $referrer['referrals'] % REFERRAL_MILESTONE_COUNT === 0) {
            $referrer['balance'] += REFERRAL_MILESTONE_BONUS;
            sendMessage($referrer_id, "🎉 *Milestone Bonus!* You've reached {$referrer['referrals']} referrals and earned an extra ₹" . REFERRAL_MILESTONE_BONUS . "!");
        }

        saveUser($user);
        saveUser($referrer);
        sendMessage($referrer_id, "✅ You have a new referral! You've earned ₹" . REFERRAL_BONUS . ".");
    }
}

// UPDATED to accept callback_query_id
function handleSpin($chat_id, $user_id, $callback_query_id) {
    $user = getUser($user_id);
    $today = date('Y-m-d');

    if ($user['last_spin_date'] === $today) {
        answerCallbackQuery($callback_query_id, 'You have already used your free spin for today. Come back tomorrow!');
        return;
    }
    
    $tasks = loadJson(TASKS_FILE);
    $rewards = $tasks['spin_rewards'];
    $reward = $rewards[array_rand($rewards)];
    
    $user['last_spin_date'] = $today;
    $message = "🎉 You spun the wheel and won: *{$reward['text']}*!";

    switch ($reward['type']) {
        case 'money':
            $user['balance'] += $reward['value'];
            $message .= "\n₹{$reward['value']} has been added to your wallet.";
            break;
        case 'premium_access':
            $user['is_premium'] = true;
            $user['premium_expiry'] = date('Y-m-d', strtotime("+{$reward['value']} day"));
            $message .= "\nYou now have Premium access for {$reward['value']} day!";
            break;
    }
    
    saveUser($user);
    sendMessage($chat_id, $message, getBackKeyboard());
}

function sendTaskMenu($chat_id, $user_id, $task_type) {
    $user = getUser($user_id);
    $tasks_data = loadJson(TASKS_FILE);
    $country = $user['country'] ?? 'other';
    $tasks = $tasks_data[$task_type]['tasks'];
    
    $text = "Here are the available *{$tasks_data[$task_type]['title']}*:\n\n";
    $keyboard_buttons = [];
    
    $is_premium = $user['is_premium'] || ($user['referrals'] >= 3); // Unlock condition from flowchart

    foreach ($tasks as $task) {
        if (isset($task['countries']) && !in_array($country, $task['countries'])) {
            continue;
        }

        if ($task['is_locked'] && !$is_premium) {
            $keyboard_buttons[] = [['text' => "🔒 {$task['text']}", 'callback_data' => 'premium_unlock']];
        } else {
            $keyboard_buttons[] = [['text' => "✅ {$task['text']}", 'url' => $task['link']]];
        }
    }
    
    if (empty($keyboard_buttons)) {
        $text = "Sorry, no tasks available for your region right now. Please check back later!";
    }

    $keyboard_buttons[] = [['text' => '⬅️ Back', 'callback_data' => 'back_to_main']];
    $keyboard = ['inline_keyboard' => $keyboard_buttons];
    sendMessage($chat_id, $text, $keyboard);
}

function sendReferralInfo($chat_id, $user_id) {
    $user = getUser($user_id);
    $ref_link = "https://t.me/" . BOT_USERNAME . "?start={$user_id}";
    $text = "👥 *Refer & Earn*\n\n";
    $text .= "Invite your friends and earn *₹" . REFERRAL_BONUS . "* for every friend who joins!\n\n";
    $text .= "Reach *" . REFERRAL_MILESTONE_COUNT . " referrals* for a milestone bonus of *₹" . REFERRAL_MILESTONE_BONUS . "!*\n\n";
    $text .= "Your personal referral link:\n`{$ref_link}`\n\n";
    $text .= "You currently have *{$user['referrals']}* referrals.";
    
    sendMessage($chat_id, $text, getBackKeyboard());
}

function sendPremiumInfo($chat_id, $user_id) {
    $user = getUser($user_id);
    $tasks = loadJson(TASKS_FILE);
    $premium_info = $tasks['premium'];

    $text = "💸 *Unlock Premium Access*\n\n";
    $text .= "Upgrade to Premium to unlock all high-paying tasks, get 2x daily spins, and more!\n\n";
    $text .= "*How to Unlock:*\n";
    $text .= "1️⃣ Refer *" . PREMIUM_UNLOCK_REFERRALS . " friends* (You have {$user['referrals']}).\n";
    $text .= "2️⃣ Purchase for *{$premium_info['price']}*.\n\n";
    
    if ($user['is_premium']) {
       $text = "✅ You are already a Premium member!";
       if($user['premium_expiry']) {
           $text .= "\nYour access expires on: {$user['premium_expiry']}";
       }
    }
    
    $keyboard_buttons = [];
    if (!$user['is_premium']) {
        $keyboard_buttons[] = [['text' => $premium_info['button_text'], 'url' => $premium_info['payment_link']]];
    }
    $keyboard_buttons[] = [['text' => '⬅️ Back', 'callback_data' => 'back_to_main']];
    $keyboard = ['inline_keyboard' => $keyboard_buttons];

    sendMessage($chat_id, $text, $keyboard);
}

function sendWalletInfo($chat_id, $user_id) {
    $user = getUser($user_id);
    $balance = number_format($user['balance'], 2);
    $text = "💳 *My Wallet*\n\n";
    $text .= "Total Earned: *₹{$balance}*\n";
    $text .= "Pending Approval: *₹0.00*\n";
    $text .= "Referral Bonuses: *₹" . number_format($user['referrals'] * REFERRAL_BONUS, 2) . "*\n\n";
    $text .= "Minimum withdrawal amount is *₹" . MIN_WITHDRAWAL_AMOUNT . "*.\n\n";

    $keyboard_buttons = [];
    if ($user['balance'] >= MIN_WITHDRAWAL_AMOUNT) {
        $text .= "You can request a withdrawal now!";
        $keyboard_buttons[] = [['text' => '💸 Withdraw Now', 'callback_data' => 'withdraw_start']];
    } else {
        $needed = MIN_WITHDRAWAL_AMOUNT - $user['balance'];
        $text .= "You need *₹" . number_format($needed, 2) . "* more to withdraw.";
    }

    $keyboard_buttons[] = [['text' => '⬅️ Back', 'callback_data' => 'back_to_main']];
    $keyboard = ['inline_keyboard' => $keyboard_buttons];
    sendMessage($chat_id, $text, $keyboard);
}

?>
