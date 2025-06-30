<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials
$db = [
    'host' => 'sql300.infinityfree.com',
    'user' => 'if0_39344537',
    'pass' => 'kaelmejares7',
    'db'   => 'if0_39344537_destination',
    'port' => 3306
];
$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['db'], $db['port']);
if ($conn->connect_errno) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connect Error: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

function getCount($conn, $table) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM $table");
    $row = $res->fetch_assoc();
    return $row['cnt'];
}

function getChartData($conn, $chart) {
    switch ($chart) {
        case 'monthly_sales':
            $res = $conn->query("SELECT CONCAT(year, '-', LPAD(month,2,'0')) as ym, SUM(total_sales) as sales FROM sales_analytics GROUP BY year, month ORDER BY year DESC, month DESC LIMIT 12");
            $labels = [];
            $data = [];
            $rows = [];
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
            $rows = array_reverse($rows); // chronological order
            foreach ($rows as $row) {
                $labels[] = $row['ym'];
                $data[] = $row['sales'];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'top_products_pie':
            $res = $conn->query("SELECT product_name, total_revenue FROM product_performance ORDER BY total_revenue DESC LIMIT 10");
            $labels = [];
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['product_name'];
                $data[] = $row['total_revenue'];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'top_customers_line':
            $res = $conn->query("SELECT customer_name, total_spent FROM customer_analytics ORDER BY total_spent DESC LIMIT 10");
            $labels = [];
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['customer_name'];
                $data[] = $row['total_spent'];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'revenue_by_country_bubble':
            $res = $conn->query("SELECT country, SUM(total_revenue) as revenue, SUM(customer_count) as customers FROM geographic_analytics GROUP BY country");
            $labels = [];
            $data = [];
            $i = 0;
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['country'];
                $data[] = [
                    'x' => $i++,
                    'y' => (float)$row['revenue'],
                    'r' => max(5, sqrt($row['customers'])*2),
                    'country' => $row['country']
                ];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'product_performance_bar':
            $res = $conn->query("SELECT product_name, total_quantity_sold FROM product_performance ORDER BY total_quantity_sold DESC");
            $labels = [];
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['product_name'];
                $data[] = $row['total_quantity_sold'];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'customer_analytics_donut':
            $res = $conn->query("SELECT country, COUNT(*) as cnt FROM customer_analytics GROUP BY country ORDER BY cnt DESC");
            $labels = [];
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['country'];
                $data[] = $row['cnt'];
            }
            return ['labels' => $labels, 'data' => $data];
        default:
            return ['labels' => [], 'data' => []];
    }
}

$dashboardCharts = [
    'monthly_sales' => 'Monthly Sales Analytics',
    'top_products_pie' => 'Top 10 Products by Revenue',
    'top_customers_line' => 'Top Customers by Spending',
    'revenue_by_country_bubble' => 'Revenue by Country',
    'product_performance_bar' => 'Product Performance',
    'customer_analytics_donut' => 'Customer Analytics by Country',
];

$chartData = [];
foreach ($dashboardCharts as $key => $label) {
    $chartData[$key] = getChartData($conn, $key);
}

$summaryCounts = [];
$tables = [
    'customer_analytics' => 'Customer Analytics',
    'employee_performance' => 'Employee Performance',
    'geographic_analytics' => 'Geographic Analytics',
    'product_performance' => 'Product Performance',
    'sales_analytics' => 'Sales Analytics',
];
foreach ($tables as $table => $label) {
    $summaryCounts[$table] = getCount($conn, $table);
}

$lastEtlRun = 'Never';
if (file_exists('last_etl_run.txt')) {
    $lastEtlRun = file_get_contents('last_etl_run.txt');
}

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'chartData' => $chartData,
    'summaryCounts' => $summaryCounts,
    'lastEtlRun' => $lastEtlRun
]);
exit;
?>