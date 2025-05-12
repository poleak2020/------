<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// นำเข้าไฟล์ header.php
include '../admin/header.php';

// นำเข้าไฟล์ nav.php
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
function formatThaiMonthYear($month, $year)
{
    $thai_month = getThaiMonth($month);
    $thai_year = ($year < 2500) ? ($year + 543) : $year;
    return "$thai_month พ.ศ. $thai_year";
}

// ตรวจสอบว่ามีการส่งค่าเดือนและปีมาหรือไม่
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// แปลงปี ค.ศ. เป็น พ.ศ.
$thai_year = formatThaiMonthYear($selected_month, $selected_year);

// หาวันแรกและวันสุดท้ายของเดือนที่เลือก
$start_date = "$selected_year-$selected_month-01 00:00:00";
$end_date = date("Y-m-t 23:59:59", strtotime($start_date));

// ดึงข้อมูลปัญหาทั้งหมดในเดือนและปีที่เลือก พร้อมกับการดำเนินการแก้ไข
$sql = "SELECT p.id, p.house_number, p.description, p.status, p.created_at, 
               a.action, a.performed_by, a.performed_at, u.owner_name
        FROM problems p
        LEFT JOIN actions a ON p.id = a.problem_id
        LEFT JOIN users u ON a.performed_by = u.id
        WHERE p.created_at BETWEEN ? AND ?
        ORDER BY p.id ASC, a.performed_at ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $problems = [];
    while ($row = $result->fetch_assoc()) {
        $problems[$row['id']][] = $row;
    }

    $stmt->close();
} else {
    die("SQL Preparation Error: " . $conn->error);
}

// สรุปข้อมูล
$total_problems = count($problems);
$pending_problems = 0;
$received_problems = 0;
$resolved_problems = 0;
$unresolved_problems = 0;

foreach ($problems as $problem_id => $actions) {
    $status = end($actions)['status']; // สถานะล่าสุดของปัญหา
    if ($status === 'pending') {
        $pending_problems++;
    } elseif ($status === 'received') {
        $received_problems++;
    } elseif ($status === 'resolved') {
        $resolved_problems++;
    } elseif ($status === 'unmodifiable') {
        $unresolved_problems++;
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการแก้ปัญหารายเดือน</title>
    <!-- เพิ่ม Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .report-container {
            padding: 100px;
            background-color: white;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
        }

        .header-text h2 {
            font-weight: bold;
            margin-bottom: 15px;
        }

        .summary {
            margin-top: 20px;
            font-size: 18px;
        }

        .summary p {
            font-size: 16px;
        }

        table {
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            text-align: center;
            padding: 8px;
        }

        th {
            background-color: #f8f9fa;
        }

        .table-secondary {
            background-color: #f8f9fa;
        }

        .status-pending {
            background-color: #ffcccc;
        }

        .status-received {
            background-color: #ffffcc;
        }

        .status-resolved {
            background-color: #ccffcc;
        }

        .status-unmodifiable {
            background-color: #ff9999;
        }

        @media print {
            .report-container {
                border: none;
                box-shadow: none;
                margin: 0;
            }
            .btn, .form-container {
                display: none;
            }
        }

        .print-button {
            margin-top: 20px;
            text-align: right;
        }

        .form-container {
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container report-container">
        <div class="header-text text-center">
            <h2>รายงานการแก้ปัญหารายเดือน</h2>
            <p><?php echo htmlspecialchars($thai_year, ENT_QUOTES, 'UTF-8'); ?></p>
            <hr>
        </div>

        <!-- ปุ่มย้อนกลับไปยังหน้า ../admin/problems.php -->
        <div class="text-start mb-4">
            <a href="../admin/problems.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> ย้อนกลับไปยังหน้าปัญหา
            </a>
        </div>

        <!-- ฟอร์มเลือกเดือนและปี -->
        <form method="GET" class="form-container">
            <div class="row">
                <div class="col-md-4">
                    <label for="month" class="form-label">เลือกเดือน</label>
                    <select name="month" id="month" class="form-select" required>
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m == $selected_month) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>" . htmlspecialchars(getThaiMonth($m), ENT_QUOTES, 'UTF-8') . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
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
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">แสดงรายงาน</button>
                </div>
            </div>
        </form>

        <!-- ตารางรายงาน -->
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>ลำดับ</th>
                    <th>บ้านเลขที่</th>
                    <th>รายละเอียดปัญหา</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_problems > 0) {
                    $counter = 1;
                    foreach ($problems as $problem_id => $actions) {
                        $problem = $actions[0]; // ข้อมูลปัญหา

                        // กำหนดสถานะและสีพื้นหลัง
                        $status_text = '';
                        $row_class = '';
                        switch ($problem['status']) {
                            case 'pending':
                                $status_text = 'ยังไม่ได้ดำเนินการ';
                                $row_class = 'status-pending';
                                break;
                            case 'received':
                                $status_text = 'กำลังดำเนินการ';
                                $row_class = 'status-received';
                                break;
                            case 'resolved':
                                $status_text = 'แก้ไขแล้ว';
                                $row_class = 'status-resolved';
                                break;
                            case 'unmodifiable':
                                $status_text = 'แก้ไขปัญหาไม่ได้';
                                $row_class = 'status-unmodifiable';
                                break;
                            default:
                                $status_text = 'ไม่ระบุสถานะ';
                                break;
                        }

                        echo '<tr class="' . htmlspecialchars($row_class, ENT_QUOTES, 'UTF-8') . '">
                                <td>' . htmlspecialchars($counter++, ENT_QUOTES, 'UTF-8') . '</td>
                                <td>' . htmlspecialchars($problem['house_number'], ENT_QUOTES, 'UTF-8') . '</td>
                                <td>' . htmlspecialchars($problem['description'], ENT_QUOTES, 'UTF-8') . '</td>
                                <td>' . htmlspecialchars($status_text, ENT_QUOTES, 'UTF-8') . '</td>
                              </tr>';
                    }

                    // แสดงจำนวนรวมของแต่ละสถานะ
                    echo '<tr class="table-secondary">
                            <td colspan="3" class="text-end"><strong>รวมสถานะยังไม่ได้ดำเนินการ:</strong></td>
                            <td>' . htmlspecialchars($pending_problems, ENT_QUOTES, 'UTF-8') . ' ปัญหา</td>
                          </tr>';
                    echo '<tr class="table-secondary">
                            <td colspan="3" class="text-end"><strong>รวมสถานะกำลังดำเนินการ:</strong></td>
                            <td>' . htmlspecialchars($received_problems, ENT_QUOTES, 'UTF-8') . ' ปัญหา</td>
                          </tr>';
                    echo '<tr class="table-secondary">
                            <td colspan="3" class="text-end"><strong>รวมสถานะแก้ไขแล้ว:</strong></td>
                            <td>' . htmlspecialchars($resolved_problems, ENT_QUOTES, 'UTF-8') . ' ปัญหา</td>
                          </tr>';
                    echo '<tr class="table-secondary">
                            <td colspan="3" class="text-end"><strong>รวมสถานะแก้ไขปัญหาไม่ได้:</strong></td>
                            <td>' . htmlspecialchars($unresolved_problems, ENT_QUOTES, 'UTF-8') . ' ปัญหา</td>
                          </tr>';
                } else {
                    echo '<tr>
                            <td colspan="4" class="text-center text-muted">ไม่มีข้อมูลปัญหาในเดือนและปีที่เลือก</td>
                          </tr>';
                }
                ?>
            </tbody>
        </table>

        <!-- ปุ่มพิมพ์รายงาน -->
        <div class="print-button">
            <button class="btn btn-success" onclick="window.print()">พิมพ์รายงาน</button>
        </div>
    </div>

    <!-- เพิ่ม Bootstrap JS และ dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
$conn->close();
?>
