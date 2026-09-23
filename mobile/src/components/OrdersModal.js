// mobile/src/components/OrdersModal.js
import React, { useState, useEffect } from 'react';
import { 
  View, Text, Image, Modal, TouchableOpacity, ScrollView, 
  ActivityIndicator, StyleSheet 
} from 'react-native';
import { apiGetUserOrders } from '../services/api';
import { THEME } from '../theme';

export default function OrdersModal({ visible, onClose, user, onSelectOrder }) {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const [viewSlipUrl, setViewSlipUrl] = useState(null);

  useEffect(() => {
    if (visible && user) {
      loadOrders();
    }
  }, [visible, user]);

  const loadOrders = async () => {
    if (!user) return;
    setLoading(true);
    const res = await apiGetUserOrders(user.id);
    setLoading(false);
    if (res.success) {
      setOrders(res.orders || []);
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'Paid':
        return { bg: '#E8F5E9', text: '#2E7D32', label: 'PAID' };
      case 'Shipped':
        return { bg: '#E3F2FD', text: '#1565C0', label: 'SHIPPED' };
      case 'Completed':
        return { bg: '#E8F5E9', text: '#2E7D32', label: 'COMPLETED' };
      case 'Cancelled':
        return { bg: '#FFEBEE', text: '#C62828', label: 'CANCELLED' };
      case 'Pending':
      default:
        return { bg: '#FFF8E1', text: '#F57F17', label: 'PENDING' };
    }
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>ORDER HISTORY</Text>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <Text style={styles.closeText}>✕</Text>
          </TouchableOpacity>
        </View>

        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color={THEME.dark} />
            <Text style={styles.loadingText}>กำลังโหลดประวัติออเดอร์...</Text>
          </View>
        ) : (
          <ScrollView contentContainerStyle={styles.list}>
            {orders.length === 0 ? (
              <View style={styles.emptyBox}>
                <Text style={styles.emptyText}>ยังไม่มีประวัติการสั่งซื้อ</Text>
              </View>
            ) : (
              orders.map((o) => {
                const badge = getStatusBadge(o.status);
                return (
                  <TouchableOpacity 
                    key={o.id} 
                    style={styles.orderCard}
                    activeOpacity={0.85}
                    onPress={() => {
                      if (onSelectOrder) onSelectOrder(o.id);
                    }}
                  >
                    <View style={styles.cardHeader}>
                      <Text style={styles.orderNumber}>ORDER #{o.id}</Text>
                      <View style={[styles.statusBadge, { backgroundColor: badge.bg }]}>
                        <Text style={[styles.statusText, { color: badge.text }]}>{badge.label}</Text>
                      </View>
                    </View>

                    <Text style={styles.dateText}>DATE: {o.created_at || 'RECENT'}</Text>
                    <Text style={styles.paymentMethod}>PAYMENT: {o.payment_method}</Text>

                    <View style={styles.cardFooter}>
                      <Text style={styles.totalLabel}>
                        TOTAL: <Text style={styles.totalValue}>฿{Number(o.total_amount).toLocaleString()}</Text>
                      </Text>

                      <View style={styles.actionBtnRow}>
                        <Text style={styles.detailHint}>RECEIPT ➜</Text>
                      </View>
                    </View>
                  </TouchableOpacity>
                );
              })
            )}
          </ScrollView>
        )}

        {/* Modal ขยายรูปสลิป */}
        <Modal visible={!!viewSlipUrl} transparent={true} animationType="fade">
          <View style={styles.slipModalOverlay}>
            <View style={styles.slipModalBox}>
              <View style={styles.slipModalHeader}>
                <Text style={styles.slipModalTitle}>PAYMENT SLIP</Text>
                <TouchableOpacity onPress={() => setViewSlipUrl(null)}>
                  <Text style={styles.closeText}>✕</Text>
                </TouchableOpacity>
              </View>
              {viewSlipUrl && (
                <Image source={{ uri: viewSlipUrl }} style={styles.slipFullImg} resizeMode="contain" />
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
  closeBtn: { padding: 5 },
  closeText: { fontSize: 20, color: THEME.dark, fontWeight: 'bold' },
  loadingBox: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 10, color: THEME.gray, fontSize: 13 },
  list: { padding: 16, paddingBottom: 40 },
  emptyBox: { padding: 50, alignItems: 'center' },
  emptyText: { color: THEME.gray, fontSize: 14 },
  orderCard: {
    backgroundColor: THEME.white,
    borderWidth: 1,
    borderColor: THEME.border,
    padding: 16,
    marginBottom: 14,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  orderNumber: { fontSize: 14, fontWeight: 'bold', color: THEME.dark, letterSpacing: 1 },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 3 },
  statusText: { fontSize: 11, fontWeight: '700', letterSpacing: 1 },
  dateText: { fontSize: 12, color: THEME.gray, marginBottom: 4 },
  paymentMethod: { fontSize: 12, color: THEME.dark, marginBottom: 10 },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#F5F0EB',
    paddingTop: 10,
  },
  totalLabel: { fontSize: 13, color: THEME.gray },
  totalValue: { fontSize: 16, fontWeight: 'bold', color: THEME.dark },
  actionBtnRow: { flexDirection: 'row', alignItems: 'center' },
  detailHint: { fontSize: 11, fontWeight: '800', color: THEME.dark, letterSpacing: 1 },
  viewSlipBtn: { borderWidth: 1, borderColor: THEME.dark, paddingHorizontal: 12, paddingVertical: 6 },
  viewSlipBtnText: { fontSize: 11, color: THEME.dark, fontWeight: '700', letterSpacing: 1 },
  slipModalOverlay: { flex: 1, backgroundColor: 'rgba(45,40,40,0.85)', justifyContent: 'center', alignItems: 'center', padding: 20 },
  slipModalBox: { width: '100%', height: '80%', backgroundColor: THEME.white, padding: 16, borderWidth: 1, borderColor: THEME.border },
  slipModalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  slipModalTitle: { fontSize: 14, fontWeight: 'bold', letterSpacing: 1.5, color: THEME.dark },
  slipFullImg: { width: '100%', height: '90%' },
});
