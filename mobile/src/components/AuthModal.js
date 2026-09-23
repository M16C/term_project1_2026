// mobile/src/components/AuthModal.js
import React, { useState } from 'react';
import { 
  View, Text, TextInput, Modal, TouchableOpacity, ScrollView, 
  ActivityIndicator, Alert, StyleSheet 
} from 'react-native';
import { apiLogin, apiRegister } from '../services/api';
import { THEME } from '../theme';

export default function AuthModal({ visible, onClose, onAuthSuccess }) {
  const [isRegister, setIsRegister] = useState(false);
  const [username, setUsername] = useState('admin');
  const [password, setPassword] = useState('1234');
  const [fullname, setFullname] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    if (!username.trim() || !password.trim()) {
      Alert.alert('แจ้งเตือน', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
      return;
    }

    setLoading(true);

    if (isRegister) {
      if (!fullname.trim() || !email.trim()) {
        Alert.alert('แจ้งเตือน', 'กรุณากรอกชื่อ-นามสกุล และอีเมลให้ครบถ้วน');
        setLoading(false);
        return;
      }

      const res = await apiRegister({ username, password, fullname, email, phone });
      setLoading(false);
      if (res.success) {
        Alert.alert('สำเร็จ 🎉', 'สมัครสมาชิกและเข้าสู่ระบบเรียบร้อยแล้ว');
        onAuthSuccess(res.user);
        onClose();
      } else {
        Alert.alert('สมัครสมาชิกไม่สำเร็จ', res.message);
      }
    } else {
      const res = await apiLogin(username, password);
      setLoading(false);
      if (res.success) {
        Alert.alert('ยินดีต้อนรับ 👋', `เข้าสู่ระบบในฐานะ: ${res.user.fullname} (${res.user.role})`);
        onAuthSuccess(res.user);
        onClose();
      } else {
        Alert.alert('เข้าสู่ระบบไม่สำเร็จ', res.message);
      }
    }
  };

  return (
    <Modal visible={visible} animationType="slide" transparent={true} onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.card}>
          <View style={styles.header}>
            <Text style={styles.title}>{isRegister ? 'REGISTER' : 'ACCOUNT LOGIN'}</Text>
            <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
              <Text style={styles.closeText}>✕</Text>
            </TouchableOpacity>
          </View>

          {/* Tab Selector */}
          <View style={styles.tabRow}>
            <TouchableOpacity
              style={[styles.tabBtn, !isRegister && styles.tabBtnActive]}
              onPress={() => setIsRegister(false)}
            >
              <Text style={[styles.tabText, !isRegister && styles.tabTextActive]}>SIGN IN</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.tabBtn, isRegister && styles.tabBtnActive]}
              onPress={() => setIsRegister(true)}
            >
              <Text style={[styles.tabText, isRegister && styles.tabTextActive]}>CREATE ACCOUNT</Text>
            </TouchableOpacity>
          </View>

          <ScrollView style={styles.form}>
            {isRegister && (
              <>
                <Text style={styles.label}>FULL NAME</Text>
                <TextInput
                  style={styles.input}
                  placeholder="เช่น สมชาย ใจดี"
                  value={fullname}
                  onChangeText={setFullname}
                />
                <Text style={styles.label}>EMAIL ADDRESS</Text>
                <TextInput
                  style={styles.input}
                  placeholder="example@email.com"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  value={email}
                  onChangeText={setEmail}
                />
                <Text style={styles.label}>PHONE NUMBER</Text>
                <TextInput
                  style={styles.input}
                  placeholder="081-234-5678"
                  keyboardType="phone-pad"
                  value={phone}
                  onChangeText={setPhone}
                />
              </>
            )}

            <Text style={styles.label}>USERNAME</Text>
            <TextInput
              style={styles.input}
              placeholder="Username"
              autoCapitalize="none"
              value={username}
              onChangeText={setUsername}
            />

            <Text style={styles.label}>PASSWORD</Text>
            <TextInput
              style={styles.input}
              placeholder="Password"
              secureTextEntry
              value={password}
              onChangeText={setPassword}
            />

            <TouchableOpacity 
              style={[styles.submitBtn, loading && styles.btnDisabled]} 
              disabled={loading}
              onPress={handleSubmit}
            >
              {loading ? (
                <ActivityIndicator color={THEME.white} />
              ) : (
                <Text style={styles.submitBtnText}>{isRegister ? 'CONFIRM REGISTRATION' : 'SIGN IN'}</Text>
              )}
            </TouchableOpacity>
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: { flex: 1, backgroundColor: 'rgba(45,40,40,0.65)', justifyContent: 'center', alignItems: 'center', padding: 20 },
  card: { width: '100%', maxHeight: '85%', backgroundColor: THEME.white, borderWidth: 1, borderColor: THEME.border, padding: 22 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  title: { fontSize: 16, fontWeight: 'bold', color: THEME.dark, letterSpacing: 2 },
  closeBtn: { padding: 5 },
  closeText: { fontSize: 18, color: THEME.dark, fontWeight: 'bold' },
  tabRow: { flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: THEME.border, marginBottom: 18 },
  tabBtn: { flex: 1, paddingVertical: 10, alignItems: 'center' },
  tabBtnActive: { borderBottomWidth: 2, borderBottomColor: THEME.dark },
  tabText: { fontSize: 12, fontWeight: '700', color: THEME.gray, letterSpacing: 1 },
  tabTextActive: { color: THEME.dark },
  form: { width: '100%' },
  label: { fontSize: 11, fontWeight: '700', color: THEME.dark, letterSpacing: 1, marginBottom: 6 },
  input: { borderWidth: 1, borderColor: THEME.border, padding: 12, fontSize: 13, marginBottom: 14, backgroundColor: THEME.light, color: THEME.dark },
  submitBtn: { backgroundColor: THEME.dark, paddingVertical: 14, alignItems: 'center', marginTop: 10, marginBottom: 10 },
  btnDisabled: { opacity: 0.6 },
  submitBtnText: { color: THEME.white, fontSize: 13, fontWeight: 'bold', letterSpacing: 1.5 },
});
