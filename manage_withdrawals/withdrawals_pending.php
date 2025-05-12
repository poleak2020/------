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

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    echo '<div class="alert alert-danger" role="alert">
            ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง
          </div>';
    exit();
}

// ดึงข้อมูลการเบิกเงินที่มีสถานะ 'Pending'
$withdrawals = [];
$stmt = $conn->prepare("SELECT * FROM withdrawals WHERE status = 'Pending' ORDER BY created_at DESC");
if ($stmt === false) {
    echo '<div class="alert alert-danger" role="alert">
            การเตรียมคำสั่ง SQL ล้มเหลว: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8') . '
          </div>';
    exit();
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $withdrawals[] = $row;
}
$stmt->close();

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

// ฟังก์ชันสำหรับตรวจสอบชนิดไฟล์
function validateFileType($fileType)
{
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png']; // อนุญาตเฉพาะไฟล์ PDF, JPEG, PNG
    return in_array($fileType, $allowedTypes);
}

// ฟังก์ชันสำหรับตรวจสอบขนาดไฟล์
function validateFileSize($fileSize)
{
    $maxSize = 5 * 1024 * 1024; // ขนาดสูงสุด 5 MB
    return $fileSize <= $maxSize;
}

// ฟังก์ชันสำหรับอัปโหลดไฟล์และบันทึก URL
function uploadFilesAndSaveUrls($files, $conn, $withdrawalId)
{
    $fileUrls = [];
    $targetDir = "../uploads/";

    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = basename($files['name'][$i]);
        $targetFile = $targetDir . uniqid() . '_' . $fileName;

        // ตรวจสอบชนิดและขนาดไฟล์
        if (validateFileType($files['type'][$i]) && validateFileSize($files['size'][$i])) {
            if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                $fileUrls[] = $targetFile;
            }
        }
    }

    // เก็บ URL ในฐานข้อมูล
    if (!empty($fileUrls)) {
        $fileUrlsStr = implode(',', $fileUrls);
        $stmt = $conn->prepare("UPDATE withdrawals SET related_files = ? WHERE id = ?");
        $stmt->bind_param('si', $fileUrlsStr, $withdrawalId);
        $stmt->execute();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <title>รายการเบิกเงินรออนุมัติ</title>
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
            background-color: #ffc107;
            color: white;
        }

        .table-warning th {
            background-color: #ffeeba;
            color: #664d03;
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
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: '<?= addslashes(htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8')) ?>',
                    confirmButtonText: 'ตกลง'
                });
            </script>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])) : ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: '<?= addslashes(htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8')) ?>',
                    confirmButtonText: 'ตกลง'
                });
            </script>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- ตารางแสดงรายการเบิกเงินรออนุมัติ -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>รายการเบิกเงินรออนุมัติ</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-warning">
                            <tr>
                                <th scope="col" class="text-center">หมายเลขอ้างอิง</th>
                                <th scope="col" class="text-center">จำนวนเงิน (บาท)</th>
                                <th scope="col">รายละเอียด</th>
                                <th scope="col">หมายเหตุ</th>
                                <th scope="col" class="text-center">วันที่เบิก</th>
                                <th scope="col" class="text-center">สถานะ</th>
                                <th scope="col" class="text-center">ดาวน์โหลดใบเบิก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($withdrawals) > 0) : ?>
                                <?php foreach ($withdrawals as $row) : ?>
                                    <tr>
                                        <td class="text-center"><?= htmlspecialchars($row['reference_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= number_format($row['amount'], 2) ?></td>
                                        <td><?= nl2br(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8')) ?></td>
                                        <td><?= nl2br(htmlspecialchars($row['note'], ENT_QUOTES, 'UTF-8')) ?></td>
                                        <td class="text-center"><?= formatThaiDate($row['withdrawal_date']) ?></td>
                                        <td class="text-center">
                                            <form method="POST" action="../manage_withdrawals/update_status.php" enctype="multipart/form-data">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" class="btn btn-success btn-sm" onclick="confirmAction('approve', <?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>')">รับเรื่อง</button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmAction('reject', <?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>')">ไม่รับเรื่อง</button>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <a href="../manage_withdrawals/view_withdrawal.php?id=<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-info btn-sm">ใบเบิก</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="text-center">ไม่มีรายการเบิกเงินรออนุมัติ</td>
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
    <!-- เพิ่ม Bootstrap JS และ SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmAction(action, id, csrf_token) {
            let actionText = action === 'approve' ? 'รับเรื่อง' : 'ไม่รับเรื่อง';
            Swal.fire({
                title: `คุณต้องการ${actionText} ใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                html: action === 'approve' ? `
                    <div id="file-upload-container">
                        <label for="related_files" class="form-label">แนบเอกสารการเบิกเงิน และเอกสารที่เกี่ยวข้อง (หลายไฟล์):</label>
                        <input type="file" id="related_files" name="related_files[]" multiple class="form-control">
                        <small class="form-text text-muted">กรุณาแนบเอกสารการเบิก และเอกสารที่เกี่ยวข้องทั้งหมดที่จำเป็น</small>
                    </div>
                    <button type="button" class="btn btn-secondary mt-2" id="add-file-input">เพิ่มไฟล์</button>
                ` : ''
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '../manage_withdrawals/update_status.php';
                    form.enctype = 'multipart/form-data';

                    // เพิ่ม CSRF token
                    let csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = csrf_token;
                    form.appendChild(csrfInput);

                    // เพิ่ม id
                    let idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = id;
                    form.appendChild(idInput);

                    // เพิ่ม action
                    let actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = action;
                    form.appendChild(actionInput);

                    // ตรวจสอบว่ามีไฟล์แนบหรือไม่
                    if (action === 'approve') {
                        let fileInputs = document.querySelectorAll('input[type="file"]');
                        fileInputs.forEach((input) => {
                            form.appendChild(input.cloneNode(true));
                        });
                    }

                    document.body.appendChild(form);
                    form.submit();
                }
            });

            // ฟังก์ชันสำหรับเพิ่มช่องอัปโหลดไฟล์
            document.getElementById('add-file-input').addEventListener('click', function() {
                let container = document.getElementById('file-upload-container');
                let newInput = document.createElement('input');
                newInput.type = 'file';
                newInput.name = 'related_files[]';
                newInput.className = 'form-control mt-2';
                container.appendChild(newInput);
            });
        }
    </script>
</body>

</html>
