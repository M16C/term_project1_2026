// mobile/src/components/AdminStockModal.js - Matching admin/manage_stock.php
import React, { useState, useEffect } from 'react';
import { 
  View, Text, Image, TextInput, Modal, TouchableOpacity, ScrollView, 
  ActivityIndicator, Alert, StyleSheet 
} from 'react-native';
import { apiGetProducts, apiSaveStock, apiDeleteProduct, apiGetAdminStockStats } from '../services/api';
import { THEME } from '../theme';

export default function AdminStockModal({ visible, onClose, onStockChanged }) {
  const [products, setProducts] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(false);

  // Add Product Form State
  const [showAddForm, setShowAddForm] = useState(false);
  const [name, setName] = useState('');
  const [price, setPrice] = useState('');
  const [stock, setStock] = useState('');
  const [gender, setGender] = useState('men');
  const [category, setCategory] = useState('men');
  const [subcategory, setSubcategory] = useState('shirts');
  const [description, setDescription] = useState('');
  const [isClearance, setIsClearance] = useState(false);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (visible) {
      loadStockData();
    }
  }, [visible]);

  const loadStockData = async () => {
    setLoading(true);
    const [pRes, sRes] = await Promise.all([
      apiGetProducts({ sort: 'newest' }),
      apiGetAdminStockStats()
    ]);
    setLoading(false);

    if (pRes.success) setProducts(pRes.products || []);
    if (sRes.success) setStats(sRes.stats || null);
  };

  const handleQuickStockChange = async (prod, delta) => {
    const newStock = Math.max(0, prod.stock + delta);
    const res = await apiSaveStock('update', {
      id: prod.id,
      name: prod.name,
      price: prod.price,
      stock: newStock,
      gender: prod.gender,
      category: prod.category,
      subcategory: prod.subcategory || '',
      description: prod.description || '',
      is_clearance: prod.is_clearance ? 1 : 0
    });

    if (res.success) {
      setProducts(prev => prev.map(p => p.id === prod.id ? { ...p, stock: newStock } : p));
      if (onStockChanged) onStockChanged();
    } else {
      Alert.alert('ผิดพลาด', res.message);
    }
  };

  const handleDelete = (prod) => {
    Alert.alert(
      'ยืนยันการลบสินค้า',
      `คุณต้องการลบสินค้า "${prod.name}" (ID #${prod.id}) ออกจากระบบหรือไม่?`,
      [
        { text: 'ยกเลิก', style: 'cancel' },
        { 
          text: 'ลบสินค้า (Delete)', 
          style: 'destructive',
          onPress: async () => {
            const res = await apiDeleteProduct(prod.id);
            if (res.success) {
              Alert.alert('สำเร็จ 🎉', res.message);
              loadStockData();
              if (onStockChanged) onStockChanged();
            } else {
              Alert.alert('ผิดพลาด', res.message);
            }
          }
        }
      ]
    );
  };

  const handleAddProduct = async () => {
    if (!name.trim() || !price || !stock) {
      Alert.alert('แจ้งเตือน', 'กรุณากรอกชื่อสินค้า ราคา และจำนวนสต็อก');
      return;
    }

    setSaving(true);
    const res = await apiSaveStock('add', {
      name: name.trim(),
      price: Number(price),
      stock: Number(stock),
      gender,
      category,
      subcategory,
      description: description.trim(),
      is_clearance: isClearance ? 1 : 0
    });
    setSaving(false);

    if (res.success) {
      Alert.alert('สำเร็จ 🎉', 'เพิ่มสินค้าใหม่เรียบร้อยแล้ว');
      setName('');
      setPrice('');
      setStock('');
      setDescription('');
      setShowAddForm(false);
      loadStockData();
      if (onStockChanged) onStockChanged();
    } else {
      Alert.alert('ผิดพลาด', res.message);
    }
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <View>
            <Text style={styles.headerTitle}>STOCK MANAGEMENT</Text>
            <Text style={styles.headerSubtitle}>ADMIN INVENTORY CONTROL</Text>
          </View>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <Text style={styles.closeText}>✕</Text>
          </TouchableOpacity>
        </View>

        {/* Stats Summary */}
        {stats && (
          <View style={styles.statsRow}>
            <View style={styles.statCard}>
              <Text style={styles.statVal}>{stats.total_products}</Text>
              <Text style={styles.statLabel}>PRODUCTS</Text>
            </View>
            <View style={[styles.statCard, { borderColor: THEME.sale }]}>
              <Text style={[styles.statVal, { color: THEME.sale }]}>{stats.low_stock || 0}</Text>
              <Text style={styles.statLabel}>LOW STOCK</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statVal}>{stats.total_units || 0}</Text>
              <Text style={styles.statLabel}>TOTAL UNITS</Text>
            </View>
          </View>
        )}

        {/* Add Product Button */}
        <View style={styles.actionToolbar}>
          <TouchableOpacity 
            style={styles.addBtn}
            onPress={() => setShowAddForm(true)}
          >
            <Text style={styles.addBtnText}>+ ADD NEW PRODUCT</Text>
          </TouchableOpacity>
        </View>

        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color={THEME.dark} />
            <Text style={styles.loadingText}>LOADING INVENTORY...</Text>
          </View>
        ) : (
          <ScrollView contentContainerStyle={styles.list}>
            {products.map(p => {
              const isLow = p.stock <= 5 && p.stock > 0;
              const isOut = p.stock <= 0;

              return (
                <View key={p.id} style={styles.productCard}>
                  <View style={styles.cardHeader}>
                    {p.image_url ? (
                      <Image source={{ uri: p.image_url }} style={styles.thumb} resizeMode="cover" />
                    ) : (
                      <View style={[styles.thumb, { backgroundColor: THEME.secondary }]} />
                    )}
                    <View style={styles.info}>
                      <Text style={styles.category}>{p.gender?.toUpperCase()} • {p.category?.toUpperCase()}</Text>
                      <Text style={styles.name} numberOfLines={1}>{p.name}</Text>
                      <Text style={styles.price}>฿{Number(p.price).toLocaleString()}</Text>
                    </View>
                    <TouchableOpacity 
                      style={styles.deleteBtn}
                      onPress={() => handleDelete(p)}
                    >
                      <Text style={styles.deleteBtnText}>🗑</Text>
                    </TouchableOpacity>
                  </View>

                  {/* Stock Adjuster */}
                  <View style={styles.stockControlRow}>
                    <View style={styles.stockStatus}>
                      <Text style={[styles.stockBadge, isOut && styles.badgeOut, isLow && styles.badgeLow]}>
                        {isOut ? 'OUT OF STOCK' : isLow ? `LOW STOCK: ${p.stock}` : `IN STOCK: ${p.stock}`}
                      </Text>
                    </View>

                    <View style={styles.stepper}>
                      <TouchableOpacity 
                        style={styles.stepBtn}
                        onPress={() => handleQuickStockChange(p, -1)}
                      >
                        <Text style={styles.stepText}>-</Text>
                      </TouchableOpacity>
                      <Text style={styles.stockVal}>{p.stock}</Text>
                      <TouchableOpacity 
                        style={styles.stepBtn}
                        onPress={() => handleQuickStockChange(p, 1)}
                      >
                        <Text style={styles.stepText}>+</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              );
            })}
          </ScrollView>
        )}

        {/* Modal เพิ่มสินค้าใหม่ (Add Product Modal) */}
        <Modal visible={showAddForm} animationType="slide" transparent={true}>
          <View style={styles.overlay}>
            <View style={styles.modalCard}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>ADD NEW PRODUCT</Text>
                <TouchableOpacity onPress={() => setShowAddForm(false)}>
                  <Text style={styles.closeText}>✕</Text>
                </TouchableOpacity>
              </View>

              <ScrollView style={styles.formScroll}>
                <Text style={styles.label}>PRODUCT NAME</Text>
                <TextInput
                  style={styles.input}
                  value={name}
                  onChangeText={setName}
                  placeholder="เช่น Minimalist Linen Shirt"
                />

                <View style={styles.row}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.label}>PRICE (฿)</Text>
                    <TextInput
                      style={styles.input}
                      value={price}
                      onChangeText={setPrice}
                      keyboardType="numeric"
                      placeholder="เช่น 590"
                    />
                  </View>
                  <View style={{ flex: 1, marginLeft: 10 }}>
                    <Text style={styles.label}>STOCK</Text>
                    <TextInput
                      style={styles.input}
                      value={stock}
                      onChangeText={setStock}
                      keyboardType="numeric"
                      placeholder="เช่น 20"
                    />
                  </View>
                </View>

                <Text style={styles.label}>GENDER</Text>
                <View style={styles.genderRow}>
                  {['men', 'women', 'unisex'].map(g => (
                    <TouchableOpacity
                      key={g}
                      style={[styles.chip, gender === g && styles.chipActive]}
                      onPress={() => setGender(g)}
                    >
                      <Text style={[styles.chipText, gender === g && styles.chipTextActive]}>{g.toUpperCase()}</Text>
                    </TouchableOpacity>
                  ))}
                </View>

                <Text style={styles.label}>SUBCATEGORY</Text>
                <View style={styles.genderRow}>
                  {['shirts', 'bottoms'].map(sub => (
                    <TouchableOpacity
                      key={sub}
                      style={[styles.chip, subcategory === sub && styles.chipActive]}
                      onPress={() => setSubcategory(sub)}
                    >
                      <Text style={[styles.chipText, subcategory === sub && styles.chipTextActive]}>{sub.toUpperCase()}</Text>
                    </TouchableOpacity>
                  ))}
                </View>

                <Text style={styles.label}>DESCRIPTION</Text>
                <TextInput
                  style={[styles.input, { minHeight: 60 }]}
                  value={description}
                  onChangeText={setDescription}
                  multiline
                  placeholder="รายละเอียดเนื้อผ้าหรือขนาด"
                />

                <TouchableOpacity 
                  style={[styles.submitAddBtn, saving && styles.btnDisabled]}
                  disabled={saving}
                  onPress={handleAddProduct}
                >
                  {saving ? <ActivityIndicator color={THEME.white} /> : (
                    <Text style={styles.submitAddText}>SAVE NEW PRODUCT</Text>
                  )}
                </TouchableOpacity>
              </ScrollView>
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
  headerTitle: { fontSize: 14, fontWeight: '800', letterSpacing: 2, color: THEME.dark },
  headerSubtitle: { fontSize: 11, color: THEME.gray, marginTop: 2, letterSpacing: 1 },
  closeBtn: { padding: 5 },
  closeText: { fontSize: 20, color: THEME.dark, fontWeight: 'bold' },
  statsRow: { flexDirection: 'row', padding: 14, gap: 10, backgroundColor: THEME.white, borderBottomWidth: 1, borderBottomColor: THEME.border },
  statCard: { flex: 1, backgroundColor: THEME.light, padding: 10, borderWidth: 1, borderColor: THEME.border },
  statVal: { fontSize: 16, fontWeight: '800', color: THEME.dark },
  statLabel: { fontSize: 10, color: THEME.gray, fontWeight: '700', letterSpacing: 1, marginTop: 2 },
  actionToolbar: { padding: 14, backgroundColor: THEME.white, borderBottomWidth: 1, borderBottomColor: THEME.border },
  addBtn: { backgroundColor: THEME.dark, paddingVertical: 12, alignItems: 'center' },
  addBtnText: { color: THEME.white, fontSize: 12, fontWeight: '800', letterSpacing: 1.5 },
  loadingBox: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 10, color: THEME.gray },
  list: { padding: 16, paddingBottom: 40 },
  productCard: { backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 14, marginBottom: 12 },
  cardHeader: { flexDirection: 'row', alignItems: 'center' },
  thumb: { width: 50, height: 50, borderWidth: 1, borderColor: THEME.border },
  info: { flex: 1, marginLeft: 12 },
  category: { fontSize: 10, color: THEME.gray, fontWeight: '700', letterSpacing: 1 },
  name: { fontSize: 14, fontWeight: '600', color: THEME.dark, marginVertical: 2 },
  price: { fontSize: 14, fontWeight: 'bold', color: THEME.dark },
  deleteBtn: { padding: 8 },
  deleteBtnText: { fontSize: 18 },
  stockControlRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', borderTopWidth: 1, borderTopColor: '#F5F0EB', paddingTop: 10, marginTop: 10 },
  stockStatus: { flex: 1 },
  stockBadge: { fontSize: 11, fontWeight: '700', letterSpacing: 1, color: THEME.dark },
  badgeOut: { color: THEME.sale },
  badgeLow: { color: THEME.warning },
  stepper: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  stepBtn: { width: 30, height: 30, borderWidth: 1, borderColor: THEME.border, backgroundColor: THEME.light, justifyContent: 'center', alignItems: 'center' },
  stepText: { fontSize: 16, fontWeight: 'bold', color: THEME.dark },
  stockVal: { fontSize: 14, fontWeight: 'bold', minWidth: 24, textAlign: 'center', color: THEME.dark },

  // Add Product Modal
  overlay: { flex: 1, backgroundColor: 'rgba(45,40,40,0.65)', justifyContent: 'center', alignItems: 'center', padding: 20 },
  modalCard: { width: '100%', maxHeight: '90%', backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 20 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, borderBottomWidth: 1, borderBottomColor: THEME.border, paddingBottom: 10 },
  modalTitle: { fontSize: 14, fontWeight: 'bold', letterSpacing: 2, color: THEME.dark },
  formScroll: { width: '100%' },
  label: { fontSize: 11, fontWeight: '700', letterSpacing: 1, color: THEME.dark, marginTop: 10, marginBottom: 4 },
  input: { borderWidth: 1, borderColor: THEME.border, padding: 10, fontSize: 13, backgroundColor: THEME.light, color: THEME.dark },
  row: { flexDirection: 'row' },
  genderRow: { flexDirection: 'row', gap: 8, marginTop: 4 },
  chip: { borderWidth: 1, borderColor: THEME.border, paddingVertical: 8, paddingHorizontal: 12, backgroundColor: THEME.light },
  chipActive: { backgroundColor: THEME.dark, borderColor: THEME.dark },
  chipText: { fontSize: 11, color: THEME.dark, fontWeight: '600' },
  chipTextActive: { color: THEME.white, fontWeight: 'bold' },
  submitAddBtn: { backgroundColor: THEME.dark, paddingVertical: 14, alignItems: 'center', marginTop: 20, marginBottom: 10 },
  submitAddText: { color: THEME.white, fontSize: 13, fontWeight: 'bold', letterSpacing: 1.5 },
  btnDisabled: { opacity: 0.6 },
});
