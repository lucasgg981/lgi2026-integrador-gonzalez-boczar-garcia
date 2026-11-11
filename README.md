# Sistema de eventos y cursos - LGI 2026

Integrantes: Lucas González, Boczar y García.

Incluye inicio y cierre de sesión, contraseñas con hash, paneles por rol y el
módulo de eventos y consulta de cupos. La gestión de cuentas, participantes e
inscripciones se incorporará en siguientes entregas.

## Preparar cada localhost

Requisitos: Apache, PHP 8.1 o superior con `pdo_mysql` y MySQL 8.0.16 o superior.

1. Ubicar el repositorio en `/var/www/html/lgi2026-integrador-gonzalez-boczar-garcia`.
2. Importar `database/esquema.sql` en MySQL, por ejemplo desde phpMyAdmin.
   Crea la base `eventos_cursos` y las tablas `usuarios`, `eventos`,
   `participantes` e `inscripciones` sin borrar datos existentes.
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

El módulo `eventos/` usa esa misma conexión PDO y la configuración de
`config/database.local.php`. No se usa `conexion.php` ni `config.local.php`.
Desde una pantalla dentro de `eventos/`, obtener la conexión así:

```php
require_once __DIR__ . '/../includes/database.php';
$conexion = conectar_db();
```

Las consultas usan `fetch()` y las sentencias preparadas reciben sus parámetros
con `execute([...])`. Todos los módulos usan la misma base `eventos_cursos` y el
único esquema de instalación es `database/esquema.sql`.

Si ya se había importado `basededatos.sql` en la base `sistema_eventos`, sus datos
no se trasladan automáticamente. Se debe coordinar su migración a `eventos_cursos`
antes de continuar con esos registros; no importar el esquema anterior.

Una pantalla protegida debe comprobar los permisos en el servidor antes de
mostrar contenido o procesar una acción. Para una función exclusiva del administrador:

```php
require __DIR__ . '/includes/auth.php';
$usuario = requerir_rol('administrador');
```

Los dos paneles son puntos de entrada. Para una función permitida a ambos roles,
usar `$usuario = requerir_sesion();` en lugar de `requerir_rol`. Los nombres de
archivos del ejemplo suponen que la pantalla está en la raíz del proyecto.

`ruta_aplicacion()` genera rutas desde la raíz de la aplicación. La cookie de
sesión usa esa misma raíz, también cuando se accede desde `eventos/`.

## Eventos y cupos

- Ambos roles pueden consultar `eventos/index.php` desde sus paneles.
- Solo el administrador puede crear, editar o eliminar eventos.
- Los formularios reutilizan `includes/formulario_evento.php` y las validaciones
  de `includes/eventos.php`, con token CSRF y consultas preparadas PDO.
- La eliminación solo acepta POST con token válido y se impide si el evento
  tiene inscripciones, incluso canceladas.
- Los cupos disponibles se calculan restando las inscripciones de estado `activa`.
- Las pantallas reutilizan `assets/styles.css` y muestran mensajes generales de
  error; el detalle técnico se registra en el log de PHP.

## Verificación

Desde la raíz del proyecto, ejecutar:

```bash
php tests/eventos.php
```

Estas pruebas verifican validación de eventos, restricciones por rol, CSRF,
eliminación mediante POST, escape de HTML y rutas de sesión desde subcarpetas.
No requieren conexión a MySQL ni modifican registros. Las operaciones completas
de alta, consulta, edición y eliminación deben probarse además con la base local
configurada y el esquema importado.

## Reparto y acuerdos de integración

- González: login, logout, usuarios, roles y acceso a los dos paneles.
- Boczar: eventos y consulta de cupos.
- García: participantes, inscripciones y cancelaciones.

La base compartida se llama `eventos_cursos`. El esquema está en
`database/esquema.sql`. Incluye las tablas necesarias para el módulo de eventos;
la gestión de participantes e inscripciones queda a cargo de García. Coordinar
antes cualquier cambio de columnas o relaciones del archivo SQL.

`usuarios.id_usuario` es un `INT` con signo. Los campos son `nombre`, `apellido`,
`email` (único), `clave_hash` y `rol`. Los valores de `rol` son `administrador`
y `empleado`. La sesión expone esos datos en `$_SESSION['usuario']`, salvo el hash.
Los participantes no tienen cuentas de acceso.

## GitHub Flow

Cada integrante trabaja en su copia local y en una rama de tarea desde `main`
actualizado. El módulo de eventos se trabaja en `feature/boczar-eventos-cupos`.
Los cambios se integran a `main` mediante un Pull Request revisado por otro
integrante. Las siguientes tareas parten nuevamente de `main` actualizado.
