<?php
require_once '../app/controllers/AuthController.php';

echo "<h2>Complete Auth System Test</h2>";

$authController = new AuthController();

// Test 1: Check if user is logged in (should be false)
echo "<h3>Test 1: Check Login Status</h3>";
if ($authController->isLoggedIn()) {
    echo "❌ User is logged in (should not be)<br>";
} else {
    echo "✅ User is not logged in (correct)<br>";
}

// Test 2: Registration
echo "<h3>Test 2: User Registration</h3>";
$testUser = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john' . rand(1000, 9999) . '@example.com',
    'phone' => '07123456' . rand(10, 99),
    'password' => 'password123',
    'username' => 'johndoe' . rand(1000, 9999),
    'bio' => 'Test user bio',
    'university' => 'Test University',
    'location' => 'Test City'
];

$regResult = $authController->register($testUser);
if ($regResult['success']) {
    echo "✅ Registration successful: " . $regResult['message'] . "<br>";
    
    // Test 3: Login with email
    echo "<h3>Test 3: Login with Email</h3>";
    $loginResult = $authController->login($testUser['email'], $testUser['password']);
    if ($loginResult['success']) {
        echo "✅ Login with email successful<br>";
        echo "Logged in as: " . $loginResult['user']['username'] . "<br>";
    } else {
        echo "❌ Login with email failed:<br>";
        foreach ($loginResult['errors'] as $error) {
            echo " - " . $error . "<br>";
        }
    }
    
    // Test 4: Check login status
    echo "<h3>Test 4: Check Login Status After Login</h3>";
    if ($authController->isLoggedIn()) {
        echo "✅ User is now logged in<br>";
        
        // Test 5: Get current user
        echo "<h3>Test 5: Get Current User</h3>";
        $currentUser = $authController->getCurrentUser();
        if ($currentUser) {
            echo "✅ Current user: " . $currentUser['username'] . " (" . $currentUser['email'] . ")<br>";
        } else {
            echo "❌ Could not get current user<br>";
        }
        
        // Test 6: Logout
        echo "<h3>Test 6: Logout</h3>";
        $logoutResult = $authController->logout();
        if ($logoutResult['success']) {
            echo "✅ Logout successful: " . $logoutResult['message'] . "<br>";
        }
        
        // Test 7: Check login status after logout
        echo "<h3>Test 7: Check Login Status After Logout</h3>";
        if ($authController->isLoggedIn()) {
            echo "❌ User is still logged in (should be logged out)<br>";
        } else {
            echo "✅ User is logged out (correct)<br>";
        }
        
    } else {
        echo "❌ User is not logged in after successful login<br>";
    }
    
    // Test 8: Login with phone
    echo "<h3>Test 8: Login with Phone Number</h3>";
    $loginResult = $authController->login($testUser['phone'], $testUser['password']);
    if ($loginResult['success']) {
        echo "✅ Login with phone successful<br>";
        $authController->logout(); // Clean up
    } else {
        echo "❌ Login with phone failed:<br>";
        foreach ($loginResult['errors'] as $error) {
            echo " - " . $error . "<br>";
        }
    }
    
} else {
    echo "❌ Registration failed:<br>";
    foreach ($regResult['errors'] as $error) {
        echo " - " . $error . "<br>";
    }
}

// Test 9: Search users
echo "<h3>Test 9: Search Users</h3>";
$searchResult = $authController->searchUsers('john');
if (!empty($searchResult)) {
    echo "✅ Search found " . count($searchResult) . " users<br>";
} else {
    echo "ℹ️ No users found with search term 'john'<br>";
}
?>