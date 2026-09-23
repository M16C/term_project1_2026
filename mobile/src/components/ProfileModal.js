// mobile/src/components/ProfileModal.js - Matching profile.php, edit_profile.php, change_password.php
import React, { useState } from 'react';
import { 
  View, Text, TextInput, Modal, TouchableOpacity, ScrollView, 
  ActivityIndicator, Alert, StyleSheet 
} from 'react-native';
import { apiUpdateProfile, apiChangePassword } from '../services/api';
import { THEME } from '../theme';

export default function ProfileModal({ 
  visible, onClose, user, onUserUpdated, onOpenOrders, onLogout 
}) {
  const [activeTab, setActiveTab] = useState('info'); // 'info', 'edit', 'password'

  // Edit Profile Form State
  const [fullname, setFullname] = useState(user?.fullname || '');
  const [email, setEmail] = useState(user?.email || '');
  const [phone, setPhone] = useState(user?.phone || '');
  const [editLoading, setEditLoading] = useState(false);

  // Change Password Form State
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passLoading, setPassLoading] = useState(false);

  // Sync state if user updates
  React.useEffect(() => {
    if (user) {
      setFullname(user.fullname || '');
      setEmail(user.email || '');
      setPhone(user.phone || '');
    }
  }, [user]);

  if (!user) return null;

  const handleSaveProfile = async () => {
    if (!fullname.trim() || !email.trim()) {
      Alert.alert('แจ้งเตือน', 'กรุณากรอกชื่อ-นามสกุล และอีเมล');
      return;
    }

    setEditLoading(true);
    const res = await apiUpdateProfile({
      userId: user.id,
      fullname: fullname.trim(),
      email: email.trim(),
      phone: phone.trim(),
    });
    setEditLoading(false);

    if (res.success) {
      Alert.alert('สำเร็จ 🎉', res.message);
      onUserUpdated(res.user);
      setActiveTab('info');
    } else {
      Alert.alert('ผิดพลาด', res.message);
    }
  };

  const handleSavePassword = async () => {
    if (!oldPassword.trim() || !newPassword.trim()) {
      Alert.alert('แจ้งเตือน', 'กรุณากรอกรหัสผ่านเดิมและรหัสผ่านใหม่');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('แจ้งเตือน', 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
      return;
    }

    setPassLoading(true);
    const res = await apiChangePassword({
      userId: user.id,
      oldPassword: oldPassword.trim(),
      newPassword: newPassword.trim(),
    });
    setPassLoading(false);

    if (res.success) {
      Alert.alert('สำเร็จ 🎉', res.message);
      setOldPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setActiveTab('info');
    } else {
      Alert.alert('ผิดพลาด', res.message);
    }
  };

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>MY ACCOUNT</Text>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <Text style={styles.closeText}>✕</Text>
          </TouchableOpacity>
        </View>

        {/* Tab Navigation */}
        <View style={styles.navRow}>
          <TouchableOpacity 
            style={[styles.navBtn, activeTab === 'info' && styles.navBtnActive]}
            onPress={() => setActiveTab('info')}
          >
            <Text style={[styles.navText, activeTab === 'info' && styles.navTextActive]}>PROFILE</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.navBtn, activeTab === 'edit' && styles.navBtnActive]}
            onPress={() => setActiveTab('edit')}
          >
            <Text style={[styles.navText, activeTab === 'edit' && styles.navTextActive]}>EDIT INFO</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.navBtn, activeTab === 'password' && styles.navBtnActive]}
            onPress={() => setActiveTab('password')}
          >
            <Text style={[styles.navText, activeTab === 'password' && styles.navTextActive]}>SECURITY</Text>
          </TouchableOpacity>
        </View>

        <ScrollView contentContainerStyle={styles.content}>
          {/* TAB 1: Profile Info */}
          {activeTab === 'info' && (
            <View style={styles.card}>
              <View style={styles.avatarBox}>
                <Text style={styles.avatarText}>{user.fullname ? user.fullname.charAt(0).toUpperCase() : 'U'}</Text>
              </View>

              <Text style={styles.userName}>{user.fullname}</Text>
              <Text style={styles.userRole}>ROLE: {user.role.toUpperCase()}</Text>

              <View style={styles.divider} />

              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>USERNAME:</Text>
                <Text style={styles.infoVal}>{user.username}</Text>
              </View>
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>EMAIL:</Text>
                <Text style={styles.infoVal}>{user.email}</Text>
              </View>
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>PHONE:</Text>
                <Text style={styles.infoVal}>{user.phone || 'ยังไม่ได้ระบุ'}</Text>
              </View>

              <View style={styles.divider} />

              <TouchableOpacity style={styles.actionBtn} onPress={onOpenOrders}>
                <Text style={styles.actionBtnText}>📦 VIEW ORDER HISTORY</Text>
              </TouchableOpacity>

              <TouchableOpacity style={styles.logoutBtn} onPress={onLogout}>
                <Text style={styles.logoutBtnText}>SIGN OUT</Text>
              </TouchableOpacity>
            </View>
          )}

          {/* TAB 2: Edit Profile */}
          {activeTab === 'edit' && (
            <View style={styles.card}>
              <Text style={styles.secTitle}>EDIT PROFILE DETAILS</Text>

              <Text style={styles.label}>FULL NAME</Text>
              <TextInput
                style={styles.input}
                value={fullname}
                onChangeText={setFullname}
                placeholder="ชื่อ-นามสกุล"
              />

              <Text style={styles.label}>EMAIL ADDRESS</Text>
              <TextInput
                style={styles.input}
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                placeholder="example@mail.com"
              />

              <Text style={styles.label}>PHONE NUMBER</Text>
              <TextInput
                style={styles.input}
                value={phone}
                onChangeText={setPhone}
                keyboardType="phone-pad"
                placeholder="081-234-5678"
              />

              <TouchableOpacity 
                style={[styles.saveBtn, editLoading && styles.btnDisabled]} 
                disabled={editLoading}
                onPress={handleSaveProfile}
              >
                {editLoading ? <ActivityIndicator color={THEME.white} /> : (
                  <Text style={styles.saveBtnText}>SAVE CHANGES</Text>
                )}
              </TouchableOpacity>
            </View>
          )}

          {/* TAB 3: Change Password */}
          {activeTab === 'password' && (
            <View style={styles.card}>
              <Text style={styles.secTitle}>CHANGE PASSWORD</Text>

              <Text style={styles.label}>CURRENT PASSWORD</Text>
              <TextInput
                style={styles.input}
                value={oldPassword}
                onChangeText={setOldPassword}
                secureTextEntry
                placeholder="รหัสผ่านปัจจุบัน"
              />

              <Text style={styles.label}>NEW PASSWORD</Text>
              <TextInput
                style={styles.input}
                value={newPassword}
                onChangeText={setNewPassword}
                secureTextEntry
                placeholder="รหัสผ่านใหม่ (อย่างน้อย 4 ตัวอักษร)"
              />

              <Text style={styles.label}>CONFIRM NEW PASSWORD</Text>
              <TextInput
                style={styles.input}
                value={confirmPassword}
                onChangeText={setConfirmPassword}
                secureTextEntry
                placeholder="ยืนยันรหัสผ่านใหม่"
              />

              <TouchableOpacity 
                style={[styles.saveBtn, passLoading && styles.btnDisabled]} 
                disabled={passLoading}
                onPress={handleSavePassword}
              >
                {passLoading ? <ActivityIndicator color={THEME.white} /> : (
                  <Text style={styles.saveBtnText}>UPDATE PASSWORD</Text>
                )}
              </TouchableOpacity>
            </View>
          )}
        </ScrollView>
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
  navRow: { flexDirection: 'row', backgroundColor: THEME.white, borderBottomWidth: 1, borderBottomColor: THEME.border },
  navBtn: { flex: 1, paddingVertical: 12, alignItems: 'center' },
  navBtnActive: { borderBottomWidth: 2, borderBottomColor: THEME.dark },
  navText: { fontSize: 11, fontWeight: '700', letterSpacing: 1.5, color: THEME.gray },
  navTextActive: { color: THEME.dark },
  content: { padding: 16 },
  card: { backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 20 },
  avatarBox: { width: 64, height: 64, borderRadius: 32, backgroundColor: THEME.dark, justifyContent: 'center', alignItems: 'center', alignSelf: 'center', marginBottom: 12 },
  avatarText: { color: THEME.white, fontSize: 24, fontWeight: 'bold' },
  userName: { fontSize: 18, fontWeight: '700', color: THEME.dark, textAlign: 'center', marginBottom: 2 },
  userRole: { fontSize: 11, fontWeight: '700', color: THEME.primary, letterSpacing: 1.5, textAlign: 'center', marginBottom: 16 },
  divider: { height: 1, backgroundColor: '#F0EBE6', marginVertical: 14 },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#F5F0EB' },
  infoLabel: { fontSize: 12, fontWeight: '700', color: THEME.gray, letterSpacing: 1 },
  infoVal: { fontSize: 13, fontWeight: '600', color: THEME.dark },
  actionBtn: { borderWidth: 1, borderColor: THEME.dark, paddingVertical: 12, alignItems: 'center', marginTop: 10, backgroundColor: THEME.white },
  actionBtnText: { color: THEME.dark, fontSize: 12, fontWeight: '700', letterSpacing: 1.5 },
  logoutBtn: { backgroundColor: '#FDF2F2', borderWidth: 1, borderColor: '#F8D7DA', paddingVertical: 12, alignItems: 'center', marginTop: 12 },
  logoutBtnText: { color: THEME.sale, fontSize: 12, fontWeight: '700', letterSpacing: 1.5 },
  secTitle: { fontSize: 13, fontWeight: '800', letterSpacing: 1.5, color: THEME.dark, marginBottom: 16 },
  label: { fontSize: 11, fontWeight: '700', letterSpacing: 1, color: THEME.dark, marginBottom: 6, marginTop: 10 },
  input: { borderWidth: 1, borderColor: THEME.border, padding: 12, fontSize: 13, backgroundColor: THEME.light, color: THEME.dark },
  saveBtn: { backgroundColor: THEME.dark, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  saveBtnText: { color: THEME.white, fontSize: 13, fontWeight: 'bold', letterSpacing: 1.5 },
  btnDisabled: { opacity: 0.6 },
});
