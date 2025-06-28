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

## Project Structure

```
.
├── d_dashboard/          # Contains the web dashboard application
│   └── index.php         # The main dashboard file
├── destination.sql       # Schema for the destination analytics database
├── etl.html              # A simple webpage to trigger the ETL pipeline
├── pipeline.php          # The core ETL script
├── README.md             # This file
└── source.sql            # Schema and data for the source database (classicmodels)
```

## Local Setup and Installation

1.  **Prerequisites:**
    - PHP 7.4+
    - A running MySQL server

2.  **Clone the repository:**
    ```bash
    git clone <your-repository-url>
    cd BI_PROJECT
    ```

3.  **Database Setup:**
    - Create a new MySQL database (e.g., `bi_project_db`).
    - Import the source data and schema: `mysql -u your_user -p your_database_name < source.sql`
    - Import the destination schema: `mysql -u your_user -p your_database_name < destination.sql`

4.  **Environment Configuration:**
    The PHP scripts (`pipeline.php` and `d_dashboard/index.php`) are configured to read database credentials from environment variables. You will need to set the following for your local environment:
    - `DB_HOST`
    - `DB_USER`
    - `DB_PASS`
    - `DB_NAME`
    - `DB_PORT`

5.  **Run the ETL Pipeline:**
    Execute the pipeline script from your terminal to populate the analytics tables.
    ```bash
    php pipeline.php
    ```
    Alternatively, you can open the `etl.html` file in a browser to trigger the pipeline.

6.  **Run the Dashboard:**
    Serve the `d_dashboard/` directory using a local web server. You can use the built-in PHP server:
    ```bash
    php -S localhost:8000 -t d_dashboard
    ```
    Now you can access the dashboard at `http://localhost:8000`.

## Deployment on Render

This project is well-suited for deployment on Render.

1.  **Database:**
    - Create a new **MySQL** instance on Render.
    - Use a local `mysql` client or a GUI tool to connect to your Render database and import `source.sql` and `destination.sql`.

2.  **Web Service (Dashboard):**
    - Create a new **Web Service** on Render and connect it to your Git repository.
    - **Runtime:** `PHP`
    - **Root Directory:** `d_dashboard`
    - **Start Command:** `apache2-foreground`
    - Add the database credentials from your Render MySQL instance as environment variables in the **Environment** tab.

3.  **Cron Job (ETL):**
    - To automate the ETL process, create a new **Cron Job** on Render.
    - **Runtime:** `PHP`
    - **Command:** `php pipeline.php`
    - **Schedule:** Set a schedule for how often you want the ETL to run (e.g., `0 0 * * *` for daily at midnight).
    - Add the same database environment variables as the web service.
