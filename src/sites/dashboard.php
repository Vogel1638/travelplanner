<?php
session_start();
require '../php/db.php'; 

// NOTE Check whether the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../php/login.php");
    exit();
}

$user_email = $_SESSION['user'];

// NOTE Retrieving the user ID from the database
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
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/fonts.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <link rel="stylesheet" href="../../public/assets/css/dashboard.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
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
            margin-top: 70px;
            padding: 50px 20px;
            background-image: url('../assets/images/hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
        }

        .hero h1 {
            font-size: 36px;
        }

        .trip-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            padding: 20px;
        }

        .trip-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            width: 300px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .trip-card h3 {
            margin: 10px 0;
        }

        .budget-bar {
            width: 100%;
            height: 10px;
            background-color: #ddd;
            border-radius: 5px;
            margin-top: 10px;
        }

        .budget-bar span {
            display: block;
            height: 100%;
            border-radius: 5px;
        }

        .icons {
            margin-top: 10px;
            display: flex;
            justify-content: space-around;
        }

        .icons a {
            font-size: 18px;
            color: #333;
            text-decoration: none;
        }

        .no-trips-message {
            text-align: center;
            font-size: 20px;
            color: #888;
            padding: 50px;
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

<div class="hero">
    <h1>Willkommen zu deinem Dashboard!</h1>
    <p>Verwalte deine Reisen, Budgets, To-Dos und mehr.</p>
</div>

<div class="trip-cards">
    <?php if (empty($trips)): ?>
        <p class="no-trips-message">Bis jetzt keine Reisen erstellt.</p>
    <?php else: ?>
        <?php foreach ($trips as $trip): ?>
            <?php
                $budget_percentage = $trip['max_budget'] > 0 ? ($trip['max_budget'] / 100) * 100 : 0;
            ?>
            <div class="trip-card">
                <h3><?php echo htmlspecialchars($trip['title']); ?></h3>
                <p><strong>Zielort:</strong> <?php echo htmlspecialchars($trip['main_destination']); ?></p>
                <p><strong>Datum:</strong> <?php echo date('d.m.Y', strtotime($trip['start_date'])) . ' - ' . date('d.m.Y', strtotime($trip['end_date'])); ?></p>

                <div class="budget-bar">
                    <span style="width: <?php echo $budget_percentage; ?>%; background-color: <?php echo $budget_percentage >= 75 ? 'red' : ($budget_percentage >= 50 ? 'orange' : 'green'); ?>;"></span>
                </div>
                <p>Budget: <?php echo number_format($trip['max_budget'], 2, ',', '.'); ?> €</p>

                <div class="icons">
                    <a href="trip_details.php?trip_id=<?php echo $trip['id']; ?>" title="Detailansicht">&#128065;</a>
                    <a href="trip_edit.php?trip_id=<?php echo $trip['id']; ?>" title="Bearbeiten">&#9998;</a>
                    <a href="trip_delete.php?trip_id=<?php echo $trip['id']; ?>" title="Löschen" onclick="return confirm('Bist du sicher, dass du diese Reise löschen möchtest?')">&#128465;</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
