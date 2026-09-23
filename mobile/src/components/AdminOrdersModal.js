// mobile/src/components/AdminOrdersModal.js
import React, { useState, useEffect } from 'react';
import { 
  View, Text, Image, Modal, TouchableOpacity, ScrollView, 
  ActivityIndicator, Alert, StyleSheet 
} from 'react-native';
import { apiGetAdminOrders, apiUpdateOrderStatus } from '../services/api';
import { THEME } from '../theme';

const STATUS_FILTERS = [
  { key: 'all', label: 'ALL ORDERS' },
  { key: 'Pending', label: 'PENDING' },
  { key: 'Paid', label: 'PAID' },
];

export default function AdminOrdersModal({ visible, onClose }) {
  const [orders, setOrders] = useState([]);
  const [kpi, setKpi] = useState(null);
  const [activeFilter, setActiveFilter] = useState('Pending');
  const [loading, setLoading] = useState(false);
  const [selectedSlipOrder, setSelectedSlipOrder] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    if (visible) {
      loadAdminOrders();
    }
  }, [visible, activeFilter]);

  const loadAdminOrders = async () => {
    setLoading(true);
    const res = await apiGetAdminOrders(activeFilter);
    setLoading(false);
    if (res.success) {
      setOrders(res.orders || []);
      setKpi(res.kpi || null);
    } else {
      Alert.alert('ข้อผิดพลาด', res.message || 'ไม่สามารถดึงข้อมูลคำสั่งซื้อได้');
    }
  };

  const handleSetPaid = async (orderId) => {
    Alert.alert(
      'ยืนยันการอนุมัติสลิป',
      `คุณต้องการอนุมัติการชำระเงินสำหรับออเดอร์ #${orderId} และเปลี่ยนสถานะเป็น 'Paid' หรือไม่?`,
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { 
          text: 'อนุมัติ (Confirm Paid)', 
          onPress: async () => {
            setActionLoading(true);
            const res = await apiUpdateOrderStatus(orderId, 'Paid');
            setActionLoading(false);
            if (res.success) {
              Alert.alert('สำเร็จ 🎉', res.message);
              if (selectedSlipOrder && selectedSlipOrder.id === orderId) {
                setSelectedSlipOrder(null);
              }
              loadAdminOrders();
            } else {
              Alert.alert('ผิดพลาด', res.message);
            }
          }
        }
      ]
    );
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'Paid':
        return { bg: '#E8F5E9', text: '#2E7D32', label: 'PAID' };
      case 'Pending':
      default:
        return { bg: '#FFF8E1', text: '#F57F17', label: 'PENDING' };
    }
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.container}>
        <View style={styles.header}>
          <View>
            <Text style={styles.headerTitle}>ADMIN DASHBOARD</Text>
            <Text style={styles.headerSubtitle}>ORDER VERIFICATION & SLIP INSPECTION</Text>
          </View>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <Text style={styles.closeText}>✕</Text>
          </TouchableOpacity>
        </View>

        {/* KPI Summary Cards */}
        {kpi && (
          <View style={styles.kpiContainer}>
            <View style={styles.kpiCard}>
              <Text style={styles.kpiVal}>{kpi.total_orders}</Text>
              <Text style={styles.kpiLabel}>TOTAL</Text>
            </View>
            <View style={[styles.kpiCard, { borderColor: THEME.primary }]}>
              <Text style={[styles.kpiVal, { color: THEME.dark }]}>{kpi.pending_orders}</Text>
              <Text style={styles.kpiLabel}>PENDING</Text>
            </View>
            <View style={styles.kpiCard}>
              <Text style={styles.kpiVal}>฿{Number(kpi.total_revenue).toLocaleString()}</Text>
              <Text style={styles.kpiLabel}>REVENUE</Text>
            </View>
          </View>
        )}

        {/* Status Filters */}
        <View style={styles.filterRow}>
          {STATUS_FILTERS.map(f => (
            <TouchableOpacity
              key={f.key}
              style={[styles.filterBtn, activeFilter === f.key && styles.filterBtnActive]}
              onPress={() => setActiveFilter(f.key)}
            >
              <Text style={[styles.filterText, activeFilter === f.key && styles.filterTextActive]}>
                {f.label}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color={THEME.dark} />
            <Text style={styles.loadingText}>กำลังโหลดรายการออเดอร์...</Text>
          </View>
        ) : (
          <ScrollView contentContainerStyle={styles.list}>
            {orders.length === 0 ? (
              <View style={styles.emptyBox}>
                <Text style={styles.emptyText}>ไม่มีรายการออเดอร์ในสถานะนี้</Text>
              </View>
            ) : (
              orders.map((o) => {
                const badge = getStatusBadge(o.status);
                const isPending = o.status === 'Pending';

                return (
                  <View key={o.id} style={styles.orderCard}>
                    <View style={styles.cardTop}>
                      <View>
                        <Text style={styles.orderId}>ORDER #{o.id}</Text>
                        <Text style={styles.customerName}>CUSTOMER: {o.fullname} ({o.username})</Text>
                        {o.phone ? <Text style={styles.customerPhone}>TEL: {o.phone}</Text> : null}
                      </View>
                      <View style={[styles.statusBadge, { backgroundColor: badge.bg }]}>
                        <Text style={[styles.statusText, { color: badge.text }]}>{badge.label}</Text>
                      </View>
                    </View>

                    <View style={styles.cardDetails}>
                      <Text style={styles.date}>DATE: {o.created_at || 'RECENT'}</Text>
                      <Text style={styles.amount}>TOTAL: <Text style={styles.amountVal}>฿{Number(o.total_amount).toLocaleString()}</Text></Text>
                    </View>

                    {/* Action Buttons */}
                    <View style={styles.actionRow}>
                      {o.slip_url ? (
                        <TouchableOpacity 
                          style={styles.inspectSlipBtn} 
                          onPress={() => setSelectedSlipOrder(o)}
                        >
                          <Text style={styles.inspectSlipText}>🔍 VIEW SLIP</Text>
                        </TouchableOpacity>
                      ) : (
                        <Text style={styles.noSlipText}>NO SLIP ATTACHED</Text>
                      )}

                      {isPending && (
                        <TouchableOpacity 
                          style={styles.approveBtn} 
                          disabled={actionLoading}
                          onPress={() => handleSetPaid(o.id)}
                        >
                          <Text style={styles.approveBtnText}>✓ SET PAID</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                  </View>
                );
              })
            )}
          </ScrollView>
        )}

        {/* Modal ตรวจสอบสลิปขนาดเต็ม */}
        <Modal visible={!!selectedSlipOrder} transparent={true} animationType="fade">
          <View style={styles.slipModalOverlay}>
            <View style={styles.slipModalBox}>
              <View style={styles.slipModalHeader}>
                <View>
                  <Text style={styles.slipModalTitle}>ORDER #{selectedSlipOrder?.id} SLIP</Text>
                  <Text style={styles.slipModalSubtitle}>
                    ฿{Number(selectedSlipOrder?.total_amount || 0).toLocaleString()} • {selectedSlipOrder?.fullname}
                  </Text>
                </View>
                <TouchableOpacity onPress={() => setSelectedSlipOrder(null)}>
                  <Text style={styles.closeText}>✕</Text>
                </TouchableOpacity>
              </View>

              {selectedSlipOrder?.slip_url && (
                <Image source={{ uri: selectedSlipOrder.slip_url }} style={styles.slipFullImg} resizeMode="contain" />
              )}

              {selectedSlipOrder?.status === 'Pending' && (
                <TouchableOpacity 
                  style={styles.modalApproveBtn} 
                  disabled={actionLoading}
                  onPress={() => handleSetPaid(selectedSlipOrder.id)}
                >
                  <Text style={styles.modalApproveBtnText}>✓ CONFIRM PAYMENT (SET PAID)</Text>
                </TouchableOpacity>
              )}
            </View>
          </View>
        </Modal>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: THEME.light },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 50,
    paddingBottom: 15,
    backgroundColor: THEME.white,
    borderBottomWidth: 1,
    borderBottomColor: THEME.border,
  },
  headerTitle: { fontSize: 14, fontWeight: '700', letterSpacing: 2, color: THEME.dark },
  headerSubtitle: { fontSize: 11, color: THEME.gray, marginTop: 2, letterSpacing: 1 },
  closeBtn: { padding: 5 },
  closeText: { fontSize: 20, color: THEME.dark, fontWeight: 'bold' },
  kpiContainer: { flexDirection: 'row', padding: 14, gap: 10, backgroundColor: THEME.white, borderBottomWidth: 1, borderBottomColor: THEME.border },
  kpiCard: { flex: 1, backgroundColor: THEME.light, padding: 10, borderWidth: 1, borderColor: THEME.border },
  kpiVal: { fontSize: 15, fontWeight: '800', color: THEME.dark },
  kpiLabel: { fontSize: 10, color: THEME.gray, marginTop: 2, letterSpacing: 1, fontWeight: '700' },
  filterRow: { flexDirection: 'row', padding: 12, gap: 8, backgroundColor: THEME.white, borderBottomWidth: 1, borderBottomColor: THEME.border },
  filterBtn: { paddingVertical: 8, paddingHorizontal: 14, borderWidth: 1, borderColor: THEME.border, backgroundColor: THEME.light },
  filterBtnActive: { backgroundColor: THEME.dark, borderColor: THEME.dark },
  filterText: { fontSize: 11, color: THEME.dark, fontWeight: '700', letterSpacing: 1 },
  filterTextActive: { color: THEME.white },
  loadingBox: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 10, color: THEME.gray },
  list: { padding: 16, paddingBottom: 40 },
  emptyBox: { padding: 50, alignItems: 'center' },
  emptyText: { color: THEME.gray, fontSize: 14 },
  orderCard: { backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 16, marginBottom: 14 },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 10 },
  orderId: { fontSize: 14, fontWeight: 'bold', color: THEME.dark, letterSpacing: 1 },
  customerName: { fontSize: 13, fontWeight: '600', color: THEME.dark, marginTop: 2 },
  customerPhone: { fontSize: 12, color: THEME.gray, marginTop: 2 },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 3 },
  statusText: { fontSize: 10, fontWeight: 'bold', letterSpacing: 1 },
  cardDetails: { borderTopWidth: 1, borderTopColor: '#F5F0EB', paddingTop: 8, marginBottom: 12 },
  date: { fontSize: 11, color: THEME.gray, marginBottom: 4 },
  amount: { fontSize: 13, color: THEME.dark },
  amountVal: { fontSize: 15, fontWeight: 'bold', color: THEME.dark },
  actionRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 10 },
  inspectSlipBtn: { flex: 1, borderWidth: 1, borderColor: THEME.dark, paddingVertical: 9, alignItems: 'center', backgroundColor: THEME.white },
  inspectSlipText: { color: THEME.dark, fontWeight: '700', fontSize: 12, letterSpacing: 1 },
  approveBtn: { flex: 1, backgroundColor: THEME.dark, paddingVertical: 10, alignItems: 'center' },
  approveBtnText: { color: THEME.white, fontWeight: '700', fontSize: 12, letterSpacing: 1 },
  noSlipText: { color: THEME.gray, fontSize: 11, fontStyle: 'italic' },
  slipModalOverlay: { flex: 1, backgroundColor: 'rgba(45,40,40,0.85)', justifyContent: 'center', alignItems: 'center', padding: 20 },
  slipModalBox: { width: '100%', height: '85%', backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 16 },
  slipModalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 10 },
  slipModalTitle: { fontSize: 14, fontWeight: 'bold', letterSpacing: 1.5, color: THEME.dark },
  slipModalSubtitle: { fontSize: 12, color: THEME.gray, marginTop: 2 },
  slipFullImg: { width: '100%', height: '75%', backgroundColor: THEME.secondary },
  modalApproveBtn: { backgroundColor: THEME.dark, paddingVertical: 14, alignItems: 'center', marginTop: 12 },
  modalApproveBtnText: { color: THEME.white, fontWeight: 'bold', fontSize: 13, letterSpacing: 1.5 },
});
