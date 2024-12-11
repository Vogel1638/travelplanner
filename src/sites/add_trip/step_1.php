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
    $user = $stmt->fetch();

    if (!$user) {
        die('Benutzer nicht gefunden');
    }

    $user_id = $user['id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $title = $_POST['title'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $max_budget = $_POST['max_budget'];
        $participants = $_POST['participants'];

        // NOTE Inserting the trip
        $sql = "INSERT INTO trips (title, start_date, end_date, max_budget, participants, user_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $start_date, $end_date, $max_budget, $participants, $user_id]);

        // NOTE Retrieve trip_id after inserting the trip
        $trip_id = $pdo->lastInsertId();

        // NOTE Save trip_id in the session
        $_SESSION['trip_id'] = $trip_id;

        header("Location: step_2.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schritt 1: Reise erstellen</title>
</head>
<body>
    <h1>Reise erstellen</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="title">Reisetitel:</label>
        <input type="text" id="title" name="title" required><br>
        
        <label for="start_date">Startdatum:</label>
        <input type="date" id="start_date" name="start_date" required><br>
        
        <label for="end_date">Enddatum:</label>
        <input type="date" id="end_date" name="end_date" required><br>
        
        <label for="max_budget">Maximales Budget:</label>
        <input type="number" id="max_budget" name="max_budget" step="0.01"><br>
        
        <label for="participants">Teilnehmeranzahl:</label>
        <input type="number" id="participants" name="participants" required><br>
        
        <button type="submit">Weiter</button>
    </form>
</body>
</html>
