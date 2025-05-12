<?php

// นำเข้าฟาย header.php
include '../admin/header.php';

// นำเข้าฟาย nav.php
include '../admin/nav.php';

// รวมการเชื่อมต่อฐานข้อมูล
include '../admin/db_connection.php';

// ฟังก์ชันแปลงเดือนเป็นชื่อเดือนภาษาไทย
function getThaiMonth($monthNumber)
{
    $months = [
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
    return $months[(int)$monthNumber] ?? '';
}

// ฟังก์ชันแปลงเดือนเป็นชื่อเดือนภาษาไทยและปีพุทธศักราช
function formatThaiMonthYear($start_month, $end_month, $year)
{
    $months = [
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

    // แปลงค่าเป็น integer เพื่อให้ตรงกับคีย์ใน array
    $start_month_int = (int)$start_month;
    $end_month_int = (int)$end_month;

    // ตรวจสอบว่าปีที่รับมาเป็นปี ค.ศ. แล้วบวก 543 เพื่อแปลงเป็น พ.ศ.
    if ($year < 2500) {
        $year_thai = $year + 543; // แปลงเป็นปีพุทธศักราช
    } else {
        $year_thai = $year; // หากเป็น พ.ศ. แล้วไม่ต้องแปลง
    }

    // ใช้ค่าที่แปลงแล้วเพื่อดึงชื่อเดือนจาก array
    $start_month_name = $months[$start_month_int] ?? '';
    $end_month_name = $months[$end_month_int] ?? '';

    if ($start_month_int === $end_month_int) {
        return "$start_month_name พ.ศ. $year_thai";
    } else {
        return "$start_month_name - $end_month_name พ.ศ. $year_thai";
    }
}

// ตรวจสอบว่ามีการส่งค่าเดือนและปีมาหรือไม่
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// แปลงปี ค.ศ. เป็น พ.ศ.
$thai_year = $selected_year + 543;

// หาจำนวนวันในเดือนที่เลือก
$num_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);

$sql = "SELECT p.payment_date, p.house_number, p.start_month, p.end_month, p.amount
        FROM payments p
        WHERE MONTH(p.payment_date) = ? AND YEAR(p.payment_date) = ? 
        AND p.status = 'approved'
        ORDER BY p.payment_date ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $selected_month, $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $date = $row['payment_date'];
        if (!isset($payments[$date])) {
            $payments[$date] = [];
        }
        $payments[$date][] = $row;
    }
    $stmt->close();
} else {
    die("การเตรียมคำสั่ง SQL ผิดพลาด: " . $conn->error);
}


// เริ่มต้นค่าการสรุป
$total_houses = [];
$total_months = 0;
$total_amount = 0.00;

// สร้างอาร์เรย์ของทุกวันที่ในเดือนที่เลือก
$dates = [];
for ($day = 1; $day <= $num_days; $day++) {
    $date = sprintf("%04d-%02d-%02d", $selected_year, $selected_month, $day);
    $dates[] = $date;
}
?>

<style>
    body {
        font-family: 'Roboto', Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: #f9f9f9;
    }

    article {
        padding: 120px;
        width: 90%;
        max-width: 960px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 0.95rem;
    }

    table th,
    table td {
        padding: 10px;
        border: 1px solid #dee2e6;
        text-align: center;
    }

    table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .date-header td {
        text-align: left;
        background-color: #e9ecef;
        font-weight: bold;
    }

    .summary-row td {
        font-size: 1.1rem;
        font-weight: bold;
        background-color: #f8f9fa;
    }


    /* สไตล์สำหรับการพิมพ์ */
    @media print {
        body {
            background-color: white;
            margin: 0;
        }

        .btn-group,
        .mb-4 {
            display: none;
        }

        article {
            width: 100%;
            box-shadow: none;
        }
    }
</style>

<article>

    <div class="report-container">
        <div class="header-text text-center mb-4">
            <h2>รายงานการจ่ายค่าส่วนกลาง</h2>
            <p>ประจำเดือน <?php echo getThaiMonth($selected_month); ?> พ.ศ. <?php echo $thai_year; ?></p>
            <hr>
        </div>

        <!-- ฟอร์มเลือกเดือนและปี -->
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="month" class="form-label">เลือกเดือน</label>
                    <select name="month" id="month" class="form-select" required>
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m == $selected_month) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>" . getThaiMonth($m) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="year" class="form-label">เลือกปี</label>
                    <select name="year" id="year" class="form-select" required>
                        <?php
                        $current_year = date('Y');
                        for ($y = $current_year - 5; $y <= $current_year + 5; $y++) {
                            $selected = ($y == $selected_year) ? 'selected' : '';
                            echo "<option value=\"$y\" $selected>" . ($y + 543) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary">แสดงรายงาน</button>
            </div>
        </form>

        <!-- ตารางรายงาน -->
        <table>
            <thead>
                <tr>
                    <th>บ้านเลขที่</th>
                    <th>จำนวนเดือน</th>
                    <th>จำนวนเงิน (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($payments)) {
                    foreach ($payments as $date => $daily_payments) {
                        $timestamp = strtotime($date);
                        $display_date = date('j', $timestamp) . ' ' . getThaiMonth(date('n', $timestamp)) . ' พ.ศ. ' . (date('Y', $timestamp) + 543);

                        echo "<tr class=\"date-header\"><td colspan=\"3\">วันที่ $display_date</td></tr>";

                        foreach ($daily_payments as $payment) {
                            // ตรวจสอบค่าที่ดึงมาจากฐานข้อมูล
                            $house_number = htmlspecialchars($payment['house_number']);
                            $start_month = intval($payment['start_month']);
                            $end_month = intval($payment['end_month']);
                            $amount = floatval($payment['amount']); // แปลงเป็นตัวเลขเพื่อการคำนวณ

                            // คำนวณจำนวนเดือนที่ชำระ
                            $months_paid = ($end_month >= $start_month) ? ($end_month - $start_month) + 1 : 0;

                            $amount = floatval($payment['amount']); // แปลงเป็นตัวเลขเพื่อการคำนวณ

                            if (!empty($house_number)) {
                                $total_houses[$house_number] = true; // เก็บเฉพาะบ้านที่มีการชำระ
                            }
                            $total_months += $months_paid; // เพิ่มจำนวนเดือนที่ชำระ
                            $total_amount += $amount; // เพิ่มจำนวนเงินที่ชำระ


                            // แสดงผลในตาราง
                            echo "<tr>
                <td>$house_number</td>
                <td>$months_paid</td>
                <td>" . number_format($amount, 2) . "</td>
            </tr>";
                        }
                    }
                }
                if (empty($payments)) {
                    echo "<tr><td colspan='3' class='text-center text-muted'>ไม่มีข้อมูลการชำระเงินในเดือนนี้</td></tr>";
                }

                ?>

                <tr class="summary-row">
                    <td>รวม</td>
                    <td>ชำระ: <?php echo count($total_houses); ?> หลัง</td>
                    <td>ทั้งหมด: <?php echo number_format($total_amount, 2); ?> บาท</td>
                </tr>

            </tbody>
        </table>

        <div class="text-center mt-4">
            <button onclick="window.location.href='manage.php';" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> ย้อนกลับ
            </button>
            <button class="btn btn-success" onclick="window.print()">พิมพ์รายงาน</button>
        </div>

    </div>
</article>


<?php
$conn->close();
?>