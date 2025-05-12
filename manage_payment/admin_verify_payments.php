<?php
include '../admin/db_connection.php'; // Connect to the database

// Retrieve pending payments
$sql = "SELECT p.id, p.house_number, p.payment_date, p.amount, p.payment_method, i.file_name, u.owner_name, u.contact_number
        FROM payments p
        INNER JOIN users u ON p.house_number = u.house_number
        LEFT JOIN images i ON p.payment_proof_image_id = i.id
        LEFT JOIN receipts r ON p.id = r.payment_id
        WHERE r.id IS NULL AND p.status = 'pending'";

$result = $conn->query($sql);

// Check for query success
if (!$result) {
    die("SQL Error: " . $conn->error);
}

// Display number of rows returned
echo "Rows returned: " . $result->num_rows;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบการชำระเงินที่เข้ามา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Your existing CSS */
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        article {
            float: left;
            padding: 20px;
            width: 82%;
            background-color: white;
            text-align: center;
            margin-top: 100px;
            margin-left: 10%;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 100%;
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            color: #333;
            background: linear-gradient(to right, #007bff, #00c6ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        table {
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            text-align: center;
            padding: 15px;
            vertical-align: middle;
        }

        th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
        }

        td {
            background-color: #f2f2f2;
            border-bottom: 1px solid #ddd;
        }

        .btn {
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 30px;
            transition: background-color 0.3s ease, transform 0.3s ease;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
        }

        .btn-back a {
            font-size: 16px;
            text-decoration: none;
            color: #007bff;
        }

        .btn-back a:hover {
            text-decoration: underline;
        }

        .btn-back a i {
            margin-right: 8px;
        }

        .no-payments {
            text-align: center;
            font-size: 18px;
            color: #6c757d;
            padding: 30px 0;
        }

        @media (max-width: 768px) {
            article {
                width: 100%;
                margin-left: 0;
                padding: 10px;
            }

            .container {
                padding: 20px;
            }

            .btn {
                font-size: 12px;
                padding: 8px 15px;
            }
        }
    </style>
</head>

<body>

    <?php include '../admin/header.php'; ?>
    <?php include '../admin/nav.php'; ?>

    <article>
        <div class="btn-back">
            <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> ย้อนกลับ</a>
        </div>

        <div class="container">
            <h1>ตรวจสอบการชำระเงินที่เข้ามา</h1>

            <?php if ($result->num_rows > 0): ?>
                <table class="table table-hover table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>บ้านเลขที่</th>
                            <th>เจ้าของบ้าน</th>
                            <th>วันที่ชำระเงิน</th>
                            <th>จำนวนเงิน (บาท)</th>
                            <th>วิธีชำระเงิน</th>
                            <th>สลิปโอนเงิน</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['house_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                                <td><?php echo date('d/m/', strtotime($row['payment_date'])) . (date('Y', strtotime($row['payment_date'])) + 543); ?></td>
                                <td><?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_method'] == 'transfer' ? 'โอน' : $row['payment_method']); ?></td>
                                <td>
                                    <?php if (!empty($row['file_name'])): ?>
                                        <a href="/Pruksa28/uploads/<?php echo htmlspecialchars($row['file_name']); ?>" class="btn btn-primary btn-sm" target="_blank">ดูสลิป</a>
                                    <?php else: ?>
                                        <span class="text-muted">ไม่มีสลิป</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="confirmPayment(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-check-circle"></i> ยืนยัน
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectPayment(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-times-circle"></i> ปฏิเสธ
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-payments">ไม่พบการชำระเงินที่รอการตรวจสอบ</p>
            <?php endif; ?>
        </div>
    </article>

    <script>
        function confirmPayment(paymentId) {
            Swal.fire({
                title: 'ยืนยันการชำระเงิน?',
                text: "คุณแน่ใจหรือไม่ว่าต้องการยืนยันการชำระเงินนี้?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show success message before redirecting
                    Swal.fire({
                        title: 'ยืนยันการชำระเงินสำเร็จแล้ว!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = `confirm_payment.php?id=${paymentId}`;
                    });
                }
            });
        }

        function rejectPayment(paymentId) {
            Swal.fire({
                title: 'ปฏิเสธการชำระเงิน?',
                text: "คุณแน่ใจหรือไม่ว่าต้องการปฏิเสธการชำระเงินนี้?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#28a745',
                confirmButtonText: 'ปฏิเสธ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show success message before redirecting
                    Swal.fire({
                        title: 'ปฏิเสธการชำระเงินเรียบร้อยแล้ว!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = `reject_payment.php?id=${paymentId}`;
                    });
                }
            });
        }
    </script>
</body>

</html>
