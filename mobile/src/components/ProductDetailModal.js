// mobile/src/components/ProductDetailModal.js - Fully matching product_detail.php
import React, { useState } from 'react';
import { 
  View, Text, Image, Modal, TouchableOpacity, ScrollView, StyleSheet 
} from 'react-native';
import { THEME } from '../theme';

const SIZES = ['S', 'M', 'L', 'XL', '2XL'];

const SIZE_GUIDE = [
  { size: 'S', chest: '38"', length: '27"', shoulder: '17"' },
  { size: 'M', chest: '40"', length: '28"', shoulder: '18"' },
  { size: 'L', chest: '42"', length: '29"', shoulder: '19"' },
  { size: 'XL', chest: '44"', length: '30"', shoulder: '20"' },
  { size: '2XL', chest: '46"', length: '31"', shoulder: '21"' },
];

export default function ProductDetailModal({ 
  visible, product, onClose, onAddToCart, onSelectRelated 
}) {
  const [selectedSize, setSelectedSize] = useState('M');
  const [quantity, setQuantity] = useState(1);
  const [activeTab, setActiveTab] = useState('desc'); // 'desc', 'size_guide', 'shipping'

  if (!product) return null;
  const isOutOfStock = product.stock <= 0;

  const handleAdd = () => {
    onAddToCart({ ...product, selectedSize }, quantity);
    onClose();
  };

  return (
    <Modal visible={visible} animationType="slide" transparent={false} onRequestClose={onClose}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>PRODUCT DETAIL</Text>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <Text style={styles.closeText}>✕</Text>
          </TouchableOpacity>
        </View>

        <ScrollView contentContainerStyle={styles.content}>
          {/* Main Image */}
          {product.image_url ? (
            <Image source={{ uri: product.image_url }} style={styles.image} resizeMode="cover" />
          ) : (
            <View style={[styles.image, styles.noImage]}>
              <Text style={styles.noImageText}>NO IMAGE</Text>
            </View>
          )}

          {/* Product Overview */}
          <View style={styles.body}>
            <Text style={styles.categoryBadge}>
              {product.gender?.toUpperCase()} • {product.category?.toUpperCase()} {product.subcategory ? `• ${product.subcategory.toUpperCase()}` : ''}
            </Text>
            <Text style={styles.title}>{product.name}</Text>
            <Text style={styles.price}>฿{Number(product.price).toLocaleString()}</Text>

            <View style={styles.stockRow}>
              <Text style={[styles.stockText, isOutOfStock && styles.stockEmpty]}>
                STATUS: {isOutOfStock ? 'OUT OF STOCK' : `IN STOCK (${product.stock} ITEMS)`}
              </Text>
            </View>

            {/* Size Selector */}
            <Text style={styles.sectionLabel}>SELECT SIZE</Text>
            <View style={styles.sizeRow}>
              {SIZES.map((sz) => (
                <TouchableOpacity
                  key={sz}
                  style={[styles.sizeBtn, selectedSize === sz && styles.sizeBtnActive]}
                  onPress={() => setSelectedSize(sz)}
                >
                  <Text style={[styles.sizeText, selectedSize === sz && styles.sizeTextActive]}>{sz}</Text>
                </TouchableOpacity>
              ))}
            </View>

            {/* Quantity Stepper */}
            <Text style={styles.sectionLabel}>QUANTITY</Text>
            <View style={styles.qtyRow}>
              <TouchableOpacity
                style={styles.qtyBtn}
                onPress={() => setQuantity(Math.max(1, quantity - 1))}
              >
                <Text style={styles.qtyBtnText}>-</Text>
              </TouchableOpacity>
              <Text style={styles.qtyValue}>{quantity}</Text>
              <TouchableOpacity
                style={styles.qtyBtn}
                onPress={() => setQuantity(Math.min(product.stock, quantity + 1))}
                disabled={quantity >= product.stock}
              >
                <Text style={styles.qtyBtnText}>+</Text>
              </TouchableOpacity>
            </View>

            {/* Information Tabs (Matching Website Accordion) */}
            <View style={styles.tabNav}>
              <TouchableOpacity 
                style={[styles.tabBtn, activeTab === 'desc' && styles.tabBtnActive]}
                onPress={() => setActiveTab('desc')}
              >
                <Text style={[styles.tabText, activeTab === 'desc' && styles.tabTextActive]}>รายละเอียด</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.tabBtn, activeTab === 'size_guide' && styles.tabBtnActive]}
                onPress={() => setActiveTab('size_guide')}
              >
                <Text style={[styles.tabText, activeTab === 'size_guide' && styles.tabTextActive]}>ตารางไซส์</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.tabBtn, activeTab === 'shipping' && styles.tabBtnActive]}
                onPress={() => setActiveTab('shipping')}
              >
                <Text style={[styles.tabText, activeTab === 'shipping' && styles.tabTextActive]}>การจัดส่ง</Text>
              </TouchableOpacity>
            </View>

            {/* Tab Contents */}
            <View style={styles.tabContentBox}>
              {activeTab === 'desc' && (
                <Text style={styles.tabDescription}>
                  {product.description || 'ผ้าคอตตอน 100% สวมใส่สบาย ระบายอากาศได้ดีเยี่ยม ทรงมาตรฐานมินิมอล ใส่ได้ทุกวันทุกโอกาส ตัดเย็บประณีต'}
                </Text>
              )}

              {activeTab === 'size_guide' && (
                <View style={styles.table}>
                  <View style={styles.tableRowHeader}>
                    <Text style={styles.th}>SIZE</Text>
                    <Text style={styles.th}>อก (CHEST)</Text>
                    <Text style={styles.th}>ยาว (LENGTH)</Text>
                    <Text style={styles.th}>ไหล่ (SHOULDER)</Text>
                  </View>
                  {SIZE_GUIDE.map(item => (
                    <View key={item.size} style={styles.tableRow}>
                      <Text style={[styles.td, { fontWeight: 'bold' }]}>{item.size}</Text>
                      <Text style={styles.td}>{item.chest}</Text>
                      <Text style={styles.td}>{item.length}</Text>
                      <Text style={styles.td}>{item.shoulder}</Text>
                    </View>
                  ))}
                </View>
              )}

              {activeTab === 'shipping' && (
                <View style={styles.shippingBox}>
                  <Text style={styles.shipText}>🚚 จัดส่งฟรีทั่วประเทศเมื่อสั่งซื้อครบ 500 บาท</Text>
                  <Text style={styles.shipText}>⏱️ ระยะเวลาจัดส่ง 1 - 3 วันทำการ</Text>
                  <Text style={styles.shipText}>🔄 นโยบายเปลี่ยนหรือคืนสินค้าภายใน 7 วันทำการ</Text>
                </View>
              )}
            </View>

            {/* Related Products Section */}
            {product.related && product.related.length > 0 && (
              <View style={styles.relatedSection}>
                <Text style={styles.relatedTitle}>สินค้าที่เกี่ยวข้อง (RELATED)</Text>
                <View style={styles.relatedGrid}>
                  {product.related.map(rel => (
                    <TouchableOpacity 
                      key={rel.id} 
                      style={styles.relatedCard}
                      onPress={() => {
                        if (onSelectRelated) onSelectRelated(rel.id);
                      }}
                    >
                      {rel.image_url ? (
                        <Image source={{ uri: rel.image_url }} style={styles.relImg} resizeMode="cover" />
                      ) : (
                        <View style={[styles.relImg, { backgroundColor: THEME.secondary }]} />
                      )}
                      <Text style={styles.relName} numberOfLines={1}>{rel.name}</Text>
                      <Text style={styles.relPrice}>฿{Number(rel.price).toLocaleString()}</Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>
            )}
          </View>
        </ScrollView>

        {/* Footer Button */}
        <View style={styles.footer}>
          <TouchableOpacity
            style={[styles.addBtn, isOutOfStock && styles.addBtnDisabled]}
            disabled={isOutOfStock}
            onPress={handleAdd}
          >
            <Text style={styles.addBtnText}>
              {isOutOfStock ? 'OUT OF STOCK' : `+ ADD TO CART (฿${(product.price * quantity).toLocaleString()})`}
            </Text>
          </TouchableOpacity>
        </View>
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
    borderBottomWidth: 1,
    borderBottomColor: THEME.border,
    backgroundColor: THEME.white,
  },
  headerTitle: { fontSize: 13, fontWeight: '800', color: THEME.dark, letterSpacing: 2 },
  closeBtn: { padding: 5 },
  closeText: { fontSize: 20, color: THEME.dark, fontWeight: 'bold' },
  content: { paddingBottom: 40 },
  image: { width: '100%', height: 350, backgroundColor: THEME.secondary },
  noImage: { justifyContent: 'center', alignItems: 'center' },
  noImageText: { color: THEME.gray, fontSize: 14, letterSpacing: 2 },
  body: { padding: 20, backgroundColor: THEME.white, margin: 12, borderWidth: 1, borderColor: THEME.border },
  categoryBadge: { fontSize: 11, fontWeight: '700', color: THEME.primary, letterSpacing: 1.5, marginBottom: 8 },
  title: { fontSize: 20, fontWeight: '700', color: THEME.dark, marginBottom: 8 },
  price: { fontSize: 22, fontWeight: '800', color: THEME.dark, marginBottom: 8 },
  stockRow: { marginBottom: 14 },
  stockText: { fontSize: 12, color: THEME.success, fontWeight: '600', letterSpacing: 1 },
  stockEmpty: { color: THEME.sale },
  sectionLabel: { fontSize: 11, fontWeight: '800', color: THEME.dark, letterSpacing: 1.5, marginTop: 14, marginBottom: 8 },
  sizeRow: { flexDirection: 'row', gap: 8 },
  sizeBtn: {
    borderWidth: 1,
    borderColor: THEME.border,
    paddingVertical: 10,
    paddingHorizontal: 14,
    minWidth: 48,
    alignItems: 'center',
    backgroundColor: THEME.white,
  },
  sizeBtnActive: { borderColor: THEME.dark, backgroundColor: THEME.dark },
  sizeText: { fontSize: 13, fontWeight: '600', color: THEME.dark },
  sizeTextActive: { color: THEME.white, fontWeight: 'bold' },
  qtyRow: { flexDirection: 'row', alignItems: 'center', gap: 12, marginTop: 4 },
  qtyBtn: {
    width: 36,
    height: 36,
    borderWidth: 1,
    borderColor: THEME.border,
    backgroundColor: THEME.light,
    justifyContent: 'center',
    alignItems: 'center',
  },
  qtyBtnText: { fontSize: 18, fontWeight: 'bold', color: THEME.dark },
  qtyValue: { fontSize: 16, fontWeight: 'bold', minWidth: 30, textAlign: 'center', color: THEME.dark },

  // Tabs
  tabNav: { flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: THEME.border, marginTop: 22 },
  tabBtn: { flex: 1, paddingVertical: 10, alignItems: 'center' },
  tabBtnActive: { borderBottomWidth: 2, borderBottomColor: THEME.dark },
  tabText: { fontSize: 12, fontWeight: '700', color: THEME.gray },
  tabTextActive: { color: THEME.dark },
  tabContentBox: { paddingVertical: 14 },
  tabDescription: { fontSize: 13, color: THEME.gray, lineHeight: 22 },

  // Table
  table: { borderWidth: 1, borderColor: THEME.border },
  tableRowHeader: { flexDirection: 'row', backgroundColor: THEME.light, paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: THEME.border },
  tableRow: { flexDirection: 'row', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#F5F0EB' },
  th: { flex: 1, textAlign: 'center', fontSize: 10, fontWeight: '800', color: THEME.dark, letterSpacing: 0.5 },
  td: { flex: 1, textAlign: 'center', fontSize: 11, color: THEME.gray },

  shippingBox: { gap: 8 },
  shipText: { fontSize: 13, color: THEME.dark, lineHeight: 20 },

  // Related Products
  relatedSection: { marginTop: 20, borderTopWidth: 1, borderTopColor: THEME.border, paddingTop: 16 },
  relatedTitle: { fontSize: 12, fontWeight: '800', letterSpacing: 1.5, color: THEME.dark, marginBottom: 12 },
  relatedGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  relatedCard: { width: '48%', borderWidth: 1, borderColor: THEME.border, padding: 8, backgroundColor: THEME.light },
  relImg: { width: '100%', height: 110, marginBottom: 6 },
  relName: { fontSize: 12, fontWeight: '600', color: THEME.dark, marginBottom: 2 },
  relPrice: { fontSize: 12, fontWeight: '800', color: THEME.dark },

  footer: {
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: THEME.border,
    backgroundColor: THEME.white,
  },
  addBtn: {
    backgroundColor: THEME.dark,
    paddingVertical: 15,
    alignItems: 'center',
  },
  addBtnDisabled: { backgroundColor: '#CCCCCC' },
  addBtnText: { color: THEME.white, fontSize: 14, fontWeight: 'bold', letterSpacing: 1.5 },
});
