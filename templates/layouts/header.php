<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? h($page_title) . ' - ' : '' ?>Smart Inventory System</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Sarabun (Thai) -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8fafc;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* DataTables Customization */
        .dataTables_wrapper {
            padding: 1rem 0;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 0.5rem 2.5rem 0.5rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background-color: white;
            font-size: 0.875rem;
            color: #475569;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            margin-left: 0.5rem;
            font-size: 0.875rem;
            color: #475569;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #64748b;
            font-size: 0.875rem;
        }

        table.dataTable thead th {
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: #334155;
            background-color: #f8fafc;
        }

        table.dataTable tbody td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        table.dataTable tbody tr:hover {
            background-color: #f8fafc;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.75rem;
            margin: 0 0.125rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            color: #64748b !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            color: #1e293b !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }

        /* Mobile Responsiveness Fixes */
        @media (max-width: 640px) {

            /* Hide Sidebar by default on mobile */
            aside {
                transform: translateX(-100%);
                z-index: 50 !important;
            }

            /* Show Sidebar when active */
            aside.active {
                transform: translateX(0);
            }

            /* Reset Navbar margin */
            nav {
                margin-left: 0 !important;
            }

            /* Add overlay when sidebar is open */
            #sidebarOverlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 45;
            }

            #sidebarOverlay.active {
                display: block;
            }

            /* Button Transition */
            #mobileMenuBtn {
                transition: transform 0.3s ease-in-out;
            }

            /* Move button when sidebar is open */
            #mobileMenuBtn.active {
                transform: translateX(18rem);
                /* w-72 = 18rem */
                background-color: #f1f5f9;
                /* Match sidebar bg */
                color: #ef4444;
                /* Red color */
            }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800">

    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn" class="sm:hidden fixed top-3 left-4 z-50 p-2 rounded-lg bg-white shadow-md text-slate-600 border border-slate-200">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Overlay -->
    <div id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="fixed top-0 left-0 z-40 w-72 h-screen transition-transform bg-white border-r border-slate-200 shadow-sm">
        <div class="h-full px-4 py-6 overflow-y-auto flex flex-col">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-2 mb-8">
                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 text-white p-2.5 rounded-xl shadow-lg shadow-blue-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 tracking-tight">Smart Inventory</h1>
                    <p class="text-xs text-slate-500 font-medium">ระบบจัดการสต็อกสินค้า</p>
                </div>
            </div>

            <!-- Navigation -->
            <ul class="space-y-1.5 font-medium flex-1">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

                <li>
                    <a href="index.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'index.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-5 h-5 <?= $current_page == 'index.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="ml-3">ภาพรวมระบบ</span>
                    </a>
                </li>

                <li>
                    <a href="products.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'products.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-5 h-5 <?= $current_page == 'products.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span class="ml-3">รายการสินค้า</span>
                    </a>
                </li>

                <li>
                    <a href="stock_in.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'stock_in.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-5 h-5 <?= $current_page == 'stock_in.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                        </svg>
                        <span class="ml-3">รับสินค้าเข้า</span>
                    </a>
                </li>

                <li>
                    <a href="stock_out.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'stock_out.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-5 h-5 <?= $current_page == 'stock_out.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2h2m3-4H9a2 2 0 00-2 2v4a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2h-1m-1 4l-3 3m0 0l-3-3m3 3V3"></path>
                        </svg>
                        <span class="ml-3">เบิกสินค้าออก</span>
                    </a>
                </li>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li>
                        <a href="suppliers.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'suppliers.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                            <svg class="w-5 h-5 <?= $current_page == 'suppliers.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="ml-3">จัดการคู่ค้า</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'users.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                            <svg class="w-5 h-5 <?= $current_page == 'users.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="ml-3">จัดการผู้ใช้งาน</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Divider -->
                <li class="pt-6 mt-2 mb-2 border-t border-slate-100">
                    <p class="px-3 mb-3 text-xs font-bold text-slate-400 uppercase tracking-wider">รายงาน</p>
                </li>

                <li>
                    <a href="reports.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'reports.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-5 h-5 <?= $current_page == 'reports.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-3">รายงานสต็อก</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Top Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 ml-0 sm:ml-72 bg-white border-b border-slate-200 h-16">
        <div class="h-full px-6 flex items-center justify-between gap-4">
            <!-- Search Bar -->
            <div class="flex-1 max-w-2xl">
                <form action="products.php" method="GET" class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" name="search"
                        class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl leading-5 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 sm:text-sm transition-all duration-200"
                        placeholder="ค้นหาสินค้า (ชื่อ หรือ SKU)..."
                        autocomplete="off">
                </form>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-4">
                <!-- Notifications -->
                <button class="relative p-2 text-slate-400 hover:text-slate-600 transition-colors rounded-lg hover:bg-slate-50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>

                <!-- Profile Dropdown -->
                <div class="relative ml-3">
                    <button type="button" onclick="document.getElementById('profileDropdown').classList.toggle('hidden')" class="flex items-center gap-3 focus:outline-none">
                        <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold border-2 border-white shadow-sm">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-semibold text-slate-700"><?= h($_SESSION['username'] ?? 'Admin') ?></p>
                            <p class="text-xs text-slate-500 capitalize"><?= h($_SESSION['role'] ?? 'admin') ?></p>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-1 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-sm text-slate-500">เข้าสู่ระบบโดย</p>
                            <p class="text-sm font-bold text-slate-800 truncate"><?= h($_SESSION['username'] ?? 'Admin') ?></p>
                        </div>
                        <a href="profile.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">โปรไฟล์ส่วนตัว</a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">ตั้งค่าระบบ</a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">ออกจากระบบ</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.querySelector('aside');
            const overlay = document.getElementById('sidebarOverlay');

            if (menuBtn && sidebar && overlay) {
                menuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                    menuBtn.classList.toggle('active');

                    // Toggle icon
                    if (menuBtn.classList.contains('active')) {
                        menuBtn.innerHTML = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>`;
                    } else {
                        menuBtn.innerHTML = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>`;
                    }
                });

                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    menuBtn.classList.remove('active');
                    menuBtn.innerHTML = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>`;
                });
            }
        });
    </script>

    <!-- Main Content -->
    <div class="p-6 sm:ml-72 pt-20">
        <div class="p-2">