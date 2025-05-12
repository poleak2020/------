<?php
// edit_problem.php
include '../../admin/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีการส่งค่า ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // คำสั่ง SQL เพื่อดึงข้อมูลปัญหาตาม ID
    $sql = "SELECT * FROM problems WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        // ตรวจสอบว่าพบข้อมูลปัญหาหรือไม่
        if ($result->num_rows > 0) {
            $problem = $result->fetch_assoc();
        } else {
            echo "<script>Swal.fire('ไม่พบข้อมูล!', 'ไม่พบข้อมูลปัญหา', 'error');</script>";
            exit();
        }

        $stmt->close();
    } else {
        echo "<script>Swal.fire('เกิดข้อผิดพลาด!', 'เกิดข้อผิดพลาด: " . $conn->error . "', 'error');</script>";
        exit();
    }
} else {
    echo "<script>Swal.fire('ไม่มีข้อมูล!', 'ไม่มีการส่งข้อมูลที่จำเป็น', 'warning');</script>";
    exit();
}

// ปิดการเชื่อมต่อฐานข้อมูลชั่วคราว
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แก้ไขปัญหา</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        article {
            margin-left: 18%;
            padding: 20px;
            background-color: white;
            margin-top: 100px;
        }

        .btn-back {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
            display: inline-block;
            text-decoration: none;
            color: white;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background-color: #5a6268;
            border-color: #4e555b;
        }

        .btn-back-wrapper {
            display: flex;
            justify-content: flex-start;
        }
    </style>
</head>

<body>
    <?php include '../../admin/header.php'; ?>
    <?php include '../../admin/nav.php'; ?>

    <article>
        <div class="container mt-5">
            <div class="btn-back-wrapper">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> ย้อนกลับ
                </a>
            </div>
            <h2>แก้ไขปัญหา</h2>
            <form id="editProblemForm">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($problem['id']); ?>">
                <div class="form-group">
                    <label for="description">เรื่องที่ร้องเรียน</label>
                    <textarea name="description" class="form-control" id="description" required><?php echo htmlspecialchars($problem['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image_url">URL รูปภาพ</label>
                    <input type="text" name="image_url" class="form-control" id="image_url" value="<?php echo htmlspecialchars($problem['image_url']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                <a href="http://localhost/%e0%b8%9d%e0%b8%b6%e0%b8%81%e0%b8%87%e0%b8%b2%e0%b8%99/admin/problems.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
                <div id="responseMessage" class="mt-3"></div>
            </form>
        </div>
    </article>

    <script>
        $(document).ready(function() {
            $('#editProblemForm').on('submit', function(e) {
                e.preventDefault();

                // ยืนยันการบันทึกการเปลี่ยนแปลง
                Swal.fire({
                    title: 'ยืนยันการบันทึก',
                    text: "คุณแน่ใจว่าต้องการบันทึกการเปลี่ยนแปลงนี้?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ใช่, บันทึกเลย!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // ส่งข้อมูลไปยัง update_problem.php
                        $.ajax({
                            type: 'POST',
                            url: 'update_problem.php',
                            data: $(this).serialize(),
                            success: function(response) {
                                if (response.trim() === 'success') {
                                    Swal.fire({
                                        title: 'สำเร็จ!',
                                        text: 'การอัปเดตข้อมูลสำเร็จ',
                                        icon: 'success',
                                        confirmButtonText: 'ตกลง'
                                    }).then(() => {
                                        window.location.href = 'http://localhost/%e0%b8%9d%e0%b8%b6%e0%b8%81%e0%b8%87%e0%b8%b2%e0%b8%99/admin/problems.php';
                                    });
                                } else {
                                    $('#responseMessage').html('<div class="alert alert-danger">เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' + response + '</div>');
                                }
                            },
                            error: function(xhr, status, error) {
                                $('#responseMessage').html('<div class="alert alert-danger">เกิดข้อผิดพลาด: ' + xhr.responseText + '</div>');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>
