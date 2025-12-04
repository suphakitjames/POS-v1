<?php
if (!isset($_SESSION)) {
    session_start();
}

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = $page_title ?? 'Smart Inventory System';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> - Smart Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

        /* Sidebar Styles */
        aside {
            transform: translateX(-100%);
        }

        aside.active {
            transform: translateX(0);
        }

        @media (min-width: 640px) {
            aside {
                transform: translateX(0);
            }
        }

        #sidebarOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 30;
        }

        #sidebarOverlay.active {
            display: block;
        }

        .notification-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f8fafc;
        }

        .notification-item.unread {
            background-color: #eff6ff;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn"
        class="sm:hidden fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-lg text-slate-700 hover:text-blue-600 transition-colors">
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

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <!-- Admin Only Menu -->
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
                <?php endif; ?>

                <!-- Both Admin and Staff -->
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

                <?php if ($_SESSION['role'] === 'staff'): ?>
                    <!-- Staff Only - POS Menu -->
                    <li>
                        <a href="pos.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'pos.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                            <svg class="w-5 h-5 <?= $current_page == 'pos.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="ml-3">ขายหน้าร้าน (POS)</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Both Admin and Staff - Sales History -->
                <li>
                    <a href="sales_history.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'sales_history.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-5 h-5 <?= $current_page == 'sales_history.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-3">ประวัติการขาย</span>
                    </a>
                </li>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <!-- Admin Only - Management Menu -->
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

                    <li>
                        <a href="settings.php" class="flex items-center p-3 rounded-xl transition-all duration-200 group <?= $current_page == 'settings.php' ? 'text-blue-700 bg-blue-50/80 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                            <svg class="w-5 h-5 <?= $current_page == 'settings.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' ?> transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="ml-3">ตั้งค่าระบบ</span>
                        </a>
                    </li>

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
                <?php endif; ?>
            </ul>
        </div>
    </aside>

    <!-- Top Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 ml-0 sm:ml-72 bg-white border-b border-slate-200 h-16">
        <div class="h-full px-6 flex items-center justify-between gap-4">
            <!-- Page Title (Mobile) -->
            <div class="flex-1 sm:hidden">
                <h2 class="text-lg font-bold text-slate-800"><?= h($page_title) ?></h2>
            </div>

            <!-- Search Bar (Desktop) -->
            <div class="hidden sm:block flex-1 max-w-2xl">
                <form action="products.php" method="GET" class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" name="search" placeholder="ค้นหาสินค้า..."
                        class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl leading-5 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 sm:text-sm transition-all duration-200">
                </form>
            </div>

            <!-- Right Section -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell -->
                <button class="relative p-2 text-slate-400 hover:text-slate-600 transition-colors rounded-lg hover:bg-slate-50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
                </button>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <button id="profileBtn" class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-semibold text-slate-700"><?= h($_SESSION['username'] ?? 'User') ?></p>
                            <p class="text-xs text-slate-500 capitalize"><?= h($_SESSION['role'] ?? 'staff') ?></p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-sm">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-1 ring-1 ring-black ring-opacity-5 focus:outline-none z-50 transform origin-top-right transition-all duration-200">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-sm text-slate-500">เข้าสู่ระบบโดย</p>
                            <p class="text-sm font-bold text-slate-800 truncate"><?= h($_SESSION['username'] ?? 'User') ?></p>
                        </div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">ออกจากระบบ</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile menu toggle
            const menuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.querySelector('aside');
            const overlay = document.getElementById('sidebarOverlay');

            if (menuBtn && sidebar && overlay) {
                menuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });

                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }

            // Profile Dropdown Toggle
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <!-- Main Content -->
    <div class="p-6 sm:ml-72 pt-20">
        <div class="p-2">