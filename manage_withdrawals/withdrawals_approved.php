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

// สร้าง CSRF Token พร้อมระบุเวลาหมดอายุ (เช่น 30 นาที)
if (empty($_SESSION['csrf_token']) || $_SESSION['csrf_token_expiry'] < time()) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_expiry'] = time() + 1800;
}

// ฟังก์ชันสำหรับแปลงวันที่เป็นรูปแบบ วัน/เดือน/ปี
function formatThaiDate($dateStr)
{
    $date = new DateTime($dateStr);
    $day = $date->format('d');
    $month = $date->format('m');
    $year = $date->format('Y') + 543; // แปลงปีเป็น พ.ศ.
    return "$day/$month/$year";
}

// ดึงข้อมูลการเบิกเงินที่มีสถานะ 'Approved'
$withdrawals = [];
$stmt = $conn->prepare("SELECT * FROM withdrawals WHERE status = 'Approved' ORDER BY created_at DESC");
if ($stmt === false) {
    $_SESSION['error'] = "การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error);
    header("Location: withdrawals.php");
    exit();
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $withdrawals[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <title>รายการเบิกเงินอนุมัติแล้ว</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card-header {
            background-color: #28a745;
            color: white;
        }

        .table-success th {
            background-color: #d4edda;
            color: #155724;
        }

        @media (max-width: 992px) {
            .container {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">

        <!-- แสดงข้อความสำเร็จหรือข้อผิดพลาด -->
        <?php if (isset($_SESSION['success'])) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php session_write_close(); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php session_write_close(); ?>
        <?php endif; ?>

        <!-- ตารางแสดงรายการเบิกเงินอนุมัติแล้ว -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>รายการเบิกเงินอนุมัติแล้ว</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-success">
                            <tr>
                                <th scope="col" class="text-center">หมายเลขอ้างอิง</th>
                                <th scope="col" class="text-center">จำนวนเงิน (บาท)</th>
                                <th scope="col">รายละเอียด</th>
                                <th scope="col">หมายเหตุ</th>
                                <th scope="col" class="text-center">วันที่เบิก</th>
                                <th scope="col" class="text-center">สถานะ</th>
                                <th scope="col" class="text-center">เอกสารที่เกี่ยวข้อง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($withdrawals) > 0) : ?>
                                <?php foreach ($withdrawals as $row) : ?>
                                    <tr>
                                        <td class="text-center"><?= htmlspecialchars($row['reference_number']) ?></td>
                                        <td class="text-center"><?= number_format($row['amount'], 2) ?></td>
                                        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                        <td><?= nl2br(htmlspecialchars($row['note'])) ?></td>
                                        <td class="text-center"><?= htmlspecialchars(formatThaiDate($row['withdrawal_date'])) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= ($row['status'] === 'Approved') ? 'ดำเนินการเสร็จสิ้น' : htmlspecialchars($row['status']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($row['related_files'])) : ?>
                                                <?php
                                                $files = explode(',', $row['related_files']);
                                                foreach ($files as $file) : 
                                                    if (file_exists('../uploads/' . trim($file))) : ?>
                                                        <a href="<?= htmlspecialchars('../uploads/' . trim($file)) ?>" class="btn btn-info btn-sm mb-1" target="_blank">
                                                            <i class="bi bi-file-earmark"></i> ดูเอกสาร
                                                        </a><br>
                                                    <?php else : ?>
                                                        <span class="text-muted">ไฟล์ไม่พบ</span><br>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <span class="text-muted">ไม่มีเอกสาร</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="text-center">ไม่มีรายการเบิกเงินที่ได้รับการอนุมัติแล้ว</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (Includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
