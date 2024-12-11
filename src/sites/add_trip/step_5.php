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

// Reisedaten holen
$stmt = $pdo->prepare("SELECT main_destination, start_location, start_date, end_date FROM trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    die('Fehler: Reise nicht gefunden.');
}

$main_destination = $trip['main_destination'];
$start_location = $trip['start_location'];
$start_date = new DateTime($trip['start_date']);
$end_date = new DateTime($trip['end_date']);

$interval = $start_date->diff($end_date);
$total_nights = $interval->days; 

// Stopover-Daten holen
$stmt = $pdo->prepare("SELECT * FROM stopovers WHERE trip_id = ? ORDER BY position ASC");
$stmt->execute([$trip_id]);
$stopovers = $stmt->fetchAll();

$days_for_stopovers = [];
$remaining_days = $total_nights;

foreach ($stopovers as $stopover) {
    $nights = $stopover['nights']; 
    $days_for_stopovers[] = [
        'location' => $stopover['location'],
        'days' => $nights, 
    ];
    $remaining_days -= $nights; 
}

$main_days = $remaining_days;

$days = [];
$current_date = clone $start_date;

foreach ($days_for_stopovers as $stopover_data) {
    for ($i = 0; $i < $stopover_data['days']; $i++) {
        $days[] = [
            'date' => $current_date->format('Y-m-d'), 
            'location' => $stopover_data['location'] 
        ];
        $current_date->modify('+1 day');
    }
}

for ($i = 0; $i < $main_days; $i++) {
    $days[] = [
        'date' => $current_date->format('Y-m-d'), 
        'location' => $main_destination 
    ];
    $current_date->modify('+1 day'); 
}

$activities = [];
foreach ($days as $day) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE trip_id = ? AND day_date = ?");
    $stmt->execute([$trip_id, $day['date']]);
    $activities[$day['date']] = $stmt->fetchAll(); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_activity'])) {
    // Holen der Formulardaten
    $day_date = $_POST['day_date'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time']; 
    $end_time = $_POST['end_time']; 
    $price = $_POST['price'];

    // Debugging: Überprüfen, ob die POST-Daten korrekt empfangen wurden
    echo '<pre>';
    var_dump($_POST);  // Zeigt alle POST-Daten an
    echo '</pre>';

    // Überprüfen, ob alle Felder ausgefüllt sind
    if (!empty($day_date) && !empty($title) && !empty($description) && !empty($start_time) && !empty($end_time) && isset($price)) {
        // Daten in die Datenbank einfügen
        $stmt = $pdo->prepare("INSERT INTO activities (trip_id, day_date, title, description, start_time, end_time, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Sicherstellen, dass $trip_id gesetzt ist, bevor der Query ausgeführt wird
        $stmt->execute([$_SESSION['trip_id'], $day_date, $title, $description, $start_time, $end_time, $price]);

        // Umleiten, um die Seite neu zu laden
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();  // Verhindert das Weiterlaufen des Skripts
    } else {
        // Wenn Felder fehlen, Fehlermeldung anzeigen
        echo "<p style='color: red;'>Alle Felder müssen ausgefüllt sein!</p>";
    }
}
?>




<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schritt 5: Tagesaktivitäten für die Reise</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            display: flex;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .tabs-container {
            flex: 1;
            margin-right: 20px;
            border-right: 2px solid #ccc;
        }

        .tab {
            padding: 12px 20px;
            background-color: #f4f4f4;
            margin-bottom: 5px;
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-weight: bold;
        }

        .tab:hover {
            background-color: #ddd;
        }

        .tab.active {
            background-color: #4CAF50;
            color: white;
        }

        .tab-content-container {
            flex: 3;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .tab-content {
            display: none;
            padding: 10px;
        }

        .tab-content.active {
            display: block;
        }

        .add-activity-btn {
            display: block;
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            text-align: center;
            margin-top: 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .add-activity-btn:hover {
            background-color: #45a049;
        }

        .activity-item {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .activity-item strong {
            font-size: 1.1em;
        }

        .no-activities {
            color: #ff6347;
        }

        /* Styling for the add activity button */
.add-activity-btn-container {
    margin-top: 20px;
}

.add-activity-btn {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 10px 15px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.add-activity-btn:hover {
    background-color: #45a049;
}

/* The form will be hidden by default */
.activity-form {
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    margin: 20px auto;
    opacity: 0;  /* Initially hidden */
    transform: translateY(-20px); /* Start position for animation */
    visibility: hidden; /* Ensures that it's not interactable when hidden */
    transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s 0.3s; /* Animation effects */
}

/* Form becomes visible with animation */
.activity-form.visible {
    opacity: 1;
    transform: translateY(0); /* End position */
    visibility: visible; /* Makes the form interactable */
    transition: opacity 0.3s ease, transform 0.3s ease;
}

/* Styling for the form fields */
.form-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-field label {
    font-weight: bold;
    color: #333;
}

.form-field input,
.form-field textarea {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    outline: none;
    transition: border-color 0.3s ease;
}

.form-field input:focus,
.form-field textarea:focus {
    border-color: #4CAF50;
}

/* Time Fields */
.time-fields {
    display: flex;
    gap: 15px;
}

.time-field {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.time-field input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    outline: none;
    transition: border-color 0.3s ease;
}

.time-field input:focus {
    border-color: #4CAF50;
}

/* Submit Button */
.submit-btn {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 10px 15px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.submit-btn:hover {
    background-color: #45a049;
}



        .next-step-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            font-size: 1.1em;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 30px;
            transition: background-color 0.3s;
        }

        .next-step-btn:hover {
            background-color: #45a049;
        }

        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .tabs-container {
                margin-right: 0;
                border-right: none;
            }

            .tab-content-container {
                margin-top: 20px;
            }
        }

    </style>
</head>
<body>

    <h1 style="text-align: center; padding: 20px; background-color: #4CAF50; color: white; margin-bottom: 0;">Schritt 5: Tagesaktivitäten für die Reise</h1>

    <div class="container">
        <!-- Tabs Container -->
        <div class="tabs-container">
            <?php foreach ($days as $index => $day): ?>
                <div class="tab" onclick="switchTab(<?php echo $index; ?>)">
                    Tag <?php echo $index + 1; ?>: <?php echo htmlspecialchars($day['location']); ?> (<?php 
                    $date = new DateTime($day['date']);
                    echo $date->format('d.m.Y'); // Schweizer Datum im Format TT.MM.JJJJ
                    ?>)
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Tab Content -->
        <div class="tab-content-container">
            <?php foreach ($days as $index => $day): ?>
                <div class="tab-content">
                    <h3>Tag <?php echo $index + 1; ?> - <?php echo htmlspecialchars($day['location']); ?></h3>
                    <p><strong>Datum:</strong> <?php echo $day['date']; ?></p>
                    
                    <?php if (count($activities[$day['date']]) > 0): ?>
                        <?php foreach ($activities[$day['date']] as $activity): ?>
                            <div class="activity-item">
                                <strong><?php echo htmlspecialchars($activity['title']); ?></strong><br>
                                <?php echo htmlspecialchars($activity['description']); ?><br>
                                <span><strong>Zeit:</strong> <?php echo $activity['start_time']; ?> bis <?php echo $activity['end_time']; ?></span><br>
                                <span><strong>Preis:</strong> €<?php echo number_format($activity['price'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-activities">Keine Aktivitäten für diesen Tag erstellt.</div>
                    <?php endif; ?>

                    <div class="add-activity-btn-container">
                        <button class="add-activity-btn" onclick="toggleActivityForm('<?php echo $day['date']; ?>')">+ Neue Aktivität hinzufügen</button>
                    </div>

                    <div id="activity-form-<?php echo $day['date']; ?>" class="activity-form hidden">
                        <h3 class="form-title">Neue Aktivität hinzufügen</h3>
                        <form method="POST" class="activity-form-content">
                            <!-- Hidden input for day date -->
                            <input type="hidden" name="day_date" value="<?php echo $day['date']; ?>">

                            <!-- Title Field -->
                            <div class="form-field">
                                <label for="title">Titel der Aktivität</label>
                                <input type="text" name="title" placeholder="Z.B. Stadtrundfahrt" required>
                            </div>

                            <!-- Description Field -->
                            <div class="form-field">
                                <label for="description">Beschreibung</label>
                                <textarea name="description" placeholder="Details zur Aktivität..." required></textarea>
                            </div>

                            <!-- Time Fields -->
                            <div class="time-fields">
                                <div class="time-field">
                                    <label for="start_time">Startzeit</label>
                                    <input type="time" name="start_time" required>
                                </div>
                                <div class="time-field">
                                    <label for="end_time">Endzeit</label>
                                    <input type="time" name="end_time" required>
                                </div>
                            </div>

                            <!-- Price Field -->
                            <div class="form-field">
                                <label for="price">Preis (€)</label>
                                <input type="number" name="price" placeholder="Preis der Aktivität" step="0.01" required>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" name="add_activity" class="submit-btn">Aktivität hinzufügen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <a href="step_6.php">
        <button class="next-step-btn">Weiter zu Schritt 6</button>
    </a>

    <script>
        function switchTab(index) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => tab.classList.remove('active')); 
            contents.forEach(content => content.classList.remove('active')); 
            
            tabs[index].classList.add('active'); 
            contents[index].classList.add('active'); 
        }

        function toggleActivityForm(dayDate) {
    const form = document.getElementById('activity-form-' + dayDate);
    
    // Toggle the visibility of the form
    if (form.classList.contains('visible')) {
        form.classList.remove('visible');
    } else {
        form.classList.add('visible');
    }
}


        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            if (tabs.length > 0 && contents.length > 0) {
                switchTab(0);
            }
        });
    </script>

</body>
</html>
