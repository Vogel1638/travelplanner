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

    // Retrieve destinations
    $stmt = $pdo->prepare("SELECT destination FROM trips WHERE trip_id = ?");
    $stmt->execute([$trip_id]);
    $main_destination = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT name FROM destinations WHERE trip_id = ?");
    $stmt->execute([$trip_id]);
    $locations = array_merge([$main_destination], $stmt->fetchAll(PDO::FETCH_COLUMN));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $activities = $_POST['activities'] ?? [];
        $descriptions = $_POST['descriptions'] ?? [];
        $times = $_POST['times'] ?? [];
        $prices = $_POST['prices'] ?? [];
        $tags = $_POST['tags'] ?? [];

        // Delete existing activities
        $stmt = $pdo->prepare("DELETE FROM activities WHERE trip_id = ?");
        $stmt->execute([$trip_id]);

        // Save new activities
        foreach ($activities as $index => $activity) {
            if (!empty($activity)) {
                $stmt = $pdo->prepare("
                    INSERT INTO activities (trip_id, location, activity, description, time, price, tags) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $trip_id,
                    $_POST['locations'][$index],
                    $activity,
                    $descriptions[$index],
                    $times[$index],
                    $prices[$index],
                    $tags[$index]
                ]);
            }
        }

        header("Location: step_6.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiseerstellung - Tagesplan</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
        }

        .container {
            display: flex;
            width: 90%;
            margin: 50px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .tabs {
            flex: 1;
            background: #f4f4f4;
            padding: 20px;
            border-right: 1px solid #ddd;
        }

        .tabs button {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            background: #3498db;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            text-align: left;
            transition: background 0.3s;
        }

        .tabs button.active {
            background: #2980b9;
        }

        .tabs button:hover {
            background: #1d6fa5;
        }

        .content {
            flex: 3;
            padding: 20px;
            background: #f9f9f9;
        }

        .activity-container {
            display: none;
        }

        .activity-container.active {
            display: block;
        }

        .activity-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .activity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .activity-card input, .activity-card textarea {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .activity-card button {
            margin-top: 10px;
            background-color: red;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
        }

        .add-activity {
            background-color: #2ecc71;
            margin-top: 10px;
            color: white;
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }

        .add-activity:hover {
            background-color: #27ae60;
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
    </style>
    <script>
        function showTab(location) {
            const containers = document.querySelectorAll('.activity-container');
            containers.forEach(container => container.classList.remove('active'));

            const activeContainer = document.getElementById(`activities-${location}`);
            activeContainer.classList.add('active');
        }

        function addActivity(location) {
            const container = document.getElementById(`activities-${location}`);
            const card = document.createElement('div');
            card.classList.add('activity-card');

            card.innerHTML = `
                <input type="hidden" name="locations[]" value="${location}">
                <input type="text" name="activities[]" placeholder="Aktivität" required>
                <textarea name="descriptions[]" placeholder="Beschreibung (optional)" rows="3"></textarea>
                <input type="text" name="times[]" placeholder="Zeit (z.B. 15:00 - 16:00)" required>
                <input type="number" name="prices[]" placeholder="Preis (CHF)" step="0.01" required>
                <input type="text" name="tags[]" placeholder="Tags (z.B. Natur, Erkundung)">
                <button type="button" onclick="removeActivity(this)">Entfernen</button>
            `;
            container.appendChild(card);
        }

        function removeActivity(button) {
            const card = button.parentElement;
            card.remove();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="tabs">
            <?php foreach ($locations as $index => $location): ?>
                <button class="<?php echo $index === 0 ? 'active' : ''; ?>" onclick="showTab('<?php echo htmlspecialchars($location); ?>')">
                    <?php echo htmlspecialchars($location); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="content">
            <form method="POST">
                <?php foreach ($locations as $index => $location): ?>
                    <div class="activity-container <?php echo $index === 0 ? 'active' : ''; ?>" id="activities-<?php echo htmlspecialchars($location); ?>">
                        <h2>Aktivitäten für <?php echo htmlspecialchars($location); ?></h2>
                        <div class="add-activity" onclick="addActivity('<?php echo htmlspecialchars($location); ?>')">
                            + Aktivität hinzufügen
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit">Weiter</button>
            </form>
        </div>
    </div>
</body>
</html>
