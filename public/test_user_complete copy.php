<?php
require_once '../config/config.php';
require_once '../app/core/Database.php';
require_once '../app/models/User.php';
require_once '../app/controllers/AuthController.php';

echo "<h3>User Model Test (With Phone Number Support)</h3>";

try {
    $userModel = new User();
    
    // Test 1: Registration with phone number
    echo "<h4>1. Testing Registration with Phone Number</h4>";
    $randomSuffix = rand(1000, 9999);
    $testUser = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test' . $randomSuffix . '@example.com',
        'phone_number' => '07' . rand(10000000, 99999999), // Random phone number
        'password' => 'password123',
        'username' => 'testuser' . $randomSuffix,
        'bio' => 'Test user with phone',
        'university' => 'Test University'
    ];
    
    if ($userModel->register($testUser)) {
        echo "✅ User registration with phone successful!<br>";
        echo "📧 Email: " . $testUser['email'] . "<br>";
        echo "📞 Phone: " . $testUser['phone_number'] . "<br>";
        
        // Test 2: Find by email
        echo "<h4>2. Testing Find by Email</h4>";
        $userByEmail = $userModel->findByEmail($testUser['email']);
        if ($userByEmail) {
            echo "✅ User found by email: " . $userByEmail['username'] . "<br>";
            echo "📞 Phone in DB: " . ($userByEmail['phone_number'] ?? 'Not set') . "<br>";
            
            $user_id = $userByEmail['user_id'];
        } else {
            echo "❌ User not found by email<br>";
        }
        
        // Test 3: Find by phone number
        echo "<h4>3. Testing Find by Phone Number</h4>";
        $userByPhone = $userModel->findByPhone($testUser['phone_number']);
        if ($userByPhone) {
            echo "✅ User found by phone: " . $userByPhone['username'] . "<br>";
            echo "📧 Email: " . $userByPhone['email'] . "<br>";
        } else {
            echo "❌ User not found by phone<br>";
        }
        
        // Test 4: Profile update with phone
        echo "<h4>4. Testing Profile Update with Phone</h4>";
        $updateData = [
            'first_name' => 'Updated First',
            'last_name' => 'Updated Last', 
            'username' => $userByEmail['username'],
            'email' => $userByEmail['email'],
            'phone_number' => '07123456789', // Update phone number
            'bio' => 'Updated bio with new phone'
        ];
        
        if ($userModel->updateProfile($userByEmail['user_id'], $updateData)) {
            echo "✅ Profile update with phone successful!<br>";
            
            // Verify the update
            $updatedUser = $userModel->findById($userByEmail['user_id']);
            echo "📞 Updated phone: " . ($updatedUser['phone_number'] ?? 'Not set') . "<br>";
        } else {
            echo "❌ Profile update failed<br>";
        }
        
        // Test 5: Check phone uniqueness - FIXED
        echo "<h4>5. Testing Phone Uniqueness</h4>";
        $testPhone = '07123456789'; // Use the updated phone number
        if ($userModel->phoneExists($testPhone)) {
            echo "✅ Phone exists check working - Phone '{$testPhone}' exists<br>";
        } else {
            echo "❌ Phone exists check failed - Phone '{$testPhone}' not found<br>";
        }
        
        // Test 6: Check phone uniqueness with exclude - FIXED
        if ($userModel->phoneExists($testPhone, $user_id)) {
            echo "❌ Phone uniqueness with exclude failed - Should return false when excluding current user<br>";
        } else {
            echo "✅ Phone uniqueness with exclude working - Correctly returns false when excluding current user<br>";
        }
        
    } else {
        echo "❌ User registration failed!<br>";
    }
    
    // Test 7: Test duplicate phone registration - FIXED
    echo "<h4>6. Testing Duplicate Phone Registration</h4>";
    $duplicateUser = [
        'first_name' => 'Duplicate',
        'last_name' => 'User', 
        'email' => 'duplicate' . $randomSuffix . '@example.com',
        'phone_number' => '07123456789', // Use the phone that now exists
        'password' => 'password123',
        'username' => 'duplicate' . $randomSuffix
    ];
    
    // This should fail due to duplicate phone
    try {
        $result = $userModel->register($duplicateUser);
        if (!$result) {
            echo "✅ Duplicate phone registration correctly blocked<br>";
        } else {
            echo "❌ Duplicate phone registration should have failed but didn't<br>";
        }
    } catch (Exception $e) {
        echo "✅ Duplicate phone registration correctly blocked with error: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test AuthController with phone - FIXED (using public method)
echo "<h3>AuthController Test</h3>";
try {
    $authController = new AuthController();
    
    // Test registration via the public register method
    echo "<h4>Testing Registration via AuthController</h4>";
    
    // First, let's test a successful registration
    $_POST = [
        'first_name' => 'AuthTest',
        'last_name' => 'User',
        'email' => 'authtest' . rand(1000,9999) . '@example.com',
        'phone' => '07' . rand(10000000, 99999999),
        'password' => 'password123',
        'confirmPassword' => 'password123',
        'username' => 'authtest' . rand(1000,9999)
    ];
    
    $result = $authController->register();
    if ($result === true) {
        echo "✅ AuthController registration successful!<br>";
    } else if (is_array($result)) {
        echo "❌ AuthController registration failed with errors: " . implode(', ', $result) . "<br>";
    }
    
    // Test login with phone number
    echo "<h4>Testing Login with Phone Number</h4>";
    
    // Create a test user first
    $testPhone = '07123456000';
    $testPassword = 'testpass123';
    
    $testUserData = [
        'first_name' => 'Login',
        'last_name' => 'Test',
        'email' => 'logintest@example.com',
        'phone_number' => $testPhone,
        'password' => $testPassword,
        'username' => 'logintestuser'
    ];
    
    if ($userModel->register($testUserData)) {
        // Test login with phone
        if ($authController->login($testPhone, $testPassword)) {
            echo "✅ Login with phone number successful!<br>";
            
            // Test get current user
            $currentUser = $authController->getCurrentUser();
            if ($currentUser) {
                echo "✅ Current user retrieved: " . $currentUser['username'] . "<br>";
            }
            
            // Logout
            $authController->logout();
            echo "✅ Logout successful<br>";
        } else {
            echo "❌ Login with phone number failed<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ AuthController Error: " . $e->getMessage() . "<br>";
}

// Additional debug: Check the actual phoneExists method
echo "<h3>Debug: Phone Exists Method</h3>";
try {
    $userModel = new User();
    
    // Test with a known phone
    $testPhone = '07123456789';
    $exists = $userModel->phoneExists($testPhone);
    echo "Phone '{$testPhone}' exists: " . ($exists ? 'YES' : 'NO') . "<br>";
    
    // Test with non-existent phone
    $fakePhone = '07999999999';
    $exists = $userModel->phoneExists($fakePhone);
    echo "Phone '{$fakePhone}' exists: " . ($exists ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "Debug Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Test Complete</h3>";
?>