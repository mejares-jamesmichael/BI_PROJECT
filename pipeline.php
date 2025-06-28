<?php
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
        die('Connect Error: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

$src = connectDb($sourceDb);
$dst = connectDb($destDb);

// Get all customers
$customers = $src->query('SELECT customerNumber, customerName, country, city FROM customers');
if (!$customers) die('Error fetching customers: ' . $src->error);

while ($cust = $customers->fetch_assoc()) {
    $customerNumber = $cust['customerNumber'];
    $customerName = $src->real_escape_string($cust['customerName']);
    $country = $src->real_escape_string($cust['country']);
    $city = $src->real_escape_string($cust['city']);

    // Total orders
    $ordersRes = $src->query("SELECT COUNT(*) as total_orders, MIN(orderDate) as first_order, MAX(orderDate) as last_order, GROUP_CONCAT(orderNumber) as order_ids FROM orders WHERE customerNumber = $customerNumber");
    $orders = $ordersRes->fetch_assoc();
    $total_orders = (int)$orders['total_orders'];
    $first_order = $orders['first_order'] ?? null;
    $last_order = $orders['last_order'] ?? null;
    $order_ids = $orders['order_ids'];

    // Total spent (from payments)
    $payRes = $src->query("SELECT SUM(amount) as total_spent FROM payments WHERE customerNumber = $customerNumber");
    $pay = $payRes->fetch_assoc();
    $total_spent = $pay['total_spent'] ?? 0;

    // Avg order value (from orderdetails)
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

    // Insert or update into destination
    $stmt = $dst->prepare("REPLACE INTO customer_analytics (customer_number, customer_name, country, city, total_orders, total_spent, avg_order_value, first_order_date, last_order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssiddss', $customerNumber, $customerName, $country, $city, $total_orders, $total_spent, $avg_order_value, $first_order, $last_order);
    $stmt->execute();
    $stmt->close();
}

// --- EMPLOYEE PERFORMANCE ETL ---
// Get all employees
$employees = $src->query('SELECT e.employeeNumber, e.lastName, e.firstName, e.jobTitle, e.officeCode, o.city as office_city FROM employees e LEFT JOIN offices o ON e.officeCode = o.officeCode');
if (!$employees) die('Error fetching employees: ' . $src->error);

while ($emp = $employees->fetch_assoc()) {
    $employeeNumber = $emp['employeeNumber'];
    $employeeName = $src->real_escape_string($emp['firstName'] . ' ' . $emp['lastName']);
    $jobTitle = $src->real_escape_string($emp['jobTitle']);
    $officeCity = $src->real_escape_string($emp['office_city']);

    // Customers managed
    $custRes = $src->query("SELECT customerNumber FROM customers WHERE salesRepEmployeeNumber = $employeeNumber");
    $customerNumbers = [];
    while ($row = $custRes->fetch_assoc()) {
        $customerNumbers[] = $row['customerNumber'];
    }
    $customersManaged = count($customerNumbers);

    // Total sales (sum of payments for all managed customers)
    $totalSales = 0;
    if ($customersManaged > 0) {
        $custList = implode(',', $customerNumbers);
        $salesRes = $src->query("SELECT SUM(amount) as total_sales FROM payments WHERE customerNumber IN ($custList)");
        $sales = $salesRes->fetch_assoc();
        $totalSales = $sales['total_sales'] ?? 0;
    }

    // Avg sales per customer
    $avgSalesPerCustomer = $customersManaged > 0 ? ($totalSales / $customersManaged) : 0;

    // Insert or update into destination
    $stmt = $dst->prepare("REPLACE INTO employee_performance (employee_number, employee_name, job_title, office_city, customers_managed, total_sales, avg_sales_per_customer) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssidd', $employeeNumber, $employeeName, $jobTitle, $officeCity, $customersManaged, $totalSales, $avgSalesPerCustomer);
    $stmt->execute();
    $stmt->close();
}

// --- SALES ANALYTICS ETL ---
// Aggregate by year and month
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
if (!$salesRes) die('Error fetching sales analytics: ' . $src->error);

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
if (!$productRes) die('Error fetching product performance: ' . $src->error);

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
// For each country, aggregate customer count, total revenue, avg revenue per customer, order count
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
if (!$geoRes) die('Error fetching geographic analytics: ' . $src->error);

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

$src->close();
$dst->close();
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'ETL completed for customer_analytics']);
exit; 