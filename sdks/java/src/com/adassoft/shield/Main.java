package com.adassoft.shield;

import com.adassoft.shield.models.LicenseInfo;
import com.adassoft.shield.models.Plan;
import java.util.List;
import java.util.Scanner;

public class Main {
    public static void main(String[] args) {
        System.out.println("=== Shield SDK Java Demo ===");

        // 1. Configuração
        String apiUrl = "http://localhost/shield";
        String apiKey = "123456";
        int softwareId = 1;
        String version = "1.0.0";

        ShieldConfig config = new ShieldConfig(apiKey, softwareId, version);
        ShieldClient client = new ShieldClient(apiUrl, config);

        // 2. Simular Input do Usuário com try-with-resources para fechar Scanner
        try (Scanner scanner = new Scanner(System.in)) {
            System.out.print("Digite o Serial da Licença: ");
            String serial = scanner.nextLine().trim();

            if (serial.isEmpty()) {
                System.out.println("Serial vazio. Saindo.");
                return;
            }

            System.out.println("\nValidando licença...");
            LicenseInfo info = client.checkLicense(serial);

            // 3. Exibir Resultados
            System.out.println("--- Resultado ---");
            System.out.println("Status: " + info.getStatus());
            System.out.println("Mensagem: " + info.getMensagem());

            if (info.isValid()) {
                System.out.println("Expira em: " + info.getDataExpiracao());
                System.out.println("Dias Restantes: " + info.getDiasRestantes());
                System.out.println("Terminais: " + info.getTerminaisUtilizados() + "/" + info.getTerminaisPermitidos());

                if (info.shouldWarnExpiration()) {
                    System.out.println("\n[ALERTA] Sua licença vai vencer em breve! Renove agora.");
                }
            } else {
                System.out.println("Licença inválida ou expirada.");
            }

            // 4. Listar Planos (Renovação)
            System.out.println("\n--- Planos de Renovação Disponíveis ---");
            List<Plan> plans = client.getAvailablePlans(info);

            if (plans.isEmpty()) {
                System.out.println("Nenhum plano encontrado ou erro ao buscar.");
            } else {
                for (Plan p : plans) {
                    System.out.println("ID: " + p.getId() + " | " + p.getNome() + " - R$ " + p.getValor());
                }

                System.out.print("\nDigite o ID do plano para gerar link de pagamento (0 para sair): ");
                try {
                    String line = scanner.nextLine();
                    int planId = Integer.parseInt(line);
                    if (planId > 0) {
                        String url = client.createCheckoutUrl(planId, serial);
                        if (!url.isEmpty()) {
                            System.out.println("Link de Pagamento gerado: " + url);
                        } else {
                            System.out.println("Erro ao gerar link.");
                        }
                    }
                } catch (Exception e) {
                    // ignore
                }
            }
        } // Scanner fecha automaticamente aqui
    }
}
