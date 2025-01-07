<?php
session_start();
require '../php/db.php';  

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../php/login.php");
    exit();
}

$user_email = $_SESSION['user'];

// Retrieve the user ID from the database
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch();

if (!$user) {
    die("Benutzer nicht gefunden.");
}

$user_id = $user['user_id'];

// Retrieve trips of the user with budget and To-Do counts
$stmt = $pdo->prepare("
    SELECT trips.trip_id, trips.trip_title, trips.start_date, trips.end_date, trips.budget AS max_budget, trips.destination AS main_destination,
           (SELECT SUM(amount) FROM budgets WHERE budgets.trip_id = trips.trip_id) AS budget_used,
           (SELECT COUNT(*) FROM todos WHERE todos.trip_id = trips.trip_id) AS todo_count
    FROM trips
    WHERE trips.user_id = ?
");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            margin-top: 40px;
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

<div class="cards-container">
    <?php if (!empty($trips)): ?>
        <?php foreach ($trips as $trip): ?>
            <?php
                $budget_percentage = $trip['max_budget'] > 0 ? ($trip['budget_used'] / $trip['max_budget']) * 100 : 0;
            ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($trip['trip_title']); ?></h3>
                <p><strong>Zielort:</strong> <?php echo htmlspecialchars($trip['main_destination']); ?></p>
                <p><strong>Datum:</strong> <?php echo date('d.m.Y', strtotime($trip['start_date'])) . ' - ' . date('d.m.Y', strtotime($trip['end_date'])); ?></p>
                <p><strong>Budget:</strong> <?php echo number_format($trip['max_budget'], 2, ',', '.') . ' €'; ?></p>
                <p><strong>Ausgegeben:</strong> <?php echo number_format($trip['budget_used'] ?? 0, 2, ',', '.') . ' €'; ?></p>
                <p><strong>To-Dos:</strong> <?php echo $trip['todo_count']; ?></p>

                <div class="card-footer">
                    <a href="trip_details.php?id=<?php echo $trip['trip_id']; ?>" class="btn">Details</a>
                    <a href="edit_trip.php?id=<?php echo $trip['trip_id']; ?>" class="btn">Bearbeiten</a>
                    <a href="delete_trip.php?id=<?php echo $trip['trip_id']; ?>" class="btn">Löschen</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Du hast noch keine Reisen erstellt. <a href="add_trip/step_1.php">Jetzt eine Reise erstellen</a></p>
    <?php endif; ?>
</div>

</body>
</html>
