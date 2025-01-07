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

    // Check if a trip ID exists
    if (!isset($_SESSION['trip_id'])) {
        header("Location: step_1.php");
        exit();
    }

    $trip_id = $_SESSION['trip_id'];

    // Process main destination and stopovers
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $main_destination = $_POST['main_destination'] ?? '';
        $stops = $_POST['stops'] ?? [];

        // Validation
        if (empty($main_destination)) {
            $error_message = "Bitte geben Sie ein Hauptziel ein.";
        } else {
            $stmt = $pdo->prepare("UPDATE trips SET destination = ? WHERE trip_id = ?");
            $stmt->execute([$main_destination, $trip_id]);


            $stmt = $pdo->prepare("DELETE FROM destinations WHERE trip_id = ?");
            $stmt->execute([$trip_id]);


            foreach ($stops as $stop) {
                if (!empty($stop)) {
                    $stmt = $pdo->prepare("INSERT INTO destinations (trip_id, name) VALUES (?, ?)");
                    $stmt->execute([$trip_id, $stop]);
                }
            }

            header("Location: step_3.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiseerstellung - Schritt 2</title>
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

        .stops-list {
            margin-top: 10px;
        }

        .stops-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stops-list input {
            flex: 1;
            margin-right: 10px;
        }

        .stops-list button {
            background-color: red;
            color: white;
            border: none;
            padding: 5px;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
    <script>
        function addStop() {
            const stopsList = document.getElementById('stops-list');
            const newStop = document.createElement('li');

            newStop.innerHTML = `
                <input type="text" name="stops[]" placeholder="Zwischenstopp">
                <button type="button" onclick="removeStop(this)">Entfernen</button>
            `;
            stopsList.appendChild(newStop);
        }

        function removeStop(button) {
            const li = button.parentElement;
            li.remove();
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Reiseerstellung - Schritt 2</h1>
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="main_destination">Hauptziel:</label>
            <input type="text" name="main_destination" id="main_destination" placeholder="z.B. Paris" required>

            <label>Zwischenstopps:</label>
            <ul id="stops-list" class="stops-list">
            </ul>
            <button type="button" onclick="addStop()">Zwischenstopp hinzufügen</button>

            <button type="submit">Weiter</button>
        </form>
        <a href="step_1.php" style="display: block; margin-top: 20px; text-align: center;">Zurück</a>
    </div>
</body>
</html>
