package com.adassoft.shield;

public class ShieldConfig {
    private String apiKey;
    private int softwareId;
    private String softwareVersion;
    private String cacheDir;

    public ShieldConfig(String apiKey, int softwareId, String softwareVersion) {
        this.apiKey = apiKey;
        this.softwareId = softwareId;
        this.softwareVersion = softwareVersion;
        // Padrão: user.home/ShieldApps
        this.cacheDir = System.getProperty("user.home") + "/ShieldApps";
    }

    public String getApiKey() {
        return apiKey;
    }

    public int getSoftwareId() {
        return softwareId;
    }

    public String getSoftwareVersion() {
        return softwareVersion;
    }

    public String getCacheDir() {
        return cacheDir;
    }

    public void setCacheDir(String cacheDir) {
        this.cacheDir = cacheDir;
    }
}
