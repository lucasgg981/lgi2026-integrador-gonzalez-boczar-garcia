# Sistema de eventos y cursos - LGI 2026

Integrantes: Lucas González, Boczar y García.

Incluye inicio y cierre de sesión, contraseñas con hash, paneles por rol, el
módulo de eventos y consulta de cupos, la gestión de participantes y las
inscripciones y cancelaciones, y la gestión de cuentas del personal.

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
   Si la base ya existía, ejecutar `php scripts/actualizar_usuarios.php` con
   permiso `ALTER`. El script permite repetir la actualización y conserva los
   datos. Si la cuenta de la aplicación carece de ese permiso, un administrador
   de MySQL puede aplicar `database/registro_publico.sql` una vez sobre la base
   del MVP. En instalaciones anteriores que todavía carezcan de `activo` y
   `version_sesion`, aplicar primero `database/actualizar_usuarios.sql`.
5. Cargar los datos de demostración desde la carpeta del proyecto:

   ```bash
   php scripts/sembrar_datos_demo.php
   ```

   El script guarda hashes y crea las cuentas de prueba, cuatro eventos, seis
   participantes y algunas inscripciones. Es idempotente: no duplica registros
   ni modifica los que ya existan. Las credenciales de demostración son:

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
- `registro.php` crea una cuenta pública con rol `usuario`, vinculada a un
  participante nuevo. El registro admite correos de hasta 150 caracteres para
  conservarlos también en el perfil de participante. `mi-cuenta.php` es el
  destino de inicio de sesión para este rol.
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

En las pantallas para ambos roles del personal, usar
`$usuario = requerir_rol('administrador', 'empleado');`. En una pantalla de la
cuenta pública, usar `$usuario = requerir_rol('usuario');` y obtener el ID del
participante desde `$usuario['id_participante']`. Los nombres de archivos del
ejemplo suponen que la pantalla está en la raíz del proyecto.

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

## Usuarios del personal

- Solo el administrador puede listar, crear y editar cuentas desde su panel.
- El correo es único y los roles son `administrador` y `empleado`.
- Se puede cambiar la contraseña desde la edición; dejarla vacía conserva la actual.
- Las contraseñas se guardan con `password_hash` y nunca se muestran.
- La desactivación conserva las inscripciones y evita nuevos ingresos.
- No se permite quitarse el propio acceso ni dejar al sistema sin un administrador activo.
- Guardar una edición invalida las sesiones anteriores de esa cuenta. Si editás
  tu propia cuenta, deberás volver a iniciar sesión.
- Las sesiones creadas antes de actualizar este módulo requieren iniciar sesión otra vez.

## Participantes

- Ambos roles pueden consultar `participantes/index.php`, buscar por nombre,
  apellido, correo o teléfono y registrar o modificar participantes.
- Solo el administrador puede eliminar participantes, y únicamente si no tienen
  inscripciones registradas, activas o canceladas.
- El correo y el teléfono son opcionales. La validación está en
  `includes/participantes.php` y el formulario compartido en
  `includes/formulario_participante.php`.
- Los formularios usan token CSRF y consultas preparadas PDO. El listado muestra
  las inscripciones activas de cada participante.
- Los participantes cargados manualmente pueden seguir operándose sin una
  cuenta. El registro público crea su propio participante y no vincula registros
  anteriores por correo. Si un participante tiene una cuenta vinculada, se
  conserva su registro.

## Inscripciones y cancelaciones

- Ambos roles pueden consultar `inscripciones/index.php` e inscribir participantes.
- Solo el administrador puede cancelar inscripciones.
- El formulario muestra los cupos disponibles de cada evento y deshabilita los
  que están completos con la marca `[COMPLETO]`.
- La inscripción se crea en estado `activa`, descuenta un cupo del evento y no
  puede duplicarse para el mismo participante y evento.
- El alta usa una transacción PDO y bloquea la fila del evento con
  `SELECT ... FOR UPDATE` antes de verificar el cupo, evitando sobreinscripciones
  si dos personas intentan inscribirse al mismo tiempo. Si falla, se revierte la
  transacción con `rollBack()`.
- No se inscribe si el evento no tiene cupos disponibles o si ya cerró el plazo.
- La cancelación cambia el estado a `cancelada` y libera el cupo; no borra el
  registro, por lo que los eventos y participantes con inscripciones (activas o
  canceladas) no pueden eliminarse.
- Los formularios usan token CSRF y consultas preparadas PDO.

## Solicitudes de cupo del usuario

- El catálogo enlaza a `inscripciones/solicitar.php` solo para el rol `usuario`.
  El personal sigue inscribiendo desde `inscripciones/crear.php`.
- `solicitar.php` toma el participante y el usuario de la sesión, vuelve a
  consultar el evento y reutiliza `registrar_inscripcion()` de
  `includes/inscripciones.php`, con la misma transacción y el bloqueo
  `SELECT ... FOR UPDATE` del alta manual.
- Se rechazan los eventos inexistentes, sin cupos, con el plazo cerrado y las
  solicitudes duplicadas. Si dos personas piden el último cupo a la vez, solo una
  confirmación se guarda.
- `inscripciones/mis-inscripciones.php` muestra únicamente las inscripciones del
  participante vinculado a la cuenta, con el estado `Confirmada` o `Cancelada`.
  `mi-cuenta.php` enlaza al catálogo y a las inscripciones propias.
- El autor de una autoinscripción se guarda en `inscripciones.id_usuario` con la
  cuenta pública que la realizó. Las altas manuales conservan el usuario del
  personal, así que la trazabilidad por operador no se pierde.
- Las pantallas usan token CSRF y consultas preparadas PDO.

## Verificación

Desde la raíz del proyecto, ejecutar:

```bash
php tests/usuarios.php
php tests/eventos.php
php tests/participantes.php
php tests/inscripciones.php
```

Estas pruebas verifican validación de eventos y participantes, restricciones por
rol, CSRF, eliminación mediante POST, escape de HTML y rutas de sesión desde
subcarpetas. Requieren MySQL con el esquema actualizado y permiso `CREATE TEMPORARY TABLES`.
Usan cuentas en tablas temporales sin modificar los usuarios reales. La prueba
del último cupo y la de autoinscripción crean un evento, participantes y una
inscripción temporales en las tablas reales, y los eliminan al terminar. Las
operaciones completas de alta, consulta, edición y eliminación deben probarse
además con la base local configurada y el esquema importado.

## Reparto y acuerdos de integración

- González: login, logout, usuarios, roles y acceso a los dos paneles.
- Boczar: eventos y consulta de cupos.
- García: participantes, inscripciones y cancelaciones.

La base compartida se llama `eventos_cursos`. El esquema está en
`database/esquema.sql`. Incluye las tablas necesarias para el módulo de eventos;
la gestión de participantes e inscripciones queda a cargo de García. Coordinar
antes cualquier cambio de columnas o relaciones del archivo SQL.

`usuarios.id_usuario` es un `INT` con signo. Los campos son `nombre`, `apellido`,
`email` (único), `clave_hash` y `rol`. Los valores de `rol` son `administrador`,
`empleado` y `usuario`. La sesión conserva el identificador de participante para
las cuentas públicas. El hash de la contraseña queda en la base de datos.

## GitHub Flow

Cada integrante trabaja en su copia local y en una rama de tarea desde `main`
actualizado. El módulo de eventos se trabaja en `feature/boczar-eventos-cupos`.
Los cambios se integran a `main` mediante un Pull Request revisado por otro
integrante. Las siguientes tareas parten nuevamente de `main` actualizado.
