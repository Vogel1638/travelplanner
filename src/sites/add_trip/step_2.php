<?php
    session_start();
    require '../../php/db.php';

    if (!isset($_SESSION['user'])) {
        die('Benutzer nicht eingeloggt');
    }

    if (!isset($_SESSION['trip_id'])) {
        die('Keine Reise-ID gefunden. Bitte die Reise zuerst erstellen.');
    }

    $trip_id = $_SESSION['trip_id']; 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_stopover_id'])) {
            // NOTE Deleting a stopover
            $stopover_id = $_POST['delete_stopover_id'];
            $stmt = $pdo->prepare("DELETE FROM stopovers WHERE id = ?");
            $stmt->execute([$stopover_id]);
        } else {
            // NOTE Saving the main destination and stopovers
            $main_destination = $_POST['main_destination']; 
            $stopover_locations = $_POST['stopover_locations'];

            $stmt = $pdo->prepare("UPDATE trips SET main_destination = ? WHERE id = ?");
            $stmt->execute([$main_destination, $trip_id]);

            $stmt = $pdo->prepare("DELETE FROM stopovers WHERE trip_id = ?");
            $stmt->execute([$trip_id]);

            foreach ($stopover_locations as $index => $location) {
                $stmt = $pdo->prepare("INSERT INTO stopovers (trip_id, location, position) VALUES (?, ?, ?)");
                $stmt->execute([$trip_id, $location, $index + 1]);  
            }
        }

        header("Location: step_3.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schritt 2: Zwischenstopps und Hauptziel</title>
</head>
<body>
    <h1>Schritt 2: Bestimmen Sie Ihr Hauptziel und die Zwischenstopps</h1>

    <form action="step_2.php" method="post">
        <h2>Hauptziel</h2>
        <input type="text" name="main_destination" required placeholder="Hauptziel eingeben" value="<?php echo htmlspecialchars($main_destination ?? '', ENT_QUOTES); ?>">

        <h2>Zwischenstopps</h2>
        <div id="stopover-fields">
            <?php if ($stopovers): ?>
                <?php foreach ($stopovers as $stopover): ?>
                    <div class="stopover-field" id="stopover_<?php echo $stopover['id']; ?>">
                        <label for="stopover_<?php echo $stopover['id']; ?>">Zwischenstopp <?php echo $stopover['position']; ?>:</label>
                        <input type="text" name="stopover_locations[]" required placeholder="Ort hinzufügen" value="<?php echo htmlspecialchars($stopover['location'], ENT_QUOTES); ?>">
                        <button type="submit" name="delete_stopover_id" value="<?php echo $stopover['id']; ?>">Löschen</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="stopover-field">
                    <label for="stopover_1">Zwischenstopp 1:</label>
                    <input type="text" name="stopover_locations[]" required placeholder="Ort hinzufügen">
                </div>
            <?php endif; ?>
        </div>

        <button type="button" id="add-stopover">Weiteren Zwischenstopp hinzufügen</button>
        <br><br>
        <button type="submit">Weiter</button>
    </form>

    <script>
        document.getElementById('add-stopover').addEventListener('click', function() {
            var stopoverFields = document.getElementById('stopover-fields');
            var newStopoverField = document.createElement('div');
            newStopoverField.classList.add('stopover-field');
            newStopoverField.innerHTML = `
                <label for="stopover_${stopoverFields.children.length + 1}">Zwischenstopp ${stopoverFields.children.length + 1}:</label>
                <input type="text" name="stopover_locations[]" required placeholder="Ort hinzufügen">
            `;
            stopoverFields.appendChild(newStopoverField);
        });
    </script>
</body>
</html>
