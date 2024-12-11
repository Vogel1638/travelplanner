<?php
session_start();
require 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // NOTE Email validierung
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // NOTE Token generation from 50 bytes
            $token = bin2hex(random_bytes(50));

            // NOTE Expiry date to one hour
            $expires = time() + 3600;

            // NOTE Save token and expiry date in the password_resets table
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);

            $reset_link = "https://testing.jubla-galgenen.ch/travelplanner/src/php/reset-password.php?token=" . $token;

            // NOTE Send mail
            $subject = "Passwort zurücksetzen";
            $message = "Klicke auf den folgenden Link, um dein Passwort zurückzusetzen:\n\n" . $reset_link;
            $headers = "From: no-reply@testing.jubla-galgenen.ch";

            if (mail($email, $subject, $message, $headers)) {
                $success = "Eine E-Mail zum Zurücksetzen des Passworts wurde an deine E-Mail-Adresse gesendet.";
            } else {
                $error = "Fehler beim Senden der E-Mail. Bitte versuche es später erneut.";
            }
        } else {
            $error = "Diese E-Mail-Adresse ist nicht registriert.";
        }
    } else {
        $error = "Bitte gib eine gültige E-Mail-Adresse ein.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort vergessen</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href=".../../public/assets/css/fonts.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background-image: url('../../public/assets/images/japan.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.6); 
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
        }

        label {
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        p {
            margin-top: 20px;
            font-size: 14px;
        }

        a {
            text-decoration: none;
            color: var(--primary-color);
        }

        .error {
            color: red;
            font-size: 14px;
        }

        .success {
            color: var(--primary-color);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1 class="logo"><a href="../../public/index.html">Travel<span class="secondary">Planner</span></a></h1>
        <h2>Passwort vergessen</h2>
        <?php if ($error) { echo "<p class='error'>$error</p>"; } ?>
        <?php if ($success) { echo "<p class='success'>$success</p>"; } ?>
        
        <form method="POST" action="forgot_password.php">
            <label for="email">Gib deine E-Mail-Adresse ein:</label>
            <input type="email" id="email" name="email" required placeholder="E-Mail eingeben"><br><br>

            <button type="submit">Passwort zurücksetzen</button>
        </form>
        <p>Wir senden dir einen Link zum Zurücksetzen des Passworts zu.</p>
        <p><a href="login.php">Zurück zur Anmeldung</a></p>
    </div>
</body>
</html>

