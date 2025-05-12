<?php
// dashboard.php

// Enable error reporting for debugging (Remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../admin/auth_check.php';
require_once '../admin/db_connection.php';

// Function to sanitize output
function sanitize_output($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Function to execute a SQL query with parameters
function execute_query($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

// ตรวจสอบว่ามีการร้องขอข้อมูลสำหรับกราฟหรือไม่
if (isset($_GET['action']) && $_GET['action'] === 'fetch_payments') {
    // รับค่าปีและเดือนจากการร้องขอ
    $year = isset($_GET['year']) ? intval($_GET['year']) : (date('Y') + 543);
    $month = isset($_GET['month']) ? intval($_GET['month']) : 0; // 0 สำหรับทุกเดือน

    // ไม่แปลงปีพุทธศักราชเป็นคริสต์ศักราช
    $buddhist_year = $year;

    // ตรวจสอบค่าเดือน (1-12 หรือ 0)
    if ($month < 0 || $month > 12) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid month parameter.'
        ]);
        exit;
    }

    // ดึงข้อมูลจำนวนบ้านที่ชำระเงินแล้ว
    $condition = $month === 0 ? "" : " AND MONTH(payment_date) = ?";
    $sql_paid = "SELECT COUNT(DISTINCT house_number) AS paid_houses FROM payments WHERE status = 'approved' AND payment_year = ?" . $condition;
    $params = $month === 0 ? [$buddhist_year] : [$buddhist_year, $month];
    $types = $month === 0 ? "i" : "ii";

    $result_paid = execute_query($conn, $sql_paid, $params, $types);

    if ($result_paid && $row = $result_paid->fetch_assoc()) {
        $paid_houses = intval($row['paid_houses']);

        // ดึงจำนวนผู้ใช้งานทั้งหมดจากฐานข้อมูล
        $sql_total_users = "SELECT COUNT(*) AS total_users FROM users";
        $result_total_users = execute_query($conn, $sql_total_users);
        $total_users = ($result_total_users->num_rows > 0) ? intval($result_total_users->fetch_assoc()['total_users']) : 0;
        $unpaid_houses = max($total_users - $paid_houses, 0);

        // ดึงข้อมูลยอดเงินที่ได้รับรายปีตามการกรอง
        $sql_payments_per_year = "
            SELECT payment_year, SUM(amount) AS total_amount
            FROM payments
            WHERE payment_year = ?" . $condition . "
            GROUP BY payment_year
            ORDER BY payment_year ASC
        ";
        $result_yearly = execute_query($conn, $sql_payments_per_year, $params, $types);

        $payment_years = [];
        $payment_amounts = [];
        if ($result_yearly && $result_yearly->num_rows > 0) {
            while ($row = $result_yearly->fetch_assoc()) {
                $payment_years[] = $row['payment_year'];
                $payment_amounts[] = floatval($row['total_amount']) ?: 0;
            }
        }

        // ดึงข้อมูลยอดเงินที่ได้รับรายวันตามการกรอง (เฉพาะเมื่อเลือกเดือน)
        $daily_payments = [];
        if ($month !== 0) {
            $sql_daily = "
                SELECT DAY(payment_date) AS day, SUM(amount) AS total_amount
                FROM payments
                WHERE payment_year = ? AND MONTH(payment_date) = ?
                GROUP BY DAY(payment_date)
                ORDER BY DAY(payment_date) ASC
            ";
            $result_daily = execute_query($conn, $sql_daily, [$buddhist_year, $month], "ii");

            if ($result_daily && $result_daily->num_rows > 0) {
                while ($row = $result_daily->fetch_assoc()) {
                    $daily_payments[] = [
                        'day' => intval($row['day']),
                        'total_amount' => floatval($row['total_amount']) ?: 0
                    ];
                }
            }
        }

        // ดึงข้อมูลสถานะปัญหาตามการกรอง โดยเชื่อมโยงกับการชำระเงินผ่าน house_number
        $sql_problem_status = "
            SELECT p.status, COUNT(*) AS count
            FROM problems p
            JOIN payments pay ON p.house_number = pay.house_number
            WHERE pay.payment_year = ?" . $condition . "
            GROUP BY p.status
        ";
        $result_problem = execute_query($conn, $sql_problem_status, $params, $types);

        $problem_status_counts = [
            'pending' => 0,
            'resolved' => 0,
            'unmodifiable' => 0
        ];
        if ($result_problem && $result_problem->num_rows > 0) {
            while ($row = $result_problem->fetch_assoc()) {
                $status = $row['status'];
                $count = intval($row['count']);
                if (array_key_exists($status, $problem_status_counts)) {
                    $problem_status_counts[$status] = $count;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'paid_houses' => $paid_houses,
                'unpaid_houses' => $unpaid_houses,
                'payment_years' => $payment_years,
                'payment_amounts' => $payment_amounts,
                'daily_payments' => $daily_payments,
                'problem_status_counts' => [
                    'pending' => $problem_status_counts['pending'],
                    'resolved' => $problem_status_counts['resolved'],
                    'unmodifiable' => $problem_status_counts['unmodifiable']
                ],
                'total_users' => $total_users
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No data found.'
        ]);
    }

    $conn->close();
    exit;
}

// ดึงข้อมูลจำนวนผู้ใช้งานทั้งหมด (Total Houses)
$sql_total_users = "SELECT COUNT(*) AS total_users FROM users";
$result_total_users = execute_query($conn, $sql_total_users);
$total_users = ($result_total_users->num_rows > 0) ? intval($result_total_users->fetch_assoc()['total_users']) : 0;

// ดึงข้อมูลจำนวนการชำระเงินที่รอการตรวจสอบ
$sql_payments_pending = "SELECT COUNT(*) AS pending_payments FROM payments WHERE status = 'pending'";
$result_payments_pending = execute_query($conn, $sql_payments_pending);
$pending_payments = ($result_payments_pending->num_rows > 0) ? intval($result_payments_pending->fetch_assoc()['pending_payments']) : 0;

// ดึงข้อมูลจำนวนปัญหาที่รอการแก้ไข
$sql_problems_pending = "SELECT COUNT(*) AS pending_problems FROM problems WHERE status = 'pending'";
$result_problems_pending = execute_query($conn, $sql_problems_pending);
$pending_problems = ($result_problems_pending->num_rows > 0) ? intval($result_problems_pending->fetch_assoc()['pending_problems']) : 0;

// ดึงข้อมูลจำนวนปัญหาที่ได้รับการแก้ไขแล้ว
$sql_problems_resolved = "SELECT COUNT(*) AS resolved_problems FROM problems WHERE status = 'resolved'";
$result_problems_resolved = execute_query($conn, $sql_problems_resolved);
$resolved_problems = ($result_problems_resolved->num_rows > 0) ? intval($result_problems_resolved->fetch_assoc()['resolved_problems']) : 0;

// เพิ่มจำนวนปัญหาที่ไม่สามารถแก้ไขได้
$sql_problems_unmodifiable = "SELECT COUNT(*) AS unmodifiable_problems FROM problems WHERE status = 'unmodifiable'";
$result_problems_unmodifiable = execute_query($conn, $sql_problems_unmodifiable);
$unmodifiable_problems = ($result_problems_unmodifiable->num_rows > 0) ? intval($result_problems_unmodifiable->fetch_assoc()['unmodifiable_problems']) : 0;

// ดึงข้อมูลยอดเงินที่ได้รับทั้งหมด
$sql_total_amount = "SELECT SUM(amount) AS total_received FROM payments WHERE status = 'approved'";
$result_total_amount = execute_query($conn, $sql_total_amount);
$total_received = ($result_total_amount->num_rows > 0) ? floatval($result_total_amount->fetch_assoc()['total_received']) : 0;

$sql_total_withdrawn = "SELECT SUM(amount) AS total_withdrawn FROM withdrawals WHERE status = 'approved'";
$result_total_withdrawn = execute_query($conn, $sql_total_withdrawn);
$total_withdrawn = ($result_total_withdrawn->num_rows > 0) ? floatval($result_total_withdrawn->fetch_assoc()['total_withdrawn']) : 0;

// ดึงข้อมูลยอดเงินที่ได้รับรายปี
$current_year = date('Y') + 543; // Thai Buddhist calendar
$start_year = 2552;
$years = range($start_year, $current_year);

// Initialize payment_data array
$payment_data = [];
foreach ($years as $year_item) {
    $payment_data[$year_item] = 0;
}

$sql_payments_per_year = "
    SELECT payment_year, SUM(amount) AS total_amount
    FROM payments
    WHERE payment_year >= $start_year
    GROUP BY payment_year
    ORDER BY payment_year ASC
";
$result_payments_per_year = execute_query($conn, $sql_payments_per_year);

if ($result_payments_per_year->num_rows > 0) {
    while ($row = $result_payments_per_year->fetch_assoc()) {
        $payment_year = $row['payment_year'];
        $total_amount = $row['total_amount'];
        // Ensure the payment_year exists in the $payment_data array
        if (array_key_exists($payment_year, $payment_data)) {
            $payment_data[$payment_year] = floatval($total_amount) ?: 0;
        }
    }
}

// ดึงข้อมูลการชำระเงินรายเดือน (สำหรับ initial load)
$current_gregorian_year = date('Y'); // Assuming the database uses Gregorian year
$current_month = date('n'); // Numeric representation of a month, without leading zeros

// Paid houses count
$sql_paid = "SELECT COUNT(DISTINCT house_number) AS paid_houses FROM payments WHERE status = 'approved' AND YEAR(payment_date) = $current_gregorian_year AND MONTH(payment_date) = $current_month";
$result_paid = execute_query($conn, $sql_paid);
$paid_houses = ($result_paid->num_rows > 0) ? intval($result_paid->fetch_assoc()['paid_houses']) : 0;

// Unpaid houses count
$unpaid_houses = $total_users - $paid_houses;
$unpaid_houses = max($unpaid_houses, 0); // Prevent negative counts

// ข้อมูลสำหรับแผนภูมิสถานะปัญหา
$problem_status_data = [
    'pending' => $pending_problems,
    'resolved' => $resolved_problems,
    'unmodifiable' => $unmodifiable_problems
];

// ดึงข้อมูลการชำระเงินรายวันสำหรับเดือนปัจจุบัน (สำหรับการโหลดครั้งแรก)
$sql_initial_daily = "
    SELECT DAY(payment_date) AS day, SUM(amount) AS total_amount
    FROM payments
    WHERE status = 'approved' AND payment_year = $current_year AND MONTH(payment_date) = $current_month
    GROUP BY DAY(payment_date)
    ORDER BY DAY(payment_date) ASC
";
$result_initial_daily = execute_query($conn, $sql_initial_daily);
$initial_daily_payments = [];
if ($result_initial_daily && $result_initial_daily->num_rows > 0) {
    while ($row = $result_initial_daily->fetch_assoc()) {
        $initial_daily_payments[] = [
            'day' => intval($row['day']),
            'total_amount' => floatval($row['total_amount']) ?: 0
        ];
    }
}

$conn->close();

// Now, $payment_data should be an array
$payment_years = array_keys($payment_data);
$payment_amounts = array_values($payment_data);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ดูแลระบบ</title>
    <!-- ใช้เวอร์ชันเสถียรของ Font Awesome พร้อมกับ Integrity Attribute -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pQtjjC0KpPxLWr10PuF4zpFsWDRzKl3G1oNTmMEnHEHHIXGH1dSsKG+4kOZh3GxC0sKEE6Ck2vLu8tXLFu1xFg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f5f5f5;
        }

        /* Existing card styles */
        .card {
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        .card-header {
            font-size: 18px;
            font-weight: bold;
            background-color: #007bff;
            color: white;
            padding: 15px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            text-align: center;
        }

        .card-body {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            padding: 30px 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-body {
                font-size: 20px;
                padding: 20px 10px;
            }

            .card-header {
                font-size: 16px;
                padding: 10px;
            }
        }

        /* Article CSS as per user request */
        article {
            margin-left: 0;
            padding: 20px;
            background-color: white;
            margin-top: 100px;
        }

        /* Responsive adjustments for article */
        @media (max-width: 768px) {
            article {
                margin-left: 0;
                margin-top: 50px;
                padding: 10px;
            }
        }

        /* ตารางแสดงยอดเงินรายวัน */
        #dailyPaymentsTable {
            margin-top: 30px;
        }
    </style>
</head>

<body>

    <?php
    // Re-enable includes if you have a sidebar/navigation
    include '../admin/header.php';
    include '../admin/nav.php';
    ?>

    <article>
        <div class="container">
            <h1 class="text-center mb-4">แดชบอร์ดผู้ดูแลระบบ</h1>
            <!-- Filter Controls -->
            <!-- Filter Controls -->
            <div class="row mb-4">
                <!-- Year Filter -->
                <div class="col-md-3">
                    <label for="filterYear" class="form-label">เลือกปี:</label>
                    <select id="filterYear" class="form-select">
                        <?php
                        // Generate year options in Thai Buddhist calendar
                        for ($y = $start_year; $y <= $current_year; $y++) {
                            // Set the current year as selected by default
                            $selected = ($y == $current_year) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Month Filter -->
                <div class="col-md-3">
                    <label for="filterMonth" class="form-label">เลือกเดือน:</label>
                    <select id="filterMonth" class="form-select">
                        <option value="0" selected>ทั้งหมด</option>
                        <?php
                        // Generate month options
                        $thai_month_names = [
                            1 => 'มกราคม',
                            2 => 'กุมภาพันธ์',
                            3 => 'มีนาคม',
                            4 => 'เมษายน',
                            5 => 'พฤษภาคม',
                            6 => 'มิถุนายน',
                            7 => 'กรกฎาคม',
                            8 => 'สิงหาคม',
                            9 => 'กันยายน',
                            10 => 'ตุลาคม',
                            11 => 'พฤศจิกายน',
                            12 => 'ธันวาคม'
                        ];
                        for ($m = 1; $m <= 12; $m++) {
                            echo "<option value='$m'>{$thai_month_names[$m]}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">สถานะการชำระเงิน:</label>
                    <select id="filterStatus" class="form-select">
                        <option value="all" selected>ทั้งหมด</option>
                        <option value="approved">ชำระแล้ว</option>
                        <option value="pending">รอการตรวจสอบ</option>
                    </select>
                </div>

                <!-- Problem Status Filter -->
                <div class="col-md-3">
                    <label for="filterProblemStatus" class="form-label">สถานะปัญหา:</label>
                    <select id="filterProblemStatus" class="form-select">
                        <option value="all" selected>ทั้งหมด</option>
                        <option value="pending">รอการแก้ไข</option>
                        <option value="resolved">แก้ไขแล้ว</option>
                        <option value="unmodifiable">ไม่สามารถแก้ไขได้</option>
                    </select>
                </div>
            </div>

            <!-- Button to trigger the filtering process -->
            <div class="row mb-4">
                <div class="col-md-12 d-flex align-items-end">
                    <button id="filterButton" class="btn btn-primary w-100">กรองข้อมูล</button>
                </div>
            </div>


            <!-- Statistics Cards -->
            <div class="row text-center">
                <div class="col-sm-6 col-lg-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users"></i> จำนวนผู้ใช้งานทั้งหมด (คน)
                        </div>
                        <div class="card-body">
                            <?php echo sanitize_output($total_users); ?> 
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-check-alt"></i> ชำระเงินที่รอการตรวจสอบ (รายการ)
                        </div>
                        <div class="card-body">
                            <?php echo sanitize_output($pending_payments); ?> 
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle"></i> ปัญหาที่ยังไม่แก้ไข (ปัญหา)
                        </div>
                        <div class="card-body">
                            <?php echo sanitize_output($pending_problems); ?> 
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-check-circle"></i> ปัญหาที่แก้ไขแล้ว (ปัญหา)
                        </div>
                        <div class="card-body">
                            <?php echo sanitize_output($resolved_problems); ?> 
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-wallet"></i> ยอดเงินทั้งหมดที่ได้รับ (บาท)
                        </div>
                        <div class="card-body">
                            <?php echo number_format($total_received, 2); ?> 
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-hand-holding-usd"></i> จำนวนเงินที่เบิก (บาท)
                        </div>
                        <div class="card-body">
                            <?php echo number_format($total_withdrawn, 2); ?> 
                        </div>
                    </div>
                </div>

            </div>
            <!-- Charts Section -->
            <div class="row">
                <!-- Yearly Payments Line Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> รายงานจำนวนเงินที่ได้รับรายปี
                        </div>
                        <div class="card-body">
                            <canvas id="paymentChart" role="img" aria-label="กราฟเส้นแสดงยอดเงินที่ได้รับรายปี"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Problem Status Bar Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> สถานะปัญหา
                        </div>
                        <div class="card-body">
                            <canvas id="problemBarChart" role="img" aria-label="กราฟแท่งแสดงสถานะปัญหา"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Monthly Payments Pie Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i> การชำระเงินรายเดือน
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyPaymentPieChart" role="img" aria-label="กราฟวงกลมแสดงการชำระเงินรายเดือน"></canvas>
                        </div>
                    </div>
                </div>

                <!-- ตารางแสดงยอดเงินรายวัน -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-table"></i> รายละเอียดการชำระเงินรายวัน
                        </div>
                        <div class="card-body">
                            <table class="table table-striped" id="dailyPaymentsTable">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>ยอดเงินที่ได้รับ (บาท)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- ข้อมูลจะถูกเพิ่มผ่าน JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </article>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery for simplicity -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script> <!-- Optional: Data Labels Plugin -->

    <script>
        // Declare globally to allow updates
        let monthlyPaymentPieChart;
        let paymentChart;
        let problemBarChart;

        // Function to initialize the Monthly Payments Pie Chart
        function initializeMonthlyPaymentChart(paidCount, unpaidCount) {
            if (monthlyPaymentPieChart) {
                monthlyPaymentPieChart.destroy(); // ทำลายอินสแตนซ์เก่า
            }

            const paymentStatusLabels = ['ชำระแล้ว', 'ยังไม่ได้ชำระ'];
            const paymentCountsMonthly = [paidCount, unpaidCount];

            const ctxMonthlyPie = document.getElementById('monthlyPaymentPieChart').getContext('2d');
            monthlyPaymentPieChart = new Chart(ctxMonthlyPie, {
                type: 'pie',
                data: {
                    labels: paymentStatusLabels,
                    datasets: [{
                        data: paymentCountsMonthly,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.6)', // สีสำหรับชำระแล้ว
                            'rgba(255, 99, 132, 0.6)' // สีสำหรับยังไม่ได้ชำระ
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)', // ขอบสีสำหรับชำระแล้ว
                            'rgba(255, 99, 132, 1)' // ขอบสีสำหรับยังไม่ได้ชำระ
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        datalabels: {
                            formatter: (value, context) => {
                                return value + ' หลัง';
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        // Function to update the Monthly Payments Pie Chart
        function updateMonthlyPaymentChart(paidCount, unpaidCount) {
            if (monthlyPaymentPieChart) {
                monthlyPaymentPieChart.data.datasets[0].data = [paidCount, unpaidCount];
                monthlyPaymentPieChart.update();
            }
        }

        // Function to initialize or update other charts
        function initializeCharts(paymentYears, paymentAmounts, problemData) {
            // Yearly Payments Line Chart
            const ctxLine = document.getElementById('paymentChart').getContext('2d');
            if (paymentChart) {
                paymentChart.destroy();
            }
            paymentChart = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: paymentYears,
                    datasets: [{
                        label: 'ยอดเงินที่ได้รับ (บาท)',
                        data: paymentAmounts,
                        fill: false,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' บาท';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' บาท';
                                }
                            }
                        }
                    }
                }
            });

            // Problem Status Bar Chart
            const problemLabels = ['รอการแก้ไข', 'แก้ไขแล้ว', 'ไม่สามารถแก้ไขได้'];
            const ctxBar = document.getElementById('problemBarChart').getContext('2d');
            if (problemBarChart) {
                problemBarChart.destroy();
            }
            problemBarChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: problemLabels,
                    datasets: [{
                        label: 'จำนวนปัญหา',
                        data: [
                            problemData.pending,
                            problemData.resolved,
                            problemData.unmodifiable
                        ],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)', // รอการแก้ไข
                            'rgba(75, 192, 192, 0.6)', // แก้ไขแล้ว
                            'rgba(255, 205, 86, 0.6)' // ไม่สามารถแก้ไขได้
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 205, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' ปัญหา';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Function to initialize or update the Daily Payments Table
        function initializeDailyPaymentsTable(dailyPayments) {
            const tableBody = $('#dailyPaymentsTable tbody');
            tableBody.empty(); // ล้างข้อมูลเก่า

            if (dailyPayments.length === 0) {
                tableBody.append('<tr><td colspan="2" class="text-center">ไม่มีข้อมูลการชำระเงินในเดือนนี้</td></tr>');
                return;
            }

            dailyPayments.forEach(payment => {
                const row = `<tr>
                                <td>${payment.day}</td>
                                <td>${numberWithCommas(payment.total_amount.toFixed(2))}</td>
                             </tr>`;
                tableBody.append(row);
            });
        }

        // Helper function สำหรับการจัดรูปแบบตัวเลขด้วย comma
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        $(document).ready(function() {
            // Initialize all charts on page load
            initializeCharts(
                <?php echo json_encode($payment_years); ?>,
                <?php echo json_encode($payment_amounts); ?>,
                <?php echo json_encode($problem_status_data); ?>
            );
            initializeMonthlyPaymentChart(<?php echo json_encode($paid_houses); ?>, <?php echo json_encode($unpaid_houses); ?>);

            // Initialize Daily Payments Table with initial data
            initializeDailyPaymentsTable(<?php echo json_encode($initial_daily_payments); ?>);

            $('#filterButton').on('click', function() {
                const selectedYear = $('#filterYear').val();
                const selectedMonth = $('#filterMonth').val();
                const selectedStatus = $('#filterStatus').val();
                const selectedProblemStatus = $('#filterProblemStatus').val();

                // Disable the button and show loading text
                $('#filterButton').prop('disabled', true).text('กำลังโหลด...');

                // Fetch data via AJAX from the same file
                $.ajax({
                    url: 'dashboard.php', // เรียก AJAX ไปยังไฟล์เดียวกัน
                    type: 'GET',
                    data: {
                        action: 'fetch_payments',
                        year: selectedYear,
                        month: selectedMonth,
                        status: selectedStatus, // เพิ่มสถานะการชำระเงิน
                        problem_status: selectedProblemStatus // เพิ่มสถานะปัญหา
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // อัปเดตแผนภูมิวงกลมและกราฟด้วยข้อมูลใหม่
                            updateMonthlyPaymentChart(response.data.paid_houses, response.data.unpaid_houses);
                            initializeCharts(response.data.payment_years, response.data.payment_amounts, response.data.problem_status_counts);
                            initializeDailyPaymentsTable(response.data.daily_payments);
                        } else {
                            alert(response.message || 'ไม่สามารถดึงข้อมูลได้');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);    
                        alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
                    },
                    complete: function() {
                        // Re-enable the button and reset text
                        $('#filterButton').prop('disabled', false).text('กรองข้อมูล');
                    }
                });
            });

        });
        
    </script>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>