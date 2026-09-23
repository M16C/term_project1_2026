// mobile/App.js - Complete Functional Alignment with Clothing Store Web App
import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, Text, View, TextInput, TouchableOpacity, 
  FlatList, RefreshControl, ActivityIndicator, ImageBackground,
  StatusBar, Alert 
} from 'react-native';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';

import { THEME } from './src/theme';
import { apiGetProducts, apiGetProductDetail } from './src/services/api';
import ProductCard from './src/components/ProductCard';
import ProductDetailModal from './src/components/ProductDetailModal';
import CartModal from './src/components/CartModal';
import OrdersModal from './src/components/OrdersModal';
import OrderDetailModal from './src/components/OrderDetailModal';
import AdminOrdersModal from './src/components/AdminOrdersModal';
import AdminStockModal from './src/components/AdminStockModal';
import AuthModal from './src/components/AuthModal';
import FilterModal from './src/components/FilterModal';
import ProfileModal from './src/components/ProfileModal';

const CATEGORIES = [
  { key: '', label: 'ALL' },
  { key: 'men', label: 'MEN' },
  { key: 'women', label: 'WOMEN' },
  { key: 'clearance', label: 'CLEARANCE' },
];

export default function App() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');

  // Advanced Filters (Matching products.php)
  const [filters, setFilters] = useState({
    subcategory: '',
    sort: 'newest',
    minPrice: null,
    maxPrice: null,
  });

  // User State
  const [user, setUser] = useState(null);

  // Cart State
  const [cart, setCart] = useState([]);

  // Modals
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [selectedOrderId, setSelectedOrderId] = useState(null);
  const [showCart, setShowCart] = useState(false);
  const [showOrders, setShowOrders] = useState(false);
  const [showAdminOrders, setShowAdminOrders] = useState(false);
  const [showAdminStock, setShowAdminStock] = useState(false);
  const [showAuth, setShowAuth] = useState(false);
  const [showFilter, setShowFilter] = useState(false);
  const [showProfile, setShowProfile] = useState(false);

  useEffect(() => {
    loadProducts();
  }, [category, filters]);

  const loadProducts = async () => {
    setLoading(true);
    const params = {};
    if (category) params.category = category;
    if (filters.subcategory) params.subcategory = filters.subcategory;
    if (filters.sort) params.sort = filters.sort;
    if (filters.minPrice !== null) params.min_price = filters.minPrice;
    if (filters.maxPrice !== null) params.max_price = filters.maxPrice;
    if (search.trim()) params.search = search.trim();

    const res = await apiGetProducts(params);
    setLoading(false);
    setRefreshing(false);
    if (res.success) {
      setProducts(res.products || []);
    } else {
      Alert.alert('ข้อผิดพลาดการเชื่อมต่อ', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ PHP ได้ กรุณาตรวจสอบว่า XAMPP กำลังเปิดอยู่ และอยู่ใน Wi-Fi วงเดียวกัน');
    }
  };

  const handleRefresh = () => {
    setRefreshing(true);
    loadProducts();
  };

  const handleSearchSubmit = () => {
    loadProducts();
  };

  const handleOpenDetail = async (prodId) => {
    const res = await apiGetProductDetail(prodId);
    if (res.success) {
      setSelectedProduct(res.product);
    } else {
      const fallback = products.find(p => p.id === prodId);
      if (fallback) setSelectedProduct(fallback);
    }
  };

  // Cart Management
  const handleAddToCart = (product, qty = 1) => {
    const size = product.selectedSize || 'M';
    setCart(prev => {
      const existingIdx = prev.findIndex(item => item.id === product.id && item.selectedSize === size);
      if (existingIdx > -1) {
        const updated = [...prev];
        updated[existingIdx].quantity += qty;
        return updated;
      } else {
        return [...prev, { ...product, quantity: qty, selectedSize: size }];
      }
    });
    Alert.alert('เพิ่มลงตะกร้าแล้ว', `${product.name} (ขนาด ${size}) x ${qty} ชิ้น`);
  };

  const handleUpdateCartQty = (id, size, newQty) => {
    if (newQty <= 0) {
      handleRemoveCartItem(id, size);
      return;
    }
    setCart(prev => prev.map(item => {
      if (item.id === id && item.selectedSize === size) {
        return { ...item, quantity: newQty };
      }
      return item;
    }));
  };

  const handleRemoveCartItem = (id, size) => {
    setCart(prev => prev.filter(item => !(item.id === id && item.selectedSize === size)));
  };

  const handleClearCart = () => {
    setCart([]);
  };

  const cartTotalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
  const isAdmin = user && user.role === 'admin';
  const hasActiveFilters = filters.subcategory || filters.sort !== 'newest' || filters.minPrice !== null || filters.maxPrice !== null;

  // Render Header of the product list (Hero Banner + Section Heading)
  const renderListHeader = () => (
    <View>
      {/* 1. Wireframe Hero Section matching Website */}
      <ImageBackground
        source={{ uri: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600' }}
        style={styles.heroBanner}
        imageStyle={{ opacity: 0.8 }}
      >
        <View style={styles.heroOverlay}>
          <View style={styles.pillBadge}>
            <Text style={styles.pillBadgeText}>NEW ARRIVAL</Text>
          </View>
          <Text style={styles.heroTitle}>MINIMALIST EVERYDAY WEAR</Text>
          <Text style={styles.heroQuote}>
            ค้นพบเครื่องแต่งกายสไตล์มินิมอลที่ผสานความเรียบง่าย ความสบาย และคุณภาพไว้ในทุกชิ้น
          </Text>
          <TouchableOpacity 
            style={styles.heroBtn}
            onPress={() => { setCategory(''); setFilters({ subcategory: '', sort: 'newest', minPrice: null, maxPrice: null }); }}
          >
            <Text style={styles.heroBtnText}>EXPLORE COLLECTION</Text>
          </TouchableOpacity>
        </View>
      </ImageBackground>

      {/* 2. Admin & User Action Shortcut Banners */}
      {isAdmin ? (
        <View style={styles.adminBarGroup}>
          <TouchableOpacity 
            style={styles.adminActionBar} 
            onPress={() => setShowAdminOrders(true)}
          >
            <Text style={styles.adminActionText}>⚡ MANAGE ORDERS & INSPECT SLIPS</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.adminActionBar, { backgroundColor: '#EFEBE7', borderColor: THEME.primary }]} 
            onPress={() => setShowAdminStock(true)}
          >
            <Text style={[styles.adminActionText, { color: THEME.dark }]}>📦 MANAGE INVENTORY & STOCK</Text>
          </TouchableOpacity>
        </View>
      ) : user ? (
        <TouchableOpacity 
          style={styles.userActionBar} 
          onPress={() => setShowOrders(true)}
        >
          <Text style={styles.userActionText}>📦 VIEW MY ORDER HISTORY & STATUS</Text>
        </TouchableOpacity>
      ) : null}

      {/* 3. Section Title with Taupe Divider Line */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>
          {category ? `${category.toUpperCase()} COLLECTION` : 'FEATURED PRODUCTS'}
        </Text>
        <View style={styles.taupeDivider} />
      </View>

      {/* 4. Active Filters Badges Row */}
      {hasActiveFilters && (
        <View style={styles.activeFilterBadgeRow}>
          {filters.subcategory ? (
            <View style={styles.activeFilterTag}>
              <Text style={styles.activeFilterText}>TYPE: {filters.subcategory.toUpperCase()}</Text>
            </View>
          ) : null}
          {filters.sort !== 'newest' ? (
            <View style={styles.activeFilterTag}>
              <Text style={styles.activeFilterText}>SORT: {filters.sort.toUpperCase()}</Text>
            </View>
          ) : null}
          {filters.minPrice !== null || filters.maxPrice !== null ? (
            <View style={styles.activeFilterTag}>
              <Text style={styles.activeFilterText}>฿{filters.minPrice || 0} - ฿{filters.maxPrice || 'MAX'}</Text>
            </View>
          ) : null}
          <TouchableOpacity 
            onPress={() => setFilters({ subcategory: '', sort: 'newest', minPrice: null, maxPrice: null })}
            style={styles.clearFilterTag}
          >
            <Text style={styles.clearFilterText}>CLEAR ✕</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );

  return (
    <SafeAreaProvider>
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />
        
        {/* 1. Navbar (Matching .navbar-wireframe from website) */}
        <View style={styles.navbar}>
          <Text style={styles.brandLogo}>LOGO</Text>
          
          <View style={styles.navbarRight}>
            {user ? (
              <TouchableOpacity 
                style={[styles.userBtn, isAdmin && styles.adminUserBtn]}
                onPress={() => setShowProfile(true)}
              >
                <Text style={[styles.userBtnText, isAdmin && styles.adminUserBtnText]}>
                  {isAdmin ? 'ADMIN' : user.username.toUpperCase()}
                </Text>
              </TouchableOpacity>
            ) : (
              <TouchableOpacity style={styles.signInBtn} onPress={() => setShowAuth(true)}>
                <Text style={styles.signInBtnText}>SIGN IN</Text>
              </TouchableOpacity>
            )}

            {/* Cart Icon */}
            <TouchableOpacity style={styles.cartBtn} onPress={() => setShowCart(true)}>
              <Text style={styles.cartIcon}>BAG</Text>
              {cartTotalCount > 0 && (
                <View style={styles.cartBadge}>
                  <Text style={styles.cartBadgeText}>{cartTotalCount}</Text>
                </View>
              )}
            </TouchableOpacity>
          </View>
        </View>

        {/* 2. Category Switcher Bar (Matching Website Menu) */}
        <View style={styles.categoryNav}>
          {CATEGORIES.map(c => (
            <TouchableOpacity
              key={c.key}
              style={[styles.catNavItem, category === c.key && styles.catNavItemActive]}
              onPress={() => setCategory(c.key)}
            >
              <Text style={[styles.catNavText, category === c.key && styles.catNavTextActive]}>
                {c.label}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* 3. Search Bar with Filter Button */}
        <View style={styles.searchRow}>
          <View style={styles.searchContainer}>
            <TextInput
              style={styles.searchInput}
              placeholder="SEARCH IN CATALOGUE..."
              placeholderTextColor={THEME.gray}
              value={search}
              onChangeText={setSearch}
              onSubmitEditing={handleSearchSubmit}
              returnKeyType="search"
            />
            {search !== '' && (
              <TouchableOpacity onPress={() => { setSearch(''); loadProducts(); }}>
                <Text style={styles.clearSearchBtn}>✕</Text>
              </TouchableOpacity>
            )}
          </View>

          {/* Filter & Sort Button */}
          <TouchableOpacity 
            style={[styles.filterTriggerBtn, hasActiveFilters && styles.filterTriggerActive]}
            onPress={() => setShowFilter(true)}
          >
            <Text style={[styles.filterTriggerText, hasActiveFilters && styles.filterTriggerTextActive]}>
              FILTER {hasActiveFilters ? '●' : ''}
            </Text>
          </TouchableOpacity>
        </View>

        {/* 4. Product List with Website Hero & Layout */}
        {loading && !refreshing ? (
          <View style={styles.centerContainer}>
            <ActivityIndicator size="large" color={THEME.dark} />
            <Text style={styles.loadingText}>LOADING CATALOGUE...</Text>
          </View>
        ) : (
          <FlatList
            data={products}
            keyExtractor={item => String(item.id)}
            contentContainerStyle={styles.productList}
            ListHeaderComponent={renderListHeader}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[THEME.dark]} />
            }
            renderItem={({ item }) => (
              <ProductCard
                product={item}
                onPress={() => handleOpenDetail(item.id)}
                onAddToCart={(p) => handleAddToCart(p, 1)}
              />
            )}
            ListEmptyComponent={
              <View style={styles.emptyContainer}>
                <Text style={styles.emptyTitle}>NO PRODUCTS FOUND</Text>
                <Text style={styles.emptySubtitle}>ลองเปลี่ยนคำค้นหา หรือรีเซ็ตตัวกรอง</Text>
              </View>
            }
          />
        )}

        {/* 5. Floating Bottom Bag Bar */}
        {cart.length > 0 && (
          <View style={styles.floatingBagContainer}>
            <TouchableOpacity style={styles.floatingBagBtn} onPress={() => setShowCart(true)}>
              <View style={styles.floatingBagLeft}>
                <View style={styles.floatingBagBadge}>
                  <Text style={styles.floatingBagBadgeText}>{cartTotalCount}</Text>
                </View>
                <Text style={styles.floatingBagTitle}>SHOPPING BAG</Text>
              </View>
              <Text style={styles.floatingBagPrice}>
                ฿{cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toLocaleString()} ➜
              </Text>
            </TouchableOpacity>
          </View>
        )}

        {/* 6. Modals */}
        <ProductDetailModal
          visible={!!selectedProduct}
          product={selectedProduct}
          onClose={() => setSelectedProduct(null)}
          onAddToCart={handleAddToCart}
          onSelectRelated={(relId) => handleOpenDetail(relId)}
        />

        <CartModal
          visible={showCart}
          cart={cart}
          onClose={() => setShowCart(false)}
          onUpdateQty={handleUpdateCartQty}
          onRemoveItem={handleRemoveCartItem}
          onClearCart={handleClearCart}
          user={user}
          onOrderSuccess={() => {
            if (!isAdmin) {
              setShowOrders(true);
            } else {
              setShowAdminOrders(true);
            }
          }}
        />

        <OrdersModal
          visible={showOrders}
          onClose={() => setShowOrders(false)}
          user={user}
          onSelectOrder={(ordId) => setSelectedOrderId(ordId)}
        />

        <OrderDetailModal
          visible={!!selectedOrderId}
          orderId={selectedOrderId}
          onClose={() => setSelectedOrderId(null)}
          isAdmin={isAdmin}
          onStatusUpdated={() => loadProducts()}
        />

        <AdminOrdersModal
          visible={showAdminOrders}
          onClose={() => setShowAdminOrders(false)}
        />

        <AdminStockModal
          visible={showAdminStock}
          onClose={() => setShowAdminStock(false)}
          onStockChanged={() => loadProducts()}
        />

        <AuthModal
          visible={showAuth}
          onClose={() => setShowAuth(false)}
          onAuthSuccess={(u) => setUser(u)}
        />

        <FilterModal
          visible={showFilter}
          currentFilters={filters}
          onClose={() => setShowFilter(false)}
          onApply={(newFilters) => setFilters(newFilters)}
        />

        <ProfileModal
          visible={showProfile}
          user={user}
          onClose={() => setShowProfile(false)}
          onUserUpdated={(u) => setUser(u)}
          onOpenOrders={() => { setShowProfile(false); setShowOrders(true); }}
          onLogout={() => { setUser(null); setShowProfile(false); }}
        />
      </SafeAreaView>
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: THEME.light },
  
  // Navbar Wireframe
  navbar: {
    backgroundColor: THEME.white,
    paddingHorizontal: 20,
    paddingVertical: 14,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: THEME.border,
  },
  brandLogo: {
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: 3,
    color: THEME.dark,
  },
  navbarRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  signInBtn: {
    borderWidth: 1,
    borderColor: THEME.dark,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  signInBtnText: {
    fontSize: 11,
    fontWeight: '700',
    color: THEME.dark,
    letterSpacing: 1.5,
  },
  userBtn: {
    borderWidth: 1,
    borderColor: THEME.border,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: THEME.light,
  },
  adminUserBtn: {
    borderColor: THEME.dark,
    backgroundColor: THEME.dark,
  },
  userBtnText: {
    fontSize: 11,
    fontWeight: '700',
    color: THEME.dark,
    letterSpacing: 1,
  },
  adminUserBtnText: {
    color: THEME.white,
  },
  cartBtn: {
    position: 'relative',
    borderWidth: 1,
    borderColor: THEME.dark,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: THEME.white,
  },
  cartIcon: {
    fontSize: 11,
    fontWeight: '800',
    color: THEME.dark,
    letterSpacing: 1.5,
  },
  cartBadge: {
    position: 'absolute',
    top: -6,
    right: -6,
    backgroundColor: THEME.sale,
    width: 18,
    height: 18,
    borderRadius: 9,
    justifyContent: 'center',
    alignItems: 'center',
  },
  cartBadgeText: {
    color: THEME.white,
    fontSize: 10,
    fontWeight: 'bold',
  },

  // Category Nav matching Website
  categoryNav: {
    flexDirection: 'row',
    backgroundColor: THEME.white,
    borderBottomWidth: 1,
    borderBottomColor: THEME.border,
  },
  catNavItem: {
    flex: 1,
    paddingVertical: 12,
    alignItems: 'center',
  },
  catNavItemActive: {
    borderBottomWidth: 2,
    borderBottomColor: THEME.dark,
  },
  catNavText: {
    fontSize: 12,
    fontWeight: '600',
    color: THEME.gray,
    letterSpacing: 1.5,
  },
  catNavTextActive: {
    color: THEME.dark,
    fontWeight: '800',
  },

  // Search Row
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: 16,
    marginTop: 12,
    gap: 8,
  },
  searchContainer: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: THEME.white,
    borderWidth: 1,
    borderColor: THEME.border,
    paddingHorizontal: 12,
  },
  searchInput: {
    flex: 1,
    paddingVertical: 9,
    fontSize: 12,
    color: THEME.dark,
    letterSpacing: 1,
  },
  clearSearchBtn: {
    fontSize: 14,
    color: THEME.gray,
    padding: 6,
  },
  filterTriggerBtn: {
    borderWidth: 1,
    borderColor: THEME.border,
    backgroundColor: THEME.white,
    paddingHorizontal: 12,
    paddingVertical: 11,
    alignItems: 'center',
  },
  filterTriggerActive: {
    backgroundColor: THEME.dark,
    borderColor: THEME.dark,
  },
  filterTriggerText: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1,
    color: THEME.dark,
  },
  filterTriggerTextActive: {
    color: THEME.white,
  },

  // Hero Section matching Website Wireframe
  heroBanner: {
    marginHorizontal: 16,
    marginTop: 14,
    marginBottom: 16,
    minHeight: 260,
    backgroundColor: THEME.dark,
    borderWidth: 1,
    borderColor: THEME.border,
    overflow: 'hidden',
  },
  heroOverlay: {
    flex: 1,
    backgroundColor: 'rgba(45, 40, 40, 0.55)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  pillBadge: {
    backgroundColor: THEME.dark,
    borderRadius: 50,
    paddingHorizontal: 16,
    paddingVertical: 5,
    marginBottom: 12,
  },
  pillBadgeText: {
    color: THEME.white,
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 1.5,
  },
  heroTitle: {
    color: THEME.white,
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: 2,
    textAlign: 'center',
    marginBottom: 8,
  },
  heroQuote: {
    color: '#F0EAE6',
    fontSize: 12,
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 16,
    maxWidth: 280,
  },
  heroBtn: {
    backgroundColor: THEME.dark,
    borderWidth: 1,
    borderColor: THEME.primary,
    paddingHorizontal: 20,
    paddingVertical: 10,
  },
  heroBtnText: {
    color: THEME.white,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 1.5,
  },

  // Action Banners
  adminBarGroup: {
    gap: 8,
    marginHorizontal: 16,
    marginBottom: 14,
  },
  adminActionBar: {
    backgroundColor: '#FFF8E1',
    borderWidth: 1,
    borderColor: '#FFE082',
    padding: 12,
    alignItems: 'center',
  },
  adminActionText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#B78103',
    letterSpacing: 1,
  },
  userActionBar: {
    marginHorizontal: 16,
    marginBottom: 14,
    backgroundColor: THEME.white,
    borderWidth: 1,
    borderColor: THEME.border,
    padding: 12,
    alignItems: 'center',
  },
  userActionText: {
    fontSize: 11,
    fontWeight: '700',
    color: THEME.dark,
    letterSpacing: 1,
  },

  // Section Header matching Website
  sectionHeader: {
    alignItems: 'center',
    marginTop: 10,
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '800',
    letterSpacing: 2,
    color: THEME.dark,
  },
  taupeDivider: {
    width: 45,
    height: 2,
    backgroundColor: THEME.primary,
    marginTop: 8,
  },

  // Active Filter Badges
  activeFilterBadgeRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginHorizontal: 16,
    marginBottom: 12,
  },
  activeFilterTag: {
    backgroundColor: THEME.white,
    borderWidth: 1,
    borderColor: THEME.border,
    paddingVertical: 4,
    paddingHorizontal: 8,
  },
  activeFilterText: {
    fontSize: 10,
    fontWeight: '700',
    color: THEME.dark,
    letterSpacing: 1,
  },
  clearFilterTag: {
    backgroundColor: THEME.dark,
    paddingVertical: 4,
    paddingHorizontal: 8,
  },
  clearFilterText: {
    fontSize: 10,
    fontWeight: '700',
    color: THEME.white,
    letterSpacing: 1,
  },

  // Product List
  productList: {
    paddingBottom: 90,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    color: THEME.gray,
    fontSize: 12,
    letterSpacing: 1.5,
    fontWeight: '600',
  },
  emptyContainer: {
    alignItems: 'center',
    paddingVertical: 50,
  },
  emptyTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    letterSpacing: 1.5,
    color: THEME.dark,
    marginBottom: 6,
  },
  emptySubtitle: {
    fontSize: 12,
    color: THEME.gray,
  },

  // Floating Shopping Bag Button
  floatingBagContainer: {
    position: 'absolute',
    bottom: 20,
    left: 16,
    right: 16,
  },
  floatingBagBtn: {
    backgroundColor: THEME.dark,
    paddingVertical: 14,
    paddingHorizontal: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: THEME.primary,
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
  },
  floatingBagLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  floatingBagBadge: {
    backgroundColor: THEME.primary,
    width: 22,
    height: 22,
    borderRadius: 11,
    justifyContent: 'center',
    alignItems: 'center',
  },
  floatingBagBadgeText: {
    color: THEME.white,
    fontWeight: 'bold',
    fontSize: 11,
  },
  floatingBagTitle: {
    color: THEME.white,
    fontWeight: '700',
    fontSize: 13,
    letterSpacing: 1.5,
  },
  floatingBagPrice: {
    color: THEME.white,
    fontWeight: '800',
    fontSize: 15,
    letterSpacing: 1,
  },
});
