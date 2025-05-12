<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การตั้งค่า - แดชบอร์ดผู้ดูแลระบบ</title>
    <!-- ลิงก์ Bootstrap และไอคอน -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto';
            margin: 0;
            padding-top: 120px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1000px;
        }

        .card {
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        .card-header {
            font-size: 18px;
            font-weight: bold;
            background-color: #007bff;
            color: white;
            padding: 15px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .card-body {
            padding: 20px;
        }
    </style>
</head>

<body>
    <!-- นำเข้าฟาย header.php -->
    <?php include '../admin/header.php'; ?>

    <!-- นำเข้าฟาย nav.php -->
    <?php include '../admin/nav.php'; ?>

    <div class="container">
        <h2 class="text-center mb-4">การตั้งค่า</h2>

        <!-- การตั้งค่าบัญชี -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i> การตั้งค่าบัญชี
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label for="email" class="form-label">ที่อยู่อีเมล</label>
                        <input type="email" class="form-control" id="email" placeholder="กรอกอีเมลของคุณ">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">เปลี่ยนรหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" placeholder="รหัสผ่านใหม่">
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>

        <!-- การตั้งค่าการแสดงผล -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-palette"></i> การตั้งค่าการแสดงผล
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label for="theme" class="form-label">ธีม</label>
                        <select id="theme" class="form-select">
                            <option value="light">โหมดสว่าง</option>
                            <option value="dark">โหมดมืด</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="language" class="form-label">ภาษา</label>
                        <select id="language" class="form-select">
                            <option value="th">ภาษาไทย</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>

        <!-- การตั้งค่าความเป็นส่วนตัว -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i> การตั้งค่าความเป็นส่วนตัว
            </div>
            <div class="card-body">
                <form>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="2fa">
                        <label class="form-check-label" for="2fa">
                            เปิดใช้งานการยืนยันตัวตนสองขั้นตอน (2FA)
                        </label>
                    </div>
                    <div class="mb-3">
                        <label for="sessionTimeout" class="form-label">การหมดเวลาเซสชัน (นาที)</label>
                        <input type="number" class="form-control" id="sessionTimeout" value="30">
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>

        <!-- การตั้งค่าระบบ -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cogs"></i> การตั้งค่าระบบ
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label for="userRoles" class="form-label">จัดการบทบาทผู้ใช้</label>
                        <select id="userRoles" class="form-select">
                            <option value="admin">ผู้ดูแลระบบ</option>
                            <option value="editor">ผู้แก้ไข</option>
                            <option value="viewer">ผู้ชม</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-secondary">สำรองข้อมูลฐานข้อมูล</button>
                        <button type="button" class="btn btn-secondary">กู้คืนฐานข้อมูล</button>
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>

        <!-- การตั้งค่าการแจ้งเตือน -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bell"></i> การตั้งค่าการแจ้งเตือน
            </div>
            <div class="card-body">
                <form>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="emailNotifications">
                        <label class="form-check-label" for="emailNotifications">
                            รับการแจ้งเตือนทางอีเมล
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="systemNotifications">
                        <label class="form-check-label" for="systemNotifications">
                            เปิดใช้งานการแจ้งเตือนของระบบ
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
