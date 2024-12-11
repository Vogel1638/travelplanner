<?php
session_start(); 

require 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $accept_terms = isset($_POST['terms']);

    // NOTE Validation of the input data
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Alle Felder müssen ausgefüllt sein.";
    } elseif ($password !== $confirm_password) {
        $error = "Die Passwörter stimmen nicht überein.";
    } elseif (!$accept_terms) {
        $error = "Du musst die AGB akzeptieren.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Diese E-Mail-Adresse ist bereits registriert.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->execute([$email, $hashed_password]);

            $_SESSION['user'] = $email;

            header("Location: login.php");
            exit();  
        }
    }
}
?>


<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/fonts.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <link rel="stylesheet" href="../../public/assets/css/register.css">
    <style>
        .strength-meter {
            margin-top: 10px;
            height: 10px;
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 5px;
            position: relative;
            display: none;
        }

        .strength-meter div {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
        }

        .strength-weak { width: 25%; background-color: red; }
        .strength-medium { width: 50%; background-color: orange; }
        .strength-strong { width: 75%; background-color: yellowgreen; }
        .strength-very-strong { width: 100%; background-color: green; }

        .strength-text {
            margin-top: 5px;
            font-size: 14px;
            display: none;
        }

        .help-icon {
            display: inline-block;
            margin-left: 5px;
            cursor: pointer;
            font-size: 14px;
            color: #555;
            position: relative;
        }

        .help-icon:hover::after {
            content: "";
            position: absolute;
            top: 20px;
            left: 0;
            background: #333;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .help-icon:hover .tooltip-content {
            display: block;
        }

        .tooltip-content {
            display: none;
            position: absolute;
            top: 20px;
            left: 0;
            background: #333;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
        }

        .tooltip-content > p {
            font-size: 14px
        }

        .tooltip-content ul {
            padding-left: 20px;
            margin: 0;
        }

        .tooltip-content ul li {
            margin: 5px 0;
            list-style: disc;
            font-size: 14px
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="left-side">
            <img src="../../public/assets/images/register/register.jpg" alt="Login Image">
            <div class="logo">
                <h2 class="logo"><a href="../../public/index.html">Travel<span class="secondary">Planner</span></a></h2>
            </div>
        </div>

        <div class="right-side">
            <div class="close-button">
                <a href="../../public/index.html">&times;</a>
            </div>
            <div class="register-form">
                <h1 class="login-title">Registriere dich</h1>
                <p>Erstelle ein Konto, um die vollen Funktionen zu nutzen.</p>
                <hr>

                <form action="register.php" method="POST">
                    <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
                    
                    <label for="email">E-Mail</label>
                    <input type="email" id="email" name="email" required placeholder="Gib deine E-Mail ein" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

                    <label for="password">
                        Passwort 
                        <span class="help-icon">?
                            <div class="tooltip-content">
                                <p>Das Passwort wird anhand der folgenden Kriterien bewertet:</p>
                                <ul>
                                    <li>Mindestens 8 Zeichen</li>
                                    <li>Mindestens ein Grossbuchstabe</li>
                                    <li>Mindestens ein Kleinbuchstabe</li>
                                    <li>Mindestens eine Zahl</li>
                                    <li>Mindestens ein Sonderzeichen</li>
                                </ul>
                            </div>
                        </span>
                    </label>
                    <input type="password" id="password" name="password" required placeholder="Gib dein Passwort ein">
                    
                    <div class="strength-meter">
                        <div id="strength-bar"></div>
                    </div>
                    <p id="strength-text" class="strength-text"></p>

                    <label for="confirm_password">Passwort bestätigen</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Bestätige dein Passwort">

                    <div class="checkbox-container">
                        <input type="checkbox" id="accept_terms" name="terms" required <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                        <label for="terms">Ich akzeptiere die <a href="#">AGB</a>.</label>
                    </div>

                    <button type="submit">Registrieren</button>
                </form>

                <p>Schon ein Konto? <a href="login.php">Jetzt anmelden</a></p>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.querySelector('.strength-meter');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');

        passwordInput.addEventListener('focus', () => {
            strengthMeter.style.display = 'block';
            strengthText.style.display = 'block';
        });

        passwordInput.addEventListener('blur', () => {
            if (!passwordInput.value) {
                strengthMeter.style.display = 'none';
                strengthText.style.display = 'none';
            }
        });

        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
            const strength = calculateStrength(password);

            updateStrengthMeter(strength);
        });

        function calculateStrength(password) {
            let score = 0;

            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[\W_]/.test(password)) score++;

            return score;
        }

        function updateStrengthMeter(score) {
            let strengthClass = '';
            let strengthMessage = '';

            switch (score) {
                case 1:
                    strengthClass = 'strength-weak';
                    strengthMessage = 'Schwach';
                    break;
                case 2:
                    strengthClass = 'strength-medium';
                    strengthMessage = 'Mittel';
                    break;
                case 3:
                    strengthClass = 'strength-strong';
                    strengthMessage = 'Stark';
                    break;
                case 4:
                case 5:
                    strengthClass = 'strength-very-strong';
                    strengthMessage = 'Sehr stark';
                    break;
                default:
                    strengthClass = '';
                    strengthMessage = 'Zu kurz';
            }

            strengthBar.className = strengthClass;
            strengthText.textContent = strengthMessage;
        }
    </script>
</body>
</html>


