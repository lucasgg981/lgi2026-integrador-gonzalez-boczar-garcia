# Plan de releases del sistema de eventos y cursos

## Acuerdos de alcance

- **Lucas González** (`@lucasgg981`) coordina la integración y mantiene las cuentas, los roles y los releases.
- **Tobías Boczar** (`@rediografia9874`) mantiene eventos, cupos y la agenda de lugares.
- **Lautaro García** (`@lauho`) mantiene participantes y el flujo de solicitud de cupos.
- El catálogo será público. Una cuenta con rol `usuario` podrá solicitar un cupo y consultar sus propias solicitudes. Los roles de personal conservan sus permisos actuales.
- Propuesta para esta entrega. «Pedir cupo» confirma la inscripción si hay lugar y el plazo está abierto. El sistema informa el resultado en el momento. Un circuito de aprobación manual queda en el backlog.
- Los **cupos** indican cuántas personas pueden inscribirse. La **ocupación de un lugar** indica si el mismo espacio ya se usa en un horario que se superpone.

## Calendario propuesto

La consigna menciona el 26/11 en la sección de sprints y el 01/12 en la sección de entregables. Este plan usa el 26/11 como corte inmediato y el 01/12 como fecha final provisional, pendiente de confirmación docente.

| Sprint | Fecha propuesta | Objetivo y release |
| --- | --- | --- |
| 0 | 26/11 | Confirmar y publicar el MVP `v1.0.0`. Dejar abierto el backlog y enviar el plan a revisión. |
| 1 | 27–28/11 | Publicar `v1.1.0` con el recorrido completo de catálogo público, registro e inscripción. Completar la documentación obligatoria en este release. |
| 2 | 29/11–01/12 | Publicar `v1.2.0` con actualización de cupos, control de lugares y mejoras de uso. Si no entran ambos frentes, entregar `v1.1.0` completa y mover lo pendiente al backlog. |

## Backlog priorizado

Cada fila enlaza a su Issue de GitHub. El Issue incluye los criterios y un bloque listo para copiar en el asistente de cada integrante. Para cada Issue de desarrollo, el responsable crea la rama a partir de `main`, hace sus propios commits y abre un PR con `Closes #N`. Otro integrante revisa el PR antes del merge. P1 está completado. El estado de las demás tareas se actualiza en GitHub.

| ID | Tarea | Tipo | Prioridad | Esfuerzo | Responsable | Sprint | Rama |
| --- | --- | --- | --- | --- | --- | --- | --- |
| [P1 · #8](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/8) | Congelar y publicar el MVP | docs | Alta | S | Lucas | 0 | `docs/release-mvp` si hace falta preparar artefactos |
| [P2 · #9](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/9) | Preparar y revisar el plan de sprints | docs | Alta | S | Lucas | 0 | `docs/plan-releases` |
| [P3 · #10](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/10) | Registro público y rol `usuario` | nuevo-feature | Alta | M | Lucas | 1 | `feature/registro-usuarios` |
| [P4 · #11](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/11) | Catálogo público de eventos | nuevo-feature | Alta | M | Tobías | 1 | `feature/catalogo-publico` |
| [P5 · #12](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/12) | Solicitar y consultar cupos propios | nuevo-feature | Alta | M | Lautaro | 1 | `feature/solicitud-cupos` |
| [P11 · #13](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/13) | Documentar la instalación y la base de datos | docs | Alta | M | Lucas | 1 | `docs/documentacion-entrega` |
| [P6 · #14](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/14) | Actualizar cupos con JavaScript | nuevo-feature | Media | S | Tobías | 2 | `feature/cupos-en-vivo` |
| [P7 · #15](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/15) | Detectar superposición de eventos en un lugar | nuevo-feature | Media | M | Tobías | 2 | `feature/disponibilidad-lugares` |
| [P8 · #16](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/16) | Mejorar el recorrido en celular y los mensajes de estado | ui-ux | Media | S | Lautaro | 2 | `feature/experiencia-mobile` |
| [P9 · #17](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/17) | Preparar y verificar el despliegue | alcance | Media | M | Lucas | Después del cierre funcional | `chore/despliegue` |
| [P10 · #18](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/18) | Evaluar aprobación manual y lista de espera | nuevo-feature | Baja | S | Lautaro | Futuro | `docs/flujo-lista-espera` si se aprueba |

### Historias y criterios de aceptación

**P1. Congelar y publicar el MVP**

- El equipo confirmó que el commit de la presentación del 18/11 fue `032de815509ae5c310aad7e96681afe134d724e8`.
- Se publicó el tag anotado y el [release v1.0.0 — MVP](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/releases/tag/v1.0.0), con un SQL de estructura y datos ficticios.

**P2. Plan de sprints**

- El plan indica el objetivo y el release de cada sprint. El equipo valida responsables, dependencias y fechas, y el plan enlaza los Issues de GitHub con sus criterios de aceptación.

**P3. Registro público y rol `usuario`**

- Como visitante, quiero crear mi cuenta para solicitar un cupo después de iniciar sesión.
- El formulario permite registrar nombre, apellido, correo único y contraseña. PHP valida los datos y el token CSRF, guarda el hash y asigna `usuario` en el servidor. El rol enviado desde el navegador se ignora.
- La migración conserva usuarios e inscripciones actuales y vincula cada cuenta pública a su participante mediante un identificador. Los paneles y rutas internas siguen restringidos al personal. Una cuenta pública inicia sesión y puede llegar al catálogo.

**P4. Catálogo público**

- Como visitante, quiero consultar eventos sin iniciar sesión para elegir uno que me interese.
- El listado muestra título, fecha, lugar, plazo y estado de inscripción. Distingue disponible, completo y cerrado. Permite filtrar por disponibilidad y funciona desde el celular.
- La consulta reutiliza el cálculo de inscripciones activas y expone únicamente datos públicos del evento. El botón de solicitud conduce al inicio de sesión cuando corresponde.

**P5. Solicitar y consultar cupos propios**

- Como usuario registrado, quiero pedir un cupo y consultar mis inscripciones para saber dónde tengo una plaza confirmada.
- PHP toma la identidad de la sesión y reutiliza el registro transaccional de inscripciones. Rechaza solicitudes duplicadas, fuera de plazo y para eventos llenos. Con dos solicitudes simultáneas por el último cupo, solo una se confirma.
- El usuario ve únicamente sus propias inscripciones. La inscripción manual de personal y la cancelación existente siguen funcionando. Acordar con Lucas cómo se registra el autor de una autoinscripción sin perder la trazabilidad del personal.

**P6. Cupos actualizados en el catálogo**

- Como visitante, quiero ver cambios de disponibilidad aunque tenga el catálogo abierto.
- JavaScript consulta periódicamente un endpoint PHP de lectura que comparte el cálculo de P4. Actualiza cupos al volver a la pestaña y después de una inscripción. Un fallo transitorio conserva los últimos datos e indica que pueden estar desactualizados.
- El catálogo muestra un estado inicial útil con JavaScript desactivado. Al enviar una solicitud, PHP vuelve a verificar la disponibilidad en la base de datos.

**P7. Ocupación de lugares**

- Como administrador, quiero saber si un lugar está ocupado al crear o editar un evento para evitar reservas superpuestas.
- Identificar el mismo lugar de manera consistente y agregar inicio y fin al intervalo. La migración conserva eventos existentes y define cómo completar los que aún tienen solo una fecha y hora.
- PHP rechaza cruces del mismo lugar tanto al crear como al editar, excluye el evento editado de la comparación y permite eventos consecutivos cuando el anterior termina justo al empezar el siguiente. El mensaje identifica el horario en conflicto.

**P8. Recorrido en celular**

- Verificar en una pantalla de 360 px el catálogo, registro y solicitud. Los formularios se pueden usar con teclado, conservan los datos válidos ante un error y muestran un mensaje comprensible de error o confirmación.

**P9. Despliegue final**

- Validar al cierre si el destino elegido puede ejecutar el PHP del proyecto, mantener sesiones y conectarse a MySQL. Configurar credenciales fuera del repositorio y comprobar catálogo, login e inscripción con una base de demostración.

**P10. Aprobación y lista de espera**

- Acordar con el docente si necesita solicitudes que queden pendientes y qué pasa cuando se libera un cupo. Registrar la decisión antes de estimar el desarrollo.

**P11. Documentación de entrega**

- Desde un clon limpio, otro integrante puede instalar, importar los datos y entrar con los usuarios de prueba siguiendo `README.md`. El README incluye tecnologías, integrantes y capturas del flujo entregado.
- Completar `CHANGELOG.md` de más nuevo a más viejo y `docs/base-de-datos.md` con tablas, campos y relaciones. Incorporar las migraciones y datos de prueba necesarios en `database/`. Documentar las pantallas nuevas en `docs/funcionalidades.md` si entra en el sprint.

## Integración y cierre

- Cada integrante abre su Issue, copia el bloque «Instrucción para copiar al asistente» y le pasa también el enlace del Issue. El asistente trabaja en el clon y con la cuenta de ese integrante.
- Antes de crear una rama, revisar `git status --short --branch`. Si hay cambios ajenos al Issue, preservarlos y continuar en otro clon o worktree. Para una rama nueva, ejecutar `git fetch origin` y `git switch -c <rama-del-issue> origin/main`. En una rama existente, traer `main` sin reescribir commits compartidos.
- Antes de abrir el PR, revisar el diff, correr las pruebas del módulo y confirmar que el PR apunta a `main`. En el cuerpo indicar el Issue con `Closes #N`, las pruebas ejecutadas y, si aplica, los pasos de migración. Un compañero revisa el PR y los dueños de cada cambio conservan la autoría de sus commits.
- En el sprint 1, integrar el registro y su migración antes de cerrar la solicitud de cupos. El catálogo puede avanzar en paralelo con rutas públicas y una consulta de solo lectura.
- Probar en `main` la instalación limpia, una actualización desde el MVP, los dos roles de personal, el recorrido de usuario público y la disputa del último cupo.
- En cada cierre, actualizar el changelog con los Issues incluidos, etiquetar el commit revisado y publicar su GitHub Release. Escribir después una retro breve con lo que salió bien, lo que falló y el cambio acordado para el siguiente sprint.
