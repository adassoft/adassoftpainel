package com.adassoft.shield.models;

import java.time.LocalDate;
import java.time.LocalDateTime;

public class LicenseInfo {
    public enum Status {
        UNCHECKED, VALID, EXPIRED, INVALID, OFFLINE_ERROR
    }

    private int softwareId;
    private String serial;
    private int empresaCodigo;
    private String softwareNome;
    private String versao;
    private int terminaisPermitidos;
    private int terminaisUtilizados;
    private LocalDateTime dataExpiracao;
    private int diasRestantes;
    private Status status;
    private String mensagem;
    private boolean avisoAtivo;
    private int diasAviso;

    public LicenseInfo() {
        clear();
    }

    public void clear() {
        this.status = Status.UNCHECKED;
        this.serial = "";
        this.mensagem = "";
        this.terminaisUtilizados = 0;
        this.avisoAtivo = true;
        this.diasAviso = 5;
    }

    public boolean isExpired() {
        if (dataExpiracao == null)
            return false;
        return dataExpiracao.isBefore(LocalDateTime.now());
    }

    public boolean isValid() {
        return status == Status.VALID && !isExpired();
    }

    public boolean shouldWarnExpiration() {
        return isValid() && avisoAtivo && (diasRestantes <= diasAviso) && (diasRestantes >= 0);
    }

    // Getters e Setters
    public int getSoftwareId() {
        return softwareId;
    }

    public void setSoftwareId(int softwareId) {
        this.softwareId = softwareId;
    }

    public String getSerial() {
        return serial;
    }

    public void setSerial(String serial) {
        this.serial = serial;
    }

    public int getEmpresaCodigo() {
        return empresaCodigo;
    }

    public void setEmpresaCodigo(int empresaCodigo) {
        this.empresaCodigo = empresaCodigo;
    }

    public int getTerminaisPermitidos() {
        return terminaisPermitidos;
    }

    public void setTerminaisPermitidos(int terminaisPermitidos) {
        this.terminaisPermitidos = terminaisPermitidos;
    }

    public int getTerminaisUtilizados() {
        return terminaisUtilizados;
    }

    public void setTerminaisUtilizados(int terminaisUtilizados) {
        this.terminaisUtilizados = terminaisUtilizados;
    }

    public LocalDateTime getDataExpiracao() {
        return dataExpiracao;
    }

    public void setDataExpiracao(LocalDateTime dataExpiracao) {
        this.dataExpiracao = dataExpiracao;
    }

    public int getDiasRestantes() {
        return diasRestantes;
    }

    public void setDiasRestantes(int diasRestantes) {
        this.diasRestantes = diasRestantes;
    }

    public Status getStatus() {
        return status;
    }

    public void setStatus(Status status) {
        this.status = status;
    }

    public String getMensagem() {
        return mensagem;
    }

    public void setMensagem(String mensagem) {
        this.mensagem = mensagem;
    }

    public boolean isAvisoAtivo() {
        return avisoAtivo;
    }

    public void setAvisoAtivo(boolean avisoAtivo) {
        this.avisoAtivo = avisoAtivo;
    }

    public int getDiasAviso() {
        return diasAviso;
    }

    public void setDiasAviso(int diasAviso) {
        this.diasAviso = diasAviso;
    }
}
