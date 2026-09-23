// mobile/src/components/FilterModal.js
import React, { useState } from 'react';
import { 
  View, Text, TextInput, Modal, TouchableOpacity, ScrollView, StyleSheet 
} from 'react-native';
import { THEME } from '../theme';

const SORT_OPTIONS = [
  { key: 'newest', label: 'ล่าสุด (Newest)' },
  { key: 'price_asc', label: 'ราคา: ต่ำ - สูง' },
  { key: 'price_desc', label: 'ราคา: สูง - ต่ำ' },
  { key: 'name_asc', label: 'ชื่อสินค้า A - Z' },
];

const SUBCATEGORIES = [
  { key: '', label: 'ทั้งหมด (All)' },
  { key: 'shirts', label: 'เสื้อ (Shirts)' },
  { key: 'bottoms', label: 'กางเกง (Bottoms)' },
];

export default function FilterModal({ 
  visible, onClose, onApply, currentFilters 
}) {
  const [subcategory, setSubcategory] = useState(currentFilters.subcategory || '');
  const [sort, setSort] = useState(currentFilters.sort || 'newest');
  const [minPrice, setMinPrice] = useState(currentFilters.minPrice ? String(currentFilters.minPrice) : '');
  const [maxPrice, setMaxPrice] = useState(currentFilters.maxPrice ? String(currentFilters.maxPrice) : '');

  const handleApply = () => {
    onApply({
      subcategory,
      sort,
      minPrice: minPrice ? Number(minPrice) : null,
      maxPrice: maxPrice ? Number(maxPrice) : null,
    });
    onClose();
  };

  const handleReset = () => {
    setSubcategory('');
    setSort('newest');
    setMinPrice('');
    setMaxPrice('');
    onApply({
      subcategory: '',
      sort: 'newest',
      minPrice: null,
      maxPrice: null,
    });
    onClose();
  };

  return (
    <Modal visible={visible} animationType="slide" transparent={true} onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.card}>
          {/* Header */}
          <View style={styles.header}>
            <Text style={styles.title}>SEARCH & FILTER</Text>
            <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
              <Text style={styles.closeText}>✕</Text>
            </TouchableOpacity>
          </View>

          <ScrollView style={styles.body}>
            {/* 1. Subcategory */}
            <Text style={styles.label}>SUBCATEGORY (ประเภทสินค้า)</Text>
            <View style={styles.optionRow}>
              {SUBCATEGORIES.map(sub => (
                <TouchableOpacity
                  key={sub.key}
                  style={[styles.chip, subcategory === sub.key && styles.chipActive]}
                  onPress={() => setSubcategory(sub.key)}
                >
                  <Text style={[styles.chipText, subcategory === sub.key && styles.chipTextActive]}>
                    {sub.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            {/* 2. Sorting */}
            <Text style={styles.label}>SORT BY (การจัดเรียง)</Text>
            <View style={styles.sortCol}>
              {SORT_OPTIONS.map(s => (
                <TouchableOpacity
                  key={s.key}
                  style={[styles.sortItem, sort === s.key && styles.sortItemActive]}
                  onPress={() => setSort(s.key)}
                >
                  <Text style={[styles.sortText, sort === s.key && styles.sortTextActive]}>
                    {sort === s.key ? '● ' : '○ '} {s.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            {/* 3. Price Range */}
            <Text style={styles.label}>PRICE RANGE (ช่วงราคา บาท)</Text>
            <View style={styles.priceRow}>
              <TextInput
                style={styles.priceInput}
                placeholder="ต่ำสุด (Min)"
                placeholderTextColor={THEME.gray}
                keyboardType="numeric"
                value={minPrice}
                onChangeText={setMinPrice}
              />
              <Text style={styles.priceDash}>-</Text>
              <TextInput
                style={styles.priceInput}
                placeholder="สูงสุด (Max)"
                placeholderTextColor={THEME.gray}
                keyboardType="numeric"
                value={maxPrice}
                onChangeText={setMaxPrice}
              />
            </View>
          </ScrollView>

          {/* Action Buttons */}
          <View style={styles.footer}>
            <TouchableOpacity style={styles.resetBtn} onPress={handleReset}>
              <Text style={styles.resetBtnText}>RESET</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.applyBtn} onPress={handleApply}>
              <Text style={styles.applyBtnText}>APPLY FILTERS</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: { flex: 1, backgroundColor: 'rgba(45,40,40,0.65)', justifyContent: 'flex-end' },
  card: { backgroundColor: THEME.white, borderTopWidth: 1, borderColor: THEME.border, maxHeight: '80%', padding: 20 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, borderBottomWidth: 1, borderBottomColor: THEME.border, paddingBottom: 12 },
  title: { fontSize: 14, fontWeight: '800', letterSpacing: 2, color: THEME.dark },
  closeBtn: { padding: 4 },
  closeText: { fontSize: 18, fontWeight: 'bold', color: THEME.dark },
  body: { paddingBottom: 10 },
  label: { fontSize: 11, fontWeight: '800', letterSpacing: 1.5, color: THEME.dark, marginTop: 14, marginBottom: 8 },
  optionRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { borderWidth: 1, borderColor: THEME.border, paddingVertical: 8, paddingHorizontal: 12, backgroundColor: THEME.light },
  chipActive: { backgroundColor: THEME.dark, borderColor: THEME.dark },
  chipText: { fontSize: 12, color: THEME.dark, fontWeight: '600' },
  chipTextActive: { color: THEME.white, fontWeight: 'bold' },
  sortCol: { gap: 6 },
  sortItem: { paddingVertical: 10, paddingHorizontal: 12, borderWidth: 1, borderColor: '#F0EBE6', backgroundColor: THEME.light },
  sortItemActive: { borderColor: THEME.dark, backgroundColor: '#F8F5F2' },
  sortText: { fontSize: 13, color: THEME.gray },
  sortTextActive: { color: THEME.dark, fontWeight: 'bold' },
  priceRow: { flexDirection: 'row', alignItems: 'center', gap: 10, marginTop: 4 },
  priceInput: { flex: 1, borderWidth: 1, borderColor: THEME.border, padding: 10, fontSize: 13, backgroundColor: THEME.light, color: THEME.dark },
  priceDash: { fontSize: 16, color: THEME.gray, fontWeight: 'bold' },
  footer: { flexDirection: 'row', gap: 10, marginTop: 16, borderTopWidth: 1, borderTopColor: THEME.border, paddingTop: 14 },
  resetBtn: { flex: 1, borderWidth: 1, borderColor: THEME.dark, paddingVertical: 13, alignItems: 'center' },
  resetBtnText: { color: THEME.dark, fontSize: 12, fontWeight: '700', letterSpacing: 1.5 },
  applyBtn: { flex: 2, backgroundColor: THEME.dark, paddingVertical: 13, alignItems: 'center' },
  applyBtnText: { color: THEME.white, fontSize: 12, fontWeight: '700', letterSpacing: 1.5 },
});
