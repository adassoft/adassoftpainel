package com.adassoft.shield.utils;

import java.util.ArrayList;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import com.adassoft.shield.models.Plan;

/**
 * Utilitário Minimalista para Parse JSON.
 * AVISO: Em produção, substitua por Gson ou Jackson.
 * Esta classe foi criada para garantir zero dependências externas no exemplo.
 */
public class JsonHelper {

    public static String extractString(String json, String key) {
        // Busca "key" : "value"
        Pattern p = Pattern.compile("\"" + key + "\"\\s*:\\s*\"(.*?)\"");
        Matcher m = p.matcher(json);
        if (m.find()) {
            // Unescape básico
            return m.group(1).replace("\\/", "/");
        }
        return "";
    }

    public static int extractInt(String json, String key) {
        // Busca "key" : 123
        Pattern p = Pattern.compile("\"" + key + "\"\\s*:\\s*([-0-9]+)");
        Matcher m = p.matcher(json);
        if (m.find()) {
            try {
                return Integer.parseInt(m.group(1));
            } catch (Exception e) {
            }
        }
        return 0;
    }

    public static boolean extractBoolean(String json, String key) {
        // Busca "key" : true/false/1/0
        Pattern p = Pattern.compile("\"" + key + "\"\\s*:\\s*(true|false|1|0)");
        Matcher m = p.matcher(json);
        if (m.find()) {
            String val = m.group(1);
            return val.equals("true") || val.equals("1");
        }
        return false;
    }

    // Parser específico para lista de planos da API Shield
    public static List<Plan> parsePlans(String json) {
        List<Plan> plans = new ArrayList<>();
        // Assumindo estrutura [{"id":1,...}, {"id":2,...}]
        // Vamos extrair blocos entre { }

        int bracketLevel = 0;
        StringBuilder currentObj = new StringBuilder();
        boolean inObj = false;

        for (char c : json.toCharArray()) {
            if (c == '{') {
                if (bracketLevel == 0)
                    inObj = true;
                bracketLevel++;
            }

            if (inObj)
                currentObj.append(c);

            if (c == '}') {
                bracketLevel--;
                if (bracketLevel == 0 && inObj) {
                    inObj = false;
                    String block = currentObj.toString();
                    currentObj.setLength(0); // limpar

                    // Parse block
                    int id = extractInt(block, "id");
                    String nome = extractString(block, "nome_plano");
                    String recorrencia = extractString(block, "recorrencia");
                    double valor = 0.0;

                    // Extrair valor (double é chato no regex acima)
                    Pattern pv = Pattern.compile("\"valor\"\\s*:\\s*\"?([\\.0-9]+)\"?");
                    Matcher mv = pv.matcher(block);
                    if (mv.find()) {
                        try {
                            valor = Double.parseDouble(mv.group(1));
                        } catch (Exception e) {
                        }
                    }

                    if (id > 0) {
                        plans.add(new Plan(id, nome, valor, recorrencia));
                    }
                }
            }
        }
        return plans;
    }
}
