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
    $stmt = $pdo->prepare("SELECT destination AS location FROM trips WHERE trip_id = ?
        UNION ALL
        SELECT name AS location FROM destinations WHERE trip_id = ?");
    $stmt->execute([$trip_id, $trip_id]);
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titles = $_POST['titles'] ?? [];
        $dates = $_POST['dates'] ?? [];
        $descriptions = $_POST['descriptions'] ?? [];
        $tags = $_POST['tags'] ?? [];
        $priorities = $_POST['priorities'] ?? [];
        $locations = $_POST['locations'] ?? [];

        // Delete existing to-dos
        $stmt = $pdo->prepare("DELETE FROM todos WHERE trip_id = ?");
        $stmt->execute([$trip_id]);

        // Save new to-dos
        foreach ($titles as $index => $title) {
            if (!empty($title)) {
                $stmt = $pdo->prepare("
                    INSERT INTO todos (trip_id, location, title, due_date, description, tags, priority) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $trip_id,
                    $locations[$index],
                    $title,
                    $dates[$index],
                    $descriptions[$index],
                    $tags[$index],
                    $priorities[$index]
                ]);
            }
        }

        header("Location: summary.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Dos erstellen</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <link rel="stylesheet" href="../../public/assets/css/add_trip.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 20px;
            color: #343a40;
        }

        .tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tabs button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .tabs button:hover,
        .tabs button.active {
            background-color: #0056b3;
        }

        .content {
            display: none;
        }

        .content.active {
            display: block;
        }

        .add-todo-container {
            text-align: left;
            margin-bottom: 20px;
        }

        .add-todo-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .add-todo-container button:hover {
            background-color: #0056b3;
        }

        .todo-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            align-items: flex-start;
        }

        .todo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .todo-checkbox {
            margin-right: 15px;
            flex-shrink: 0;
        }

        .todo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            width: 100%;
        }

        .todo-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #343a40;
        }

        .todo-header .priority {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
        }

        .priority-low {
            background-color: #28a745;
        }

        .priority-medium {
            background-color: #ffc107;
        }

        .priority-high {
            background-color: #dc3545;
        }

        .todo-body {
            margin-top: 10px;
            font-size: 1rem;
            color: #495057;
        }

        .todo-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .tag {
            background: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .todo-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }

        .todo-footer button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .todo-footer button:hover {
            background-color: #bd2130;
        }
    </style>
    <script>
        function switchTab(tabId) {
            const tabs = document.querySelectorAll('.content');
            tabs.forEach(tab => tab.classList.remove('active'));

            const activeTab = document.getElementById(tabId);
            activeTab.classList.add('active');

            const buttons = document.querySelectorAll('.tabs button');
            buttons.forEach(button => button.classList.remove('active'));

            document.querySelector(`button[data-tab="${tabId}"]`).classList.add('active');
        }

        function addTodo(location) {
            const container = document.getElementById(`todos-${location}`);
            const todoCard = document.createElement('div');
            todoCard.classList.add('todo-card');

            todoCard.innerHTML = `
                <input type="checkbox" class="todo-checkbox">
                <div style="flex-grow: 1;">
                    <div class="todo-header">
                        <h3><input type="text" name="titles[]" placeholder="Titel" required></h3>
                        <select name="priorities[]" required>
                            <option value="Low" class="priority-low">Niedrig</option>
                            <option value="Medium" class="priority-medium">Mittel</option>
                            <option value="High" class="priority-high">Hoch</option>
                        </select>
                    </div>
                    <div class="todo-body">
                        <input type="date" name="dates[]" required>
                        <textarea name="descriptions[]" placeholder="Beschreibung (optional)" rows="2"></textarea>
                    </div>
                    <div class="todo-tags">
                        <input type="text" name="tags[]" placeholder="Tags (optional)">
                    </div>
                    <div class="todo-footer">
                        <button type="button" onclick="removeTodo(this)">Entfernen</button>
                    </div>
                </div>
            `;
            container.prepend(todoCard);
        }

        function removeTodo(button) {
            const todoCard = button.parentElement.parentElement.parentElement;
            todoCard.remove();
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>To-Dos erstellen</h1>
        <div class="tabs">
            <button class="active" data-tab="general-tab" onclick="switchTab('general-tab')">Allgemein</button>
            <?php foreach ($locations as $location): ?>
                <button data-tab="<?php echo htmlspecialchars($location); ?>-tab" onclick="switchTab('<?php echo htmlspecialchars($location); ?>-tab')">
                    <?php echo htmlspecialchars($location); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <div id="general-tab" class="content active">
                <div class="add-todo-container">
                    <button type="button" onclick="addTodo('General')">+ To-Do hinzufügen</button>
                </div>
                <div id="todos-General"></div>
            </div>

            <?php foreach ($locations as $location): ?>
                <div id="<?php echo htmlspecialchars($location); ?>-tab" class="content">
                    <div class="add-todo-container">
                        <button type="button" onclick="addTodo('<?php echo htmlspecialchars($location); ?>')">+ To-Do hinzufügen</button>
                    </div>
                    <div id="todos-<?php echo htmlspecialchars($location); ?>"></div>
                </div>
            <?php endforeach; ?>

            <div style="text-align: center; margin-top: 20px;">
                <button type="submit">Weiter</button>
            </div>
        </form>
    </div>
</body>
</html>

