<?php
    session_start();
    require '../../php/db.php'; 

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    if (!isset($_SESSION['user'])) {
        die('Benutzer nicht eingeloggt');
    }

    $user_email = $_SESSION['user'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['id'];
    } else {
        die('Benutzer existiert nicht in der Datenbank');
    }

    $trip_id = $_SESSION['trip_id'];

    // NOTE Travel information
    $stmt_trip = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
    $stmt_trip->execute([$trip_id]);
    $trip = $stmt_trip->fetch();

    // NOTE Main destination and stopovers
    $stmt_stopovers = $pdo->prepare("SELECT * FROM stopovers WHERE trip_id = ? ORDER BY position");
    $stmt_stopovers->execute([$trip_id]);
    $stopovers = $stmt_stopovers->fetchAll();

    // NOTE Types of transport
    $stmt_transport = $pdo->prepare("SELECT transport_mode FROM stopovers WHERE trip_id = ?");
    $stmt_transport->execute([$trip_id]);
    $transport_modes = $stmt_transport->fetchAll(PDO::FETCH_COLUMN);

    // NOTE Hotel overview
    $stmt_hotels = $pdo->prepare("SELECT location, hotel, nights FROM stopovers WHERE trip_id = ?");
    $stmt_hotels->execute([$trip_id]);
    $hotels = $stmt_hotels->fetchAll();

    // NOTE Daily activities
    $stmt_activities = $pdo->prepare("SELECT * FROM activities WHERE trip_id = ? ORDER BY day_date");
    $stmt_activities->execute([$trip_id]);
    $activities = $stmt_activities->fetchAll();

    // NOTE ToDo
    $stmt_todos = $pdo->prepare("SELECT * FROM todos WHERE trip_id = ? ORDER BY deadline");
    $stmt_todos->execute([$trip_id]);
    $todos = $stmt_todos->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiseplanung - Schritt 7</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        .tabs {
            display: flex;
            flex-direction: column;
        }

        .tab-titles {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
        }

        .tab-titles li {
            flex: 1;
            padding: 10px;
            background: #f4f4f4;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .tab-titles li.active {
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }

        .tab-content {
            display: none;
            padding: 15px;
            background: #f9f9f9;
            border-top: 2px solid #ccc;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content h3 {
            margin-top: 0;
        }

        .hotel-card {
        background: #fff;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .hotel-card h5, .hotel-card h4 {
        margin-top: 0;
        color: #4CAF50;
    }

    .main-destination {
        background: #f9f9f9;
        border: 2px solid #4CAF50;
    }

    .stopover-hotels h4 {
        margin-bottom: 15px;
        color: #333;
    }

    .stopover-hotels p {
        color: #555;
    }

    @media (max-width: 600px) {
        .hotel-card {
            padding: 10px;
            font-size: 0.9em;
        }
    }
</style>
    </style>

</head>
<body>
    <div class="tabs">
        <ul class="tab-titles">
            <li class="active" data-tab="reiseinfo">Reiseinformationen</li>
            <li data-tab="hauptziel">Hauptziel und Zwischenstopps</li>
            <li data-tab="transport">Transportarten</li>
            <li data-tab="hotels">Hotelübersicht</li>
            <li data-tab="activities">Tagesaktivitäten</li>
            <li data-tab="todos">ToDo Übersicht</li>
        </ul>

        <div class="tab-content" id="reiseinfo">
            <h3>Reiseinformationen</h3>
            <p><strong>Titel:</strong> <?= $trip['title'] ?></p>
            
            <?php
                $start_date = new DateTime($trip['start_date']);
                $end_date = new DateTime($trip['end_date']);
            ?>
            <p><strong>Startdatum:</strong> <?= $start_date->format('d.m.Y') ?></p>
            <p><strong>Enddatum:</strong> <?= $end_date->format('d.m.Y') ?></p>
            <p><strong>Budget:</strong> <?= $trip['max_budget'] ?> CHF</p>
            <p><strong>Teilnehmer:</strong> <?= $trip['participants'] ?></p>
        </div>


        <div class="tab-content" id="hauptziel">
            <h3>Hauptziel und Zwischenstopps</h3>
            <p><strong>Hauptziel:</strong> <?= $trip['main_destination'] ?></p>
            <h4>Zwischenstopps:</h4>
            <ul>
                <?php foreach ($stopovers as $stopover): ?>
                    <li><?= $stopover['location'] ?></li>
                <?php endforeach; ?>
            </ul>
        </div>


        <div class="tab-content" id="transport">
            <h3>Transportarten zwischen den Stopps</h3>
            <ul>
                <?php
                $start_location = $trip['start_location']; 

                if (count($stopovers) > 0) {
                    $loop_counter = 0;
                    $previous_location = $start_location;
                    
                    // NOTE Transport from the starting point to the stopovers
                    foreach ($stopovers as $stopover) {
                        if (isset($stopover['location']) && isset($transport_modes[$loop_counter])) {
                            $transport_mode = $transport_modes[$loop_counter];
                            
                            echo "<li>Von $previous_location nach " . $stopover['location'] . " mit: $transport_mode</li>";

                            $previous_location = $stopover['location'];
                        }
                        $loop_counter++;
                    }

                    // NOTE From the last stopover to the main destination
                    if (isset($transport_modes[$loop_counter])) {
                        $transport_mode = $transport_modes[$loop_counter];
                        echo "<li>Von " . $previous_location . " nach " . $trip['main_destination'] . " mit: $transport_mode</li>";
                    } else {
                        // NOTE No means of transport available for the last section
                        echo "<li>Kein Transportmittel angegeben von " . $previous_location . " nach " . $trip['main_destination'] . "</li>";
                    }
                } else {
                    // NOTE No intermediate stops available
                    if (isset($transport_modes[$loop_counter])) {
                        $transport_mode = $transport_modes[$loop_counter]; 
                        echo "<li>Von $start_location nach " . $trip['main_destination'] . " mit: $transport_mode</li>";
                    } else {
                        echo "<li>Kein Transportmittel angegeben von $start_location nach " . $trip['main_destination'] . "</li>";
                    }
                }
                ?>
            </ul>
        </div>

        <div class="tab-content" id="hotels">
            <h3>Hotelübersicht</h3>
            <div class="hotel-card main-destination">
                <h4><i class="fas fa-map-marker-alt"></i> Hauptziel Hotel:</h4>
                <p><strong>Ort:</strong> <?= $trip['main_destination'] ?></p>
                <?php if (!empty($trip['hotel'])): ?>
                    <p><strong>Hotel:</strong> <?= $trip['hotel'] ?></p>
                    <p><strong>Übernachtungen:</strong> <?= $trip['nights'] ?></p>
                <?php else: ?>
                    <p><em>Kein Hotel angegeben.</em></p>
                <?php endif; ?>
            </div>

            <div class="stopover-hotels">
                <h4>Hotels in Zwischenstopps:</h4>
                <?php if (!empty($hotels)): ?>
                    <?php foreach ($stopovers as $stopover): ?>
                        <div class="hotel-card">
                            <h5><i class="fas fa-bed"></i> <?= $stopover['location'] ?></h5>
                            <?php 
                                $hotel = array_filter($hotels, fn($h) => $h['location'] === $stopover['location']);
                                if (!empty($hotel)):
                                    $hotel = array_values($hotel)[0];
                            ?>
                                <p><strong>Hotel:</strong> <?= $hotel['hotel'] ?></p>
                                <p><strong>Übernachtungen:</strong> <?= $hotel['nights'] ?></p>
                            <?php else: ?>
                                <p><em>Kein Hotel angegeben.</em></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><em>Keine Zwischenstopps vorhanden.</em></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="activities">
            <h3>Tagesaktivitäten</h3>
            <ul>
                <?php foreach ($activities as $activity): ?>
                    <li><?= $activity['day_date'] ?> - <?= $activity['title'] ?>: <?= $activity['description'] ?>, Preis: <?= $activity['price'] ?> CHF</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="tab-content" id="todos">
            <h3>ToDo Übersicht</h3>
            <ul>
                <?php foreach ($todos as $todo): ?>
                    <li><?= $todo['title'] ?> - <?= $todo['description'] ?> (Fällig: <?= $todo['deadline'] ?>) - <?= $todo['priority'] ? 'Hoch' : 'Niedrig' ?> - <?= $todo['is_completed'] ? 'Abgeschlossen' : 'Offen' ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
        const tabs = document.querySelectorAll('.tab-titles li');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(tab => tab.classList.remove('active'));
                contents.forEach(content => content.classList.remove('active'));

                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>
