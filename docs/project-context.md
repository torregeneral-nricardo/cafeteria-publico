### **PROMPT DE CONTEXTO: Sistema de Gestión para Cafetería "Torre General"**

**🔹 OBJETIVO GENERAL**
Desarrollar un sistema web integral para una cafetería que incluya:

1. Un **punto de venta (POS)** robusto para la caja.
2. Un **dashboard administrativo** con monitoreo en tiempo real de ventas e inventario.
3. Gestión de usuarios con roles (administrador, cajero, gerente).
4. Plataforma escalable y de código personalizado.

**🔹 ESTADO ACTUAL (Punto de Partida)**

- **Repositorio Base:** `https://github.com/torregeneral-nricardo/cafeteria` (Privado) y una copia pública temporal para revisiones (`cafeteria-publico`).
- **Código Existente:** Se dispone de una **base de código PHP con arquitectura MVC personalizada**, desarrollada rápidamente como propuesta inicial. Incluye:
  - Módulo de **Autenticación completo** (login, registro, recuperación de contraseña).
  - **Sistema de roles de usuario** (lógica en `models/Rol.php`, `controllers/RolController.php`).
  - **Dashboard básico** (`views/dashboard.php`).
  - Estructura de base de datos documentada en `docs/torreGeneral_DB.sql`.
- **Stack Tecnológico Confirmado:** **PHP nativo con MVC personalizado**, MySQL, JavaScript vanilla, Bootstrap (inferido de los assets). Se descartó Node.js/React tras analizar el código existente.
- **Infraestructura:** Hosting con capacidad para migrar a VPS. Equipo de desarrollo: 2 programadores Full-Stack con disposición a aprender.

**🔹 DECISIONES TÉCNICAS Y LOGROS**

1. **Prototipo Inicial:** Se desarrolló con éxito un **prototipo funcional en PHP MVC** para demostrar capacidades de forma rápida. Este código sirve como referencia, pero **no constituye la decisión final del stack tecnológico para el sistema de producción**.
2. **Stack por Definir:** La tecnología final (ej: Node.js/React, PHP/Laravel, Python/Django, etc.) está **pendiente de evaluación y decisión** por parte del equipo de desarrollo y el cliente. La arquitectura, el plan de sprints y los diagramas deben ser **agnósticos o adaptables** hasta que se tome esta decisión.
3. **Gestión de Código:** El proyecto está versionado con Git. Se superaron exitosamente los desafíos iniciales de configuración de repositorios, autenticación con tokens y exclusión de archivos sensibles.
4. **Seguridad:** Se estableció la práctica de no subir credenciales al repositorio. Se utilizan archivos `.gitignore` para excluir `*.env`, `config/db.php`, `*.log` y dumps de BD.
5. **Arquitectura:** Se validó y adoptó la estructura MVC existente (`controllers/`, `models/`, `views/`) como base para el desarrollo futuro. Es sólida y escalable.
6. **Próxima Meta Funcional:** El foco inmediato es **evolucionar el "landing page" actual (`index.php`) hacia el portal funcional del sistema**, mejorando los módulos de autenticación y registro antes de pasar a las funcionalidades core de la cafetería.

**🔹 PRÓXIMOS PASOS PRIORITARIOS (Roadmap Inmediato)**

1. **Refinar Módulo de Autenticación:** Consolidar y asegurar el login, registro y recuperación de contraseña.
2. **Diseñar el Flujo Principal:** Transformar las vistas existentes en un dashboard útil y una interfaz de POS simplificada.
3. **Modelar Entidades de Negocio:** Extender la base de datos y los modelos PHP para incluir `Producto`, `Categoría`, `Venta`, `DetalleVenta`.
4. **Implementar Monitoreo en Tiempo Real:** Investigar e integrar una solución (ej: WebSockets con Ratchet/Pusher, o polling largo) para el dashboard de ventas en vivo.

**🔹 CONTEXTO OPERATIVO**

- **Cliente:** Comprometido, con urgencia real pero con expectativas ajustadas a un plan realista.
- **Equipo:** Motivado, con iniciativa demostrada (creación de la base de código en 24 horas). El gerente del proyecto (tú) está aprendiendo Git y la gestión técnica en paralelo.
- **Filosofía de Desarrollo:** "Aprender haciendo", con sprints cortos que entreguen valor tangible rápidamente, partiendo siempre del código funcional existente.

**🔹 PARA RETOMAR EL TRABAJO, PREGUNTAR SIEMPRE:**
"Basándonos en el estado actual del código PHP MVC en el repositorio, ¿cuál es la acción más pequeña y valiosa para avanzar hacia el objetivo del sistema de cafetería, priorizando la robustez del módulo de autenticación y la claridad del código?"

------

**¿Cómo usar este prompt?**

1. **Guárdalo** en un lugar accesible (como el archivo `README.md` de tu repositorio privado).
2. Al iniciar una nueva sesión de trabajo o al dar instrucciones a un colaborador o IA, **proporciona este texto como contexto inicial**.
3. Para tareas específicas, puedes añadir: "**Contexto Actual:** [Pegar el prompt de arriba]. **Tarea Actual:** Necesito [descripción de la tarea concreta, ej.: 'mejorar la validación del formulario de registro en `views/registro.php`']."

**Mi recomendación final:** Antes de codificar, crea un **`docs/`** en tu repo privado y guarda ahí este prompt y cualquier decisión de diseño futura. ¿Te gustaría que adaptemos este prompt para enfocarlo en una tarea de desarrollo específica que tengas ahora?