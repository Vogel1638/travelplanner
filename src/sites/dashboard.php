<?php
    session_start();
    require '../php/db.php'; 

    // Check whether the user is logged in
    if (!isset($_SESSION['user'])) {
        header("Location: ../php/login.php");
        exit();
    }

    $user_email = $_SESSION['user'];

    // Retrieving the user ID from the database
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Benutzer nicht gefunden.");
    }

    $user_id = $user['user_id'];

    // Retrieve trips of the user
    $stmt = $pdo->prepare("
        SELECT trips.trip_id, trips.trip_title, trips.start_date, trips.end_date, trips.budget AS max_budget, trips.destination AS main_destination,
            (SELECT SUM(amount) FROM budgets WHERE budgets.trip_id = trips.trip_id) AS budget_used
        FROM trips
        WHERE trips.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retrieve overall budget overview
    $totalBudgetStmt = $pdo->prepare("SELECT SUM(budget) AS total_budget FROM trips WHERE user_id = ?");
    $totalBudgetStmt->execute([$user_id]);
    $totalBudget = $totalBudgetStmt->fetchColumn();

    $totalSpentStmt = $pdo->prepare("
        SELECT SUM(amount) AS total_spent 
        FROM budgets 
        WHERE trip_id IN (SELECT trip_id FROM trips WHERE user_id = ?)
    ");
    $totalSpentStmt->execute([$user_id]);
    $totalSpent = $totalSpentStmt->fetchColumn();

    // Retrieve all To-Dos
    $todosStmt = $pdo->prepare("
        SELECT todos.title, todos.description, todos.due_date, todos.completed, trips.trip_title
        FROM todos
        INNER JOIN trips ON todos.trip_id = trips.trip_id
        WHERE trips.user_id = ?
        ORDER BY todos.due_date ASC
    ");
    $todosStmt->execute([$user_id]);
    $todos = $todosStmt->fetchAll(PDO::FETCH_ASSOC);
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

<!-- Trip Cards Section -->
<div class="trip-cards">
    <?php if (empty($trips)): ?>
        <p class="no-trips-message">Bis jetzt keine Reisen erstellt.</p>
    <?php else: ?>
        <?php foreach ($trips as $trip): ?>
            <?php
                $budget_percentage = $trip['max_budget'] > 0 ? ($trip['budget_used'] / $trip['max_budget']) * 100 : 0;
            ?>
            <div class="trip-card">
                <h3><?php echo htmlspecialchars($trip['trip_title']); ?></h3>
                <p><strong>Zielort:</strong> <?php echo htmlspecialchars($trip['main_destination']); ?></p>
                <p><strong>Datum:</strong> <?php echo date('d.m.Y', strtotime($trip['start_date'])) . ' - ' . date('d.m.Y', strtotime($trip['end_date'])); ?></p>

                <div class="budget-bar">
                    <span style="width: <?php echo $budget_percentage; ?>%; background-color: <?php echo $budget_percentage >= 75 ? 'red' : ($budget_percentage >= 50 ? 'orange' : 'green'); ?>;"></span>
                </div>
                <p>Budget: <?php echo number_format($trip['max_budget'], 2, ',', '.'); ?> €</p>
                <p>Ausgegeben: <?php echo number_format($trip['budget_used'] ?? 0, 2, ',', '.'); ?> €</p>

                <div class="icons">
                    <a href="trip_details.php?trip_id=<?php echo $trip['trip_id']; ?>" title="Detailansicht">&#128065;</a>
                    <a href="trip_edit.php?trip_id=<?php echo $trip['trip_id']; ?>" title="Bearbeiten">&#9998;</a>
                    <a href="trip_delete.php?trip_id=<?php echo $trip['trip_id']; ?>" title="Löschen" onclick="return confirm('Bist du sicher, dass du diese Reise löschen möchtest?')">&#128465;</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- General Budget Overview Section -->
<section>
    <h2>Allgemeine Budgetübersicht</h2>
    <p><strong>Gesamtbudget:</strong> <?php echo number_format($totalBudget, 2, ',', '.'); ?> €</p>
    <p><strong>Gesamtausgaben:</strong> <?php echo number_format($totalSpent ?? 0, 2, ',', '.'); ?> €</p>
    <p><strong>Verfügbar:</strong> <?php echo number_format(($totalBudget - $totalSpent) ?? 0, 2, ',', '.'); ?> €</p>
</section>

<!-- To-Dos Section -->
<section>
    <h2>To-Dos</h2>
    <div class="todo-list">
        <?php if (!empty($todos)): ?>
            <ul>
                <?php foreach ($todos as $todo): ?>
                    <li>
                        <h3><?php echo htmlspecialchars($todo['title']); ?> (<?php echo htmlspecialchars($todo['trip_title']); ?>)</h3>
                        <p><?php echo htmlspecialchars($todo['description']); ?></p>
                        <p><strong>Fällig:</strong> <?php echo date('d.m.Y', strtotime($todo['due_date'])); ?></p>
                        <p><strong>Status:</strong> <?php echo $todo['completed'] ? 'Erledigt' : 'Offen'; ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Keine To-Dos vorhanden.</p>
        <?php endif; ?>
    </div>
</section>

</body>
</html>
