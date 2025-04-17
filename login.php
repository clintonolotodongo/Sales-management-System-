<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sales Software</title>
    <link href="../assets/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            Hey, Sales Software Here!
            <h3>Welcome Back</h3>
            <form method="POST" action="">
                <div class="input-group email">
                    <input type="text" id="username" name="username" placeholder="Username" required>
                </div>
                <div class="input-group password">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
            <a href="#" class="forgot-password">Forgot password?</a>
            <a href="https://wa.me/256763828117">Support 24/7 <img src="../clogo.jpg" style="width: 80px;" alt=""></a>
            <?php
            session_start();
            require_once '../includes/config.php';

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $username = $_POST['username'];
                $password = $_POST['password'];

                // Simple query to fetch the user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                // Check if user exists and password matches (plain text comparison)
                if ($user && $user['password'] === $password) {
                    $_SESSION['user_id'] = $user['user_id'];
                    header("Location: dashboard");
                    exit();
                } else {
                    echo "<div class='alert-danger text-center'>Invalid username or password!</div>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>