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

    $stmt = $pdo->prepare("SELECT main_destination, start_location FROM trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch();

    if (!$trip) {
        die('Fehler: Reise mit der angegebenen ID nicht gefunden.');
    }

    $main_destination = $trip['main_destination'];
    $start_location = $trip['start_location'];

    $stmt = $pdo->prepare("SELECT * FROM stopovers WHERE trip_id = ? ORDER BY position ASC");
    $stmt->execute([$trip_id]);
    $stopovers = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // NOTE Check whether Starting point changed and saved in the DB
        if (isset($_POST['start_location']) && $_POST['start_location'] !== $start_location) {
            $new_start_location = $_POST['start_location'];
            $stmt_update_start = $pdo->prepare("UPDATE trips SET start_location = ? WHERE id = ?");
            $stmt_update_start->execute([$new_start_location, $trip_id]);
        }

        // NOTE Checks whether stopovers and means of transport are set
        if (isset($_POST['stopovers']) && isset($_POST['transport_modes']) && !empty($_POST['stopovers']) && !empty($_POST['transport_modes'])) {
            $transport_modes = $_POST['transport_modes'];
            $stopover_ids = $_POST['stopovers']; 

            // NOTE Update the sequence of stopovers
            $position = 1; 
            $stmt = $pdo->prepare("UPDATE stopovers SET position = ? WHERE trip_id = ? AND location = ?");
            $stmt->execute([$position, $trip_id, $start_location]);

            $position++; 

            foreach ($stopover_ids as $stopover_id) {
                if (is_numeric($stopover_id)) {
                    // NOTE Update the order in the database
                    $stmt = $pdo->prepare("UPDATE stopovers SET position = ? WHERE id = ?");
                    $stmt->execute([$position, $stopover_id]);
                    $position++;
                }
            }

            // NOTE Update means of transport
            foreach ($transport_modes as $stopover_id => $mode) {
                if (is_numeric($stopover_id) && in_array($mode, ['Zug', 'Auto', 'Flugzeug', 'Schiff'])) {
                    $stmt = $pdo->prepare("UPDATE stopovers SET transport_mode = ? WHERE id = ?");
                    $stmt->execute([$mode, $stopover_id]);
                }
            }
        } else {
            die('Fehler: Keine Transportmittel oder Zwischenstopps ausgewählt.');
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
    <title>Schritt 3: Startpunkt, Zwischenstopps und Transportmittel</title>
    <style>
        .stopover-field {
            position: relative;
            opacity: 1;
        }

        .moving {
            transition: transform 0.5s linear, opacity 0.5s linear;
        }

        .stopover-field.moving-up {
            transform: translateY(-120%);
            opacity: 0.5;
        }

        .stopover-field.moving-down {
            transform: translateY(120%);
            opacity: 0.5;
        }

        .stopover-field.target-up {
            transform: translateY(-120%);
            opacity: 1;
        }

        .stopover-field.target-down {
            transform: translateY(120%);
            opacity: 1;
        }

        .move-buttons button {
            padding: 5px 10px;
            margin: 0 5px;
            font-size: 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .move-buttons button:disabled {
            background-color: #dcdcdc;
            cursor: not-allowed;
        }

        .stopover-field {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>Schritt 3: Bestimmen Sie den Startpunkt, Zwischenstopps und die Transportmittel</h1>

    <form action="step_3.php" method="post">
        <h2>Startpunkt</h2>
        <input type="text" name="start_location" required placeholder="Startpunkt eingeben" value="<?php echo htmlspecialchars($start_location, ENT_QUOTES); ?>">

        <h2>Hauptziel</h2>
        <input type="text" name="main_destination" required placeholder="Hauptziel eingeben" value="<?php echo htmlspecialchars($main_destination, ENT_QUOTES); ?>" readonly>

        <h2>Zwischenstopps und Transportmittel</h2>
        <div id="stopover-fields">
            <div class="first-field">
                <label for="transport_0">Transportmittel vom Startpunkt zum ersten Zwischenstopp:</label>
                <select name="transport_modes[0]" id="transport_0">
                    <option value="Zug">Zug</option>
                    <option value="Auto">Auto</option>
                    <option value="Flugzeug">Flugzeug</option>
                    <option value="Schiff">Schiff</option>
                </select>
            </div>

            <h3>Zwischenstopps:</h3>
            <?php if ($stopovers): ?>
                <?php foreach ($stopovers as $index => $stopover): ?>
                    <div class="stopover-field" id="changeorder" data-id="<?php echo $stopover['id']; ?>">
                        <label for="stopover_<?php echo $stopover['id']; ?>">Von</label>
                        <input type="text" name="stopovers[<?php echo $stopover['id']; ?>]" value="<?php echo htmlspecialchars($stopover['location'], ENT_QUOTES); ?>" readonly>

                        <label for="transport_<?php echo $stopover['id']; ?>"> zum nächsten Ziel mit:</label>
                        <select name="transport_modes[<?php echo $stopover['id']; ?>]" id="transport_<?php echo $stopover['id']; ?>">
                            <option value="Zug" <?php echo ($stopover['transport_mode'] == 'Zug') ? 'selected' : ''; ?>>Zug</option>
                            <option value="Auto" <?php echo ($stopover['transport_mode'] == 'Auto') ? 'selected' : ''; ?>>Auto</option>
                            <option value="Flugzeug" <?php echo ($stopover['transport_mode'] == 'Flugzeug') ? 'selected' : ''; ?>>Flugzeug</option>
                            <option value="Schiff" <?php echo ($stopover['transport_mode'] == 'Schiff') ? 'selected' : ''; ?>>Schiff</option>
                        </select>

                        <div class="move-buttons">
                            <button type="button" class="move-up" onclick="moveStopoverUp(<?php echo $stopover['id']; ?>)">↑</button>
                            <button type="button" class="move-down" onclick="moveStopoverDown(<?php echo $stopover['id']; ?>)">↓</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Es wurden keine Zwischenstopps festgelegt.</p>
            <?php endif; ?>

            <div class="last-field">
                <label for="transport_last">Transportmittel vom letzten Zwischenstopp zum Hauptziel:</label>
                <select name="transport_modes[last]" id="transport_last">
                    <option value="Zug">Zug</option>
                    <option value="Auto">Auto</option>
                    <option value="Flugzeug">Flugzeug</option>
                    <option value="Schiff">Schiff</option>
                </select>
            </div>
        </div>
        <br><br>
        <button type="submit">Weiter</button>
    </form>

    <script>
        function moveStopoverUp(id) {
            const stopovers = document.querySelectorAll('.stopover-field');
            let currentIndex = Array.from(stopovers).findIndex(stopover => stopover.getAttribute('data-id') === String(id));

            if (currentIndex > 0) {
                const currentStopover = stopovers[currentIndex];
                const previousStopover = stopovers[currentIndex - 1];

                previousStopover.classList.add('moving');
                currentStopover.classList.add('moving');
                currentStopover.classList.add('moving-up');
                previousStopover.classList.add('target-down');
                

                setTimeout(() => {
                    currentStopover.parentNode.insertBefore(currentStopover, previousStopover);

                    currentStopover.classList.remove('moving-up');
                    previousStopover.classList.remove('target-down');
                    previousStopover.classList.remove('moving');
                    currentStopover.classList.remove('moving');

                    updateStopoverOrder();
                    updateArrowButtons();
                }, 500); 
            }
        }

        function moveStopoverDown(id) {
            const stopovers = document.querySelectorAll('.stopover-field');
            let currentIndex = Array.from(stopovers).findIndex(stopover => stopover.getAttribute('data-id') === String(id));

            if (currentIndex < stopovers.length - 1) {
                const currentStopover = stopovers[currentIndex];
                const nextStopover = stopovers[currentIndex + 1];

                nextStopover.classList.add('moving');
                currentStopover.classList.add('moving');
                currentStopover.classList.add('moving-down');
                nextStopover.classList.add('target-up');

                setTimeout(() => {
                    currentStopover.parentNode.insertBefore(nextStopover, currentStopover);

                    currentStopover.classList.remove('moving-down');
                    nextStopover.classList.remove('target-up');
                    nextStopover.classList.remove('moving');
                    currentStopover.classList.remove('moving');

                    updateStopoverOrder();
                    updateArrowButtons();
                }, 500); 
            }
        }

        function updateStopoverOrder() {
            const stopovers = document.querySelectorAll('.stopover-field');
            stopovers.forEach((stopover, index) => {
                const stopoverInput = stopover.querySelector('input');
                stopoverInput.setAttribute('name', `stopovers[${stopover.dataset.id}]`);
            });
        }

        function updateArrowButtons() {
            const stopovers = document.querySelectorAll('.stopover-field');
            stopovers.forEach((stopover, index) => {
                const upButton = stopover.querySelector('.move-up');
                const downButton = stopover.querySelector('.move-down');

                upButton.disabled = index === 0;

                downButton.disabled = index === stopovers.length - 1;
            });
        }

        document.addEventListener('DOMContentLoaded', updateArrowButtons);
    </script>
</body>
</html>
