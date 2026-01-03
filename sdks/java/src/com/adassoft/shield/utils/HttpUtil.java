package com.adassoft.shield.utils;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;

public class HttpUtil {

    private static final HttpClient client = HttpClient.newBuilder()
            .version(HttpClient.Version.HTTP_1_1)
            .connectTimeout(Duration.ofSeconds(10))
            .build();

    public static String post(String url, String jsonBody, String bearerToken) throws Exception {
        HttpRequest.Builder builder = HttpRequest.newBuilder()
                .uri(URI.create(url))
                .header("Content-Type", "application/json")
                .timeout(Duration.ofSeconds(20))
                .POST(HttpRequest.BodyPublishers.ofString(jsonBody));

        if (bearerToken != null && !bearerToken.isEmpty()) {
            builder.header("Authorization", "Bearer " + bearerToken);
        }

        HttpResponse<String> response = client.send(builder.build(), HttpResponse.BodyHandlers.ofString());

        if (response.statusCode() >= 200 && response.statusCode() < 300) {
            return response.body();
        } else {
            throw new Exception("HTTP Error " + response.statusCode() + ": " + response.body());
        }
    }

    public static String get(String url, String bearerToken) throws Exception {
        HttpRequest.Builder builder = HttpRequest.newBuilder()
                .uri(URI.create(url))
                .header("Accept", "application/json")
                .timeout(Duration.ofSeconds(20))
                .GET();

        if (bearerToken != null && !bearerToken.isEmpty()) {
            builder.header("Authorization", "Bearer " + bearerToken);
        }

        HttpResponse<String> response = client.send(builder.build(), HttpResponse.BodyHandlers.ofString());

        if (response.statusCode() >= 200 && response.statusCode() < 300) {
            return response.body();
        } else {
            throw new Exception("HTTP Error " + response.statusCode() + ": " + response.body());
        }
    }
}
