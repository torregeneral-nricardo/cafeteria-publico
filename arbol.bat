@echo off
:: Crear carpetas principales
mkdir config assets\css assets\js assets\img includes models controllers views

:: Crear archivos base vacios
type nul > .htaccess
type nul > config\db.php
type nul > assets\css\style.css
type nul > assets\js\main.js
type nul > assets\js\auth.js
type nul > includes\header.php
type nul > includes\footer.php
type nul > includes\navbar.php
type nul > models\Usuario.php
type nul > models\Suscripcion.php
type nul > controllers\AuthController.php
type nul > controllers\SubController.php
type nul > controllers\RolController.php
type nul > views\login.php
type nul > views\registro.php
type nul > views\recuperar.php
type nul > views\admin_roles.php
type nul > index.php

echo Estructura de archivos generada con exito.
pause