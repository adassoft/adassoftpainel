@echo off
cd /d "c:\xampp\htdocs\adassoft"
php artisan queue:work --stop-when-empty --tries=3
:: O comando acima processa tudo que estiver na fila e fecha quando acabar.
