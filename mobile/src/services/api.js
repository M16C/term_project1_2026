// mobile/src/services/api.js
// Client API service for communicating with PHP XAMPP backend

import { API_URL } from '../config';

// 1. เข้าสู่ระบบ
export async function apiLogin(username, password) {
  try {
    const res = await fetch(`${API_URL}/auth.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'login', username, password })
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ (' + error.message + ')' };
  }
}

// 2. สมัครสมาชิก
export async function apiRegister(userData) {
  try {
    const res = await fetch(`${API_URL}/auth.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'register', ...userData })
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ (' + error.message + ')' };
  }
}

// 3. แก้ไขข้อมูลส่วนตัว
export async function apiUpdateProfile({ userId, fullname, email, phone }) {
  try {
    const res = await fetch(`${API_URL}/auth.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'update_profile', user_id: userId, fullname, email, phone })
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// 4. เปลี่ยนรหัสผ่าน
export async function apiChangePassword({ userId, oldPassword, newPassword }) {
  try {
    const res = await fetch(`${API_URL}/auth.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'change_password', user_id: userId, old_password: oldPassword, new_password: newPassword })
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// 5. ดึงรายการสินค้า พร้อมการค้นหา ตัวกรองราคา และการจัดเรียง
export async function apiGetProducts(params = {}) {
  try {
    const query = new URLSearchParams(params).toString();
    const res = await fetch(`${API_URL}/products.php?${query}`);
    return await res.json();
  } catch (error) {
    return { success: false, products: [], message: error.message };
  }
}

// 6. ดึงรายละเอียดสินค้าเดี่ยว
export async function apiGetProductDetail(id) {
  try {
    const res = await fetch(`${API_URL}/products.php?id=${id}`);
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// 7. สั่งซื้อและแนบสลิป (รองรับทั้ง Base64 JSON และ FormData)
export async function apiCheckout({ userId, items, slipBase64, slipUri, slipName, slipType }) {
  try {
    if (slipBase64) {
      const res = await fetch(`${API_URL}/checkout.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: userId,
          items: items,
          slip_base64: slipBase64,
          slip_name: slipName || 'slip_' + Date.now() + '.jpg',
        })
      });
      return await res.json();
    }

    const formData = new FormData();
    formData.append('user_id', String(userId));
    formData.append('items', JSON.stringify(items));

    if (slipUri) {
      formData.append('slip', {
        uri: slipUri,
        name: slipName || 'slip_' + Date.now() + '.jpg',
        type: slipType || 'image/jpeg'
      });
    }

    const res = await fetch(`${API_URL}/checkout.php`, {
      method: 'POST',
      body: formData,
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: 'การสั่งซื้อขัดข้อง: ' + error.message };
  }
}

// 8. ดึงประวัติออเดอร์ของลูกค้า
export async function apiGetUserOrders(userId) {
  try {
    const res = await fetch(`${API_URL}/orders.php?user_id=${userId}`);
    return await res.json();
  } catch (error) {
    return { success: false, orders: [], message: error.message };
  }
}

// 9. ดึงรายละเอียดออเดอร์เดี่ยว (ใบเสร็จ / รายการสินค้า)
export async function apiGetOrderDetail(orderId) {
  try {
    const res = await fetch(`${API_URL}/orders.php?order_id=${orderId}`);
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// 10. สำหรับแอดมิน: ดึงรายการออเดอร์ทั้งหมดและ KPI
export async function apiGetAdminOrders(status = '', search = '') {
  try {
    let url = `${API_URL}/orders.php?admin=1`;
    if (status && status !== 'all') url += `&status=${encodeURIComponent(status)}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;

    const res = await fetch(url);
    return await res.json();
  } catch (error) {
    return { success: false, orders: [], message: error.message };
  }
}

// 11. สำหรับแอดมิน: อัปเดตสถานะออเดอร์ (เช่น อนุมัติเป็น Paid)
export async function apiUpdateOrderStatus(orderId, status) {
  try {
    const res = await fetch(`${API_URL}/orders.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'update_status', order_id: orderId, status })
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// 12. สำหรับแอดมิน: สถิติสต็อกสินค้า
export async function apiGetAdminStockStats() {
  try {
    const res = await fetch(`${API_URL}/admin_stock.php?action=stats`);
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// 13. สำหรับแอดมิน: จัดการสต็อกสินค้า (Add, Update, Delete)
export async function apiSaveStock(action, data) {
  try {
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
      if (data[key] !== undefined && data[key] !== null) {
        formData.append(key, String(data[key]));
      }
    }

    const res = await fetch(`${API_URL}/admin_stock.php`, {
      method: 'POST',
      body: formData,
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// 14. สำหรับแอดมิน: ลบสินค้า
export async function apiDeleteProduct(productId) {
  try {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', String(productId));

    const res = await fetch(`${API_URL}/admin_stock.php`, {
      method: 'POST',
      body: formData,
    });
    return await res.json();
  } catch (error) {
    return { success: false, message: error.message };
  }
}
