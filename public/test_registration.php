<?php
require_once '../app/controllers/AuthController.php';

echo "<h2>Testing Registration</h2>";

$authController = new AuthController();

// Test data
$testData = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test' . rand(1000, 9999) . '@example.com',
    'phone' => '07' . rand(10000000, 99999999),
    'password' => 'password123',
    'username' => 'testuser' . rand(1000, 9999),
    'bio' => 'This is a test user',
    'university' => 'Test University',
    'location' => 'Test City'
];

echo "<h3>Test Data:</h3>";
echo "<pre>";
print_r($testData);
echo "</pre>";

// Test registration
$result = $authController->register($testData);

echo "<h3>Registration Result:</h3>";
if ($result['success']) {
    echo "✅ <strong>SUCCESS:</strong> " . $result['message'] . "<br>";
} else {
    echo "❌ <strong>FAILED:</strong><br>";
    foreach ($result['errors'] as $error) {
        echo " - " . $error . "<br>";
    }
}
?>