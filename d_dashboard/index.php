<?php
// Database credentials read from Render's Environment Variables
$db = [
    'host' => getenv('DB_HOST'),
    'user' => getenv('DB_USER'),
    'pass' => getenv('DB_PASS'),
    'db'   => getenv('DB_NAME'),
    'port' => getenv('DB_PORT')
];
$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['db'], $db['port']);
if ($conn->connect_errno) {
    die('Connect Error: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

function getCount($conn, $table) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM $table");
    $row = $res->fetch_assoc();
    return $row['cnt'];
}

function getPreview($conn, $table) {
    $res = $conn->query("SELECT * FROM $table ORDER BY id DESC");
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$tables = [
    'customer_analytics' => 'Customer Analytics',
    'employee_performance' => 'Employee Performance',
    'geographic_analytics' => 'Geographic Analytics',
    'product_performance' => 'Product Performance',
    'sales_analytics' => 'Sales Analytics',
];

// Prepare chart data for each dashboard chart
function getChartData($conn, $chart) {
    switch ($chart) {
        case 'monthly_sales':
            // Column chart: Top 12 months by total sales
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
            // Pie chart: Top 10 products by revenue
            $res = $conn->query("SELECT product_name, total_revenue FROM product_performance ORDER BY total_revenue DESC LIMIT 10");
            $labels = [];
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['product_name'];
                $data[] = $row['total_revenue'];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'top_customers_line':
            // Line chart: Top 10 customers by spending
            $res = $conn->query("SELECT customer_name, total_spent FROM customer_analytics ORDER BY total_spent DESC LIMIT 10");
            $labels = [];
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['customer_name'];
                $data[] = $row['total_spent'];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'revenue_by_country_bubble':
            // Bubble chart: Revenue by country (x=country, y=total_revenue, r=customer_count)
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
            // Bar chart: All products by total_quantity_sold
            $res = $conn->query("SELECT product_name, total_quantity_sold FROM product_performance ORDER BY total_quantity_sold DESC");
            $labels = [];
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['product_name'];
                $data[] = $row['total_quantity_sold'];
            }
            return ['labels' => $labels, 'data' => $data];
        case 'customer_analytics_donut':
            // Donut chart: Country breakdown by customer count
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BI Data Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Press Start 2P', monospace;
      background: linear-gradient(180deg, #87ceeb 0%, #b4eaff 100%);
      background-image: url('https://svgshare.com/i/13dF.svg'); /* cloud SVG as repeating bg */
      background-repeat: repeat-x;
      background-size: auto 180px;
      margin: 0;
      min-height: 100vh;
      color: #22304a;
    }
    .dashboard-root {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      width: 100%;
    }
    .main-content {
      flex: 1;
      width: 100%;
      padding: 0;
      min-width: 0;
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }
    .welcome-banner {
      margin: 1.5em 1em 1.5em 1em;
      padding: 2em 1em 1em 1em;
      border-radius: 24px;
      background: #fffbe6;
      border: 4px solid #388e3c;
      box-shadow: 0 8px 0 #388e3c;
      text-align: center;
    }
    .welcome-banner h1 {
      font-size: 1.5em;
      color: #388e3c;
      margin-bottom: 0.5em;
      text-shadow: 2px 2px 0 #fff;
    }
    .dashboard-header {
      background: #ffe066;
      color: #388e3c;
      font-size: 1.3em;
      font-family: 'Press Start 2P', monospace;
      border-bottom: 8px solid #388e3c;
      box-shadow: 0 8px 0 #388e3c;
      padding: 2em 2em 1em 2em;
      text-shadow: 2px 2px 0 #fff, 4px 4px 0 #388e3c;
      margin-bottom: 2em;
      text-align: center;
    }
    .summary-cards {
      display: flex;
      flex-wrap: wrap;
      gap: 2em;
      margin: 2em 1em 2em 1em;
      justify-content: center;
    }
    .card {
      background: #7ed957;
      color: #22304a;
      border-radius: 0 0 32px 32px;
      border: 8px solid #388e3c;
      border-top: none;
      box-shadow: 0 12px 0 #388e3c, 0 24px 0 #388e3c;
      font-family: 'Press Start 2P', monospace;
      font-size: 1.2em;
      font-weight: 700;
      min-width: 170px;
      max-width: 220px;
      text-align: center;
      padding: 2em 1.7em 1.5em 1.7em;
      margin: 0.5em 0.5em;
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
    }
    .card-title {
      font-size: 1.2em;
      margin-bottom: 0.5em;
      color: #388e3c;
      text-shadow: 2px 2px 0 #fff;
    }
    .card-count {
      font-size: 2.2em;
      font-weight: 900;
      color: #22304a;
      text-shadow: 2px 2px 0 #fff;
    }
    .section {
      margin: 2.5em 1em 2.5em 1em;
      background: #fffbe6;
      border-radius: 32px 32px 0 0;
      border: 8px solid #388e3c;
      border-bottom: none;
      box-shadow: 0 -12px 0 #388e3c;
      font-family: 'Press Start 2P', monospace;
      padding: 2.2em 1.7em 1.7em 1.7em;
    }
    .section h2 {
      color: #388e3c;
      font-size: 1.3em;
      margin-bottom: 1.2em;
      text-shadow: 2px 2px 0 #fff;
      font-weight: 900;
    }
    .chart-container {
      background: #b4eaff;
      border: 4px solid #388e3c;
      border-radius: 16px;
      box-shadow: 0 4px 0 #388e3c;
      margin-bottom: 1.2em;
      min-height: 120px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.2em 0.7em;
    }
    .table-scroll {
      overflow-x: auto;
      margin-top: 1em;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fffbe6;
      border-radius: 10px;
      box-shadow: 0 1px 8px #388e3c33;
      border: 4px solid #388e3c;
      font-family: 'Press Start 2P', monospace;
      font-size: 1em;
      margin-bottom: 1.5em;
    }
    th, td {
      padding: 1em 0.7em;
      text-align: left;
      border-bottom: 2px solid #388e3c;
      font-size: 1em;
      min-width: 90px;
    }
    th {
      background: #ffe066;
      color: #388e3c;
      font-weight: 900;
      border-bottom: 4px solid #388e3c;
      text-shadow: 1px 1px 0 #fff;
    }
    tr:last-child td {
      border-bottom: none;
    }
    .dashboard-footer {
      width: 100%;
      background: #388e3c;
      color: #ffe066;
      text-align: center;
      font-family: 'Press Start 2P', monospace;
      padding: 1.5em 0 1em 0;
      font-size: 1em;
      letter-spacing: 1px;
      box-shadow: 0 -4px 0 #2c6b27;
      margin-top: 3em;
    }
    @media (max-width: 900px) {
      .welcome-banner, .section, .summary-cards { margin: 1em 0.5em; padding: 1em 0.5em; }
      .card { font-size: 1em; }
      table { font-size: 0.9em; min-width: 320px; }
    }
    @media (max-width: 600px) {
      .summary-cards { flex-direction: column; gap: 1em; }
      .card { font-size: 0.9em; min-width: unset; max-width: unset; width: 100%; }
      .section h2, .dashboard-header h1, .welcome-banner h1 { font-size: 1em; }
      table { font-size: 0.8em; min-width: 220px; }
      th, td { font-size: 0.8em; padding: 0.5em 0.1em; }
    }
    @media (max-width: 400px) {
      .section h2, .dashboard-header h1, .welcome-banner h1 { font-size: 0.7em; }
      .dashboard-footer { font-size: 0.7em; }
    }
  </style>
</head>
<body>
  <div class="dashboard-root">
    <div class="main-content">
      <div class="welcome-banner">
        <h1>Welcome to Flappy BI Dashboard!</h1>
        <p>Track your business analytics with a fun, retro game-inspired interface. Hover over info icons (<span class="info-icon">?</span>) for explanations.</p>
      </div>
      <header class="dashboard-header">
        <h1>BI Data Dashboard (Destination)</h1>
      </header>
      <div class="summary-cards">
        <?php foreach ($tables as $tbl => $label): ?>
          <div class="card" tabindex="0">
            <div class="card-title"><?php echo $label; ?></div>
            <div class="card-count"><?php echo getCount($conn, $tbl); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php $chartTypes = [
        'monthly_sales' => 'bar',
        'top_products_pie' => 'pie',
        'top_customers_line' => 'line',
        'revenue_by_country_bubble' => 'bubble',
        'product_performance_bar' => 'bar',
        'customer_analytics_donut' => 'doughnut',
      ]; ?>
      <?php foreach ($dashboardCharts as $key => $label): ?>
        <?php $hasChart = count($chartData[$key]['labels']) > 0 && count($chartData[$key]['data']) > 0 && array_sum((array)$chartData[$key]['data']) > 0; ?>
        <section class="section" id="<?php echo $key; ?>">
          <h2><?php echo $label; ?></h2>
          <?php if ($key === 'revenue_by_country_bubble'): ?>
            <?php if ($hasChart): ?>
              <div class="chart-container">
                <canvas id="chart_<?php echo $key; ?>" height="80"></canvas>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="chart-container">
              <?php if ($hasChart): ?>
                <canvas id="chart_<?php echo $key; ?>" height="80"></canvas>
              <?php else: ?>
                <div class="no-chart-data">No chart data available</div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($key === 'revenue_by_country_bubble'): ?>
            <div class="table-scroll">
              <table>
                <tr>
                  <th>Country</th>
                  <th>Customer Count</th>
                  <th>Total Revenue</th>
                  <th>Avg Revenue/Customer</th>
                  <th>Order Count</th>
                </tr>
                <?php
                  $geoRes = $conn->query("SELECT country, customer_count, total_revenue, avg_revenue_per_customer, order_count FROM geographic_analytics ORDER BY total_revenue DESC");
                  $geoCountries = [];
                  $geoRevenues = [];
                  if ($geoRes && $geoRes->num_rows > 0) {
                    while ($row = $geoRes->fetch_assoc()) {
                      $geoCountries[] = $row['country'];
                      $geoRevenues[] = (float)$row['total_revenue'];
                      echo '<tr>';
                      echo '<td>' . htmlspecialchars($row['country']) . '</td>';
                      echo '<td>' . htmlspecialchars($row['customer_count']) . '</td>';
                      echo '<td>' . number_format($row['total_revenue'], 2) . '</td>';
                      echo '<td>' . number_format($row['avg_revenue_per_customer'], 2) . '</td>';
                      echo '<td>' . htmlspecialchars($row['order_count']) . '</td>';
                      echo '</tr>';
                    }
                  } else {
                    echo '<tr><td colspan="5" style="text-align:center; color:#888;">No data</td></tr>';
                  }
                ?>
              </table>
            </div>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
    </div>
  </div>
  <footer class="dashboard-footer">
    Flappy BI &copy; <?php echo date('Y'); ?> &mdash; Keep flapping to success! 🐤
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  // Pastel color palette for charts
  const pastelColors = [
    '#a5b4fc', '#fbbf24', '#fca5a5', '#6ee7b7', '#f9a8d4', '#fdba74', '#93c5fd', '#fcd34d', '#c4b5fd', '#f87171', '#34d399', '#f472b6', '#fbbf24', '#60a5fa', '#facc15', '#a7f3d0', '#fda4af', '#fef08a', '#818cf8', '#f472b6', '#fcd34d', '#fca5a5', '#fbbf24', '#a5b4fc', '#6ee7b7'
  ];
  function pastel(i) { return pastelColors[i % pastelColors.length]; }
  const chartConfigs = {
    monthly_sales: {
      type: 'bar',
      data: <?php echo json_encode([
        'labels' => $chartData['monthly_sales']['labels'],
        'datasets' => [[
          'label' => 'Total Sales',
          'data' => $chartData['monthly_sales']['data'],
          'backgroundColor' => null,
        ]]
      ]); ?>,
      options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false, scales: { x: { title: { display: true, text: 'Month' } }, y: { title: { display: true, text: 'Total Sales' } } } }
    },
    top_products_pie: {
      type: 'pie',
      data: <?php echo json_encode([
        'labels' => $chartData['top_products_pie']['labels'],
        'datasets' => [[
          'data' => $chartData['top_products_pie']['data'],
          'backgroundColor' => null,
        ]]
      ]); ?>,
      options: { plugins: { legend: { position: 'bottom' } }, responsive: true, maintainAspectRatio: false }
    },
    top_customers_line: {
      type: 'line',
      data: <?php echo json_encode([
        'labels' => $chartData['top_customers_line']['labels'],
        'datasets' => [[
          'label' => 'Total Spent',
          'data' => $chartData['top_customers_line']['data'],
          'borderColor' => null,
          'backgroundColor' => null,
          'tension' => 0.3,
          'fill' => true,
          'pointBackgroundColor' => null,
        ]]
      ]); ?>,
      options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false, scales: { x: { title: { display: true, text: 'Customer' } }, y: { title: { display: true, text: 'Total Spent' } } } }
    },
    revenue_by_country_bubble: {
      type: 'bubble',
      data: <?php
        $bubbleData = $chartData['revenue_by_country_bubble']['data'];
        $bubbleLabels = $chartData['revenue_by_country_bubble']['labels'];
        echo json_encode([
          'labels' => $bubbleLabels,
          'datasets' => [[
            'label' => 'Revenue by Country',
            'data' => $bubbleData,
            'backgroundColor' => null,
          ]]
        ]);
      ?>,
      options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false, scales: { x: { title: { display: true, text: 'Country (index)' }, ticks: { callback: function(value, idx) { return <?php echo json_encode($bubbleLabels); ?>[idx] || value; } } }, y: { title: { display: true, text: 'Revenue' } } } }
    },
    product_performance_bar: {
      type: 'bar',
      data: <?php echo json_encode([
        'labels' => $chartData['product_performance_bar']['labels'],
        'datasets' => [[
          'label' => 'Quantity Sold',
          'data' => $chartData['product_performance_bar']['data'],
          'backgroundColor' => null,
        ]]
      ]); ?>,
      options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false, scales: { x: { title: { display: true, text: 'Product' } }, y: { title: { display: true, text: 'Quantity Sold' } } } }
    },
    customer_analytics_donut: {
      type: 'doughnut',
      data: <?php echo json_encode([
        'labels' => $chartData['customer_analytics_donut']['labels'],
        'datasets' => [[
          'data' => $chartData['customer_analytics_donut']['data'],
          'backgroundColor' => null,
        ]]
      ]); ?>,
      options: { plugins: { legend: { position: 'bottom' } }, responsive: true, maintainAspectRatio: false }
    }
  };
  for (const key in chartConfigs) {
    const data = chartConfigs[key].data;
    if (data && data.datasets && data.datasets[0] && data.labels) {
      if (key === 'top_customers_line') {
        data.datasets[0].borderColor = pastelColors[2];
        data.datasets[0].backgroundColor = pastelColors[2]+'33';
        data.datasets[0].pointBackgroundColor = pastelColors[2];
      } else {
        data.datasets[0].backgroundColor = pastelColors.slice(0, data.labels.length);
      }
    }
    const ctx = document.getElementById('chart_' + key);
    if (ctx) new Chart(ctx, chartConfigs[key]);
  }
  </script>
</body>
</html> 