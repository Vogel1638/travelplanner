<?php
session_start();
require '../../php/db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../php/login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_title = $_POST['trip_title'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $participants = $_POST['participants'] ?? '';
    $user_email = $_SESSION['user'];

    // Validate input
    if (empty($trip_title) || empty($start_date) || empty($end_date) || empty($budget) || empty($participants)) {
        $error_message = "Bitte füllen Sie alle Felder aus.";
    } else {
        // Retrieve user ID
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$user_email]);
        $user = $stmt->fetch();

        if (!$user) {
            die("Benutzer nicht gefunden.");
        }

        $user_id = $user['user_id'];

        // Insert trip into the database
        $stmt = $pdo->prepare("
            INSERT INTO trips (user_id, trip_title, start_date, end_date, budget, participants) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $trip_title, $start_date, $end_date, $budget, $participants]);

        // Get the newly created trip_id
        $trip_id = $pdo->lastInsertId();

        // Store the trip_id in the session
        $_SESSION['trip_id'] = $trip_id;

        // Redirect to the next step
        header("Location: step_2.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiseerstellung - Schritt 1</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <link rel="stylesheet" href="../../public/assets/css/add_trip.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
        }

        input, button {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #2980b9;
        }

        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reiseerstellung - Schritt 1</h1>
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="trip_title">Reisetitel:</label>
            <input type="text" name="trip_title" id="trip_title" placeholder="Reisetitel" required>

            <label for="start_date">Startdatum:</label>
            <input type="date" name="start_date" id="start_date" required>

            <label for="end_date">Enddatum:</label>
            <input type="date" name="end_date" id="end_date" required>

            <label for="budget">Budget:</label>
            <input type="number" name="budget" id="budget" placeholder="Maximales Budget" required>

            <label for="participants">Teilnehmer:</label>
            <input type="number" name="participants" id="participants" placeholder="Anzahl Teilnehmer" required>

            <button type="submit">Weiter</button>
        </form>
        <a href="../dashboard.php" style="display: block; margin-top: 20px; text-align: center;">Abbrechen</a>
    </div>
</body>
</html>
