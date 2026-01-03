package com.adassoft.shield.utils;

import java.net.InetAddress;
import java.net.NetworkInterface;
import java.util.Base64;
import javax.crypto.Cipher;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;

public class SecurityUtil {

    public static String getHardwareId() {
        try {
            InetAddress localHost = InetAddress.getLocalHost();
            NetworkInterface ni = NetworkInterface.getByInetAddress(localHost);
            if (ni == null)
                return "UNKNOWN-HWID";

            byte[] hardwareAddress = ni.getHardwareAddress();
            if (hardwareAddress == null)
                return "NO-MAC-" + localHost.getHostName();

            StringBuilder sb = new StringBuilder();
            for (int i = 0; i < hardwareAddress.length; i++) {
                sb.append(String.format("%02X%s", hardwareAddress[i], (i < hardwareAddress.length - 1) ? "-" : ""));
            }
            return sb.toString();
        } catch (Exception e) {
            return "ERROR-HWID";
        }
    }

    public static String getComputerName() {
        try {
            return InetAddress.getLocalHost().getHostName();
        } catch (Exception e) {
            return "UnknownPC";
        }
    }

    // Criptografia Simples para substituir DPAPI (que é Windows-only)
    // Em produção, use um Keystore seguro.
    private static final String AES_KEY = "1234567890123456"; // 16 bytes

    public static String protect(String data) {
        try {
            SecretKeySpec key = new SecretKeySpec(AES_KEY.getBytes(StandardCharsets.UTF_8), "AES");
            Cipher cipher = Cipher.getInstance("AES");
            cipher.init(Cipher.ENCRYPT_MODE, key);
            byte[] encrypted = cipher.doFinal(data.getBytes(StandardCharsets.UTF_8));
            return Base64.getEncoder().encodeToString(encrypted);
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }

    public static String unprotect(String encryptedData) {
        try {
            SecretKeySpec key = new SecretKeySpec(AES_KEY.getBytes(StandardCharsets.UTF_8), "AES");
            Cipher cipher = Cipher.getInstance("AES");
            cipher.init(Cipher.DECRYPT_MODE, key);
            byte[] original = cipher.doFinal(Base64.getDecoder().decode(encryptedData));
            return new String(original, StandardCharsets.UTF_8);
        } catch (Exception e) {
            // e.printStackTrace();
            return null;
        }
    }
}
