# Evaluación de aprobación manual y lista de espera

**Issue:** [#18](https://github.com/lucasgg981/lgi2026-integrador-gonzalez-boczar-garcia/issues/18) · **Plan:** P10 · **Responsable:** @lauho · **Prioridad:** Baja · **Esfuerzo:** S

## Contexto y estado actual

El sistema actual confirma la inscripción en el momento en que el usuario la solicita,
si hay cupo y el plazo está abierto. No existe un estado intermedio ni una lista de
espera: la fila de `inscripciones` se crea directamente con `estado = 'activa'` y el
cupo se descuenta al mismo tiempo.

El plan de releases propone esta conducta para la entrega actual y deja un circuito de
aprobación manual en el backlog. La tabla `inscripciones` ya reserva la columna
`estado` (`'activa'` / `'cancelada'`), por lo que agregar un estado `'pendiente'` es
posible sin cambiar el esquema de forma estructural, pero sí requiere revisar el
cálculo de cupos y las pantallas que lo consumen.

## Preguntas a acordar con el docente

1. **¿Se necesita una aprobación manual?** El docente debe indicar si el personal
   revisa cada solicitud antes de confirmarla o si la confirmación automática es
   aceptable para la entrega.
2. **¿Qué pasa cuando se libera un cupo?** Si hay una lista de espera, ¿se promueve
   automáticamente al primero de la fila o se abre el cupo para cualquiera que lo
   pida?
3. **¿Quién aprueba?** Administrador, empleado o ambos. Esto define qué pantallas
   deben cambiar y qué pruebas por rol hacen falta.
4. **¿El usuario ve su solicitud pendiente?** Si existe un estado `'pendiente'`, debe
   reflejarse en "Mis inscripciones" con un mensaje comprensible y no contar como
   cupo ocupado.
5. **¿Se mantiene la trazabilidad?** El campo `inscripciones.id_usuario` ya guarda
   quién realizó cada alta (personal o cuenta pública). La aprobación manual debe
   registrar quién aprueba y cuándo, sin perder ese dato.

## Opciones evaluadas

| Opción | Descripción | Ventajas | Costos |
| --- | --- | --- | --- |
| A. Confirmación automática (actual) | La solicitud se confirma al instante si hay cupo y el plazo está abierto. | Simple, ya implementada y probada; sin cambios de esquema ni de UI. | No permite revisar solicitudes antes de confirmarlas. |
| B. Aprobación manual con estado `pendiente` | La solicitud queda `'pendiente'` y el personal la confirma o rechaza. | Control del personal; trazabilidad de la aprobación. | Requiere nuevo estado, cambio en el cálculo de cupos, nuevas pantallas y pruebas por rol. |
| C. Lista de espera con promoción automática | Las solicitudes que no entran quedan en espera y se promueven cuando se libera un cupo. | Mejor uso de los cupos liberados por cancelaciones. | Lógica de orden y concurrencia compleja; riesgo de sobreinscripción si se implementa mal. |

## Impacto técnico estimado

- **Esquema:** agregar `'pendiente'` al conjunto de valores de `estado` (sin cambio
  estructural si se maneja como texto, pero conviene documentarlo). Opcionalmente
  agregar `fecha_aprobacion` e `id_usuario_aprobador` para trazabilidad.
- **Cálculo de cupos:** las consultas actuales cuentan `estado = 'activa'`. Con la
  opción B o C hay que definir si `'pendiente'` reserva cupo o no.
- **Pantallas:** catálogo, solicitud y "Mis inscripciones" deben mostrar el nuevo
  estado; el personal necesita una vista de aprobación.
- **Pruebas:** nuevas pruebas por rol y de concurrencia (promoción simultánea).

## Decisión adoptada

**Se adopta la opción A para la entrega actual**, de acuerdo con la propuesta del
plan de releases: "Pedir cupo" confirma la inscripción si hay lugar y el plazo está
abierto, e informa el resultado en el momento. Un circuito de aprobación manual queda
en el backlog.

Motivos:
- Ya está implementada, probada y en producción en `main` (issues #12 y #16).
- El esfuerzo y el riesgo de introducir la opción B o C superan el beneficio para
  esta entrega, sobre todo por la concurrencia en la promoción de cupos.
- La columna `estado` queda preparada para incorporar `'pendiente'` más adelante sin
  migraciones destructivas.

**Acuerdos pendientes con el docente (preguntas 1 a 5):** hasta que el docente
confirme la necesidad de aprobación manual, no se estima ni se desarrolla la opción B
o C. Si se aprueba, abrir un issue nuevo con la opción elegida y estimarla en un
sprint posterior.

## Registro de la decisión

- **Fecha:** 26/11/2026
- **Responsable:** Lautaro García (@lauho)
- **Coordinación:** Lucas González (@lucasgg981) valida el alcance antes de iniciar
  el desarrollo de la opción B o C.
- **Estado:** En espera de confirmación docente. La decisión vigente es la opción A.
- **Próximo paso:** si el docente solicita aprobación manual, crear un issue con la
  opción elegida, actualizar `inscripciones.php` para soportar el nuevo estado y
  agregar las pruebas correspondientes.
