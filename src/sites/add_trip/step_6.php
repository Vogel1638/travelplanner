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

    $stmt = $pdo->prepare("SELECT main_destination FROM trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $main_location = $stmt->fetchColumn();

    $locations = ['Allgemein', $main_location ];

    $stmt = $pdo->prepare("SELECT DISTINCT location FROM stopovers WHERE trip_id = ?");
    $stmt->execute([$trip_id]);
    $stopovers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $locations = array_merge($locations, $stopovers);

    $stmt = $pdo->prepare("
        SELECT * FROM todos 
        WHERE trip_id = ? 
        ORDER BY is_completed ASC, priority DESC, deadline ASC
    ");
    $stmt->execute([$trip_id]);
    $todos = $stmt->fetchAll();

    $todos_by_location = [];
    foreach ($todos as $todo) {
        $location = $todo['location'] ?: 'Allgemein';
        $todos_by_location[$location][] = $todo;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_todo'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $deadline = $_POST['deadline'];
        $priority = isset($_POST['priority']) ? 1 : 0;

        $location = $_POST['location'] !== 'Allgemein' ? $_POST['location'] : 'Allgemein';

        $stmt = $pdo->prepare("INSERT INTO todos (trip_id, title, description, deadline, priority, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$trip_id, $title, $description, $deadline, $priority, $location]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do-Liste</title>
    <style>
        .content {
            display: flex;
        }

        .tabs-container {
            display: flex;
            flex-direction: column;
            padding-right: 10px;
            border-right: 2px solid #ccc;
            max-width: 200px;
        }

        .tab {
            padding: 10px;
            background-color: #f4f4f4;
            margin-bottom: 2px;
            cursor: pointer;
            border: 1px solid #ddd;
            transition: background-color 0.3s;
        }

        .tab.active {
            background-color: #4CAF50;
            color: white;
        }

        .tab-content-container {
            display: flex;
            flex-direction: column;
            padding: 10px;
            flex: 1;
        }

        .tab-content {
            display: none;
            padding: 10px;
            background-color: #f9f9f9;
            margin: 5px 0;
        }

        .tab-content.active {
            display: block;
        }

        .todo-item {
            margin: 5px 0;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #fdfdfd;
        }

        .todo-item.completed {
            text-decoration: line-through;
            color: #888;
        }

        .priority-label {
            background-color: red;
            color: white;
            padding: 2px 5px;
            font-size: 12px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>To-Do-Liste</h1>

    <div class="content">
        <div class="tabs-container">
            <?php foreach ($locations as $index => $location): ?>
                <div class="tab" onclick="switchTab(<?php echo $index; ?>)">
                    <?php echo htmlspecialchars($location); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="tab-content-container">
            <?php foreach ($locations as $index => $location): ?>
                <div class="tab-content">
                    <h3><?php echo htmlspecialchars($location); ?></h3>
                    
                    <?php if (!empty($todos_by_location[$location])): ?>
                        <?php foreach ($todos_by_location[$location] as $todo): ?>
                            <div class="todo-item <?php echo $todo['is_completed'] ? 'completed' : ''; ?> <?php echo $todo['priority'] ? 'priority' : ''; ?>">
                                <input type="checkbox" onchange="location.href='?toggle_todo_id=<?php echo $todo['id']; ?>'" <?php echo $todo['is_completed'] ? 'checked' : ''; ?>>
                                <?php if ($todo['priority']): ?>
                                    <span class="priority-label">Priorität</span>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($todo['title']); ?></strong><br>
                                <p><?php echo htmlspecialchars($todo['description']); ?></p>
                                <small>Deadline: <?php echo htmlspecialchars($todo['deadline']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Keine To-Dos vorhanden.</p>
                    <?php endif; ?>

                    <button onclick="document.getElementById('todo-form-<?php echo $index; ?>').style.display = 'block';">+ Neues To-Do</button>
                    <div id="todo-form-<?php echo $index; ?>" style="display:none;">
                        <form method="POST">
                            <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                            <input type="text" name="title" placeholder="Titel" required><br>
                            <textarea name="description" placeholder="Beschreibung" required></textarea><br>
                            <input type="date" name="deadline" required><br>
                            <label><input type="checkbox" name="priority"> Priorität</label><br>
                            <button type="submit" name="add_todo">Hinzufügen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        let activeTabIndex = 0;

        function switchTab(index) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));
            
            tabs[index].classList.add('active');
            contents[index].classList.add('active');
            activeTabIndex = index;
        }

        document.addEventListener('DOMContentLoaded', () => {
            switchTab(activeTabIndex);
        });
    </script>
</body>
</html>
