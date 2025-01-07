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

    // Call up destination and stopovers
    $stmt = $pdo->prepare("SELECT destination, start_date, end_date FROM trips WHERE trip_id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    $main_destination = $trip['destination'];
    $total_trip_nights = (new DateTime($trip['end_date']))->diff(new DateTime($trip['start_date']))->days;

    $stmt = $pdo->prepare("SELECT destination_id, name FROM destinations WHERE trip_id = ?");
    $stmt->execute([$trip_id]);
    $stops = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Destinations for the display
    $locations = [['id' => null, 'name' => $main_destination]];
    foreach ($stops as $stop) {
        $locations[] = ['id' => $stop['destination_id'], 'name' => $stop['name']];
    }

    $error_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $hotels = $_POST['hotels'] ?? [];
        $nights = $_POST['nights'] ?? [];
        $prices = $_POST['prices'] ?? [];
        $destination_ids = $_POST['destination_ids'] ?? [];

        // Validation
        foreach ($nights as $index => $night) {
            if ($night > 0 && (empty($hotels[$index]) || empty($prices[$index]))) {
                $error_message = "Bitte geben Sie ein Hotel und einen Preis für alle Orte an, an denen Sie mindestens eine Nacht verbringen.";
                break;
            }
        }

        if (empty($error_message)) {
            // Delete existing hotel bookings
            $stmt = $pdo->prepare("DELETE FROM hotels WHERE trip_id = ?");
            $stmt->execute([$trip_id]);

            // Save new hotel bookings
            foreach ($hotels as $index => $hotel) {
                if (!empty($hotel) && $nights[$index] > 0) {
                    $destination_id = $destination_ids[$index] ?: null; // Target ID or NULL for main destination
                    $total_price = $nights[$index] * $prices[$index];
                    $stmt = $pdo->prepare("
                        INSERT INTO hotels (trip_id, destination_id, name, nights, price, total_price) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$trip_id, $destination_id, $hotel, $nights[$index], $prices[$index], $total_price]);
                }
            }

            header("Location: step_5.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiseerstellung - Hotels</title>
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
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            text-align: center;
        }

        .error {
            color: red;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .destination-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .destination-item {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .destination-item input {
            flex: 1;
            padding: 10px;
            font-size: 14px;
        }

        label {
            font-weight: bold;
        }

        .remaining-nights {
            text-align: right;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 20px;
        }

        button {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }

        button:hover {
            background-color: #2980b9;
        }

        .total-price {
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            font-size: 18px;
        }
    </style>
    <script>
        function calculateTotalAndRemainingNights(totalTripNights) {
            const nights = document.querySelectorAll('input[name="nights[]"]');
            const prices = document.querySelectorAll('input[name="prices[]"]');
            let total = 0;
            let usedNights = 0;

            nights.forEach((night, index) => {
                const price = prices[index].value || 0;
                const nightValue = night.value || 0;
                total += nightValue * price;
                usedNights += parseInt(nightValue, 10);
            });

            document.getElementById('total-price').innerText = `Gesamtkosten: CHF ${total.toFixed(2)}`;
            document.getElementById('remaining-nights').innerText = `Verbleibende Nächte: ${totalTripNights - usedNights}`;
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Reiseerstellung - Schritt 4</h1>
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="remaining-nights" id="remaining-nights">
                Verbleibende Nächte: <?php echo $total_trip_nights; ?>
            </div>
            <div class="destination-group">
                <h2>Hotelinformationen</h2>
                <?php foreach ($locations as $index => $location): ?>
                    <div class="destination-item">
                        <label><?php echo htmlspecialchars($location['name']); ?>:</label>
                        <input type="hidden" name="destination_ids[]" value="<?php echo $location['id']; ?>">
                        <input type="text" name="hotels[]" placeholder="Hotelname">
                        <input type="number" name="nights[]" placeholder="Anzahl Nächte" min="0" onchange="calculateTotalAndRemainingNights(<?php echo $total_trip_nights; ?>)" required>
                        <input type="number" name="prices[]" placeholder="Preis pro Nacht (CHF)" min="0" step="0.01" onchange="calculateTotalAndRemainingNights(<?php echo $total_trip_nights; ?>)">
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="total-price" class="total-price">Gesamtkosten: CHF 0.00</div>
            <button type="submit">Weiter</button>
        </form>
        <a href="step_3.php" style="display: block; margin-top: 20px; text-align: center;">Zurück</a>
    </div>
</body>
</html>
