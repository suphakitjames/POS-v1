<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">รายงานความเคลื่อนไหวสต็อก (Stock Movement Report)</h1>
        <p class="mt-2 text-sm text-gray-700">ตรวจสอบประวัติการรับเข้า เบิกออก และปรับปรุงสต็อก</p>
    </div>
</div>

<!-- Font Awesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- Filter Form -->
<div class="mt-8 bg-white shadow rounded-lg p-6">
    <form action="" method="GET" class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
        <div class="sm:col-span-2">
            <label for="start_date" class="block text-sm font-medium text-gray-700">ตั้งแต่วันที่</label>
            <div class="mt-1">
                <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
            </div>
        </div>

        <div class="sm:col-span-2">
            <label for="end_date" class="block text-sm font-medium text-gray-700">ถึงวันที่</label>
            <div class="mt-1">
                <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
            </div>
        </div>

        <div class="sm:col-span-1">
            <label for="type" class="block text-sm font-medium text-gray-700">ประเภทรายการ</label>
            <div class="mt-1">
                <select id="type" name="type"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    <option value="">ทั้งหมด</option>
                    <option value="in" <?= $filters['type'] === 'in' ? 'selected' : '' ?>>รับเข้า (In)</option>
                    <option value="out" <?= $filters['type'] === 'out' ? 'selected' : '' ?>>เบิกออก (Out)</option>
                    <option value="adjust" <?= $filters['type'] === 'adjust' ? 'selected' : '' ?>>ปรับปรุง (Adjust)</option>
                </select>
            </div>
        </div>

        <div class="sm:col-span-1 flex items-end">
            <button type="submit"
                class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                ค้นหา
            </button>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="mt-8 flex flex-col">
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg bg-white p-4">
        <table id="movementTable" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>วันที่/เวลา</th>
                    <th>สินค้า</th>
                    <th>ประเภท</th>
                    <th>จำนวน</th>
                    <th>คงเหลือ</th>
                    <th>ผู้ทำรายการ</th>
                    <th>หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-500">ไม่พบข้อมูลตามเงื่อนไขที่ระบุ</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                            <td><?= htmlspecialchars($t['product_name']) ?></td>
                            <td>
                                <?php
                                $typeClass = '';
                                $typeText = '';
                                switch ($t['type']) {
                                    case 'in':
                                        $typeClass = 'bg-green-100 text-green-800';
                                        $typeText = 'รับเข้า';
                                        break;
                                    case 'out':
                                        $typeClass = 'bg-red-100 text-red-800';
                                        $typeText = 'เบิกออก';
                                        break;
                                    case 'adjust':
                                        $typeClass = 'bg-yellow-100 text-yellow-800';
                                        $typeText = 'ปรับปรุง';
                                        break;
                                }
                                ?>
                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 <?= $typeClass ?>">
                                    <?= $typeText ?>
                                </span>
                            </td>
                            <td><?= number_format($t['quantity']) ?></td>
                            <td><?= number_format($t['remaining_stock']) ?></td>
                            <td><?= htmlspecialchars($t['username']) ?></td>
                            <td>
                                <?php
                                // แปลหมายเหตุเป็นภาษาไทย
                                $note = $t['note'];
                                $translations = [
                                    '[Sale]' => '[ขาย]',
                                    '[Usage]' => '[ใช้งาน]',
                                    '[Damaged]' => '[เสียหาย]',
                                    '[Lost]' => '[สูญหาย]',
                                    '[Expired]' => '[หมดอายุ]',
                                    '[Adjust]' => '[ปรับปรุง]',
                                    '[Return]' => '[คืน]',
                                ];

                                foreach ($translations as $eng => $thai) {
                                    $note = str_replace($eng, $thai, $note);
                                }

                                echo htmlspecialchars($note);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Get current username for signature
        var currentUser = '<?= $_SESSION['username'] ?>';

        $('#movementTable').DataTable({
            dom: 'Bfrtip',
            buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fa fa-file-excel"></i> Export Excel',
                    title: 'รายงานความเคลื่อนไหวสต็อก',
                    className: 'bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    className: 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-2',
                    title: 'รายงานความเคลื่อนไหวสต็อก',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customize: function(win) {
                        // Add signature section after table
                        $(win.document.body).append(
                            '<div style="margin-top: 50px; padding: 0 20px;">' +
                            '<div style="display: flex; justify-content: space-between;">' +
                            '<div style="text-align: center; flex: 1;">' +
                            '<p style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">ผู้ทำรายการ</p>' +
                            '<p style="margin-top: 5px; font-size: 12px; color: #666;">( ' + currentUser + ' )</p>' +
                            '</div>' +
                            '<div style="text-align: center; flex: 1;">' +
                            '<p style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">ผู้อนุมัติ / หัวหน้าคลัง</p>' +
                            '</div>' +
                            '</div>' +
                            '</div>'
                        );

                        // Style adjustments for print
                        $(win.document.body).find('table')
                            .addClass('compact')
                            .css('font-size', '12px');

                        $(win.document.body).find('th, td')
                            .css('border', '1px solid #ddd')
                            .css('padding', '8px');

                        $(win.document.body).find('th')
                            .css('background-color', '#f2f2f2')
                            .css('font-weight', 'bold');
                    }
                }
            ],
            language: {
                "sProcessing": "กำลังดำเนินการ...",
                "sLengthMenu": "แสดง _MENU_ รายการ",
                "sZeroRecords": "ไม่พบข้อมูล",
                "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sInfoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ",
                "sInfoFiltered": "(กรองข้อมูล _MAX_ ทุกรายการ)",
                "sInfoPostFix": "",
                "sSearch": "ค้นหา:",
                "sUrl": "",
                "oPaginate": {
                    "sFirst": "หน้าแรก",
                    "sPrevious": "ก่อนหน้า",
                    "sNext": "ถัดไป",
                    "sLast": "หน้าสุดท้าย"
                }
            },
            pageLength: 25,
            responsive: true,
            order: [
                [0, 'desc']
            ]
        });
    });
</script>