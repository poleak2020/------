<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #f0f8ff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #5E5E5E;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        nav {
            position: fixed;
            top: 105px;
            left: 0;
            width: 18%;
            height: calc(100% - 100px);
            background: #7BC59D;
            padding: 20px;
            overflow: auto;
            z-index: 1000;
        }

        article {
            margin-left: 0;
            padding: 20px;
            background-color: white;
            margin-top: 100px;
        }

        .form-control,
        .btn {
            border-radius: 10px;
        }

        .search-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .rows-per-page {
            margin-right: 20px;
            display: flex;
            align-items: center;
        }

        .rows-per-page label {
            margin-right: 10px;
        }

        .input-group {
            max-width: 300px;
            width: 100%;
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
            <h1>จัดการผู้ใช้งาน</h1>

            <!-- แสดงข้อความสำเร็จ -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <!-- ฟอร์มค้นหาและเลือกจำนวนแถว -->
            <form method="GET" action="" class="search-form">
                <div class="rows-per-page">
                    <label for="rows">แสดง:</label>
                    <select id="rows" name="rows" class="form-select" style="width: auto;">
                        <option value="1" <?php if (isset($_GET['rows']) && $_GET['rows'] == 1) echo 'selected'; ?>>1</option>
                        <option value="5" <?php if (isset($_GET['rows']) && $_GET['rows'] == 5) echo 'selected'; ?>>5</option>
                        <option value="10" <?php if (isset($_GET['rows']) && $_GET['rows'] == 10) echo 'selected'; ?>>10</option>
                    </select>
                </div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="ค้นหาบ้านเลขที่" name="house_number" value="<?php echo isset($_GET['house_number']) ? htmlspecialchars($_GET['house_number']) : ''; ?>">
                    <button class="btn btn-success" type="submit">ค้นหา</button>
                </div>
            </form>

            <!-- ตารางข้อมูลผู้ใช้งาน -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>แปลงที่</th>
                            <th>บ้านเลขที่</th>
                            <th>เนื้อที่ (ตร.ว.)</th>
                            <th>ไอดี</th>
                            <th>เจ้าบ้าน/ผู้ดูแล</th>
                            <th>เบอร์ติดต่อ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // เชื่อมต่อฐานข้อมูล
                        require '../admin/db_connection.php';

                        // รับค่าค้นหาจากฟอร์ม
                        $search_house_number = isset($_GET['house_number']) ? htmlspecialchars(trim($_GET['house_number'])) : '';
                        $rows_per_page = isset($_GET['rows']) && in_array((int)$_GET['rows'], [1, 5, 10]) ? (int)$_GET['rows'] : 10;

                        // คำนวณหน้าปัจจุบัน
                        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        $offset = ($page - 1) * $rows_per_page;

                        // เตรียมคำสั่ง SQL เพื่อดึงข้อมูล
                        $sql = "SELECT id, plot_number, house_number, area, user_id, owner_name, contact_number FROM users";
                        if (!empty($search_house_number)) {
                            $sql .= " WHERE house_number LIKE ?";
                        }
                        $sql .= " LIMIT ?, ?";

                        // เตรียมและดำเนินการคำสั่ง SQL
                        $stmt = $conn->prepare($sql);
                        if ($stmt === false) {
                            die("Error: " . $conn->error);
                        }

                        if (!empty($search_house_number)) {
                            $search_term = "%" . $search_house_number . "%";
                            $stmt->bind_param("sii", $search_term, $offset, $rows_per_page);
                        } else {
                            $stmt->bind_param("ii", $offset, $rows_per_page);
                        }

                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            // ข้อมูลมีอยู่
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row["plot_number"]) . "</td>"; // แสดงข้อมูลแปลงที่
                                echo "<td>" . htmlspecialchars($row["house_number"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["area"]) . "</td>"; // แสดงข้อมูลเนื้อที่
                                echo "<td>" . htmlspecialchars($row["user_id"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["owner_name"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["contact_number"]) . "</td>";
                                echo "<td>
                                        <a href='../users_manage/edit_user.php?id=" . htmlspecialchars($row["id"]) . "' class='btn btn-warning btn-sm'><i class='bi bi-pencil'></i> แก้ไข</a>
                                        <button onclick=\"confirmDelete('../users_manage/delete_user.php?id=" . htmlspecialchars($row["id"]) . "')\" class='btn btn-danger btn-sm'><i class='bi bi-trash'></i> ลบ</button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>ไม่มีข้อมูลผู้ใช้งาน</td></tr>";
                        }

                        // ปิดการเชื่อมต่อ
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- แสดง Pagination -->
            <nav>
                <ul class="pagination">
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&rows=<?php echo $rows_per_page; ?>">Previous</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&rows=<?php echo $rows_per_page; ?>">Next</a>
                    </li>
                </ul>
            </nav>

        </div>
    </article>

    <script>
        function confirmDelete(url) {
            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "คุณจะไม่สามารถกู้คืนข้อมูลนี้ได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบมัน!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>
</body>

</html>
