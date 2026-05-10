<?php

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Testing Pusher Configuration...\n\n";

$appId = $_ENV['PUSHER_APP_ID'] ?? null;
$key = $_ENV['PUSHER_APP_KEY'] ?? null;
$secret = $_ENV['PUSHER_APP_SECRET'] ?? null;
$cluster = $_ENV['PUSHER_APP_CLUSTER'] ?? 'eu';

echo "App ID: " . ($appId ?: 'NOT SET') . "\n";
echo "Key: " . ($key ?: 'NOT SET') . "\n";
echo "Secret: " . ($secret ? str_repeat('*', strlen($secret)) : 'NOT SET') . "\n";
echo "Cluster: " . $cluster . "\n\n";

if (!$appId || !$key || !$secret) {
    echo "❌ ERROR: Pusher credentials not configured in .env\n";
    exit(1);
}

try {
    echo "Initializing Pusher...\n";
    $pusher = new Pusher\Pusher($key, $secret, $appId, [
        'cluster' => $cluster,
        'useTLS' => true
    ]);
    
    echo "✅ Pusher initialized successfully\n\n";
    
    echo "Sending test notification...\n";
    $result = $pusher->trigger('admin-channel', 'test-event', [
        'message' => 'Test notification from PHP',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    echo "✅ Test notification sent successfully!\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "✅ SUCCESS: Pusher is working correctly!\n";
    echo "\nNext steps:\n";
    echo "1. Open admin dashboard in browser: http://localhost:8000/admin\n";
    echo "2. Open browser console (F12)\n";
    echo "3. You should see Pusher connection logs\n";
    echo "4. Create a reservation from front-end\n";
    echo "5. Notification should appear in admin dashboard\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPossible issues:\n";
    echo "- Wrong credentials in .env\n";
    echo "- Wrong cluster (you have: $cluster)\n";
    echo "- Network/firewall blocking Pusher\n";
    echo "- Pusher app not active in dashboard\n";
    exit(1);
}
