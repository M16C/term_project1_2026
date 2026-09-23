// mobile/src/components/CartModal.js
import React, { useState } from 'react';
import { 
  View, Text, Image, Modal, TouchableOpacity, ScrollView, 
  TextInput, Alert, ActivityIndicator, StyleSheet 
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { QR_CODE_URL } from '../config';
import { apiCheckout } from '../services/api';
import { THEME } from '../theme';

export default function CartModal({ 
  visible, cart, onClose, onUpdateQty, onRemoveItem, onClearCart, user, onOrderSuccess 
}) {
  const [address, setAddress] = useState('123/45 ถนนสุขุมวิท แขวงคลองเตย กรุงเทพฯ 10110');
  const [slipImage, setSlipImage] = useState(null);
  const [loading, setLoading] = useState(false);

  const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  const handlePickSlip = async () => {
    try {
      const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permission.granted) {
        Alert.alert('ต้องการสิทธิ์การเข้าถึง', 'กรุณาอนุญาตการเข้าถึงรูปภาพเพื่อเลือกรูปสลิป');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        quality: 0.7,
        base64: true,
      });

      if (!result.canceled && result.assets && result.assets.length > 0) {
        const asset = result.assets[0];
        setSlipImage({
          uri: asset.uri,
          base64: asset.base64,
          name: asset.fileName || 'slip_' + Date.now() + '.jpg',
          type: asset.mimeType || 'image/jpeg',
        });
      }
    } catch (e) {
      Alert.alert('ข้อผิดพลาด', 'ไม่สามารถเปิดอัลบั้มรูปภาพได้ (' + e.message + ')');
    }
  };

  const handleCheckout = async () => {
    if (!user) {
      Alert.alert('แจ้งเตือน', 'กรุณาเข้าสู่ระบบก่อนทำการสั่งซื้อ');
      return;
    }
    if (cart.length === 0) {
      Alert.alert('แจ้งเตือน', 'ไม่มีสินค้าในตะกร้า');
      return;
    }
    if (!slipImage) {
      Alert.alert('จำเป็นต้องแนบสลิป', 'กรุณาสแกน QR Code และแนบภาพสลิปหลักฐานการโอนเงินก่อนสั่งซื้อ');
      return;
    }

    setLoading(true);
    const checkoutItems = cart.map(item => ({
      id: item.id,
      quantity: item.quantity,
      price: item.price,
    }));

    const res = await apiCheckout({
      userId: user.id,
      items: checkoutItems,
      slipBase64: slipImage.base64,
      slipUri: slipImage.uri,
      slipName: slipImage.name,
      slipType: slipImage.type,
    });

    setLoading(false);

    if (res.success) {
      Alert.alert(
        'สั่งซื้อสำเร็จ 🎉',
        `คำสั่งซื้อ #${res.order_id} ถูกส่งเรียบร้อยแล้ว สถานะ: Pending (รอแอดมินตรวจสอบสลิป)`,
        [
          {
            text: 'ตกลง',
            onPress: () => {
              setSlipImage(null);
              onClearCart();
              onClose();
              if (onOrderSuccess) onOrderSuccess();
            }
          }
        ]
      );
    } else {
      Alert.alert('การสั่งซื้อไม่สำเร็จ', res.message || 'เกิดข้อผิดพลาดในการสั่งซื้อ');
    }
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>SHOPPING BAG ({cart.length})</Text>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <Text style={styles.closeText}>✕</Text>
          </TouchableOpacity>
        </View>

        <ScrollView contentContainerStyle={styles.content}>
          {cart.length === 0 ? (
            <View style={styles.emptyCart}>
              <Text style={styles.emptyText}>ไม่มีสินค้าในตะกร้าของคุณ</Text>
            </View>
          ) : (
            <>
              {/* Cart Items */}
              <View style={styles.itemsSection}>
                {cart.map((item, idx) => (
                  <View key={`${item.id}-${item.selectedSize || 'def'}-${idx}`} style={styles.itemRow}>
                    {item.image_url ? (
                      <Image source={{ uri: item.image_url }} style={styles.itemImg} />
                    ) : (
                      <View style={[styles.itemImg, styles.noImg]} />
                    )}
                    <View style={styles.itemInfo}>
                      <Text style={styles.itemName} numberOfLines={1}>{item.name}</Text>
                      {item.selectedSize && <Text style={styles.itemSize}>SIZE: {item.selectedSize}</Text>}
                      <Text style={styles.itemPrice}>฿{Number(item.price).toLocaleString()}</Text>
                    </View>
                    <View style={styles.itemQtyRow}>
                      <TouchableOpacity 
                        style={styles.qtyBtn} 
                        onPress={() => onUpdateQty(item.id, item.selectedSize, item.quantity - 1)}
                      >
                        <Text style={styles.qtyBtnText}>-</Text>
                      </TouchableOpacity>
                      <Text style={styles.qtyText}>{item.quantity}</Text>
                      <TouchableOpacity 
                        style={styles.qtyBtn} 
                        onPress={() => onUpdateQty(item.id, item.selectedSize, item.quantity + 1)}
                      >
                        <Text style={styles.qtyBtnText}>+</Text>
                      </TouchableOpacity>
                    </View>
                    <TouchableOpacity 
                      onPress={() => onRemoveItem(item.id, item.selectedSize)} 
                      style={styles.removeBtn}
                    >
                      <Text style={styles.removeBtnText}>✕</Text>
                    </TouchableOpacity>
                  </View>
                ))}
              </View>

              {/* Shipping Address */}
              <View style={styles.sectionCard}>
                <Text style={styles.sectionTitle}>SHIPPING ADDRESS</Text>
                <TextInput
                  style={styles.addressInput}
                  value={address}
                  onChangeText={setAddress}
                  placeholder="กรอกที่อยู่จัดส่งสินค้า"
                  multiline
                />
              </View>

              {/* PromptPay QR Code Section */}
              <View style={styles.sectionCard}>
                <Text style={styles.sectionTitle}>PAYMENT: PROMPTPAY QR</Text>
                <Text style={styles.sectionDesc}>
                  สแกนเพื่อชำระเงิน ยอดสุทธิ <Text style={styles.highlightPrice}>฿{totalAmount.toLocaleString()}</Text>
                </Text>

                <View style={styles.qrContainer}>
                  <Image source={{ uri: QR_CODE_URL }} style={styles.qrImage} resizeMode="contain" />
                  <Text style={styles.qrAccount}>CLOTHING STORE PROMPTPAY</Text>
                </View>

                {/* Slip Upload */}
                <Text style={styles.slipLabel}>หลักฐานการโอนเงิน (PAYMENT SLIP):</Text>
                {slipImage ? (
                  <View style={styles.slipPreviewBox}>
                    <Image source={{ uri: slipImage.uri }} style={styles.slipImg} resizeMode="contain" />
                    <TouchableOpacity style={styles.changeSlipBtn} onPress={handlePickSlip}>
                      <Text style={styles.changeSlipText}>เปลี่ยนรูปสลิป</Text>
                    </TouchableOpacity>
                  </View>
                ) : (
                  <TouchableOpacity style={styles.pickSlipBtn} onPress={handlePickSlip}>
                    <Text style={styles.pickSlipIcon}>📷</Text>
                    <Text style={styles.pickSlipText}>+ เลือกรูปสลิปจากอัลบั้ม</Text>
                  </TouchableOpacity>
                )}
              </View>

              {/* Price Summary */}
              <View style={styles.summaryCard}>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>SUBTOTAL</Text>
                  <Text style={styles.summaryVal}>฿{totalAmount.toLocaleString()}</Text>
                </View>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>SHIPPING</Text>
                  <Text style={[styles.summaryVal, { color: THEME.success }]}>FREE</Text>
                </View>
                <View style={[styles.summaryRow, styles.summaryTotalRow]}>
                  <Text style={styles.totalLabel}>TOTAL</Text>
                  <Text style={styles.totalVal}>฿{totalAmount.toLocaleString()}</Text>
                </View>
              </View>
            </>
          )}
        </ScrollView>

        {cart.length > 0 && (
          <View style={styles.footer}>
            <TouchableOpacity 
              style={[styles.checkoutBtn, loading && styles.btnDisabled]} 
              disabled={loading}
              onPress={handleCheckout}
            >
              {loading ? (
                <ActivityIndicator color={THEME.white} />
              ) : (
                <Text style={styles.checkoutBtnText}>CONFIRM ORDER & ATTACH SLIP</Text>
              )}
            </TouchableOpacity>
          </View>
        )}
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
  content: { padding: 16, paddingBottom: 40 },
  emptyCart: { padding: 60, alignItems: 'center' },
  emptyText: { color: THEME.gray, fontSize: 14, letterSpacing: 1 },
  itemsSection: { backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 12, marginBottom: 16 },
  itemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#F5F0EB' },
  itemImg: { width: 55, height: 55, backgroundColor: THEME.secondary },
  noImg: { backgroundColor: '#EFEBE7' },
  itemInfo: { flex: 1, marginLeft: 12 },
  itemName: { fontSize: 14, fontWeight: '600', color: THEME.dark },
  itemSize: { fontSize: 11, color: THEME.gray, marginTop: 2, letterSpacing: 1 },
  itemPrice: { fontSize: 14, color: THEME.dark, fontWeight: 'bold', marginTop: 4 },
  itemQtyRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  qtyBtn: { width: 28, height: 28, backgroundColor: THEME.light, borderWidth: 1, borderColor: THEME.border, justifyContent: 'center', alignItems: 'center' },
  qtyBtnText: { fontSize: 14, fontWeight: 'bold', color: THEME.dark },
  qtyText: { fontSize: 13, fontWeight: 'bold', minWidth: 18, textAlign: 'center', color: THEME.dark },
  removeBtn: { padding: 8, marginLeft: 6 },
  removeBtnText: { fontSize: 16, color: THEME.gray },
  sectionCard: { backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 16, marginBottom: 16 },
  sectionTitle: { fontSize: 13, fontWeight: '700', color: THEME.dark, letterSpacing: 1.5, marginBottom: 8 },
  sectionDesc: { fontSize: 12, color: THEME.gray, marginBottom: 12, lineHeight: 18 },
  highlightPrice: { color: THEME.dark, fontWeight: 'bold' },
  addressInput: { borderWidth: 1, borderColor: THEME.border, padding: 10, fontSize: 13, minHeight: 60, textAlignVertical: 'top', backgroundColor: THEME.light },
  qrContainer: { alignItems: 'center', backgroundColor: THEME.light, padding: 16, borderWidth: 1, borderColor: THEME.border, marginBottom: 16 },
  qrImage: { width: 200, height: 200 },
  qrAccount: { marginTop: 10, fontSize: 11, color: THEME.dark, fontWeight: '700', letterSpacing: 1.5 },
  slipLabel: { fontSize: 12, fontWeight: '700', color: THEME.dark, letterSpacing: 1, marginBottom: 8 },
  pickSlipBtn: {
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: THEME.primary,
    padding: 16,
    alignItems: 'center',
    backgroundColor: '#FDFCFB',
  },
  pickSlipIcon: { fontSize: 24, marginBottom: 4 },
  pickSlipText: { color: THEME.dark, fontWeight: '700', fontSize: 13, letterSpacing: 1 },
  slipPreviewBox: { alignItems: 'center', backgroundColor: THEME.light, borderWidth: 1, borderColor: THEME.border, padding: 10 },
  slipImg: { width: '100%', height: 220 },
  changeSlipBtn: { marginTop: 8, padding: 6 },
  changeSlipText: { color: THEME.primary, fontSize: 12, fontWeight: '700', letterSpacing: 1 },
  summaryCard: { backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 16, marginBottom: 20 },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  summaryLabel: { fontSize: 13, color: THEME.gray, letterSpacing: 1 },
  summaryVal: { fontSize: 13, fontWeight: '600', color: THEME.dark },
  summaryTotalRow: { borderTopWidth: 1, borderTopColor: THEME.border, paddingTop: 10, marginTop: 4 },
  totalLabel: { fontSize: 15, fontWeight: 'bold', color: THEME.dark, letterSpacing: 1.5 },
  totalVal: { fontSize: 18, fontWeight: '800', color: THEME.dark },
  footer: { padding: 16, backgroundColor: THEME.white, borderTopWidth: 1, borderTopColor: THEME.border },
  checkoutBtn: { backgroundColor: THEME.dark, paddingVertical: 15, alignItems: 'center' },
  btnDisabled: { opacity: 0.6 },
  checkoutBtnText: { color: THEME.white, fontSize: 14, fontWeight: 'bold', letterSpacing: 1.5 },
});
