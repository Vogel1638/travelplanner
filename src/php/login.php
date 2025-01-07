<?php
    session_start();
    require 'db.php';

    $error = "";

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if (isset($_SESSION['user_id'])) {
        header("Location: ../sites/dashboard.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $error = "Bitte füllen Sie alle Felder aus.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];

                if (isset($_POST['remember'])) {
                    setcookie("user_email", $email, time() + 3600 * 24 * 30, "/");
                }

                header("Location: ../sites/dashboard.php");
                exit();
            } else {
                $error = "Ungültige E-Mail oder Passwort.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/fonts.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <link rel="stylesheet" href="../../public/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="left-side">
            <img src="../../public/assets/images/register/login.jpg" alt="Login Image">
            <div class="logo">
                <h2 class="logo"><a href="../../public/index.html">Travel<span class="secondary">Planner</span></a></h2>
            </div>
        </div>

        <div class="right-side">
            <div class="close-button">
                <a href="../../public/index.html">&times;</a>
            </div>
            <div class="login-form">
                <h1 class="login-title">Willkommen zurück</h1>
                <p>Bitte melde dich an, um fortzufahren.</p>
                <hr>

                <form action="login.php" method="POST">
                    <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
                    
                    <label for="email">E-Mail</label>
                    <input type="email" id="email" name="email" required placeholder="Gib deine E-Mail ein" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

                    <label for="password">Passwort</label>
                    <input type="password" id="password" name="password" required placeholder="Gib dein Passwort ein">

                    <div class="checkbox-container">
                        <input type="checkbox" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                        <label for="stay_logged-in">Angemeldet bleiben</label>
                    </div>

                    <button type="submit">Anmelden</button>
                </form>

                <p>Noch keinen Account? <a href="register.php">Jetzt registrieren</a></p>
                <p><a href="forgot_password.php" class="forgot_password">Passwort vergessen?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
