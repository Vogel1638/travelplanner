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

    $stmt = $pdo->prepare("
        SELECT destination FROM trips WHERE trip_id = ?
    ");
    $stmt->execute([$trip_id]);
    $main_destination = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT name FROM destinations WHERE trip_id = ? ORDER BY destination_id ASC
    ");
    $stmt->execute([$trip_id]);
    $stops = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $start_point = $_POST['start_point'] ?? '';
        $transport_modes = $_POST['transport_modes'] ?? [];
        $transport_prices = $_POST['transport_prices'] ?? [];

        // Delete existing transport data
        $stmt = $pdo->prepare("DELETE FROM transport WHERE trip_id = ?");
        $stmt->execute([$trip_id]);

        // Save transport data
        $locations = array_merge([$start_point], $stops, [$main_destination]);
        for ($i = 0; $i < count($locations) - 1; $i++) {
            $stmt = $pdo->prepare("
                INSERT INTO transport (trip_id, start_location, end_location, mode_of_transport, price, stop_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $trip_id,
                $locations[$i],
                $locations[$i + 1],
                $transport_modes[$i],
                $transport_prices[$i],
                $i + 1
            ]);
        }

        header("Location: step_4.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiseerstellung - Transport</title>
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

        .section {
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            padding-left: 10px;
            padding-top: 10px;
            padding-bottom: 10px;
            background: #f4faff;
            border-radius: 8px;
        }

        .transport-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 10px;
        }

        .transport-row label {
            flex: 1;
            font-weight: bold;
            text-align: right;
        }

        .transport-row select, .transport-row input {
            flex: 2;
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .divider {
            height: 2px;
            background: #ddd;
            margin: 20px 0;
        }

        .button-container {
            text-align: center;
        }

        button {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #2980b9;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reiseerstellung - Transport</h1>
        <form method="POST">
            <div class="section">
                <h3>Startpunkt</h3>
                <div class="transport-row">
                    <label for="start_point">Von:</label>
                    <input type="text" name="start_point" id="start_point" placeholder="Startpunkt" required>
                </div>
            </div>

            <?php if (!empty($stops)): ?>
                <div class="divider"></div>
                <div class="section">
                    <h3>Zwischenstopps</h3>
                    <?php foreach ($stops as $index => $stop): ?>
                        <div class="transport-row">
                            <label>Von: <?php echo $index === 0 ? 'Startpunkt' : htmlspecialchars($stops[$index - 1]); ?></label>
                            <label>Nach: <?php echo htmlspecialchars($stop); ?></label>
                            <select name="transport_modes[]" required>
                                <option value="Zug">Zug</option>
                                <option value="Auto">Auto</option>
                                <option value="Flugzeug">Flugzeug</option>
                                <option value="Bus">Bus</option>
                                <option value="Sonstige">Sonstige</option>
                            </select>
                            <input type="number" name="transport_prices[]" placeholder="Preis in CHF" required>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="divider"></div>
            <div class="section">
                <h3>Hauptziel</h3>
                <div class="transport-row">
                    <label>Von: <?php echo !empty($stops) ? htmlspecialchars(end($stops)) : 'Startpunkt'; ?></label>
                    <label>Nach: <?php echo htmlspecialchars($main_destination); ?></label>
                    <select name="transport_modes[]" required>
                        <option value="Zug">Zug</option>
                        <option value="Auto">Auto</option>
                        <option value="Flugzeug">Flugzeug</option>
                        <option value="Bus">Bus</option>
                        <option value="Sonstige">Sonstige</option>
                    </select>
                    <input type="number" name="transport_prices[]" placeholder="Preis in CHF" required>
                </div>
            </div>

            <div class="button-container">
                <button type="submit">Weiter</button>
            </div>
        </form>
        <a href="step_2.php">Zurück</a>
    </div>
</body>
</html>


