Árbol de directorios y archivos
|   index.php
|   
+---assets
|   +---css
|   |   |   all.min.css
|   |   |   style.css
|   |   |   
|   |   +---mobile
|   |   |       base.css
|   |   |       
|   |   \---tablet
|   |           base.css
|   |           
|   +---img
|   |       dibujo-edificio-GENERAL-1000x960.jpg
|   |       torre_general_logo_290x290.png
|   |       
|   \---js
|       |   auth.js
|       |   main.js
|       |   
|       \---mobile
|               main.js
|               
+---config
|       arbol.md
|       db.php
|       torregen_main.sql
|       
+---controllers
|       AuthController.php
|       RolController.php
|       SubController.php
|       UsuarioController.php
|       
+---includes
|       auditoria.php
|       conexion.php
|       footer.php
|       header.php
|       helpers.php
|       navbar.php
|       seguridad.php
|       
+---models
|       Permisos.php
|       Rol.php
|       Suscripcion.php
|       Usuario.php
|       
\---views
    \---dashboard
        |   index.php
        |   
        +---admin
        |       index.php
        |       
        +---cajero
        |   |   index.php
        |   |   
        |   +---caja
        |   |       abrir.php
        |   |       cerrar.php
        |   |       
        |   +---clientes
        |   |       consulta.php
        |   |       test_conexion.php
        |   |       
        |   +---inventario
        |   |       consulta.php
        |   |       
        |   \---ventas
        |           detalle_venta.php
        |           historial.php
        |           nueva.php
        |           ticket.php
        |           
        +---cajero-mobile
        |   |   index.php
        |   |   test-dashboard.html
        |   |   
        |   +---components
        |   +---css
        |   |       mobile.css
        |   |       tablet.css
        |   |       
        |   +---js
        |   \---layouts
        |           mobile.php
        |           
        +---gerente
        |       index.php
        |       
        +---inquilino
        |       index.php
        |       
        +---personal-obras
        |       index.php
        |       
        +---seguridad
        |       index.php
        |       
        \---visitante
                index.php
                
