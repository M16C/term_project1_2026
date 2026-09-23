// mobile/src/components/ProductCard.js
import React from 'react';
import { View, Text, Image, TouchableOpacity, StyleSheet } from 'react-native';
import { THEME } from '../theme';

export default function ProductCard({ product, onPress, onAddToCart }) {
  const isOutOfStock = product.stock <= 0;

  return (
    <TouchableOpacity style={styles.card} activeOpacity={0.9} onPress={onPress}>
      {/* 1. Image Container */}
      <View style={styles.imageContainer}>
        {product.image_url ? (
          <Image source={{ uri: product.image_url }} style={styles.image} resizeMode="cover" />
        ) : (
          <View style={[styles.image, styles.noImage]}>
            <Text style={styles.noImageText}>NO IMAGE</Text>
          </View>
        )}
        {product.is_clearance === 1 && (
          <View style={styles.badgeClearance}>
            <Text style={styles.badgeText}>CLEARANCE</Text>
          </View>
        )}
      </View>

      {/* 2. Product Details */}
      <View style={styles.info}>
        <Text style={styles.category}>
          {product.gender?.toUpperCase()} • {product.category?.toUpperCase()}
        </Text>
        <Text style={styles.name} numberOfLines={2}>{product.name}</Text>

        <View style={styles.footerRow}>
          <Text style={styles.price}>฿{Number(product.price).toLocaleString()}</Text>
          <Text style={[styles.stock, isOutOfStock && styles.stockEmpty]}>
            {isOutOfStock ? 'OUT OF STOCK' : `คงเหลือ ${product.stock}`}
          </Text>
        </View>

        {/* 3. Add to Cart Button (Matching .btn-brand-dark) */}
        <TouchableOpacity 
          style={[styles.addBtn, isOutOfStock && styles.addBtnDisabled]} 
          disabled={isOutOfStock}
          onPress={() => onAddToCart(product)}
        >
          <Text style={styles.addBtnText}>
            {isOutOfStock ? 'สินค้าหมด' : '+ ใส่ตะกร้า'}
          </Text>
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: THEME.white,
    borderWidth: 1,
    borderColor: THEME.border,
    borderRadius: 0, // Sharp minimalist wireframe style from website
    marginBottom: 20,
    overflow: 'hidden',
  },
  imageContainer: {
    position: 'relative',
    height: 220,
    backgroundColor: THEME.secondary,
  },
  image: {
    width: '100%',
    height: '100%',
  },
  noImage: {
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#EFEBE7',
  },
  noImageText: {
    color: THEME.gray,
    fontSize: 12,
    letterSpacing: 1.5,
    fontWeight: '600',
  },
  badgeClearance: {
    position: 'absolute',
    top: 10,
    right: 10,
    backgroundColor: THEME.sale,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  badgeText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 1,
  },
  info: {
    padding: 14,
    backgroundColor: THEME.white,
  },
  category: {
    fontSize: 11,
    color: THEME.gray,
    letterSpacing: 1.5,
    fontWeight: '600',
    marginBottom: 6,
  },
  name: {
    fontSize: 15,
    fontWeight: '600',
    color: THEME.dark,
    marginBottom: 10,
    lineHeight: 20,
    minHeight: 40,
  },
  footerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'baseline',
    marginBottom: 12,
    paddingTop: 6,
    borderTopWidth: 1,
    borderTopColor: '#F5F0EB',
  },
  price: {
    fontSize: 17,
    fontWeight: '800',
    color: THEME.dark,
    letterSpacing: 0.5,
  },
  stock: {
    fontSize: 12,
    color: THEME.gray,
    fontWeight: '500',
  },
  stockEmpty: {
    color: THEME.sale,
    fontWeight: 'bold',
  },
  addBtn: {
    backgroundColor: THEME.dark,
    paddingVertical: 11,
    borderRadius: 0,
    alignItems: 'center',
  },
  addBtnDisabled: {
    backgroundColor: '#CCCCCC',
  },
  addBtnText: {
    color: THEME.white,
    fontSize: 13,
    fontWeight: '700',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
});
