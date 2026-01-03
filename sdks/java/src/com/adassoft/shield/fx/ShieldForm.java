package com.adassoft.shield.fx;

import com.adassoft.shield.ShieldClient;
import com.adassoft.shield.models.LicenseInfo;
import com.adassoft.shield.models.Plan;

import javafx.application.Platform;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.*;
import javafx.scene.paint.Color;
import javafx.scene.text.Font;
import javafx.scene.text.FontWeight;
import javafx.stage.Modality;
import javafx.stage.Stage;
import javafx.stage.StageStyle;

import java.awt.Desktop;
import java.net.URI;
import java.util.List;

/**
 * Interface Gráfica de Registro para JavaFX.
 * Estilo moderno e minimalista.
 */
public class ShieldForm extends Stage {

    private final ShieldClient client;
    private final TextField txtSerial;
    private final Label lblStatus;
    private final Label lblDetail;
    private final ProgressBar progressDays;
    private final VBox plansContainer;
    private final Button btnAction;

    private LicenseInfo currentLicense;

    public ShieldForm(ShieldClient client, String currentSerial) {
        this.client = client;

        // Configuração da Janela
        this.initModality(Modality.APPLICATION_MODAL);
        this.initStyle(StageStyle.UTILITY);
        this.setTitle("Licenciamento de Software");
        this.setResizable(false);
        this.setWidth(450);
        this.setHeight(600);

        // --- Layout Principal ---
        VBox root = new VBox(15);
        root.setPadding(new Insets(20));
        root.setStyle("-fx-background-color: #f4f6f8; -fx-font-family: 'Segoe UI', sans-serif;");
        root.setAlignment(Pos.TOP_CENTER);

        // 1. Cabeçalho
        Label title = new Label("Ativação do Produto");
        title.setFont(Font.font("Segoe UI", FontWeight.BOLD, 22));
        title.setTextFill(Color.web("#2c3e50"));

        Label subtitle = new Label("Insira seu serial para liberar o acesso.");
        subtitle.setTextFill(Color.web("#7f8c8d"));

        // 2. Área do Serial
        VBox boxSerial = new VBox(5);
        boxSerial.setAlignment(Pos.CENTER_LEFT);
        Label lblInput = new Label("Chave de Licença (Serial):");
        lblInput.setFont(Font.font(12));

        txtSerial = new TextField(currentSerial);
        txtSerial.setPromptText("AAAA-BBBB-CCCC-DDDD");
        txtSerial.setStyle(
                "-fx-padding: 10; -fx-background-radius: 5; -fx-border-color: #bdc3c7; -fx-border-radius: 5;");
        txtSerial.setFont(Font.font("Monospaced", 14));

        boxSerial.getChildren().addAll(lblInput, txtSerial);

        // 3. Área de Status
        VBox boxStatus = new VBox(10);
        boxStatus.setStyle(
                "-fx-background-color: white; -fx-padding: 15; -fx-background-radius: 8; -fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.1), 5, 0, 0, 2);");
        boxStatus.setAlignment(Pos.CENTER);

        lblStatus = new Label("Aguardando validação...");
        lblStatus.setFont(Font.font("Segoe UI", FontWeight.BOLD, 14));

        lblDetail = new Label("");
        lblDetail.setWrapText(true);
        lblDetail.setTextAlignment(javafx.scene.text.TextAlignment.CENTER);

        progressDays = new ProgressBar(0);
        progressDays.setMaxWidth(Double.MAX_VALUE);
        progressDays.setStyle("-fx-accent: #27ae60;"); // Verde base

        boxStatus.getChildren().addAll(lblStatus, progressDays, lblDetail);

        // 4. Botão Principal
        btnAction = new Button("Validar Licença");
        btnAction.setStyle(
                "-fx-background-color: #3498db; -fx-text-fill: white; -fx-font-weight: bold; -fx-padding: 10 20; -fx-cursor: hand; -fx-background-radius: 5;");
        btnAction.setMaxWidth(Double.MAX_VALUE);
        btnAction.setOnAction(e -> handleValidation());

        // 5. Container de Planos (Invisível por padrão)
        plansContainer = new VBox(10);
        plansContainer.setAlignment(Pos.CENTER);
        plansContainer.setVisible(false);

        Label lblPlans = new Label("Renovar Assinatura:");
        lblPlans.setFont(Font.font(12));

        plansContainer.getChildren().add(lblPlans);

        // Montagem Final
        root.getChildren().addAll(title, subtitle, new Separator(), boxSerial, btnAction, boxStatus, plansContainer);

        Scene scene = new Scene(root);
        this.setScene(scene);

        // Auto-validar se já tiver serial
        if (currentSerial != null && !currentSerial.isEmpty()) {
            Platform.runLater(this::handleValidation);
        }
    }

    private void handleValidation() {
        String serial = txtSerial.getText().trim();
        if (serial.isEmpty()) {
            showAlert("Erro", "Por favor, informe o serial.");
            return;
        }

        disableControls(true);
        lblStatus.setText("Verificando...");
        lblStatus.setTextFill(Color.GRAY);

        // Thread separada para não travar UI
        new Thread(() -> {
            LicenseInfo info = client.checkLicense(serial);
            this.currentLicense = info;

            Platform.runLater(() -> {
                updateUI(info);
                disableControls(false);
            });
        }).start();
    }

    private void updateUI(LicenseInfo info) {
        lblDetail.setText(info.getMensagem());

        if (info.isValid()) {
            // Sucesso
            lblStatus.setText("LICENÇA ATIVA");
            lblStatus.setTextFill(Color.web("#27ae60")); // Green
            txtSerial.setStyle(
                    "-fx-padding: 10; -fx-background-radius: 5; -fx-border-color: #27ae60; -fx-border-radius: 5;");

            // Barra de Progresso
            if (info.getDataExpiracao() != null) {
                // Lógica de progresso visual (assumindo ciclo de 30 dias para visualização ou
                // 365)
                double progress = 1.0;
                if (info.getDiasRestantes() < 30) {
                    progress = info.getDiasRestantes() / 30.0;
                }
                progressDays.setProgress(progress);

                String warning = "";
                if (info.shouldWarnExpiration()) {
                    warning = "\n⚠️ Renove agora para evitar bloqueio!";
                    progressDays.setStyle("-fx-accent: #f1c40f;"); // Yellow warning
                    loadPlans(); // Carregar planos se estiver vencendo
                } else {
                    progressDays.setStyle("-fx-accent: #27ae60;");
                }

                lblDetail.setText("Válido até: " + info.getDataExpiracao().toLocalDate() +
                        "\nDias Restantes: " + info.getDiasRestantes() + warning);
            }

            btnAction.setText("Licença Verificada");
            btnAction.setStyle(
                    "-fx-background-color: #2ecc71; -fx-text-fill: white; -fx-font-weight: bold; -fx-padding: 10 20; -fx-background-radius: 5;");

        } else {
            // Erro
            lblStatus.setText("SERIAL INVÁLIDO / EXPIRADO");
            lblStatus.setTextFill(Color.web("#e74c3c")); // Red
            progressDays.setProgress(0);
            txtSerial.setStyle(
                    "-fx-padding: 10; -fx-background-radius: 5; -fx-border-color: #e74c3c; -fx-border-radius: 5;");

            btnAction.setText("Tentar Novamente");
            btnAction.setStyle(
                    "-fx-background-color: #3498db; -fx-text-fill: white; -fx-font-weight: bold; -fx-padding: 10 20; -fx-background-radius: 5;");

            loadPlans(); // Carregar planos para comprar
        }
    }

    private void loadPlans() {
        plansContainer.setVisible(true);
        // Limpar planos antigos exceto label
        if (plansContainer.getChildren().size() > 1) {
            plansContainer.getChildren().remove(1, plansContainer.getChildren().size());
        }

        Label loading = new Label("Carregando planos...");
        plansContainer.getChildren().add(loading);

        new Thread(() -> {
            List<Plan> plans = client.getAvailablePlans(currentLicense);

            Platform.runLater(() -> {
                plansContainer.getChildren().remove(loading);
                if (plans.isEmpty()) {
                    plansContainer.getChildren().add(new Label("Nenhum plano disponível no momento."));
                    return;
                }

                for (Plan p : plans) {
                    Button btnPlan = new Button(p.getNome() + " - R$ " + String.format("%.2f", p.getValor()));
                    btnPlan.setMaxWidth(Double.MAX_VALUE);
                    btnPlan.setStyle(
                            "-fx-background-color: white; -fx-border-color: #3498db; -fx-text-fill: #3498db; -fx-cursor: hand;");
                    btnPlan.setOnAction(e -> openCheckout(p.getId()));
                    plansContainer.getChildren().add(btnPlan);
                }
            });
        }).start();
    }

    private void openCheckout(int planId) {
        new Thread(() -> {
            String url = client.createCheckoutUrl(planId, txtSerial.getText());
            if (url != null && !url.isEmpty()) {
                Platform.runLater(() -> {
                    try {
                        Desktop.getDesktop().browse(new URI(url));
                    } catch (Exception ex) {
                        showAlert("Link Gerado", "Copie o link: " + url);
                    }
                });
            }
        }).start();
    }

    private void disableControls(boolean disable) {
        txtSerial.setDisable(disable);
        btnAction.setDisable(disable);
    }

    private void showAlert(String title, String msg) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(msg);
        alert.showAndWait();
    }
}
