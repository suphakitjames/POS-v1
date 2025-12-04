// POS System JavaScript
let cart = [];
let selectedPaymentMethod = 'cash';
let currentShiftId = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('POS System Initialized');
    checkShift();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Product Search
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(e.target.value);
            }, 300);
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const keyword = e.target.value.trim();
                if (keyword) {
                    searchProducts(keyword);
                }
            }
        });
    }

    // Received Amount Input - Calculate change
    const receivedInput = document.getElementById('receivedAmount');
    if (receivedInput) {
        receivedInput.addEventListener('input', calculateChange);
    }

    // Payment modal buttons
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    if (confirmPaymentBtn) {
        console.log('Binding confirmPaymentBtn');
        confirmPaymentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Confirm Payment Button Clicked via Event Listener');
            confirmPayment();
        });
    }

    const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
    if (cancelPaymentBtn) {
        cancelPaymentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closePaymentModal();
        });
    }

    // Close shift modal buttons
    const confirmCloseShiftBtn = document.getElementById('confirmCloseShiftBtn');
    if (confirmCloseShiftBtn) {
        console.log('Binding confirmCloseShiftBtn');
        confirmCloseShiftBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Confirm Close Shift Button Clicked via Event Listener');
            confirmCloseShift();
        });
    }

    const cancelCloseShiftBtn = document.getElementById('cancelCloseShiftBtn');
    if (cancelCloseShiftBtn) {
        cancelCloseShiftBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeCloseShiftModal();
        });
    }
}

// Open Shift
window.openShift = function() {
    const startCash = parseFloat(document.getElementById('startCashInput').value) || 0;
    
    if (startCash < 0) {
        alert('กรุณาระบุจำนวนเงินที่ถูกต้อง');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'open');
    formData.append('start_cash', startCash);

    fetch('api/shift_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentShiftId = data.shift_id;
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเปิดกะ');
    });
};

// Check Shift Status
function checkShift() {
    fetch('api/shift_action.php?action=check')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.shift) {
                currentShiftId = data.shift.id;
                console.log('Current Shift ID:', currentShiftId);
            }
        })
        .catch(error => console.error('Error checking shift:', error));
}

// Search Products
function searchProducts(keyword) {
    if (!keyword || keyword.length < 2) {
        document.getElementById('productGrid').innerHTML = '<p class="col-span-full text-center text-gray-500 py-8">ค้นหาสินค้าหรือยิงบาร์โค้ดเพื่อเพิ่มลงตะกร้า</p>';
        return;
    }

    fetch(`api/pos_search.php?keyword=${encodeURIComponent(keyword)}`)
        .then(response => response.json())
        .then(products => {
            displayProducts(products);
            
            // Auto-add if exact barcode match
            if (products.length === 1 && products[0].barcode === keyword) {
                addToCart(products[0]);
                document.getElementById('productSearch').value = '';
                document.getElementById('productGrid').innerHTML = '<p class="col-span-full text-center text-gray-500 py-8">ค้นหาสินค้าหรือยิงบาร์โค้ดเพื่อเพิ่มลงตะกร้า</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Display Products
function displayProducts(products) {
    const grid = document.getElementById('productGrid');
    
    if (products.length === 0) {
        grid.innerHTML = '<p class="col-span-full text-center text-gray-500 py-8">ไม่พบสินค้า</p>';
        return;
    }

    grid.innerHTML = products.map(product => `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow cursor-pointer" onclick='addToCart(${JSON.stringify(product)})'>
            <div class="aspect-square bg-gray-100 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                ${product.image_path ? 
                    `<img src="${product.image_path}" class="w-full h-full object-cover" alt="${product.name}">` :
                    `<svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>`
                }
            </div>
            <h3 class="font-semibold text-gray-900 text-sm mb-1 truncate">${product.name}</h3>
            <p class="text-xs text-gray-500 mb-2">${product.sku}</p>
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-blue-600">${parseFloat(product.selling_price).toFixed(2)} ฿</span>
                <span class="text-xs text-gray-500">คงเหลือ: ${product.stock_quantity}</span>
            </div>
        </div>
    `).join('');
}

// Add to Cart
window.addToCart = function(product) {
    const existingItem = cart.find(item => item.product_id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity >= product.stock_quantity) {
            alert('สินค้าคงเหลือไม่เพียงพอ');
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({
            product_id: product.id,
            name: product.name,
            price: parseFloat(product.selling_price),
            quantity: 1,
            stock_quantity: product.stock_quantity
        });
    }
    
    updateCart();
};

// Update Cart Display
function updateCart() {
    const cartItems = document.getElementById('cartItems');
    const cartCount = document.getElementById('cartCount');
    const totalAmount = document.getElementById('totalAmount');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="text-center text-gray-400 py-12">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="font-medium">ตะกร้าว่างเปล่า</p>
            </div>
        `;
        cartCount.textContent = '0';
        totalAmount.textContent = '0.00 ฿';
        checkoutBtn.disabled = true;
        return;
    }
    
    let total = 0;
    cartItems.innerHTML = cart.map((item, index) => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        return `
            <div class="bg-white rounded-lg p-3 border border-gray-200">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 text-sm">${item.name}</h4>
                        <p class="text-xs text-gray-500">${item.price.toFixed(2)} ฿</p>
                    </div>
                    <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button onclick="updateQuantity(${index}, -1)" class="w-8 h-8 bg-gray-200 hover:bg-gray-300 rounded-md font-bold">-</button>
                        <span class="w-12 text-center font-bold">${item.quantity}</span>
                        <button onclick="updateQuantity(${index}, 1)" class="w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded-md font-bold">+</button>
                    </div>
                    <span class="font-bold text-blue-600">${subtotal.toFixed(2)} ฿</span>
                </div>
            </div>
        `;
    }).join('');
    
    cartCount.textContent = cart.length;
    totalAmount.textContent = total.toFixed(2) + ' ฿';
    checkoutBtn.disabled = false;
}

// Update Quantity
window.updateQuantity = function(index, change) {
    const item = cart[index];
    const newQty = item.quantity + change;
    
    if (newQty <= 0) {
        removeFromCart(index);
        return;
    }
    
    if (newQty > item.stock_quantity) {
        alert('สินค้าคงเหลือไม่เพียงพอ');
        return;
    }
    
    item.quantity = newQty;
    updateCart();
};

// Remove from Cart
window.removeFromCart = function(index) {
    cart.splice(index, 1);
    updateCart();
};

// Clear Cart
window.clearCart = function() {
    if (cart.length === 0) return;
    if (confirm('ยกเลิกรายการทั้งหมด?')) {
        cart = [];
        updateCart();
    }
};

// Show Payment Modal
window.showPaymentModal = function() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    document.getElementById('paymentTotal').textContent = total.toFixed(2) + ' ฿';
    document.getElementById('receivedAmount').value = total.toFixed(2);
    calculateChange();
    selectPaymentMethod('cash');
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
    document.getElementById('receivedAmount').focus();
};

window.closePaymentModal = function() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentModal').classList.remove('flex');
};

// Select Payment Method
window.selectPaymentMethod = function(method) {
    selectedPaymentMethod = method;
    console.log('Selected payment method:', method);
    
    // Update button styles
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.classList.remove('active', 'border-blue-500', 'bg-blue-50', 'text-blue-700');
        btn.classList.add('border-gray-300', 'text-gray-700');
    });
    
    const activeBtn = document.querySelector(`[data-method="${method}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active', 'border-blue-500', 'bg-blue-50', 'text-blue-700');
        activeBtn.classList.remove('border-gray-300', 'text-gray-700');
    }
    
    // Show/hide fields
    if (method === 'cash') {
        document.getElementById('cashFields').classList.remove('hidden');
        document.getElementById('qrFields').classList.add('hidden');
    } else if (method === 'qr') {
        document.getElementById('cashFields').classList.add('hidden');
        document.getElementById('qrFields').classList.remove('hidden');
        generateQRCode();
    } else {
        document.getElementById('cashFields').classList.add('hidden');
        document.getElementById('qrFields').classList.add('hidden');
    }
};

// Calculate Change
function calculateChange() {
    const total = parseFloat(document.getElementById('paymentTotal').textContent);
    const received = parseFloat(document.getElementById('receivedAmount').value) || 0;
    const change = received - total;
    document.getElementById('changeAmount').textContent = change.toFixed(2) + ' ฿';
}

// Generate QR Code
function generateQRCode() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    fetch(`api/generate_qr.php?amount=${total}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const qrDisplay = document.getElementById('qrCodeDisplay');
                qrDisplay.innerHTML = '';
                QRCode.toCanvas(data.payload, { width: 256 }, (error, canvas) => {
                    if (error) {
                        console.error(error);
                        qrDisplay.innerHTML = '<p class="text-red-500">ไม่สามารถสร้าง QR Code ได้</p>';
                    } else {
                        qrDisplay.appendChild(canvas);
                    }
                });
            } else {
                document.getElementById('qrCodeDisplay').innerHTML = '<p class="text-red-500">' + data.message + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('qrCodeDisplay').innerHTML = `<p class="text-red-500 text-sm">Error: ${error.message}</p>`;
        });
}

// Confirm Payment - MAIN FUNCTION
window.confirmPayment = function() {
    console.log('=== CONFIRM PAYMENT FUNCTION CALLED ===');
    console.log('Payment method:', selectedPaymentMethod);
    console.log('Cart:', cart);
    
    if (selectedPaymentMethod === 'cash') {
        const total = parseFloat(document.getElementById('paymentTotal').textContent);
        const received = parseFloat(document.getElementById('receivedAmount').value) || 0;
        
        console.log('Total:', total, 'Received:', received);
        
        if (received < total) {
            alert('จำนวนเงินไม่พอ!');
            return;
        }
    }
    
    // Prepare items
    const items = cart.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        price: item.price
    }));
    
    console.log('Sending to API:', { items, payment_method: selectedPaymentMethod });
    
    // Send to server
    fetch('api/pos_checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            items: items,
            payment_method: selectedPaymentMethod
        })
    })
    .then(response => {
        console.log('API Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API Response data:', data);
        if (data.success) {
            alert('บันทึกการขายสำเร็จ!\n\nเลขที่ใบเสร็จ: ' + data.receipt_number);
            
            // Open receipt in new window
            window.open(`receipt.php?id=${data.sale_id}`, '_blank', 'width=800,height=600');
            
            // Clear cart and close modal
            cart = [];
            updateCart();
            closePaymentModal();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + error.message);
    });
};

// Show Close Shift Modal
window.showCloseShiftModal = function() {
    console.log('=== SHOW CLOSE SHIFT MODAL ===');
    if (cart.length > 0) {
        alert('กรุณาชำระรายการที่ค้างอยู่ก่อนปิดกะ');
        return;
    }

    console.log('Fetching summary for shift:', currentShiftId);
    
    fetch(`api/shift_action.php?action=summary&shift_id=${currentShiftId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Shift summary data:', data);
            if (data.success) {
                const summary = data.summary;
                const startCash = parseFloat(summary.start_cash || 0);
                const cashTotal = parseFloat(summary.cash_total || 0);
                const expectedCash = startCash + cashTotal;
                
                document.getElementById('summaryStartCash').textContent = startCash.toFixed(2) + ' ฿';
                document.getElementById('summaryCashSales').textContent = cashTotal.toFixed(2) + ' ฿';
                document.getElementById('summaryQRSales').textContent = parseFloat(summary.qr_total || 0).toFixed(2) + ' ฿';
                document.getElementById('summaryExpectedCash').textContent = expectedCash.toFixed(2) + ' ฿';
                
                document.getElementById('endCashInput').value = expectedCash.toFixed(2);
            }
        })
        .catch(error => {
            console.error('Error fetching summary:', error);
        });
    
    document.getElementById('closeShiftModal').classList.remove('hidden');
    document.getElementById('closeShiftModal').classList.add('flex');
};

window.closeCloseShiftModal = function() {
    document.getElementById('closeShiftModal').classList.add('hidden');
    document.getElementById('closeShiftModal').classList.remove('flex');
};

// Confirm Close Shift - MAIN FUNCTION
window.confirmCloseShift = function() {
    console.log('=== CONFIRM CLOSE SHIFT FUNCTION CALLED ===');
    console.log('Shift ID:', currentShiftId);
    
    const endCash = parseFloat(document.getElementById('endCashInput').value) || 0;
    console.log('End cash:', endCash);
    
    const formData = new FormData();
    formData.append('action', 'close');
    formData.append('shift_id', currentShiftId);
    formData.append('end_cash', endCash);
    
    fetch('api/shift_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Close shift response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Close shift response data:', data);
        if (data.success) {
            const diff = data.diff_amount;
            let msg = 'ปิดกะสำเร็จ!\n\n';
            msg += `เงินสดที่ควรมี: ${parseFloat(data.expected_cash).toFixed(2)} ฿\n`;
            msg += `เงินสดที่นับได้: ${parseFloat(data.end_cash).toFixed(2)} ฿\n`;
            
            if (diff > 0) {
                msg += `เงินเกิน: +${diff.toFixed(2)} ฿`;
            } else if (diff < 0) {
                msg += `เงินขาด: ${diff.toFixed(2)} ฿`;
            } else {
                msg += 'ยอดเงินตรงกัน ✓';
            }
            
            alert(msg);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการปิดกะ: ' + error.message);
    });
};
