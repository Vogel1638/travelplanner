<?php
    session_start();
    require '../../php/db.php';

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    if (!isset($_SESSION['user'])) {
        die('Benutzer nicht eingeloggt');
    }

    if (!isset($_SESSION['trip_id'])) {
        die('Keine Reise-ID gefunden. Bitte die Reise zuerst erstellen.');
    }

    $trip_id = $_SESSION['trip_id']; 

    // NOTE Calling up the main destination, starting point and stopovers
    $stmt = $pdo->prepare("SELECT main_destination, start_location, start_date, end_date FROM trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch();

    if (!$trip) {
        die('Fehler: Reise mit der angegebenen ID nicht gefunden.');
    }

    $main_destination = $trip['main_destination'];
    $start_location = $trip['start_location'];
    $start_date = new DateTime($trip['start_date']);
    $end_date = new DateTime($trip['end_date']);

    // NOTE Calculation of the remaining nights
    $interval = $start_date->diff($end_date);
    $total_nights = $interval->days; 

    $stmt = $pdo->prepare("SELECT * FROM stopovers WHERE trip_id = ? ORDER BY position ASC");
    $stmt->execute([$trip_id]);
    $stopovers = $stmt->fetchAll();

    $given_nights = 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nightsMainDestination = $_POST['nights']['main_destination'] ?? 0;
        $hotelMainDestination = $_POST['hotels']['main_destination'] ?? '';
    
        $stmt = $pdo->prepare("UPDATE trips SET hotel = ?, nights = ? WHERE id = ?");
        $stmt->execute([$hotelMainDestination, $nightsMainDestination, $trip_id]);
    
        $nightsForStopovers = $_POST['nights'] ?? [];
        $hotelsForStopovers = $_POST['hotels'] ?? [];
    
        foreach ($stopovers as $stopover) {
            $stopoverId = $stopover['id'];
    
            $nights = $nightsForStopovers[$stopoverId] ?? 0;
            $hotel = $hotelsForStopovers[$stopoverId] ?? '';
    
            $stmt = $pdo->prepare("UPDATE stopovers SET hotel = ?, nights = ? WHERE id = ?");
            $stmt->execute([$hotel, $nights, $stopoverId]);
        }
    
        header("Location: step_5.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schritt 4: Hotel Auswahl und Nächte</title>
    <script>
        var totalNights = <?php echo $total_nights; ?>; 
        var givenNights = 0; 

        function updateRemainingNights() {
            var nightsMainDestination = parseInt(document.getElementById('nights_main').value) || 0;

            var nightInputs = document.querySelectorAll('input[name^="nights"]');
            var stopoverNights = 0;

            nightInputs.forEach(input => {
                stopoverNights += parseInt(input.value) || 0;
            });

            givenNights = stopoverNights;

            var remainingNights = totalNights - givenNights;

            document.getElementById('remaining_nights_input').textContent = remainingNights;
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('nights_main').addEventListener('input', updateRemainingNights);

            const nightInputs = document.querySelectorAll('input[name^="nights"]');
            nightInputs.forEach(input => {
                input.addEventListener('input', updateRemainingNights);
            });

            updateRemainingNights();
        });
    </script>
</head>
<body>
    <h1>Schritt 4: Hotel Auswahl und Nächte für Zwischenstopps und Hauptziel</h1>

    <form action="step_4.php" method="post">
        <h2>Hauptziel: <?php echo htmlspecialchars($main_destination); ?></h2>
        <label for="hotel_main">Hotel am Hauptziel:</label>
        <input type="text" name="hotels[main_destination]" id="hotel_main" placeholder="Hotel am Hauptziel eingeben">

        <label for="nights_main">Anzahl Nächte am Hauptziel:</label>
        <input type="number" name="nights[main_destination]" id="nights_main" placeholder="Anzahl Nächte am Hauptziel">

        <h2>Zwischenstopps:</h2>
        <?php foreach ($stopovers as $stopover): ?>
            <div>
                <label for="hotel_<?php echo $stopover['id']; ?>"><?php echo htmlspecialchars($stopover['location']); ?>:</label>
                <input type="text" name="hotels[<?php echo $stopover['id']; ?>]" id="hotel_<?php echo $stopover['id']; ?>" placeholder="Hotel für diesen Zwischenstopp eingeben">
                
                <label for="nights_<?php echo $stopover['id']; ?>">Anzahl Nächte:</label>
                <input type="number" name="nights[<?php echo $stopover['id']; ?>]" id="nights_<?php echo $stopover['id']; ?>" placeholder="Anzahl Nächte für diesen Zwischenstopp">
            </div>
        <?php endforeach; ?>

        <h3>Gesamt verbleibende Nächte: <span id="remaining_nights_input"><?php echo $total_nights; ?></span> Nächte</h3>

        <button type="submit">Weiter</button>
    </form>
</body>
</html>
