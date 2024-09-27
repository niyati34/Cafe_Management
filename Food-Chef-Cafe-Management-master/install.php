<?php
/**
 * Food Chef Cafe Management System - Installation Script
 * This script helps users set up the project easily
 */

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('Application is already installed. Remove config/installed.lock to reinstall.');
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP 7.4 or higher is required. Current version: ' . PHP_VERSION);
}

// Check required extensions
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("Required PHP extension not loaded: $ext");
    }
}

echo "<h1>Food Chef Cafe Management System - Installation</h1>";
echo "<p>Welcome to the installation wizard!</p>";

// Database connection test
if (isset($_POST['install'])) {
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $database = $_POST['database'];
    
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
        $pdo->exec("USE `$database`");
        
        // Import SQL file
        $sql = file_get_contents('database/project.sql');
        $pdo->exec($sql);
        
        // Update config file
        $config_content = "<?php 
define('BASEURL','http://localhost/project/');
define('HOSTNAME','$host');
define('USERNAME','$username');
define('PASSWORD','$password');
define('DB','$database');
?>";
        
        file_put_contents('config/config.php', $config_content);
        
        // Create installed lock file
        file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
        
        echo "<div style='color: green; padding: 10px; border: 1px solid green;'>";
        echo "<h2>Installation Completed Successfully!</h2>";
        echo "<p>Your Food Chef Cafe Management System is now ready to use.</p>";
        echo "<p><strong>Admin Login:</strong> username: admin, password: admin123</p>";
        echo "<p><a href='index.php'>Go to Homepage</a> | <a href='admin/'>Go to Admin Panel</a></p>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
        echo "<h2>Installation Failed!</h2>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    // Show installation form
    ?>
    <form method="post" style="max-width: 500px; margin: 20px 0;">
        <h2>Database Configuration</h2>
        
        <div style="margin: 10px 0;">
            <label>Database Host:</label><br>
            <input type="text" name="host" value="localhost" required style="width: 100%; padding: 8px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label>Database Username:</label><br>
            <input type="text" name="username" value="root" required style="width: 100%; padding: 8px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label>Database Password:</label><br>
            <input type="password" name="password" style="width: 100%; padding: 8px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label>Database Name:</label><br>
            <input type="text" name="database" value="project" required style="width: 100%; padding: 8px;">
        </div>
        
        <button type="submit" name="install" style="background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer;">
            Install Food Chef System
        </button>
    </form>
    
    <div style="background: #f0f0f0; padding: 15px; margin: 20px 0;">
        <h3>Requirements:</h3>
        <ul>
            <li>PHP 7.4 or higher</li>
            <li>MySQL 5.7 or higher</li>
            <li>Apache/Nginx web server</li>
            <li>PDO MySQL extension</li>
        </ul>
    </div>
    <?php
}
?>
