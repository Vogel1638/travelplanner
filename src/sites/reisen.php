<?php
session_start();
require '../php/db.php';  

ini_set('display_errors', 1);
error_reporting(E_ALL);

// NOTE User logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../php/login.php");
    exit();
}

$user_email = $_SESSION['user'];

// NOTE User ID from the database
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch();

if (!$user) {
    die("Benutzer nicht gefunden.");
}

$user_id = $user['id'];

// NOTE Travelling of the user
$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ?");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reisen</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/fonts.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <link rel="stylesheet" href="../../public/assets/css/reisen.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px;
        }

        .navbar .logo {
            font-size: 24px;
        }

        .navbar .auth-links {
            display: flex;
        }

        .navbar .auth-links a {
            margin-left: 10px;
        }

        .hero {
            background-image: url('../../public/assets/images/beach.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 20px;
        }

        .hero h1 {
            font-size: 2.5rem;
        }

        .cta-button {
            background-color: #3498db;
            color: white;
            font-size: 1.2rem;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .cta-button:hover {
            background-color: #2980b9;
        }

        .cards-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 300px;
            margin: 10px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            font-size: 1.6rem;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .card .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-footer .btn {
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
        }

        .card-footer .btn:hover {
            background-color: #2980b9;
        }

        .list-view {
            display: none;
            margin: 20px;
            padding: 10px;
        }

        .list-view ul {
            list-style: none;
            padding: 0;
        }

        .list-view li {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-view li .list-title {
            font-size: 1.2rem;
        }

        .list-view li .list-actions {
            display: flex;
            gap: 10px;
        }

        .view-toggle {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .view-toggle button {
            padding: 10px 20px;
            border: none;
            background-color: #3498db;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 5px;
        }

        .view-toggle button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="logo"><a href="#">Travel<span class="secondary">Planner</span></a></div>
        <div class="auth-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="reisen.php">Reisen</a>
            <a href="../php/logout.php">Logout</a>
        </div>
    </div>

    <section class="hero">
        <h1>Entdecke Deine Reisen</h1>
        <p>Plane deine Abenteuer und behalte alles im Blick!</p>
        <a href="add_trip/step_1.php" class="cta-button">Reise erstellen</a>
    </section>

    <div class="view-toggle">
        <button id="card-view-btn">Kartenansicht</button>
        <button id="list-view-btn">Listenansicht</button>
    </div>

    <div class="cards-container" id="cards-view">
        <?php if ($trips): ?>
            <?php foreach ($trips as $trip): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($trip['title']); ?></h3>
                    <p><strong>Zielort:</strong> <?php echo htmlspecialchars($trip['main_destination']); ?></p>
                    <p><strong>Datum:</strong> <?php echo date('d.m.Y', strtotime($trip['start_date'])) . ' - ' . date('d.m.Y', strtotime($trip['end_date'])); ?></p>
                    <p><strong>Budget:</strong> <?php echo number_format($trip['max_budget'], 2, ',', '.') . ' €'; ?></p>

                    <div class="card-footer">
                        <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn">Details</a>
                        <a href="edit_trip.php?id=<?php echo $trip['id']; ?>" class="btn">Bearbeiten</a>
                        <a href="delete_trip.php?id=<?php echo $trip['id']; ?>" class="btn">Löschen</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Du hast noch keine Reisen erstellt. <a href="add_trip/step_1.php">Jetzt eine Reise erstellen</a></p>
        <?php endif; ?>
    </div>

    <div class="list-view" id="list-view">
        <?php if ($trips): ?>
            <ul>
                <?php foreach ($trips as $trip): ?>
                    <li>
                        <div class="list-title"><?php echo htmlspecialchars($trip['title']); ?></div>
                        <div class="list-actions">
                            <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn">Details</a>
                            <a href="edit_trip.php?id=<?php echo $trip['id']; ?>" class="btn">Bearbeiten</a>
                            <a href="delete_trip.php?id=<?php echo $trip['id']; ?>" class="btn">Löschen</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Du hast noch keine Reisen erstellt. <a href="add_trip/step_1.php">Jetzt eine Reise erstellen</a></p>
        <?php endif; ?>
    </div>

    <script>
        // NOTE Toggle between the map and list view
        document.getElementById('card-view-btn').addEventListener('click', function() {
            document.getElementById('cards-view').style.display = 'flex';
            document.getElementById('list-view').style.display = 'none';
        });

        document.getElementById('list-view-btn').addEventListener('click', function() {
            document.getElementById('cards-view').style.display = 'none';
            document.getElementById('list-view').style.display = 'block';
        });

        // NOTE Show the map view by default
        document.getElementById('cards-view').style.display = 'flex';
        document.getElementById('list-view').style.display = 'none';
    </script>

</body>
</html>
