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
      border-radius: 16px;
      padding: 1.5em;
      min-width: 280px;
      max-width: 400px;
      flex: 1;
      box-shadow: 0 8px 0 #388e3c;
      border: 4px solid #388e3c;
      transition: transform 0.2s;
    }
    .card:hover {
      transform: translateY(-4px);
    }
    .card h3 {
      margin: 0 0 1em 0;
      font-size: 1em;
      color: #388e3c;
      text-shadow: 1px 1px 0 #fff;
    }
    .card p {
      margin: 0.5em 0;
      font-size: 0.8em;
    }
    .chart-container {
      background: #fff;
      border-radius: 16px;
      padding: 1.5em;
      margin: 1em;
      box-shadow: 0 8px 0 #388e3c;
      border: 4px solid #388e3c;
    }
    .chart-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2em;
      padding: 1em;
    }
    @media (max-width: 768px) {
      .chart-grid {
        grid-template-columns: 1fr;
      }
      .card {
        min-width: unset;
      }
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="dashboard-root">
    <header class="dashboard-header">
      <h1>Business Intelligence Dashboard</h1>
    </header>
    <div class="main-content">
      <div class="welcome-banner">
        <h1>Welcome to Your BI Dashboard!</h1>
        <p>Explore your business metrics and analytics</p>
      </div>
      <div class="summary-cards">
        <?php foreach ($tables as $table => $label): ?>
        <div class="card">
          <h3><?= htmlspecialchars($label) ?></h3>
          <p>Total Records: <?= number_format(getCount($conn, $table)) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-grid">
        <?php foreach ($dashboardCharts as $chartId => $chartLabel): ?>
        <div class="chart-container">
          <canvas id="<?= $chartId ?>"></canvas>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <script>
    const chartConfigs = {
      monthly_sales: {
        type: 'bar',
        options: {
          plugins: {
            title: { display: true, text: 'Monthly Sales Analytics' }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      },
      top_products_pie: {
        type: 'pie',
        options: {
          plugins: {
            title: { display: true, text: 'Top 10 Products by Revenue' }
          }
        }
      },
      top_customers_line: {
        type: 'line',
        options: {
          plugins: {
            title: { display: true, text: 'Top Customers by Spending' }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      },
      revenue_by_country_bubble: {
        type: 'bubble',
        options: {
          plugins: {
            title: { display: true, text: 'Revenue by Country' },
            tooltip: {
              callbacks: {
                label: (context) => {
                  const d = context.raw;
                  return `${d.country}: Revenue: $${d.y.toFixed(2)}, Size ~ Customer Count`;
                }
              }
            }
          },
          scales: {
            x: { display: false },
            y: { beginAtZero: true }
          }
        }
      },
      product_performance_bar: {
        type: 'bar',
        options: {
          plugins: {
            title: { display: true, text: 'Product Performance' }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      },
      customer_analytics_donut: {
        type: 'doughnut',
        options: {
          plugins: {
            title: { display: true, text: 'Customer Analytics by Country' }
          }
        }
      }
    };

    const chartData = <?= json_encode($chartData) ?>;

    Object.entries(chartConfigs).forEach(([chartId, config]) => {
      const ctx = document.getElementById(chartId).getContext('2d');
      const data = chartData[chartId];
      
      new Chart(ctx, {
        type: config.type,
        data: {
          labels: data.labels,
          datasets: [{
            label: chartId,
            data: data.data,
            backgroundColor: [
              '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
              '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'
            ],
            borderColor: '#fff',
            borderWidth: 2
          }]
        },
        options: config.options
      });
    });
  </script>
</body>
</html> 