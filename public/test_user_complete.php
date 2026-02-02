
<?php
require_once '../config/config.php';
require_once '../app/core/Database.php';
require_once '../app/models/User.php';
require_once '../app/controllers/AuthController.php';
// Remove AuthController from test for now

echo "<h3>User Model Test (No Redirects)</h3>";

try {
    $userModel = new User();
    
    // Test 1: Registration
    echo "<h4>1. Testing Registration</h4>";
    $testUser = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test' . rand(1000,9999) . '@example.com', // Random email
        'password' => 'password123',
        'username' => 'testuser' . rand(1000,9999), // Random username
        'bio' => 'Test user',
        'university' => 'Test University'
    ];
    
    if ($userModel->register($testUser)) {
        echo "✅ User registration successful!<br>";
        
        // Test 2: Find by email
        echo "<h4>2. Testing Find by Email</h4>";
        $user = $userModel->findByEmail($testUser['email']);
        if ($user) {
            echo "✅ User found: " . $user['username'] . "<br>";
            
            // Test 3: Profile update
            echo "<h4>3. Testing Profile Update</h4>";
            $updateData = [
                'first_name' => 'Updated First',
                'last_name' => 'Updated Last', 
                'username' => $user['username'], // Keep same username
                'bio' => 'Updated bio'
            ];
            
            if ($userModel->updateProfile($user['user_id'], $updateData)) {
                echo "✅ Profile update successful!<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>