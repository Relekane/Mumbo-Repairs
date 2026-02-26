<?php
// create_admin.php - run once to create an admin account
// Usage: visit this file in browser or run from CLI, then delete it.

require_once __DIR__ . '/db.php';

if (PHP_SAPI !== 'cli') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        if ($username === '' || $password === '') {
            echo "Missing username or password.";
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, email) VALUES (:u, :p, :e)");
        $stmt->execute([':u'=>$username, ':p'=>$hash, ':e'=>$email]);
        echo "Admin user created. Please delete create_admin.php for security.";
        exit;
    }
    // show simple form
    echo '<form method="post">
        Username: <input name="username"><br>
        Email: <input name="email"><br>
        Password: <input name="password" type="password"><br>
        <button type="submit">Create admin</button>
    </form>';
    exit;
} else {
    // CLI
    $username = $argv[1] ?? null;
    $password = $argv[2] ?? null;
    $email = $argv[3] ?? null;
    if (!$username || !$password) {
        echo "Usage: php create_admin.php username password [email]\n";
        exit(1);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, email) VALUES (:u, :p, :e)");
    $stmt->execute([':u'=>$username, ':p'=>$hash, ':e'=>$email]);
    echo "Admin created.\n";
    exit;
}