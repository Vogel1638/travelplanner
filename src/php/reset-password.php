<?php
session_start();
require 'db.php';

$error = "";
$success = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires > ?");
    $stmt->execute([$token, date("U")]);

    if ($stmt->rowCount() === 0) {
        $error = "Der Link ist entweder ungültig oder abgelaufen.";
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($password) || empty($confirm_password)) {
                $error = "Bitte gib beide Passwörter ein.";
            } elseif ($password !== $confirm_password) {
                $error = "Die Passwörter stimmen nicht überein.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $email = $row['email'];

                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $email]);

                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);

                $success = "Dein Passwort wurde erfolgreich zurückgesetzt. Du wirst jetzt zur Login-Seite weitergeleitet.";
                
                header("Location: login.php");
                exit();
            }
        }
    }
} else {
    $error = "Kein Token gefunden.";
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/fonts.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background-image: url('../../public/assets/images/u-bahn.jpg'); 
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

        .error {
            color: red;
            font-size: 14px;
        }

        .success {
            color: green;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1 class="logo"><a href="../../public/index.html">Travel<span class="secondary">Planner</span></a></h1>
        <h2>Passwort zurücksetzen</h2>
        <?php if ($error) { echo "<p class='error'>$error</p>"; } ?>
        <?php if ($success) { echo "<p class='success'>$success</p>"; } ?>
        
        <?php if (isset($_GET['token']) && !$error): ?>
        <form method="POST">
            <label for="password">Neues Passwort:</label>
            <input type="password" id="password" name="password" required><br><br>

            <label for="confirm_password">Passwort bestätigen:</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>

            <button type="submit">Passwort zurücksetzen</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
