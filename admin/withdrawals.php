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

// ฟังก์ชันสำหรับสร้างหมายเลขอ้างอิงอัตโนมัติ
function generateReferenceNumber($conn)
{
    $prefix = 'W';
    $date = date('ymd');
    $max_attempts = 5;

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        $random_number = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $reference_number = $prefix . $date . $random_number;

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM withdrawals WHERE reference_number = ?");
        if ($stmt === false) {
            throw new Exception("การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error));
        }
        $stmt->bind_param("s", $reference_number);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        }
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result['count'] == 0) {
            return $reference_number;
        }
    }

    throw new Exception("ไม่สามารถสร้างหมายเลขอ้างอิงได้");
}

// ฟังก์ชันสำหรับตรวจสอบยอดเงินคงเหลือจากตาราง payments ที่มีสถานะ 'approved'
function getAvailableBalance($conn) {
    $stmt = $conn->prepare("SELECT SUM(amount) as total_payments FROM payments WHERE status = 'approved'");
    if ($stmt === false) {
        throw new Exception("การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error));
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return isset($result['total_payments']) ? $result['total_payments'] : 0;
}

// ดึงรายละเอียดที่เคยใช้จากฐานข้อมูล
$used_descriptions = [];
$stmt = $conn->prepare("SELECT DISTINCT description FROM withdrawals ORDER BY created_at DESC");
if ($stmt === false) {
    $_SESSION['error'] = "การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error);
    header("Location: ../admin/withdrawals.php");
    exit();
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $used_descriptions[] = $row['description'];
}
$stmt->close();

// ถ้าฟอร์มถูกส่งมา
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบ CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "การร้องขอไม่ถูกต้อง";
        header("Location: ../admin/withdrawals.php");
        exit();
    }

    // ลบ CSRF Token หลังจากการตรวจสอบสำเร็จ
    unset($_SESSION['csrf_token']);
    unset($_SESSION['csrf_token_expiry']);
    $_SESSION['action_csrf_token'] = bin2hex(random_bytes(32));

    $amounts = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_REQUIRE_ARRAY);
    $descriptions = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
    $notes = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
    $withdrawal_date = date('Y-m-d');

    // ตรวจสอบยอดเงินคงเหลือ
    try {
        $availableBalance = getAvailableBalance($conn);
    } catch (Exception $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการตรวจสอบยอดเงินคงเหลือ";
        header("Location: ../admin/withdrawals.php");
        exit();
    }

    $totalWithdrawalAmount = array_sum($amounts);

    if ($totalWithdrawalAmount > $availableBalance) {
        $_SESSION['error'] = "ยอดเงินคงเหลือไม่เพียงพอสำหรับการเบิก (ยอดเงินคงเหลือ: " . number_format($availableBalance, 2) . " บาท)";
        header("Location: withdrawals.php");
        exit();
    }

    $valid = true;
    $entries = [];

    for ($i = 0; $i < count($amounts); $i++) {
        $amount = filter_var($amounts[$i], FILTER_VALIDATE_FLOAT);
        $description = trim($descriptions[$i]);
        $note = isset($notes[$i]) ? trim($notes[$i]) : '';

        // ตรวจสอบว่ารายละเอียดไม่เป็นค่าว่างและจำนวนเงินไม่ติดลบ
        if ($amount === false || $amount <= 0 || empty($description) || strlen($description) > 500 || strlen($note) > 500) {
            $valid = false;
            break;
        }

        $entries[] = [
            'amount' => $amount,
            'description' => $description,
            'note' => $note
        ];
    }

    if ($valid && count($entries) > 0) {
        if (!$conn->begin_transaction()) {
            $_SESSION['error'] = "ไม่สามารถเริ่มธุรกรรมได้ กรุณาลองใหม่อีกครั้ง";
            header("Location: ../admin/withdrawals.php");
            exit();
        }

        try {
            $stmt = $conn->prepare("INSERT INTO withdrawals (reference_number, amount, description, note, withdrawal_date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error));
            }

            foreach ($entries as $entry) {
                $reference_number = generateReferenceNumber($conn);

                if (!$stmt->bind_param("sdsss", $reference_number, $entry['amount'], $entry['description'], $entry['note'], $withdrawal_date)) {
                    throw new Exception("การผูกพารามิเตอร์ล้มเหลว: (" . $stmt->errno . ") " . $stmt->error);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                }
            }

            $stmt->close();
            $conn->commit();
            $_SESSION['success'] = "เพิ่มรายการเบิกเงินเรียบร้อยแล้ว";
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error inserting withdrawals: " . $e->getMessage());
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มรายการเบิกเงิน กรุณาลองใหม่อีกครั้ง";
        }
    } else {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง (จำนวนเงินต้องเป็นบวก รายละเอียดและหมายเหตุไม่เกิน 500 ตัวอักษร)";
    }

    header("Location: ../admin/withdrawals.php");
    exit();
}

// ดึงข้อมูลรายการเบิกเงินทั้งหมด
$withdrawals = [];
$stmt = $conn->prepare("SELECT * FROM withdrawals ORDER BY created_at DESC");
if ($stmt === false) {
    $_SESSION['error'] = "การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error);
    header("Location: ../admin/withdrawals.php");
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
    <title>จัดการการเบิกค่าใช้จ่าย</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card-header {
            background-color: #0d6efd;
            color: white;
        }

        .container {
            margin-left: 20%;
        }

        @media (max-width: 992px) {
            .container {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5 pt-5">
        <h2 class="mb-4 text-center">จัดการการเบิกค่าใช้จ่าย</h2>
        <!-- ปุ่มย้อนกลับ -->
        <div class="btn-group text-center">
            <button onclick="window.location.href='manage.php';" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> ย้อนกลับ
            </button>
        </div>

        <!-- แสดงข้อความสำเร็จหรือข้อผิดพลาดโดยใช้ SweetAlert2 -->
        <?php if (isset($_SESSION['success'])) : ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: '<?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>',
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
                    text: '<?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>',
                    confirmButtonText: 'ตกลง'
                });
            </script>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- ฟอร์มเพิ่มรายการเบิกเงินใหม่ -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header text-center bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>เพิ่มรายการเบิกเงินใหม่</h5>
            </div>
            <div class="card-body">
                <form id="withdrawal-form" method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="container-fluid">
                        <div id="withdrawal-entries">
                            <div class="row g-3 align-items-end withdrawal-entry">
                                <div class="col-md-4">
                                    <label for="description[]" class="form-label">รายละเอียด</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-pencil-square"></i></span>
                                        <!-- Input field with associated datalist -->
                                        <input list="description-list" class="form-control" name="description[]" placeholder="รายละเอียดการเบิก" required>

                                        <!-- Datalist to show previous descriptions -->
                                        <datalist id="description-list">
                                            <!-- Loop through the used descriptions to create the options -->
                                            <?php foreach ($used_descriptions as $description): ?>
                                                <option value="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label for="amount[]" class="form-label">จำนวนเงิน (บาท)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-cash-stack"></i></span>
                                        <input type="number" step="0.01" class="form-control" name="amount[]" placeholder="0.00" min="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="note[]" class="form-label">หมายเหตุ</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-chat-dots"></i></span>
                                        <textarea class="form-control" name="note[]" rows="1" placeholder="หมายเหตุเพิ่มเติม" maxlength="500"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-danger remove-button" title="ลบรายการ">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-3">
                            <div class="col-12 text-center">
                                <button type="button" class="btn btn-outline-success" id="add-withdrawal">
                                    <i class="bi bi-plus-lg me-2"></i>เพิ่มรายการเบิกเงิน
                                </button>
                            </div>
                        </div>
                        <div class="row g-3 mt-4">
                            <div class="col-12">
                                <button type="button" id="check-data" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle me-2"></i>ตรวจสอบข้อมูล
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ลิงก์ไปยังหน้าแสดงรายการตามสถานะ -->
        <nav class="mb-4">
            <div class="nav nav-tabs" id="withdrawalTabs" role="tablist">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">รออนุมัติ</button>
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">อนุมัติ</button>
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">ไม่อนุมัติ</button>
            </div>
        </nav>

        <!-- แท็บเนื้อหา -->
        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <!-- ปรับส่วนนี้ให้สามารถอัปเดตแบบ Ajax -->
                <div id="pending-content">
                    <!-- Initial load content -->
                    <?php include '../manage_withdrawals/withdrawals_pending.php'; ?>
                </div>
            </div>
            <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                <div id="approved-content">
                    <!-- Content loaded via Ajax -->
                </div>
            </div>
            <div class="tab-pane fade" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
                <div id="rejected-content">
                    <!-- Content loaded via Ajax -->
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="footer text-center mt-5">
        <p>เว็บไซต์นี้ทั้งหมดได้รับการคุ้มครองลิขสิทธิ์ 2024 - พัฒนาระบบโดยทีมงานวิทยาการคอมพิวเตอร์ (สมุทรปราการ) มหาวิทยาลัยราชภัฏธนบุรี</p>
    </div>

    <!-- Bootstrap JS Bundle (Includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript for Dynamic Tab Content Loading and Form Handling -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('#withdrawalTabs button[data-bs-toggle="tab"]');

            tabs.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(event) {
                    const target = event.target.getAttribute('data-bs-target'); // e.g., "#approved"
                    const status = target.substring(1); // Remove the '#' to get 'approved', 'rejected', etc.
                    const contentDiv = document.getElementById(`${status}-content`);

                    // Check if content is already loaded to prevent redundant requests
                    if (contentDiv.getAttribute('data-loaded') !== 'true') {
                        // Show loading indicator
                        contentDiv.innerHTML = `
                            <div class="d-flex justify-content-center my-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        `;

                        // Fetch the content via Ajax
                        fetch(`../manage_withdrawals/withdrawals_${status}.php`, {
                                method: 'GET',
                                credentials: 'same-origin' // Include cookies for session
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.text();
                            })
                            .then(data => {
                                contentDiv.innerHTML = data;
                                contentDiv.setAttribute('data-loaded', 'true');
                            })
                            .catch(error => {
                                console.error('There was a problem with the fetch operation:', error);
                                contentDiv.innerHTML = `
                                <div class="alert alert-danger" role="alert">
                                    ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง
                                </div>
                            `;
                            });
                    }
                });
            });
        });

        // Functionality to add/remove withdrawal entries in the form
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.remove-button') || e.target.closest('.remove-button'))) {
                const confirmed = confirm("คุณต้องการลบรายการนี้ใช่หรือไม่?");
                if (confirmed) {
                    const button = e.target.closest('.remove-button');
                    const entry = button.closest('.withdrawal-entry');
                    if (entry) {
                        entry.remove();
                    }
                }
            }

            if (e.target && (e.target.matches('#add-withdrawal') || e.target.closest('#add-withdrawal'))) {
                e.preventDefault();
                const entriesContainer = document.getElementById('withdrawal-entries');
                const newEntry = document.createElement('div');
                newEntry.classList.add('row', 'g-3', 'align-items-end', 'withdrawal-entry');
                newEntry.innerHTML = `
                    <div class="col-md-4">
                        <label class="form-label">รายละเอียด</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-pencil-square"></i></span>
                            <input list="description-list" class="form-control" name="description[]" placeholder="รายละเอียดการเบิก" required>
                            <datalist id="description-list">
                                <?php foreach ($used_descriptions as $description): ?>
                                    <option value="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">จำนวนเงิน (บาท)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-cash-stack"></i></span>
                            <input type="number" step="0.01" class="form-control" name="amount[]" placeholder="0.00" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">หมายเหตุ</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-chat-dots"></i></span>
                            <textarea class="form-control" name="note[]" rows="1" placeholder="หมายเหตุเพิ่มเติม" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="col-md-1 text-center">
                        <button type="button" class="btn btn-danger remove-button" title="ลบรายการ">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                entriesContainer.appendChild(newEntry);
            }
        });

        // Function to handle form data validation and balance check
        document.getElementById('check-data').addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('withdrawal-form');
            const formData = new FormData(form);
            let values = [];

            let index = 1; // เก็บเลขที่รายการ
            let currentItem = {}; // เก็บข้อมูลปัจจุบัน
            let totalWithdrawal = 0; // เก็บยอดรวมการเบิกเงินทั้งหมด

            formData.forEach((value, key) => {
                if (key === 'description[]') {
                    currentItem.description = `รายละเอียด: ${value}`;
                } else if (key === 'amount[]') {
                    currentItem.amount = parseFloat(value);
                    totalWithdrawal += currentItem.amount; // รวมยอดการเบิกในแต่ละรายการ
                } else if (key === 'note[]' && value.trim() !== '') {
                    currentItem.note = `หมายเหตุ: ${value}`;
                }

                // เมื่อเจอ amount[] ให้ถือว่าจบรายการ
                if (key === 'amount[]') {
                    let entry = `รายการที่ ${index}: ${currentItem.description || ''} จำนวนเงิน: ${currentItem.amount.toFixed(2)} บาท ${currentItem.note ? currentItem.note : ''}`;
                    values.push(entry.trim());
                    currentItem = {}; // รีเซ็ตข้อมูลสำหรับรายการถัดไป
                    index++;
                }
            });

            // ดึงข้อมูลยอดเงินคงเหลือจากฝั่งเซิร์ฟเวอร์ด้วย Ajax
            fetch('../manage_withdrawals/check_balance.php')
                .then(response => response.json())
                .then(data => {
                    const availableBalance = parseFloat(data.available_balance);

                    if (totalWithdrawal > availableBalance) {
                        // แสดงข้อความเตือนหากยอดรวมการเบิกเกินยอดเงินคงเหลือ
                        Swal.fire({
                            icon: 'error',
                            title: 'ยอดเงินไม่พอ!',
                            text: `ยอดเงินที่สามารถเบิกได้: ${availableBalance.toFixed(2)} บาท แต่คุณกำลังเบิก ${totalWithdrawal.toFixed(2)} บาท`,
                            confirmButtonText: 'ตกลง'
                        });
                    } else {
                        // ยืนยันการส่งฟอร์มหากยอดเงินพอ
                        Swal.fire({
                            title: 'ตรวจสอบข้อมูล',
                            html: values.join('<br>'), // ใช้ <br> เพื่อขึ้นบรรทัดใหม่
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'ยืนยัน',
                            cancelButtonText: 'แก้ไข',
                            preConfirm: () => {
                                // ส่งฟอร์มเมื่อผู้ใช้กดยืนยัน
                                form.submit();
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถตรวจสอบยอดเงินได้ กรุณาลองใหม่อีกครั้ง',
                        confirmButtonText: 'ตกลง'
                    });
                });
        });
    </script>
</body>

</html>
