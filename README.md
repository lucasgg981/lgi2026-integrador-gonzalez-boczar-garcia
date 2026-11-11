# Sistema de eventos y cursos - LGI 2026

Integrantes: Lucas González, Boczar y García.

Primera entrega de González: inicio y cierre de sesión, contraseñas con hash y
acceso a un panel básico según el rol. La gestión de cuentas y las funciones de
eventos, participantes e inscripciones se incorporarán en siguientes entregas.

## Preparar cada localhost

Requisitos: Apache, PHP 8.1 o superior con `pdo_mysql` y MySQL 8.0.16 o superior.

1. Ubicar el repositorio en `/var/www/html/lgi2026-integrador-gonzalez-boczar-garcia`.
2. Importar `database/esquema.sql` en MySQL, por ejemplo desde phpMyAdmin.
   Crea la base `eventos_cursos` y la tabla `usuarios` sin borrar datos existentes.
3. Copiar la configuración de ejemplo:

   ```bash
   cp config/database.example.php config/database.local.php
   ```

4. Completar `config/database.local.php` con un usuario de MySQL que tenga acceso
   a esa base y su contraseña. Este archivo es personal y Git lo ignora.
5. Crear las dos cuentas de prueba desde la carpeta del proyecto:

   ```bash
   php scripts/crear_usuarios_demo.php
   ```

   El script guarda hashes y no modifica cuentas que ya existan. Las siguientes
   credenciales son de demostración para el desarrollo local:

   | Rol | Correo | Contraseña |
   | --- | --- | --- |
   | Administrador | admin@eventos.local | Admin12345! |
   | Empleado | empleado@eventos.local | Empleado12345! |

6. Abrir `http://localhost/lgi2026-integrador-gonzalez-boczar-garcia/`.

Las cuentas de MySQL y las cuentas de acceso al sistema son distintas.
Cada computadora tiene sus propios datos; Git comparte los archivos SQL, no
los registros de la base de datos.

## Cómo funciona el acceso

- `index.php` muestra el formulario, consulta `usuarios` con una sentencia
  preparada y comprueba el hash con `password_verify`.
- `includes/auth.php` maneja la sesión, el token de los formularios y el control
  de rol. La contraseña y su hash no se guardan en la sesión.
- `admin.php` permite el rol `administrador`; `empleado.php`, el rol `empleado`.
- `logout.php` recibe el formulario de cierre y elimina la sesión.
- `includes/database.php` expone `conectar_db()` para obtener la conexión PDO.

Una pantalla protegida debe comprobar los permisos en el servidor antes de
mostrar contenido o procesar una acción. Para una función exclusiva del administrador:

```php
require __DIR__ . '/includes/auth.php';
$usuario = requerir_rol('administrador');
```

Los dos paneles son puntos de entrada. Para una función permitida a ambos roles,
usar `$usuario = requerir_sesion();` en lugar de `requerir_rol`. Los nombres de
archivos del ejemplo suponen que la pantalla está en la raíz del proyecto.

## Reparto y acuerdos de integración

- González: login, logout, usuarios, roles y acceso a los dos paneles.
- Boczar: eventos y consulta de cupos.
- García: participantes, inscripciones y cancelaciones.

La base compartida se llama `eventos_cursos`. El esquema está en
`database/esquema.sql`: por ahora contiene únicamente `usuarios`. Los módulos
siguientes agregarán las tablas `eventos`, `participantes` e `inscripciones`
coordinando antes los cambios del archivo SQL.

`usuarios.id_usuario` es un `INT` con signo. Los campos son `nombre`, `apellido`,
`email` (único), `clave_hash` y `rol`. Los valores de `rol` son `administrador`
y `empleado`. La sesión expone esos datos en `$_SESSION['usuario']`, salvo el hash.
Los participantes no tienen cuentas de acceso.

## GitHub Flow

Cada integrante trabaja en su copia local y en una rama de tarea. Esta primera
parte usa `feature/gonzalez-autenticacion-usuarios-roles`. Los cambios se publican
en esa rama y se integran a `main` mediante un Pull Request revisado por otro
integrante. Las siguientes tareas parten de `main` actualizado.
