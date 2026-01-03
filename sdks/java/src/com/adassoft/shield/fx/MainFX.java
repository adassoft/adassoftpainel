package com.adassoft.shield.fx;

import com.adassoft.shield.ShieldClient;
import com.adassoft.shield.ShieldConfig;
import javafx.application.Application;
import javafx.scene.Scene;
import javafx.scene.control.Button;
import javafx.scene.layout.StackPane;
import javafx.stage.Stage;

/**
 * Exemplo de integração JavaFX.
 * Requer OpenJFX configurado no classpath/modulepath.
 */
public class MainFX extends Application {

    @Override
    public void start(Stage primaryStage) {
        // Simula sua aplicação principal
        Button btnLaunch = new Button("Abrir Registro Shield (Simular Bloqueio)");
        btnLaunch.setOnAction(e -> showShield());

        StackPane root = new StackPane(btnLaunch);
        Scene scene = new Scene(root, 300, 250);

        primaryStage.setTitle("Minha App JavaFX");
        primaryStage.setScene(scene);
        primaryStage.show();

        // Em uma app real, você checaria a licença no inicio:
        // checkLicenseAtStartup();
    }

    private void showShield() {
        // Configuração do Shield
        ShieldConfig config = new ShieldConfig("123456", 1, "1.0.0");
        ShieldClient client = new ShieldClient("http://localhost/shield", config);

        // Abrir form
        ShieldForm form = new ShieldForm(client, "");
        form.showAndWait();
    }

    public static void main(String[] args) {
        launch(args);
    }
}
