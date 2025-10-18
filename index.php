<?php
// Autoload classes (simple example)
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/app/controllers/' . $class . '.php',
        __DIR__ . '/app/models/' . $class . '.php'
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Parse controller and action from URL
// First, check for API-style routes (e.g., /api/posts/create)
$requestUri = $_SERVER['REQUEST_URI'];  // Gets the full path, e.g., /Hanthane/api/posts/create
$basePath = '/Hanthane';  // Adjust if your folder name changes
$path = str_replace($basePath, '', $requestUri);  // Remove base, e.g., /api/posts/create

// Debug: Print to see what was parsed
/*echo "Request URI: " . $requestUri . "<br>";
echo "Path after base: " . $path . "<br>";
var_dump($path);  // Shows the string/array details*/

if (strpos($path, '/api/') === 0) {
    // API route: /api/{controller}/{action}
    $parts = explode('/', trim($path, '/'));  // Split into array
    if (count($parts) >= 3 && $parts[0] === 'api') {
        $controllerName = ucfirst($parts[1]);  // e.g., 'posts' -> 'Posts'
        $actionName = $parts[2];  // e.g., 'create'
    } else {
        // Invalid API route
        http_response_code(404);
        echo json_encode(['error' => 'Invalid API route']);
        exit;
    }
    
    // echo "API route detected. Controller: $controllerName, Action: $actionName<br>";
} else {
    // Fallback to your original param-based routing
    $controllerName = isset($_GET['controller']) ? $_GET['controller'] : 'Home';
    $actionName = isset($_GET['action']) ? $_GET['action'] : 'index';
    
    // echo "Fallback route. Controller: $controllerName, Action: $actionName<br>";
}

// Build controller class name (add 'Controller' suffix)
$controllerClass = $controllerName . 'Controller';

// Check if controller exists
if (class_exists($controllerClass)) {
    $controller = new $controllerClass();
    if (method_exists($controller, $actionName)) {
        // Call action
        $controller->$actionName();
    } else {
        // Action not found
        http_response_code(404);
        echo json_encode(['error' => "Action '$actionName' not found"]);
    }
} else {
    // Controller not found
    http_response_code(404);
    echo json_encode(['error' => "Controller '$controllerClass' not found"]);
}