<?php
// Arquivo de Debug para verificar Headers e Método HTTP (public/debug.php)
// Acesse: seu-site.com/debug.php

echo "<h1>Debug da Requisição</h1>";
echo "<p><b>Método Recebido (PHP):</b> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><b>URI:</b> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>Headers</h2>";
echo "<pre>";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}
echo "</pre>";

echo "<h2>Server Vars</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
