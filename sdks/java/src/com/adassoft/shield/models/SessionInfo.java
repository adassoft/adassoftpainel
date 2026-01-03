package com.adassoft.shield.models;

import java.time.LocalDateTime;

public class SessionInfo {
    private String token;
    private LocalDateTime expiraEm;

    public SessionInfo() {
        clear();
    }

    public void clear() {
        this.token = "";
        this.expiraEm = null;
    }

    public String getToken() {
        return token;
    }

    public void setToken(String token) {
        this.token = token;
    }
}
