<?php
// router.php
// This script allows the PHP built-in server to handle extensionless URLs (e.g., /login instead of /login.php)
// imitating Apache's mod_rewrite behavior.

$root = __DIR__;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. Default to index.php if root is requested
if ($uri === '/' || $uri === '') {
    if (file_exists($root . '/index.php')) {
        include $root . '/index.php';
        return;
    }
}

// 2. Serve existing files directly (images, css, js, etc.)
// If the file exists, return false to let PHP serve it natively.
if (file_exists($root . $uri) && !is_dir($root . $uri)) {
    return false;
}

// 3. Handle Clean URLs (Append .php locally)
// Example: Request "/public/login" -> Serve "/public/login.php"
$cleanUrlFile = $root . $uri . '.php';
if (file_exists($cleanUrlFile)) {
    // Serve the PHP file
    chdir(dirname($cleanUrlFile)); // Fix: Update CWD so relative includes (like ../app/config/db.php) work
    include $cleanUrlFile;
    return;
}

// 4. Handle Directory Index (if request is /public/, serve /public/index.php)
if (is_dir($root . $uri)) {
    $indexFile = $root . $uri . '/index.php';
    if (file_exists($indexFile)) {
        chdir(dirname($indexFile)); // Fix: Update CWD
        include $indexFile;
        return;
    }
}

// 5. 404 Not Found
http_response_code(404);
echo "404 Not Found";
?>
