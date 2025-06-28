# Retro BI Dashboard

This project is a web-based Business Intelligence (BI) dashboard with a fun, retro gaming theme. It features an ETL (Extract, Transform, Load) pipeline that processes data from a source database (`classicmodels`) and loads it into an analytics-ready destination database. The dashboard then visualizes this data, providing insights into sales, product performance, customer behavior, and more.

## Features

- **ETL Pipeline:** A PHP script (`pipeline.php`) that aggregates raw data into meaningful analytics tables for:
  - Customer Analytics
  - Employee Performance
  - Geographic Analytics
  - Product Performance
  - Sales Analytics
- **Interactive Dashboard:** A dynamic dashboard (`d_dashboard/index.php`) built with PHP and Chart.js to visualize key business metrics.
- **Web-based ETL Trigger:** An easy-to-use interface (`etl.html`) to run the ETL process on demand.
- **Retro Gaming Theme:** A unique and fun user interface inspired by classic pixel-art games.

## Technology Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Charting Library:** [Chart.js](https://www.chartjs.org/)
- **Hosting Service:** [InfinityFree](https://www.infinityfree.com/)

## Project Structure

```
.
├
│── index.php         # The main dashboard file
├── destination.sql       # Schema for the destination analytics database
├── etl.html              # A simple webpage to trigger the ETL pipeline
├── pipeline.php          # The core ETL script
├── README.md             # This file
└── source.sql            # Schema and data for the source database (classicmodels)
```

## Deployment on InfinityFree

This project is currently hosted and running on `InfinityFree`

To visit, just click this link -> [devhivepupt](http://devhivepupt.great-site.net/)
