<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tables = [
    'customer_analytics' => 'Customer Analytics',
    'employee_performance' => 'Employee Performance',
    'geographic_analytics' => 'Geographic Analytics',
    'product_performance' => 'Product Performance',
    'sales_analytics' => 'Sales Analytics',
];

$dashboardCharts = [
    'monthly_sales' => 'Monthly Sales Analytics',
    'top_products_pie' => 'Top 10 Products by Revenue',
    'top_customers_line' => 'Top Customers by Spending',
    'revenue_by_country_bubble' => 'Revenue by Country',
    'product_performance_bar' => 'Product Performance',
    'customer_analytics_donut' => 'Customer Analytics by Country',
];

$lastEtlRun = 'Never';
if (file_exists('last_etl_run.txt')) {
    $lastEtlRun = file_get_contents('last_etl_run.txt');
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
    .refresh-button {
      background: #007bff; /* Blue for refresh */
      color: #fff;
      border: 4px solid #0056b3;
      border-radius: 8px;
      padding: 0.6em 1.5em;
      font-size: 0.9em;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 4px 0 #0056b3;
      transition: background 0.1s, box-shadow 0.1s, transform 0.1s;
      margin-top: 1em;
      text-shadow: 1px 1px 0 #000;
    }
    .refresh-button:active {
      transform: translateY(2px);
      box-shadow: 0 2px 0 #0056b3;
    }
    .load-button {
      background: #28a745; /* Green for load */
      color: #fff;
      border: 4px solid #1e7e34;
      border-radius: 8px;
      padding: 0.6em 1.5em;
      font-size: 0.9em;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 4px 0 #1e7e34;
      transition: background 0.1s, box-shadow 0.1s, transform 0.1s;
      margin-top: 1em;
      margin-left: 1em; /* Space between refresh and load buttons */
      text-shadow: 1px 1px 0 #000;
    }
    .load-button:active {
      transform: translateY(2px);
      box-shadow: 0 2px 0 #1e7e34;
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
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      text-align: center;
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
    .card .count {
      font-size: 2.5em;
      font-weight: bold;
      color: #22304a;
      text-shadow: 2px 2px 0 #fff;
      margin-top: 0.5em;
    }
    .chart-container {
      background: #fff;
      border-radius: 16px;
      padding: 1.5em;
      margin: 1em;
      box-shadow: 0 8px 0 #388e3c;
      border: 4px solid #388e3c;
      position: relative; /* For positioning the actions */
    }
    .chart-actions {
      margin-top: 1em;
      display: flex;
      justify-content: center;
      gap: 10px;
    }
    .chart-actions button {
      background: #007bff; /* Blue for actions */
      color: #fff;
      border: 2px solid #0056b3;
      border-radius: 5px;
      padding: 0.5em 1em;
      font-size: 0.8em;
      cursor: pointer;
      transition: background 0.1s, border-color 0.1s;
    }
    .chart-actions button:hover {
      background: #0056b3;
    }
    /* Modal Styles */
    .modal {
      display: none; /* Hidden by default */
      position: fixed; /* Stay in place */
      z-index: 1000; /* Sit on top */
      left: 0;
      top: 0;
      width: 100%; /* Full width */
      height: 100%; /* Full height */
      overflow: auto; /* Enable scroll if needed */
      background-color: rgba(0,0,0,0.6); /* Black w/ opacity */
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background-color: #fefefe;
      margin: auto;
      padding: 20px;
      border: 5px solid #388e3c;
      width: 80%;
      max-width: 900px;
      border-radius: 16px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
      position: relative;
      font-family: Arial, sans-serif; /* Use a more readable font for data tables */
      color: #333;
    }
    .close-button {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
    }
    .close-button:hover,
    .close-button:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }
    .data-table-container {
      max-height: 500px;
      overflow-y: auto;
      margin-top: 1em;
      border: 1px solid #ddd;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
    }
    .data-table th,
    .data-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
      font-size: 0.9em;
    }
    .data-table th {
      background-color: #f2f2f2;
      font-weight: bold;
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
        <p id="lastEtlRun">Last ETL Run: <?= htmlspecialchars($lastEtlRun) ?></p>
        <button id="refreshDataBtn" class="refresh-button">Refresh Data</button>
        <button id="loadDataBtn" class="load-button">Load Data</button>
      </div>
      <div class="summary-cards">
        <?php foreach ($tables as $table => $label): ?>
        <div class="card" data-table="<?= $table ?>">
          <h3><?= htmlspecialchars($label) ?></h3>
          <p class="count"></p>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-grid">
        <?php foreach ($dashboardCharts as $chartId => $chartLabel): ?>
        <div class="chart-container">
          <canvas id="<?= $chartId ?>"></canvas>
          <div class="chart-actions">
            <button class="view-data-btn" data-chart-id="<?= $chartId ?>">View Data</button>
            <button class="download-csv-btn" data-chart-id="<?= $chartId ?>">Download CSV</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <!-- The Modal -->
  <div id="dataModal" class="modal">
    <!-- Modal content -->
    <div class="modal-content">
      <span class="close-button">&times;</span>
      <h2>Chart Data</h2>
      <div id="dataTableContainer" class="data-table-container">
        <!-- Data table will be inserted here by JavaScript -->
      </div>
    </div>
  </div>
  <script>
    const chartInstances = {}; // To store Chart.js instances

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
                  return `${d.country}: Revenue: ${d.y.toFixed(2)}, Size ~ Customer Count`;
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

    async function fetchAndRenderData() {
      try {
        const response = await fetch('get_chart_data.php');
        const data = await response.json();

        if (data.error) {
          console.error('Error fetching data:', data.error);
          return;
        }

        // Update summary cards
        for (const table in data.summaryCounts) {
          const countElement = document.querySelector(`.card[data-table="${table}"] .count`);
          if (countElement) {
            countElement.textContent = data.summaryCounts[table].toLocaleString();
          }
        }

        // Update last ETL run timestamp
        document.getElementById('lastEtlRun').textContent = 'Last ETL Run: ' + data.lastEtlRun;

        // Update charts
        Object.entries(chartConfigs).forEach(([chartId, config]) => {
          const chartData = data.chartData[chartId];
          const ctx = document.getElementById(chartId).getContext('2d');

          if (chartInstances[chartId]) {
            // Update existing chart
            chartInstances[chartId].data.labels = chartData.labels;
            chartInstances[chartId].data.datasets[0].data = chartData.data;
            chartInstances[chartId].update();
          } else {
            // Create new chart
            chartInstances[chartId] = new Chart(ctx, {
              type: config.type,
              data: {
                labels: chartData.labels,
                datasets: [{
                  label: chartId,
                  data: chartData.data,
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
          }
        });

      } catch (e) {
        console.error('Failed to fetch or render data:', e);
      }
    }

    // Initial data load
    fetchAndRenderData();

    // Refresh data every 30 seconds (adjust as needed)
    setInterval(fetchAndRenderData, 1000);

    document.getElementById('refreshDataBtn').onclick = function() {
      fetchAndRenderData(); // Manual refresh
    };

    document.getElementById('loadDataBtn').onclick = async function() {
      if (confirm('Are you sure you want to load data from the source database? This will overwrite existing data.')) {
        try {
          const response = await fetch('load_data.php');
          const result = await response.json();
          alert('Load Data Status: ' + result.status + '\nMessage: ' + result.message);
          fetchAndRenderData(); // Refresh dashboard after loading data
        } catch (e) {
          console.error('Error loading data:', e);
          alert('Error loading data. Check console for details.');
        }
      }
    };

    // Modal and Data Table Logic
    const modal = document.getElementById('dataModal');
    const span = document.getElementsByClassName('close-button')[0];
    const dataTableContainer = document.getElementById('dataTableContainer');

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
      modal.style.display = 'none';
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }

    document.querySelectorAll('.view-data-btn').forEach(button => {
      button.addEventListener('click', function() {
        const chartId = this.dataset.chartId;
        // Get the current data from the chart instance
        const data = chartInstances[chartId].data.datasets[0].data;
        const labels = chartInstances[chartId].data.labels;

        let tableHtml = '<table class="data-table"><thead><tr>';

        // Determine headers based on chart type
        if (chartId === 'revenue_by_country_bubble') {
          tableHtml += '<th>Country</th><th>Revenue</th><th>Customer Count</th>';
        } else if (labels && labels.length > 0) {
          tableHtml += `<th>${chartConfigs[chartId].options.plugins.title.text.split(' by ')[0]}</th><th>Value</th>`;
        } else {
          tableHtml += '<th>Data</th>';
        }
        tableHtml += '</tr></thead><tbody>';

        // Populate rows
        if (chartId === 'revenue_by_country_bubble') {
          labels.forEach((label, index) => {
            const item = data[index];
            tableHtml += `<tr><td>${item.country}</td><td>${item.y.toFixed(2)}</td><td>${Math.round(item.r * item.r / 4)}</td></tr>`;
          });
        } else {
          labels.forEach((label, index) => {
            tableHtml += `<tr><td>${label}</td><td>${data[index]}</td></tr>`;
          });
        }
        
        tableHtml += '</tbody></table>';
        dataTableContainer.innerHTML = tableHtml;
        modal.style.display = 'flex'; // Use flex to center the modal
      });
    });

    document.querySelectorAll('.download-csv-btn').forEach(button => {
      button.addEventListener('click', function() {
        const chartId = this.dataset.chartId;
        // Get the current data from the chart instance
        const data = chartInstances[chartId].data.datasets[0].data;
        const labels = chartInstances[chartId].data.labels;

        let csvContent = '';

        // Determine headers for CSV
        if (chartId === 'revenue_by_country_bubble') {
          csvContent += 'Country,Revenue,Customer Count\n';
        } else if (labels && labels.length > 0) {
          csvContent += `${chartConfigs[chartId].options.plugins.title.text.split(' by ')[0]},Value\n`;
        } else {
          csvContent += 'Data\n';
        }

        // Populate CSV rows
        if (chartId === 'revenue_by_country_bubble') {
          labels.forEach((label, index) => {
            const item = data[index];
            csvContent += `${item.country},${item.y.toFixed(2)},${Math.round(item.r * item.r / 4)}\n`;
          });
        } else {
          labels.forEach((label, index) => {
            csvContent += `${label},${data[index]}\n`;
          });
        }

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        if (link.download !== undefined) { // feature detection
          const url = URL.createObjectURL(blob);
          link.setAttribute('href', url);
          link.setAttribute('download', `${chartId}_data.csv`);
          link.style.visibility = 'hidden';
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        }
      });
    });
  </script>
</body>
</html> 