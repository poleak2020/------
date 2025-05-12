<?php
// ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');

// เปิดการแสดงข้อผิดพลาดเฉพาะในสภาพแวดล้อมการพัฒนา
if (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
}

// เริ่มต้น session
session_start();

// รวมไฟล์ตรวจสอบการล็อกอินและการเชื่อมต่อฐานข้อมูล
require_once '../admin/auth_check.php';
require_once '../admin/db_connection.php';

// ตรวจสอบและดึงค่า ID จาก GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ข้อมูลไม่ถูกต้อง";
    header("Location: withdrawals.php");
    exit();
}

$id = intval($_GET['id']);

// ดึงข้อมูลการเบิกเงินที่มี ID ตรงกัน
$stmt = $conn->prepare("SELECT * FROM withdrawals WHERE id = ?");
if ($stmt === false) {
    $_SESSION['error'] = "การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error);
    header("Location: withdrawals.php");
    exit();
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$withdrawal = $result->fetch_assoc();
$stmt->close();

if (!$withdrawal) {
    $_SESSION['error'] = "ไม่พบข้อมูลการเบิกเงิน";
    header("Location: withdrawals.php");
    exit();
}

// สร้างรายการผู้เซ็นรับรองการเบิก 3 คน
$signatures = [
    ['role' => 'เจ้าหน้าที่นิติบุคคล', 'name' => ''],
    ['role' => 'กรรมการ/ผู้มีอำนาจ', 'name' => ''],
    ['role' => 'กรรมการ/ผู้มีอำนาจ', 'name' => '']
];

// สมมุติว่ามีการเซ็นชื่อในตาราง `signatures` โดยใช้ withdrawal_id
$stmt = $conn->prepare("SELECT * FROM signatures WHERE withdrawal_id = ?");
if ($stmt === false) {
    $_SESSION['error'] = "การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error);
    header("Location: withdrawals.php");
    exit();
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // เติมชื่อผู้เซ็นรับรองตามบทบาทที่ตรงกัน
    foreach ($signatures as &$signature) {
        if ($signature['role'] === $row['role']) {
            $signature['name'] = $row['name'];
        }
    }
}
$stmt->close();

// ฟังก์ชันสำหรับแปลงวันที่เป็นรูปแบบ วัน/เดือน/ปี
function formatThaiDate($dateStr)
{
    $date = new DateTime($dateStr);
    $day = $date->format('d');
    $month = $date->format('m');
    $year = $date->format('Y') + 543; // แปลงปีเป็น พ.ศ.
    return "$day/$month/$year";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <title>เอกสารใบเบิกเงิน</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        .container {
            margin-top: 50px;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 700px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            color: #007bff;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            font-size: 20px;
            margin-top: 20px;
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        .details p {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            text-align: center;
        }

        .signature-box {
            width: 32%;
        }

        .signature-box .signature-line {
            border-top: 1px solid #000;
            width: 100%;
            margin-top: 40px;
            margin-bottom: 5px;
        }

        .btn-group {
            margin-top: 40px;
            text-align: center;
        }

        .btn-group button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            margin: 0 10px;
        }

        .btn-group button:hover {
            background-color: #0056b3;
        }

        @media print {
            .btn-group {
                display: none;
            }

            .container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>เอกสารใบเบิกเงิน</h2>
        <hr>

        <!-- ข้อมูลการเบิกเงิน -->
        <div class="section-title">รายละเอียดการเบิกเงิน</div>
        <div class="details">
            <p><strong>หมายเลขอ้างอิง:</strong> <?= htmlspecialchars($withdrawal['reference_number'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>จำนวนเงิน:</strong> <?= number_format($withdrawal['amount'], 2) ?> บาท</p>
            <p><strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($withdrawal['description'], ENT_QUOTES, 'UTF-8')) ?></p>
            <p><strong>หมายเหตุ:</strong> <?= nl2br(htmlspecialchars($withdrawal['note'], ENT_QUOTES, 'UTF-8')) ?></p>
            <p><strong>วันที่เบิก:</strong> <?= formatThaiDate($withdrawal['withdrawal_date']) ?></p>
        </div>

        <!-- ช่องสำหรับเซ็นรับรอง -->
        <div class="section-title">ผู้เซ็นรับรองการเบิก</div>
        <div class="signature-section">
            <?php foreach ($signatures as $signature) : ?>
                <div class="signature-box">
                    <p><?= htmlspecialchars($signature['role'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="signature-line"></div>
                    <p><?= htmlspecialchars($signature['name'], ENT_QUOTES, 'UTF-8') ? htmlspecialchars($signature['name'], ENT_QUOTES, 'UTF-8') : 'รอการเซ็น' ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ปุ่มพิมพ์เอกสารและปุ่มย้อนกลับ -->
        <div class="btn-group text-center">
            <button onclick="window.print();"><i class="bi bi-printer"></i> พิมพ์เอกสาร</button>
            <button onclick="window.location.href='../admin/withdrawals.php';"><i class="bi bi-arrow-left-circle"></i> ย้อนกลับ</button>
        </div>

    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>