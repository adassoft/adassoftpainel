package com.adassoft.shield.models;

public class Plan {
    private int id;
    private String nome;
    private double valor;
    private String recorrencia;

    public Plan(int id, String nome, double valor, String recorrencia) {
        this.id = id;
        this.nome = nome;
        this.valor = valor;
        this.recorrencia = recorrencia;
    }

    public int getId() {
        return id;
    }

    public String getNome() {
        return nome;
    }

    public double getValor() {
        return valor;
    }

    public String getRecorrencia() {
        return recorrencia;
    }

    @Override
    public String toString() {
        return String.format("%s - R$ %.2f (%s)", nome, valor, recorrencia);
    }
}
