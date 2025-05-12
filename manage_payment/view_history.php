<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <title>ประวัติการชำระเงิน</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding-top: 70px;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        table {
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            text-align: center;
            padding: 10px;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .btn-back {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
            display: inline-block;
        }

        .btn-back:hover {
            background-color: #5a6268;
            border-color: #4e555b;
        }

        .btn-back-wrapper {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        article {
            float: left;
            padding: 20px;
            width: 82%;
            background-color: white;
            text-align: center;
            margin-top: 0px;
            margin-left: 10%;
        }

        .img-thumbnail {
            max-width: 150px;
            max-height: 100px;
            object-fit: cover;
        }

        .img-link {
            display: inline-block;
            margin: 5px;
        }

        .icon-link {
            font-size: 1.5rem;
            color: #007bff;
            cursor: pointer;
        }

        .icon-link:hover {
            color: #0056b3;
        }

        .modal-body img {
            width: 100%;
            height: auto;
        }
    </style>
</head>

<body>

    <!-- นำเข้าฟาย header.php -->
    <?php include '../admin/header.php'; ?>

    <!-- นำเข้าฟาย nav.php -->
    <?php include '../admin/nav.php'; ?>

    <article>
        <div class="container">
            <div class="btn-back-wrapper">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> ย้อนกลับ
                </a>
            </div>
            <h1>ประวัติการชำระเงิน</h1>

            <form method="GET" action="">
                <div class="mb-3">
                    <label for="payment_date" class="form-label">กรองโดยวันที่</label>
                    <input type="date" id="payment_date" name="payment_date" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">ค้นหา</button>
            </form>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>บ้านเลขที่</th>
                        <th>ผู้ชำระเงิน</th>
                        <th>ตั้งแต่เดือน</th>
                        <th>ถึงเดือน</th>
                        <th>จำนวนเงิน(บาท)</th>
                        <th>ใบเสร็จ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include '../admin/db_connection.php';

                    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                    $payment_date = isset($_GET['payment_date']) ? $_GET['payment_date'] : '';

                    function getThaiMonth($monthNumber)
                    {
                        $thai_months = [
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
                        return isset($thai_months[$monthNumber]) ? $thai_months[$monthNumber] : "ไม่ทราบเดือน";
                    }

                    if ($id) {
                        $sql = "SELECT payments.*, users.owner_name, users.house_number, receipts.receipt_number
                                FROM payments
                                INNER JOIN users ON payments.house_number = users.house_number
                                LEFT JOIN receipts ON payments.id = receipts.payment_id
                                WHERE users.id = ?";

                        // กรองตามวันที่ถ้ามีการเลือก
                        if (!empty($payment_date)) {
                            $sql .= " AND DATE(payments.payment_date) = DATE(?)";
                        }

                        $sql .= " ORDER BY payments.payment_date DESC";

                        // ทำการเตรียมคำสั่ง SQL
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            die("Failed to prepare statement: " . $conn->error);
                        }

                        // ผูกพารามิเตอร์
                        if (!empty($payment_date)) {
                            $stmt->bind_param("is", $id, $payment_date);
                        } else {
                            $stmt->bind_param("i", $id);
                        }

                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $start_month_thai = getThaiMonth((int)$row['start_month']);
                                $end_month_thai = getThaiMonth((int)$row['end_month']);

                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['house_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['owner_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($start_month_thai) . "</td>";
                                echo "<td>" . htmlspecialchars($end_month_thai) . "</td>";
                                echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
                                echo "<td>";
                                if (!empty($row['receipt_number'])) {
                                    echo "<a href='../manage_payment/receipt.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-info btn-sm' target='_blank'>";
                                    echo "<i class='fas fa-file-alt'></i> ดูใบเสร็จ (เลขที่: " . htmlspecialchars($row['receipt_number']) . ")";
                                    echo "</a>";
                                } else {
                                    echo "<span class='text-danger'>ไม่มีใบเสร็จ</span>";
                                }
                                echo "</td>";
                                echo "<td>";
                                echo "<button class='btn btn-danger btn-sm' onclick='confirmDelete(" . htmlspecialchars($row['id']) . ")'>ลบ</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>ไม่มีข้อมูลประวัติการชำระเงิน</td></tr>";
                        }

                        $stmt->close();
                    } else {
                        echo "<tr><td colspan='7'>ไม่พบข้อมูลผู้ใช้</td></tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </article>

    <script>
        function confirmDelete(paymentId) {
            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "คุณจะไม่สามารถย้อนกลับได้หลังจากลบ!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../manage_payment/delete_view_history.php?id=' + paymentId;
                }
            })
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>
