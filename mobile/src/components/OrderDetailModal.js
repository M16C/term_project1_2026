// mobile/src/components/OrderDetailModal.js - Matching order_detail.php
import React, { useState, useEffect } from 'react';
import { 
  View, Text, Image, Modal, TouchableOpacity, ScrollView, 
  ActivityIndicator, Alert, StyleSheet 
} from 'react-native';
import { apiGetOrderDetail, apiUpdateOrderStatus } from '../services/api';
import { THEME } from '../theme';

export default function OrderDetailModal({ visible, orderId, onClose, isAdmin, onStatusUpdated }) {
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(false);
  const [viewSlip, setViewSlip] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    if (visible && orderId) {
      loadDetail();
    }
  }, [visible, orderId]);

  const loadDetail = async () => {
    setLoading(true);
    const res = await apiGetOrderDetail(orderId);
    setLoading(false);
    if (res.success) {
      setOrder(res.order);
    } else {
      Alert.alert('ข้อผิดพลาด', res.message || 'ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้');
    }
  };

  const handleSetPaid = async () => {
    Alert.alert(
      'ยืนยันการอนุมัติสลิป',
      `อนุมัติการชำระเงินออเดอร์ #${orderId} และเปลี่ยนสถานะเป็น 'Paid' หรือไม่?`,
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { 
          text: 'อนุมัติ (Set Paid)', 
          onPress: async () => {
            setActionLoading(true);
            const res = await apiUpdateOrderStatus(orderId, 'Paid');
            setActionLoading(false);
            if (res.success) {
              Alert.alert('สำเร็จ 🎉', res.message);
              loadDetail();
              if (onStatusUpdated) onStatusUpdated();
            } else {
              Alert.alert('ผิดพลาด', res.message);
            }
          }
        }
      ]
    );
  };

  if (!visible) return null;

  return (
    <Modal visible={visible} animationType="slide" transparent={false} onRequestClose={onClose}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>ORDER RECEIPT #{orderId}</Text>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <Text style={styles.closeText}>✕</Text>
          </TouchableOpacity>
        </View>

        {loading || !order ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color={THEME.dark} />
            <Text style={styles.loadingText}>LOADING RECEIPT...</Text>
          </View>
        ) : (
          <ScrollView contentContainerStyle={styles.content}>
            {/* Invoice Card */}
            <View style={styles.invoiceCard}>
              <View style={styles.invoiceHeader}>
                <Text style={styles.brandTitle}>CLOTHING STORE</Text>
                <View style={[styles.statusBadge, { backgroundColor: order.status === 'Paid' ? '#E8F5E9' : '#FFF8E1' }]}>
                  <Text style={[styles.statusText, { color: order.status === 'Paid' ? '#2E7D32' : '#F57F17' }]}>
                    {order.status.toUpperCase()}
                  </Text>
                </View>
              </View>

              <Text style={styles.invoiceDate}>DATE: {order.created_at || 'RECENT'}</Text>
              <Text style={styles.invoiceMeta}>ORDER ID: #{order.id}</Text>

              <View style={styles.divider} />

              {/* Customer Information */}
              <Text style={styles.secHeading}>CUSTOMER DETAILS</Text>
              <Text style={styles.metaRow}>NAME: <Text style={styles.metaVal}>{order.fullname} ({order.username})</Text></Text>
              <Text style={styles.metaRow}>EMAIL: <Text style={styles.metaVal}>{order.email}</Text></Text>
              <Text style={styles.metaRow}>TEL: <Text style={styles.metaVal}>{order.phone || '-'}</Text></Text>

              <View style={styles.divider} />

              {/* Items List */}
              <Text style={styles.secHeading}>ORDERED ITEMS</Text>
              {order.items && order.items.map((item, idx) => (
                <View key={idx} style={styles.itemRow}>
                  {item.product_image_url ? (
                    <Image source={{ uri: item.product_image_url }} style={styles.itemImg} resizeMode="cover" />
                  ) : (
                    <View style={[styles.itemImg, { backgroundColor: THEME.secondary }]} />
                  )}
                  <View style={styles.itemInfo}>
                    <Text style={styles.itemName}>{item.product_name}</Text>
                    <Text style={styles.itemQty}>QTY: {item.quantity} x ฿{Number(item.price).toLocaleString()}</Text>
                  </View>
                  <Text style={styles.itemTotal}>฿{(item.quantity * item.price).toLocaleString()}</Text>
                </View>
              ))}

              <View style={styles.divider} />

              {/* Payment & Slip */}
              <Text style={styles.secHeading}>PAYMENT METHOD</Text>
              <Text style={styles.metaRow}>METHOD: <Text style={styles.metaVal}>{order.payment_method}</Text></Text>

              {order.slip_url ? (
                <View style={styles.slipBox}>
                  <Text style={styles.metaRow}>ATTACHED PAYMENT SLIP:</Text>
                  <TouchableOpacity onPress={() => setViewSlip(true)} style={styles.slipThumbWrapper}>
                    <Image source={{ uri: order.slip_url }} style={styles.slipThumb} resizeMode="cover" />
                    <Text style={styles.viewSlipHint}>🔍 กดเพื่อดูสลิปขนาดเต็ม</Text>
                  </TouchableOpacity>
                </View>
              ) : (
                <Text style={[styles.metaRow, { color: THEME.gray, fontStyle: 'italic' }]}>ไม่มีสลิปแนบ</Text>
              )}

              <View style={styles.divider} />

              {/* Total Calculation */}
              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>NET TOTAL AMOUNT</Text>
                <Text style={styles.totalValue}>฿{Number(order.total_amount).toLocaleString()}</Text>
              </View>

              {/* Admin Approval Button if pending */}
              {isAdmin && order.status === 'Pending' && (
                <TouchableOpacity 
                  style={styles.adminApproveBtn}
                  disabled={actionLoading}
                  onPress={handleSetPaid}
                >
                  <Text style={styles.adminApproveBtnText}>✓ APPROVE PAYMENT (SET PAID)</Text>
                </TouchableOpacity>
              )}
            </View>
          </ScrollView>
        )}

        {/* Modal ขยายรูปสลิป */}
        <Modal visible={viewSlip} transparent={true} animationType="fade">
          <View style={styles.slipOverlay}>
            <View style={styles.slipBoxModal}>
              <View style={styles.slipModalTop}>
                <Text style={styles.slipTitle}>PAYMENT SLIP #{orderId}</Text>
                <TouchableOpacity onPress={() => setViewSlip(false)}>
                  <Text style={styles.closeText}>✕</Text>
                </TouchableOpacity>
              </View>
              {order?.slip_url && (
                <Image source={{ uri: order.slip_url }} style={styles.fullSlipImg} resizeMode="contain" />
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
  headerTitle: { fontSize: 13, fontWeight: '800', letterSpacing: 2, color: THEME.dark },
  closeBtn: { padding: 5 },
  closeText: { fontSize: 20, color: THEME.dark, fontWeight: 'bold' },
  loadingBox: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 10, color: THEME.gray },
  content: { padding: 16, paddingBottom: 40 },
  invoiceCard: { backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 20 },
  invoiceHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  brandTitle: { fontSize: 16, fontWeight: '900', letterSpacing: 2, color: THEME.dark },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 4 },
  statusText: { fontSize: 11, fontWeight: '800', letterSpacing: 1 },
  invoiceDate: { fontSize: 12, color: THEME.gray, marginBottom: 4 },
  invoiceMeta: { fontSize: 12, color: THEME.dark, fontWeight: '700' },
  divider: { height: 1, backgroundColor: '#F0EBE6', marginVertical: 14 },
  secHeading: { fontSize: 11, fontWeight: '800', letterSpacing: 1.5, color: THEME.dark, marginBottom: 8 },
  metaRow: { fontSize: 12, color: THEME.gray, marginBottom: 4 },
  metaVal: { color: THEME.dark, fontWeight: '600' },
  itemRow: { flexDirection: 'row', alignItems: 'center', marginVertical: 6 },
  itemImg: { width: 45, height: 45, borderWidth: 1, borderColor: THEME.border },
  itemInfo: { flex: 1, marginLeft: 10 },
  itemName: { fontSize: 13, fontWeight: '600', color: THEME.dark },
  itemQty: { fontSize: 11, color: THEME.gray, marginTop: 2 },
  itemTotal: { fontSize: 13, fontWeight: 'bold', color: THEME.dark },
  slipBox: { marginTop: 8 },
  slipThumbWrapper: { marginTop: 6, borderWidth: 1, borderColor: THEME.border, padding: 6, backgroundColor: THEME.light, alignItems: 'center' },
  slipThumb: { width: '100%', height: 140 },
  viewSlipHint: { fontSize: 11, color: THEME.dark, fontWeight: '700', marginTop: 4 },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 8 },
  totalLabel: { fontSize: 13, fontWeight: '800', letterSpacing: 1.5, color: THEME.dark },
  totalValue: { fontSize: 18, fontWeight: '800', color: THEME.dark },
  adminApproveBtn: { backgroundColor: THEME.dark, paddingVertical: 14, alignItems: 'center', marginTop: 18 },
  adminApproveBtnText: { color: THEME.white, fontWeight: 'bold', fontSize: 13, letterSpacing: 1.5 },
  slipOverlay: { flex: 1, backgroundColor: 'rgba(45,40,40,0.85)', justifyContent: 'center', alignItems: 'center', padding: 20 },
  slipBoxModal: { width: '100%', height: '80%', backgroundColor: THEME.white, padding: 16, borderWidth: 1, borderColor: THEME.border },
  slipModalTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  slipTitle: { fontSize: 13, fontWeight: '800', letterSpacing: 1.5, color: THEME.dark },
  fullSlipImg: { width: '100%', height: '90%' },
});
