<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
include '../admin/db_connection.php';

// ตรวจสอบว่าข้อมูลถูกส่งมาจากฟอร์มหรือไม่
$house_number = isset($_POST['house_number']) ? trim($_POST['house_number']) : null;
$payment_year = isset($_POST['payment_year']) ? trim($_POST['payment_year']) : null;

// แสดงค่าที่ได้รับเพื่อการตรวจสอบ
echo json_encode(['house_number' => $house_number, 'payment_year' => $payment_year]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>ชำระค่าส่วนกลาง</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            background-color: #f8f9fa;
        }

        header {
            background-color: #e9ecef;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #495057;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 18px;
        }

        nav {
            position: fixed;
            top: 70px;
            left: 0;
            width: 20%;
            height: calc(100% - 70px);
            background: #007bff;
            padding: 20px;
            overflow: auto;
            z-index: 1000;
            color: white;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        nav ul li {
            margin-bottom: 10px;
        }

        nav ul li a {
            text-decoration: none;
            color: #ffffff;
            display: block;
            padding: 10px;
            transition: background-color 0.3s ease;
            border-radius: 5px;
        }

        nav ul li a:hover {
            background-color: #0056b3;
            color: #ffffff;
        }

        section {
            margin-top: 50px;
            padding: 20px;
        }

        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .form-label {
            font-weight: bold;
        }

        .form-control {
            border-radius: 5px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }

        article {
            margin-left: auto;
            padding: 50px 20px;
            background-color: white;
            margin-top: 0;
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
            color: #fff;
            text-decoration: none;
        }

        .btn-back:hover {
            background-color: #5a6268;
            border-color: #4e555b;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-control,
        .form-select {
            border-radius: 5px;
            padding: 10px;
        }

        .form-note {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .invalid-feedback {
            display: none;
            color: red;
        }

        /* ปรับแต่ง input ที่เป็น readonly */
        input[readonly] {
            border: none;
            /* ลบเส้นกรอบ */
            background-color: #f8f9fa;
            /* สีพื้นหลัง */
            box-shadow: none;
            /* ลบเงา */
            cursor: not-allowed;
            /* เปลี่ยนเคอร์เซอร์ให้ดูเหมือนไม่ได้สามารถแก้ไข */
        }
    </style>
</head>

<body>


    <!-- นำเข้าฟาย header.php -->
    <?php include '../admin/header.php'; ?>

    <!-- นำเข้าฟาย nav.php -->
    <?php include '../admin/nav.php'; ?>

    <section>
        <div class="container-custom">
            <article>
                <div class="btn-back-wrapper">
                    <a href="../admin/common_fees.php" class="btn btn-back">
                        <i class="bi bi-arrow-left"></i> ย้อนกลับ
                    </a>
                </div>
                <h1 class="mb-4">ชำระค่าส่วนกลาง</h1>
                <p>รายการการชำระค่าส่วนกลางทั้งหมดที่บันทึกไว้ในระบบ</p>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">เพิ่มการชำระเงิน</h5>
                        <form id="paymentForm" method="POST" action="../manage_payment/process_payment.php" enctype="multipart/form-data" class="p-3">
                            <div class="row mb-3">
                                <!-- บ้านเลขที่ -->
                                <div class="col-md-6">
                                    <label for="house_number" class="form-label">บ้านเลขที่</label>
                                    <input type="text" id="house_number" name="house_number" class="form-control" required placeholder="กรอกบ้านเลขที่ (เฉพาะตัวเลข)">
                                    <div class="invalid-feedback">กรุณากรอกบ้านเลขที่เป็นตัวเลขเท่านั้น</div>
                                </div>
                                <!-- เจ้าของบ้าน -->
                                <div class="col-md-6">
                                    <label for="owner_name" class="form-label">เจ้าของบ้าน</label>
                                    <input type="text" id="owner_name" name="owner_name" class="form-control" readonly placeholder="ชื่อเจ้าของบ้านจะปรากฏที่นี่">
                                </div>
                            </div>

                            <!-- เลือกปีการชำระเงิน -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_year" class="form-label">ปี</label>
                                    <select id="payment_year" name="payment_year" class="form-select" required>
                                        <option value="" disabled selected>เลือกปี</option>
                                        <script>
                                            const currentYear = new Date().getFullYear() + 543;
                                            const yearSelect = document.getElementById('payment_year');
                                            for (let year = currentYear - 5; year <= currentYear + 1; year++) {
                                                const option = document.createElement('option');
                                                option.value = year;
                                                option.textContent = year;
                                                yearSelect.appendChild(option);
                                            }
                                        </script>
                                    </select>
                                    <div class="invalid-feedback">กรุณากรอกปีที่ถูกต้อง</div>
                                    <small class="form-note">เลือกปีในการชำระค่าส่วนกลาง</small>
                                </div>
                            </div>

                            <!-- เลือกเดือนการชำระเงิน -->
                            <div class="row mb-3">
                                <!-- ชำระเดือน -->
                                <div class="col-md-6">
                                    <label for="start_month" class="form-label">ชำระเดือน</label>
                                    <select id="start_month" name="start_month" class="form-select" required>
                                        <option value="" disabled selected>เลือกเดือน</option>
                                        <option value="01">มกราคม</option>
                                        <option value="02">กุมภาพันธ์</option>
                                        <option value="03">มีนาคม</option>
                                        <option value="04">เมษายน</option>
                                        <option value="05">พฤษภาคม</option>
                                        <option value="06">มิถุนายน</option>
                                        <option value="07">กรกฎาคม</option>
                                        <option value="08">สิงหาคม</option>
                                        <option value="09">กันยายน</option>
                                        <option value="10">ตุลาคม</option>
                                        <option value="11">พฤศจิกายน</option>
                                        <option value="12">ธันวาคม</option>
                                    </select>
                                    <div class="invalid-feedback">กรุณาเลือกเดือนเริ่มต้น</div>
                                </div>
                                <!-- ถึงเดือน -->
                                <div class="col-md-6">
                                    <label for="end_month" class="form-label">ถึงเดือน</label>
                                    <select id="end_month" name="end_month" class="form-select" required>
                                        <option value="" disabled selected>เลือกเดือน</option>
                                        <option value="01">มกราคม</option>
                                        <option value="02">กุมภาพันธ์</option>
                                        <option value="03">มีนาคม</option>
                                        <option value="04">เมษายน</option>
                                        <option value="05">พฤษภาคม</option>
                                        <option value="06">มิถุนายน</option>
                                        <option value="07">กรกฎาคม</option>
                                        <option value="08">สิงหาคม</option>
                                        <option value="09">กันยายน</option>
                                        <option value="10">ตุลาคม</option>
                                        <option value="11">พฤศจิกายน</option>
                                        <option value="12">ธันวาคม</option>
                                    </select>
                                    <div class="invalid-feedback">กรุณาเลือกเดือนสิ้นสุด</div>
                                </div>
                            </div>

                            <!-- จำนวนเงินและวิธีการชำระเงิน -->
                            <div class="row mb-3">
                                <!-- จำนวนเงิน -->
                                <div class="col-md-6">
                                    <label for="amount" class="form-label">จำนวนเงิน</label>
                                    <input type="number" id="amount" name="amount" class="form-control" required readonly>
                                    <div class="invalid-feedback">กรุณากรอกจำนวนเงินที่ถูกต้อง</div>
                                    <small class="form-note">กรุณากรอกจำนวนเงินที่ต้องชำระ</small>
                                </div>

                                <!-- วิธีการชำระเงิน -->
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label">วิธีการชำระเงิน</label>
                                    <select id="payment_method" name="payment_method" class="form-select" required>
                                        <option value="" disabled selected>เลือกวิธีการชำระเงิน</option>
                                        <option value="cash">เงินสด</option>
                                        <option value="transfer">โอนเงิน</option>
                                    </select>
                                    <div class="invalid-feedback">กรุณาเลือกวิธีการชำระเงิน</div>
                                </div>
                            </div>

                            <!-- แนบสลิปการโอน -->
                            <div class="row mb-3" id="slip_upload_group" style="display: none;">
                                <div class="col-md-12">
                                    <label for="payment_slip" class="form-label">แนบสลิปการโอน</label>
                                    <input type="file" id="payment_slip" name="payment_slip" class="form-control">
                                    <div class="invalid-feedback">กรุณาอัปโหลดไฟล์สลิปที่ถูกต้อง</div>
                                    <small class="form-note">ขนาดไฟล์ต้องไม่เกิน 2MB และรองรับเฉพาะ JPG/PNG</small>
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">บันทึกการชำระเงิน</button>
                            </div>
                        </form>

                    </div>
                </div>

            </article>
        </div>
    </section>

    <div class="modal fade" id="paymentDuplicateModal" tabindex="-1" aria-labelledby="paymentDuplicateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentDuplicateModalLabel">แจ้งเตือนการชำระเงินซ้ำ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="duplicateMessage">พบการชำระเงินในช่วงเดือนและปีนี้แล้ว กรุณาตรวจสอบข้อมูล</p>
                    <p><strong>เดือนที่จ่ายไปแล้ว:</strong> <span id="alreadyPaidMonths"></span></p>
                    <p><strong>เดือนที่ชำระซ้ำ:</strong> <span id="duplicatePaidMonths"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
        const pricePerMonth = 250; // ค่าใช้จ่ายรายเดือน

        function validateRealTime(input, message) {
            if (!input.value) {
                input.classList.add('is-invalid');
                input.nextElementSibling.textContent = message;
            } else {
                input.classList.remove('is-invalid');
            }
        }

        document.getElementById('payment_method').addEventListener('change', function() {
            var slipUploadGroup = document.getElementById('slip_upload_group');
            if (this.value === 'transfer') {
                slipUploadGroup.style.display = 'block'; // แสดงช่องแนบสลิป
            } else {
                slipUploadGroup.style.display = 'none'; // ซ่อนช่องแนบสลิป
            }
        });

        function calculateAmount() {
            const startMonth = parseInt(document.getElementById('start_month').value);
            const endMonth = parseInt(document.getElementById('end_month').value);

            if (!isNaN(startMonth) && !isNaN(endMonth) && startMonth <= endMonth) {
                const numberOfMonths = endMonth - startMonth + 1;
                const totalAmount = numberOfMonths * pricePerMonth;
                document.getElementById('amount').value = totalAmount;
            } else {
                document.getElementById('amount').value = ''; // ล้างค่า
            }
        }

        document.getElementById('start_month').addEventListener('change', function() {
            validateRealTime(this, 'กรุณาเลือกเดือนเริ่มต้น');
            calculateAmount();
            const startMonth = parseInt(this.value);
            const endMonthSelect = document.getElementById('end_month');

            // เปิดใช้งานตัวเลือกทั้งหมดก่อน
            endMonthSelect.querySelectorAll('option').forEach(option => {
                option.disabled = false; // เปิดใช้งานตัวเลือกทั้งหมด
            });

            // ปิดการใช้งานตัวเลือกที่น้อยกว่าหรือเท่ากับเดือนเริ่มต้น
            for (let month = 1; month < startMonth; month++) {
                endMonthSelect.querySelector(`option[value="${month < 10 ? '0' : ''}${month}"]`).disabled = true;
            }

            // ถ้าผู้ใช้เลือกเดือนที่มากกว่าหรือเท่ากับเดือนเริ่มต้น ปิดเดือนที่ถูกเลือกในเดือนสิ้นสุด
            if (endMonthSelect.value && parseInt(endMonthSelect.value) < startMonth) {
                endMonthSelect.value = ''; // รีเซ็ตเดือนสิ้นสุด
            }
        });

        document.getElementById('end_month').addEventListener('change', function() {
            validateRealTime(this, 'กรุณาเลือกเดือนสิ้นสุด');
            calculateAmount();
        });

        document.getElementById('payment_slip').addEventListener('change', function() {
            const file = this.files[0];
            const fileType = file['type'];
            const fileSize = file['size'];
            const validImageTypes = ['image/jpeg', 'image/png'];
            if (!validImageTypes.includes(fileType) || fileSize > 2 * 1024 * 1024) {
                this.classList.add('is-invalid');
                this.value = ''; // Reset input
            } else {
                this.classList.remove('is-invalid');
            }
        });

        function disablePaidMonths(paidMonths) {
            document.querySelectorAll('#start_month option, #end_month option').forEach(option => {
                option.disabled = false; // เปิดใช้งานตัวเลือกทั้งหมด
            });

            // ปิดการใช้งานตัวเลือกเดือนที่ชำระแล้ว
            paidMonths.forEach(month => {
                document.querySelector(`#start_month option[value="${month}"]`).disabled = true;
            });
        }

        document.getElementById('payment_year').addEventListener('change', fetchPaidMonths);

        function fetchPaidMonths() {
            const houseNumber = document.getElementById('house_number').value;
            const paymentYear = document.getElementById('payment_year').value;

            // ตรวจสอบหมายเลขบ้าน
            const isHouseNumberValid = validateHouseNumber(houseNumber);
            // ตรวจสอบปี
            const isPaymentYearValid = validatePaymentYear(paymentYear);

            // หากหมายเลขบ้านไม่ถูกต้อง ให้หยุดการทำงาน
            if (!isHouseNumberValid) {
                return;
            }

            // หากปีไม่ถูกต้อง ให้หยุดการทำงาน
            if (!isPaymentYearValid) {
                return;
            }

            // ส่งข้อมูลไปยังเซิร์ฟเวอร์
            $.ajax({
                url: '../manage_payment/get_paid_months.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    house_number: houseNumber,
                    payment_year: paymentYear
                },
                success: function(response) {
                    if (response.success) {
                        disablePaidMonths(response.paid_months); // ปิดการใช้งานเดือนที่ชำระแล้ว
                    } else {
                        console.error('เกิดข้อผิดพลาดในการดึงข้อมูลเดือนที่ชำระแล้ว:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
                }
            });
        }


        function validateHouseNumber(houseNumber) {
            const houseNumberField = document.getElementById('house_number');
            if (houseNumber && /^[0-9\/]+$/.test(houseNumber)) {
                houseNumberField.classList.remove('is-invalid');
                return true; // หมายเลขบ้านถูกต้อง
            } else {
                houseNumberField.classList.add('is-invalid');
                houseNumberField.nextElementSibling.textContent = 'กรุณากรอกหมายเลขบ้านเป็นตัวเลขหรือใช้ตัวแบ่ง / เท่านั้น';
                return false; // หมายเลขบ้านไม่ถูกต้อง
            }
        }

        function validatePaymentYear(paymentYear) {
            const paymentYearField = document.getElementById('payment_year');
            if (paymentYear && /^[0-9]+$/.test(paymentYear)) {
                paymentYearField.classList.remove('is-invalid');
                return true; // ปีถูกต้อง
            } else {
                paymentYearField.classList.add('is-invalid');
                paymentYearField.nextElementSibling.textContent = 'กรุณาเลือกปีเป็นตัวเลขเท่านั้น';
                return false; // ปีไม่ถูกต้อง
            }
        }

        // เรียกใช้ฟังก์ชันเมื่อมีการป้อนหมายเลขบ้านหรือเลือกปี
        document.getElementById('house_number').addEventListener('input', fetchPaidMonths);
        document.getElementById('payment_year').addEventListener('change', fetchPaidMonths);



        document.getElementById('house_number').addEventListener('input', function() {
            validateRealTime(this, 'กรุณากรอกบ้านเลขที่');

            const houseNumber = this.value;
            if (houseNumber && /^[0-9\/]+$/.test(houseNumber)) {
                $.ajax({
                    url: '../manage_payment/check_house_number.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        house_number: houseNumber
                    },
                    success: function(response) {
                        if (response.exists === false) {
                            $('#house_number').addClass('is-invalid');
                            $('#house_number').next('.invalid-feedback').text('ไม่พบบ้านเลขที่นี้ในระบบ');
                            $('#owner_name').val('');
                        } else {
                            $('#house_number').removeClass('is-invalid');
                            $('#owner_name').val(response.owner_name);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('เกิดข้อผิดพลาดในการตรวจสอบบ้านเลขที่:', error);
                        $('#owner_name').val('');
                    }
                });
            } else {
                this.classList.add('is-invalid');
                this.nextElementSibling.textContent = 'กรุณากรอกบ้านเลขที่เป็นตัวเลขเท่านั้น';
                $('#owner_name').val('');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            let payments = [];

            $.ajax({
                url: '../manage_payment/get_payments.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    payments = response;
                    console.log('ดึงข้อมูลการชำระเงินสำเร็จ:', payments);
                },
                error: function(xhr, status, error) {
                    console.error('เกิดข้อผิดพลาดในการดึงข้อมูลการชำระเงิน:', error);
                }
            });

            const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
            ];

            function convertMonthNumberToName(monthNumber) {
                if (monthNumber >= 1 && monthNumber <= 12) {
                    return monthNames[monthNumber - 1];
                } else {
                    return "เดือนที่ไม่ถูกต้อง";
                }
            }

            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;

                const houseNumber = document.getElementById('house_number').value;
                const startMonth = document.getElementById('start_month').value;
                const endMonth = document.getElementById('end_month').value;
                const paymentYear = document.getElementById('payment_year').value;

                let duplicate = false;
                let duplicateMonths = [];

                payments.forEach(payment => {
                    if (payment.house_number === houseNumber && payment.payment_year === paymentYear) {
                        for (let month = parseInt(payment.start_month); month <= parseInt(payment.end_month); month++) {
                            if (parseInt(startMonth) <= month && month <= parseInt(endMonth)) {
                                duplicate = true;
                                duplicateMonths.push(convertMonthNumberToName(month));
                            }
                        }
                    }
                });

                if (duplicate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ชำระเงินซ้ำ',
                        text: 'บ้านเลขที่นี้ได้ชำระค่าส่วนกลางไปแล้วสำหรับเดือน: ' + duplicateMonths.join(', '),
                    });
                    document.getElementById('paymentForm').reset();
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'บันทึกการชำระเงินสำเร็จ',
                    }).then(() => {
                        document.getElementById('paymentForm').submit();
                    });
                }
            });
        });
    </script>

</body>

</html>