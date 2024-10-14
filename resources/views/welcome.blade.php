<?php
session_start();

// Функция для получения токена авторизации
function getAuthToken() {
    $url = "https://dimmy-yammi-barnaul.iiko.it:443/resto/api/auth?login=rlabs&pass=a4f7d1b395053edf689325d6f5056ceba1ccf792";
    $response = file_get_contents($url);
    return trim($response, '"');
}

// Функция для получения данных OLAP отчета
function getOlapReport($token, $dateFrom, $dateTo) {
    $url = "https://dimmy-yammi-barnaul.iiko.it:443/resto/api/v2/reports/olap?key={$token}";
    $data = [
        "reportType" => "SALES",
        "groupByRowFields" => [
            "Delivery.Number",
            "DishServicePrintTime",
            "OpenDate.Typed",
            "Delivery.ActualTime"
        ],
        "groupByColFields" => [],
        "aggregateFields" => [],
        "filters" => [
            "OpenDate.Typed" => [
                "filterType" => "DateRange",
                "periodType" => "CUSTOM",
                "from" => $dateFrom,
                "to" => $dateTo,
                "includeLow" => "true",
                "includeHigh" => "true"
            ]
        ]
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}

// Функция для закрытия сессии
function closeSession($token) {
    $url = "https://dimmy-yammi-barnaul.iiko.it:443/resto/api/logout?key={$token}";
    file_get_contents($url);
}

// Обработка AJAX запроса
if (isset($_GET['action']) && $_GET['action'] == 'refresh') {
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-d');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-d');
    
    try {
        $token = getAuthToken();
        $report = getOlapReport($token, $dateFrom, $dateTo);
        closeSession($token);
        
        // Обработка и фильтрация данных отчета
        $processedData = processReportData($report);
        
        echo json_encode([
            'success' => true, 
            'data' => $processedData,
            'messages' => [
                'Токен успешно получен',
                'Данные успешно получены',
                'Сессия успешно закрыта'
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Функция для обработки данных отчета
function processReportData($report) {
    $processedData = [];
    if (isset($report['data']) && is_array($report['data'])) {
        foreach ($report['data'] as $row) {
            $key = $row['Delivery.Number'] ?? 'Неизвестно';
            if (!isset($processedData[$key]) || ($row['DishServicePrintTime'] ?? '') < ($processedData[$key]['time'] ?? '')) {
                $processedData[$key] = [
                    'number' => $row['Delivery.Number'] ?? 'Неизвестно',
                    'time' => $row['DishServicePrintTime'] ?? 'Нет информации',
                    'date' => $row['OpenDate.Typed'] ?? 'Нет информации',
                    'actualTime' => $row['Delivery.ActualTime'] ?? 'Нет информации'
                ];
            }
        }
    }
    return array_values($processedData);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DimmiYammi - Информация о заказах</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #2a2a2a;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(255,255,255,0.1);
        }
        h1, h2 {
            color: #4CAF50;
        }
        .info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background-color: #333;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        #loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .date-inputs {
            margin-bottom: 20px;
        }
        .date-inputs input, .date-inputs button {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>DimmiYammi - Информация о заказах</h1>
        
        <div class="info">
            <h2>Информация о системе</h2>
            <p>Версия PHP: <?php echo phpversion(); ?></p>
            <p>Сервер: DimmiYammi (<?php echo $_SERVER['SERVER_SOFTWARE']; ?>)</p>
        </div>
        
        <h2>Заказы</h2>
        <div class="date-inputs">
            <input type="date" id="dateFrom" name="dateFrom">
            <input type="date" id="dateTo" name="dateTo">
            <button id="todayButton">Сегодня</button>
            <button id="yesterdayButton">Вчера</button>
            <button id="refreshButton">Получить данные</button>
        </div>
        
        <div id="loading">
            <div class="spinner"></div>
            <p>Загрузка данных...</p>
        </div>
        
        <table id="ordersTable">
            <thead>
                <tr>
                    <th>Номер доставки</th>
                    <th>Время печати</th>
                    <th>Сервисная печать</th>
                    <th>Фактическое время</th>
                </tr>
            </thead>
            <tbody>
                <!-- Данные о заказах будут загружены сюда -->
            </tbody>
        </table>
    </div>

    <script>
        function refreshOrders() {
            const loading = document.getElementById('loading');
            const ordersTable = document.getElementById('ordersTable').getElementsByTagName('tbody')[0];
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            loading.style.display = 'block';
            ordersTable.innerHTML = '';
            
            fetch(`?action=refresh&dateFrom=${dateFrom}&dateTo=${dateTo}`)
                .then(response => response.json())
                .then(result => {
                    loading.style.display = 'none';
                    if (result.success) {
                        result.data.forEach(order => {
                            const row = ordersTable.insertRow();
                            row.insertCell(0).textContent = order.number;
                            row.insertCell(1).textContent = order.time;
                            row.insertCell(2).textContent = order.date;
                            row.insertCell(3).textContent = order.actualTime;
                        });
                        alert('Данные успешно обновлены');
                    } else {
                        alert('Ошибка: ' + result.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loading.style.display = 'none';
                    alert('Произошла ошибка при загрузке данных');
                });
        }

        document.getElementById('refreshButton').addEventListener('click', refreshOrders);
        
        document.getElementById('todayButton').addEventListener('click', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateFrom').value = today;
            document.getElementById('dateTo').value = today;
        });
        
        document.getElementById('yesterdayButton').addEventListener('click', () => {
            const yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0];
            document.getElementById('dateFrom').value = yesterday;
            document.getElementById('dateTo').value = yesterday;
        });
        
        // Устанавливаем сегодняшнюю дату при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateFrom').value = today;
            document.getElementById('dateTo').value = today;
        });
    </script>
</body>
</html>
