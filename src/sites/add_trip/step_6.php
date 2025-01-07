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

    // Retrieve main destination
    $stmt_main_destination = $pdo->prepare("SELECT destination FROM trips WHERE trip_id = ?");
    $stmt_main_destination->execute([$trip_id]);
    $main_destination = $stmt_main_destination->fetchColumn();

    // Retrieve destinations
    $stmt = $pdo->prepare("
        SELECT destination AS location FROM trips WHERE trip_id = ?
        UNION ALL
        SELECT name AS location FROM destinations WHERE trip_id = ?");
    $stmt->execute([$trip_id, $trip_id]);
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Retrieve budget data per location
    $budget_data = [];
    foreach ($locations as $location) {
        // Accommodation (calculate per location, main destination if `destination_id` is empty)
        $stmt_accommodation = $pdo->prepare("
            SELECT COALESCE(SUM(h.total_price), 0) AS accommodation_total
            FROM hotels h
            LEFT JOIN destinations d ON h.destination_id = d.destination_id
            WHERE h.trip_id = ? AND (d.name = ? OR (h.destination_id IS NULL AND ? = ?))");
        $stmt_accommodation->execute([$trip_id, $location, $location, $main_destination]);
        $accommodation_total = $stmt_accommodation->fetchColumn();

        // Activities (calculate per location)
        $stmt_activities = $pdo->prepare("
            SELECT COALESCE(SUM(a.price), 0) AS activities_total
            FROM activities a
            WHERE a.trip_id = ? AND a.location = ?");
        $stmt_activities->execute([$trip_id, $location]);
        $activities_total = $stmt_activities->fetchColumn();

        // Transport (calculate costs based on end_location)
        $stmt_transport = $pdo->prepare("
        SELECT COALESCE(SUM(t.price), 0) AS transport_total
        FROM transport t
        WHERE t.trip_id = ? AND t.end_location = ?");
        $stmt_transport->execute([$trip_id, $location]);
        $transport_total = $stmt_transport->fetchColumn();


        $budget_data[$location] = [
            'accommodation_total' => $accommodation_total,
            'activities_total' => $activities_total,
            'transport_total' => $transport_total,
        ];
    }


    // Calculate total costs
    $total_accommodation = 0;
    $total_activities = 0;
    $total_transport = 0;

    foreach ($budget_data as $data) {
        $total_accommodation += $data['accommodation_total'];
        $total_activities += $data['activities_total'];
        $total_transport += $data['transport_total'];
    }

    $total_budget = $total_accommodation + $total_activities + $total_transport;
?>


<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetübersicht</title>
    <link rel="stylesheet" href="../../public/assets/css/reset.css">
    <link rel="stylesheet" href="../../public/assets/css/general.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .chart-container {
            margin: auto;
            max-width: 300px;
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        canvas {
            max-width: 300px; 
            height: auto;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .summary-table th, .summary-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .summary-table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        .summary-table td {
            background-color: #fff;
        }

        .summary-row {
            font-weight: bold;
            background-color: #eaeaea;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
        }

        button:hover {
            background-color: #2980b9;
        }

        .buttons {
            text-align: center;
            margin-top: 20px;
        }
    </style>
    <script>
        window.onload = function() {
            const ctx = document.getElementById('budget-chart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Unterkunft', 'Transport', 'Aktivitäten'],
                    datasets: [{
                        data: [
                            <?php echo $total_accommodation; ?>,
                            <?php echo $total_transport; ?>,
                            <?php echo $total_activities; ?>
                        ],
                        backgroundColor: ['#3498db', '#2ecc71', '#e74c3c']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        };
    </script>
</head>
<body>
    <div class="container">
        <h1>Budgetübersicht</h1>

        <div class="chart-container">
            <canvas id="budget-chart"></canvas>
        </div>

        <table class="summary-table">
            <thead>
                <tr>
                    <th>Ort</th>
                    <th>Unterkunft (CHF)</th>
                    <th>Transport (CHF)</th>
                    <th>Aktivitäten (CHF)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($budget_data as $location => $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($location); ?></td>
                        <td>CHF <?php echo number_format($data['accommodation_total'], 2, ',', '.'); ?></td>
                        <td>CHF <?php echo number_format($data['transport_total'], 2, ',', '.'); ?></td>
                        <td>CHF <?php echo number_format($data['activities_total'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="summary-row">
                    <td>Gesamt</td>
                    <td>CHF <?php echo number_format($total_accommodation, 2, ',', '.'); ?></td>
                    <td>CHF <?php echo number_format($total_transport, 2, ',', '.'); ?></td>
                    <td>CHF <?php echo number_format($total_activities, 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="buttons">
            <button onclick="location.href='step_4.php'">Zurück</button>
            <button onclick="location.href='step_7.php'">Weiter</button>
        </div>
    </div>
</body>
</html>
