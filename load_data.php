<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials
$sourceDb = [
    'host' => 'sql300.infinityfree.com',
    'user' => 'if0_39344537',
    'pass' => 'kaelmejares7',
    'db'   => 'if0_39344537_source',
    'port' => 3306
];
$destDb = [
    'host' => 'sql300.infinityfree.com',
    'user' => 'if0_39344537',
    'pass' => 'kaelmejares7',
    'db'   => 'if0_39344537_destination',
    'port' => 3306
];

function connectDb($conf) {
    $mysqli = new mysqli($conf['host'], $conf['user'], $conf['pass'], $conf['db'], $conf['port']);
    if ($mysqli->connect_errno) {
        return null; // Return null on connection error
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

$src = connectDb($sourceDb);
$dst = connectDb($destDb);

if (!$src) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Could not connect to source database.']);
    exit;
}
if (!$dst) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Could not connect to destination database.']);
    $src->close();
    exit;
}

try {
    // Truncate destination tables before loading new data
    $dst->query("TRUNCATE TABLE customer_analytics");
    $dst->query("TRUNCATE TABLE employee_performance");
    $dst->query("TRUNCATE TABLE sales_analytics");
    $dst->query("TRUNCATE TABLE product_performance");
    $dst->query("TRUNCATE TABLE geographic_analytics");

    // --- CUSTOMER ANALYTICS ETL ---
    $customers = $src->query('SELECT customerNumber, customerName, country, city FROM customers');
    if (!$customers) throw new Exception('Error fetching customers: ' . $src->error);

    while ($cust = $customers->fetch_assoc()) {
        $customerNumber = $cust['customerNumber'];
        $customerName = $src->real_escape_string($cust['customerName']);
        $country = $src->real_escape_string($cust['country']);
        $city = $src->real_escape_string($cust['city']);

        $ordersRes = $src->query("SELECT COUNT(*) as total_orders, MIN(orderDate) as first_order, MAX(orderDate) as last_order, GROUP_CONCAT(orderNumber) as order_ids FROM orders WHERE customerNumber = $customerNumber");
        $orders = $ordersRes->fetch_assoc();
        $total_orders = (int)$orders['total_orders'];
        $first_order = $orders['first_order'] ?? null;
        $last_order = $orders['last_order'] ?? null;
        $order_ids = $orders['order_ids'];

        $payRes = $src->query("SELECT SUM(amount) as total_spent FROM payments WHERE customerNumber = $customerNumber");
        $pay = $payRes->fetch_assoc();
        $total_spent = $pay['total_spent'] ?? 0;

        $avg_order_value = null;
        if ($order_ids) {
            $orderIdList = $order_ids;
            $odRes = $src->query("SELECT orderNumber, SUM(quantityOrdered * priceEach) as order_value FROM orderdetails WHERE orderNumber IN ($orderIdList) GROUP BY orderNumber");
            $orderValues = [];
            while ($od = $odRes->fetch_assoc()) {
                $orderValues[] = $od['order_value'];
            }
            if (count($orderValues) > 0) {
                $avg_order_value = array_sum($orderValues) / count($orderValues);
            } else {
                $avg_order_value = 0;
            }
        } else {
            $avg_order_value = 0;
        }

        $stmt = $dst->prepare("REPLACE INTO customer_analytics (customer_number, customer_name, country, city, total_orders, total_spent, avg_order_value, first_order_date, last_order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssiddss', $customerNumber, $customerName, $country, $city, $total_orders, $total_spent, $avg_order_value, $first_order, $last_order);
        $stmt->execute();
        $stmt->close();
    }

    // --- EMPLOYEE PERFORMANCE ETL ---
    $employees = $src->query('SELECT e.employeeNumber, e.lastName, e.firstName, e.jobTitle, e.officeCode, o.city as office_city FROM employees e LEFT JOIN offices o ON e.officeCode = o.officeCode');
    if (!$employees) throw new Exception('Error fetching employees: ' . $src->error);

    while ($emp = $employees->fetch_assoc()) {
        $employeeNumber = $emp['employeeNumber'];
        $employeeName = $src->real_escape_string($emp['firstName'] . ' ' . $emp['lastName']);
        $jobTitle = $src->real_escape_string($emp['jobTitle']);
        $officeCity = $src->real_escape_string($emp['office_city']);

        $custRes = $src->query("SELECT customerNumber FROM customers WHERE salesRepEmployeeNumber = $employeeNumber");
        $customerNumbers = [];
        while ($row = $custRes->fetch_assoc()) {
            $customerNumbers[] = $row['customerNumber'];
        }
        $customersManaged = count($customerNumbers);

        $totalSales = 0;
        if ($customersManaged > 0) {
            $custList = implode(',', $customerNumbers);
            $salesRes = $src->query("SELECT SUM(amount) as total_sales FROM payments WHERE customerNumber IN ($custList)");
            $sales = $salesRes->fetch_assoc();
            $totalSales = $sales['total_sales'] ?? 0;
        }

        $avgSalesPerCustomer = $customersManaged > 0 ? ($totalSales / $customersManaged) : 0;

        $stmt = $dst->prepare("REPLACE INTO employee_performance (employee_number, employee_name, job_title, office_city, customers_managed, total_sales, avg_sales_per_customer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssidd', $employeeNumber, $employeeName, $jobTitle, $officeCity, $customersManaged, $totalSales, $avgSalesPerCustomer);
        $stmt->execute();
        $stmt->close();
    }

    // --- SALES ANALYTICS ETL ---
    $salesQuery = "
        SELECT 
            YEAR(o.orderDate) as year, 
            MONTH(o.orderDate) as month,
            COUNT(DISTINCT o.orderNumber) as order_count,
            COUNT(DISTINCT o.customerNumber) as customer_count,
            SUM(od.quantityOrdered * od.priceEach) as total_sales,
            AVG(order_totals.order_value) as avg_order_value
        FROM orders o
        JOIN orderdetails od ON o.orderNumber = od.orderNumber
        JOIN (
            SELECT orderNumber, SUM(quantityOrdered * priceEach) as order_value
            FROM orderdetails
            GROUP BY orderNumber
        ) as order_totals ON o.orderNumber = order_totals.orderNumber
        GROUP BY year, month
        ORDER BY year, month
    ";
    $salesRes = $src->query($salesQuery);
    if (!$salesRes) throw new Exception('Error fetching sales analytics: ' . $src->error);

    while ($row = $salesRes->fetch_assoc()) {
        $year = (int)$row['year'];
        $month = (int)$row['month'];
        $total_sales = $row['total_sales'] ?? 0;
        $order_count = (int)$row['order_count'];
        $customer_count = (int)$row['customer_count'];
        $avg_order_value = $row['avg_order_value'] ?? 0;

        $stmt = $dst->prepare("REPLACE INTO sales_analytics (year, month, total_sales, order_count, customer_count, avg_order_value) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iidiid', $year, $month, $total_sales, $order_count, $customer_count, $avg_order_value);
        $stmt->execute();
        $stmt->close();
    }

    // --- PRODUCT PERFORMANCE ETL ---
    $productQuery = "
        SELECT 
            p.productCode, 
            p.productName, 
            p.productLine,
            SUM(od.quantityOrdered) as total_quantity_sold,
            SUM(od.quantityOrdered * od.priceEach) as total_revenue,
            AVG(od.priceEach) as avg_price,
            COUNT(DISTINCT od.orderNumber) as order_count
        FROM products p
        LEFT JOIN orderdetails od ON p.productCode = od.productCode
        GROUP BY p.productCode, p.productName, p.productLine
    ";
    $productRes = $src->query($productQuery);
    if (!$productRes) throw new Exception('Error fetching product performance: ' . $src->error);

    while ($row = $productRes->fetch_assoc()) {
        $product_code = $row['productCode'];
        $product_name = $src->real_escape_string($row['productName']);
        $product_line = $src->real_escape_string($row['productLine']);
        $total_quantity_sold = $row['total_quantity_sold'] ?? 0;
        $total_revenue = $row['total_revenue'] ?? 0;
        $avg_price = $row['avg_price'] ?? 0;
        $order_count = $row['order_count'] ?? 0;

        $stmt = $dst->prepare("REPLACE INTO product_performance (product_code, product_name, product_line, total_quantity_sold, total_revenue, avg_price, order_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssiddi', $product_code, $product_name, $product_line, $total_quantity_sold, $total_revenue, $avg_price, $order_count);
        $stmt->execute();
        $stmt->close();
    }

    // --- GEOGRAPHIC ANALYTICS ETL ---
    $geoQuery = "
        SELECT 
            c.country,
            COUNT(DISTINCT c.customerNumber) as customer_count,
            SUM(IFNULL(p.amount,0)) as total_revenue,
            COUNT(DISTINCT o.orderNumber) as order_count
        FROM customers c
        LEFT JOIN payments p ON c.customerNumber = p.customerNumber
        LEFT JOIN orders o ON c.customerNumber = o.customerNumber
        GROUP BY c.country
    ";
    $geoRes = $src->query($geoQuery);
    if (!$geoRes) throw new Exception('Error fetching geographic analytics: ' . $src->error);

    while ($row = $geoRes->fetch_assoc()) {
        $country = $src->real_escape_string($row['country']);
        $customer_count = $row['customer_count'] ?? 0;
        $total_revenue = $row['total_revenue'] ?? 0;
        $order_count = $row['order_count'] ?? 0;
        $avg_revenue_per_customer = $customer_count > 0 ? ($total_revenue / $customer_count) : 0;

        $stmt = $dst->prepare("REPLACE INTO geographic_analytics (country, customer_count, total_revenue, avg_revenue_per_customer, order_count) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('siddi', $country, $customer_count, $total_revenue, $avg_revenue_per_customer, $order_count);
        $stmt->execute();
        $stmt->close();
    }

    // Update last ETL run timestamp
    file_put_contents('last_etl_run.txt', date('Y-m-d H:i:s'));

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Data loaded successfully!']);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if ($src) $src->close();
    if ($dst) $dst->close();
}

exit;
?>