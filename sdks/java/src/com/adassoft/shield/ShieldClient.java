package com.adassoft.shield;

import com.adassoft.shield.models.LicenseInfo;
import com.adassoft.shield.models.SessionInfo;
import com.adassoft.shield.models.Plan;
import com.adassoft.shield.utils.HttpUtil;
import com.adassoft.shield.utils.JsonHelper;
import com.adassoft.shield.utils.SecurityUtil;

import java.time.LocalDate;
import java.util.List;
import java.util.ArrayList;

public class ShieldClient {

    private final String API_BASE_URL;
    private final ShieldConfig config;
    private SessionInfo session;

    public ShieldClient(String baseUrl, ShieldConfig config) {
        this.API_BASE_URL = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
        this.config = config;
        this.session = new SessionInfo();
    }

    /**
     * Verifica a licença atual usando o serial fornecido.
     * 
     * @param serial Serial da licença
     * @return Objeto LicenseInfo preenchido
     */
    public LicenseInfo checkLicense(String serial) {
        LicenseInfo info = new LicenseInfo();
        info.setSerial(serial);
        info.setSoftwareId(config.getSoftwareId());

        try {
            // Hardware Fingerprint
            String hwid = SecurityUtil.getHardwareId();
            String pcName = SecurityUtil.getComputerName();

            // Montar JSON Manualmente (Zero Dependency)
            String jsonBody = String.format(
                    "{\"acao\":\"validar_serial\", \"serial\":\"%s\", \"software_id\":%d, \"mac\":\"%s\", \"pc_name\":\"%s\", \"versao\":\"%s\"}",
                    serial, config.getSoftwareId(), hwid, pcName, config.getSoftwareVersion());

            // Chamada API REST (AdasSoft V1)
            String response = HttpUtil.post(API_BASE_URL + "validate", jsonBody, null);

            // Processar Resposta (Suporta 'sucesso' ou 'success' em caso de ingles)
            boolean sucesso = JsonHelper.extractBoolean(response, "sucesso");
            if (!sucesso)
                sucesso = JsonHelper.extractBoolean(response, "success");

            String mensagem = JsonHelper.extractString(response, "mensagem");
            if (mensagem.isEmpty())
                mensagem = JsonHelper.extractString(response, "error");

            info.setMensagem(mensagem);

            if (sucesso) {
                // Parse Status
                String statusStr = JsonHelper.extractString(response, "status");
                boolean isValido = JsonHelper.extractBoolean(response, "valido");

                if (isValido || "ativo".equalsIgnoreCase(statusStr) || "true".equalsIgnoreCase(statusStr))
                    info.setStatus(LicenseInfo.Status.VALID);
                else if ("suspenso".equalsIgnoreCase(statusStr))
                    info.setStatus(LicenseInfo.Status.INVALID);
                else if ("expirado".equalsIgnoreCase(statusStr))
                    info.setStatus(LicenseInfo.Status.EXPIRED);
                else
                    info.setStatus(LicenseInfo.Status.INVALID);

                // Parse Datas e Terminais
                String validade = JsonHelper.extractString(response, "validade_licenca");
                if (validade.isEmpty())
                    validade = JsonHelper.extractString(response, "data_expiracao");

                if (!validade.isEmpty()) {
                    try {
                        if (validade.length() > 10)
                            validade = validade.substring(0, 10);
                        LocalDate date = LocalDate.parse(validade);
                        info.setDataExpiracao(date.atStartOfDay());

                        int dias = JsonHelper.extractInt(response, "dias_restantes");
                        info.setDiasRestantes(dias);
                    } catch (Exception e) {
                        System.err.println("Erro parse data: " + e.getMessage());
                    }
                }

                info.setEmpresaCodigo(JsonHelper.extractInt(response, "empresa_codigo"));
                info.setTerminaisPermitidos(JsonHelper.extractInt(response, "terminais_permitidos"));
                info.setTerminaisUtilizados(JsonHelper.extractInt(response, "terminais_utilizados"));

                // Configurações de Alerta
                info.setAvisoAtivo(JsonHelper.extractBoolean(response, "app_alerta_vencimento"));
                int diasAviso = JsonHelper.extractInt(response, "app_dias_alerta");
                if (diasAviso > 0)
                    info.setDiasAviso(diasAviso);

                // Token de sessão se houver
                String token = JsonHelper.extractString(response, "token");
                if (!token.isEmpty()) {
                    session.setToken(token);
                }
            } else {
                info.setStatus(LicenseInfo.Status.INVALID);
            }

        } catch (Exception e) {
            info.setStatus(LicenseInfo.Status.OFFLINE_ERROR);
            info.setMensagem("Erro de conexão: " + e.getMessage());
            e.printStackTrace();
        }

        return info;
    }

    /**
     * Busca planos disponíveis para renovação.
     * 
     * @param licenseInfo Licença atual para contexto
     * @return Lista de planos
     */
    public List<Plan> getAvailablePlans(LicenseInfo licenseInfo) {
        try {
            // Nova Rota REST: /software/{id}/plans
            String url = API_BASE_URL + "software/" + config.getSoftwareId() + "/plans";
            String response = HttpUtil.get(url, session.getToken());
            return JsonHelper.parsePlans(response);

        } catch (Exception e) {
            System.err.println("Erro ao buscar planos: " + e.getMessage());
            return new ArrayList<>();
        }
    }

    /**
     * Cria url de checkout para renovação.
     */
    public String createCheckoutUrl(int planId, String serial) {
        try {
            String jsonBody = String.format("{\"plan_id\":%d, \"licenca_serial\":\"%s\"}", planId, serial);
            // Nova Rota REST: /orders
            String response = HttpUtil.post(API_BASE_URL + "orders", jsonBody, session.getToken());

            String checkoutUrl = JsonHelper.extractString(response, "init_point");
            if (checkoutUrl.isEmpty()) {
                checkoutUrl = JsonHelper.extractString(response, "link_pagamento");
            }

            if (checkoutUrl.isEmpty()) {
                // Fallback: Montar URL manual com cod_transacao
                boolean sucesso = JsonHelper.extractBoolean(response, "success");
                if (!sucesso)
                    sucesso = JsonHelper.extractBoolean(response, "sucesso");

                String codTransacao = JsonHelper.extractString(response, "cod_transacao");
                if (sucesso && !codTransacao.isEmpty()) {
                    // URL nova do front para pagamento (ajustar conforme rota real do sistema)
                    // Se API_BASE_URL for https://site/api/v1/adassoft/,
                    // queremos https://site/checkout/pay/{code}
                    String siteUrl = API_BASE_URL;
                    if (siteUrl.contains("/api/")) {
                        siteUrl = siteUrl.substring(0, siteUrl.indexOf("/api/")) + "/";
                    }
                    return siteUrl + "checkout/pay/" + codTransacao;
                }
            }

            return checkoutUrl;

        } catch (Exception e) {
            e.printStackTrace();
            return "";
        }
    }
}
