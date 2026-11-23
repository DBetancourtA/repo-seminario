# Plataforma de Seminario de Titulación — Arquitectura

> Tecnología Superior en Desarrollo de Software · Documento de arquitectura v0.1
> Responsable: Diego (docente / administrador)

---

## 1. Propósito

Plataforma web en PHP para gestionar los proyectos de titulación de un seminario de programación:

- El **docente (admin)** crea convocatorias (seminarios), revisa y aprueba proyectos y ve un tablero de qué se construye y con qué tecnologías.
- El **estudiante** se inscribe en la convocatoria que le asignan, registra su proyecto, sigue su estado en un flujo visual y lleva una **bitácora por clase** con evidencia, de la que descarga un **PDF semanal** para adjuntar en el EVA.

Objetivo pedagógico: que cada proyecto sea real y demuestre, clase a clase, que el estudiante programa.

---

## 2. Actores y permisos

| Acción | Docente (admin) | Estudiante |
|---|:-:|:-:|
| Crear / editar / cerrar convocatorias | ✔ | — |
| Generar código de inscripción de una convocatoria | ✔ | — |
| Registrarse e inscribirse con código | — | ✔ |
| Registrar proyecto (datos + descripción + objetivos + capturas) | — | ✔ |
| Aprobar / observar / rechazar proyecto | ✔ | — |
| Definir tecnologías (solo tras aprobación) | — | ✔ |
| Subir avance por clase con captura | — | ✔ |
| Comentar un avance | ✔ | (lee) |
| Descargar PDF semanal | ✔ (de cualquiera) | ✔ (el suyo) |
| Ver tablero de tecnologías y avance global | ✔ | — |

Un estudiante pertenece a **una** convocatoria activa y tiene **un** proyecto en ella (puede ser individual; ver pregunta abierta sobre parejas).

---

## 3. Flujo del trabajo de titulación (workflow visual)

Barra horizontal fija en la parte superior del panel del estudiante. Cada etapa muestra estado: completada, actual, pendiente o con observación.

```
 ①  Registrado ─▶ ②  En revisión ─▶ ③  Aprobado ─▶ ④  Tecnologías ─▶ ⑤  Desarrollo ─▶ ⑥  Finalizado
                        │                            definidas        (sem. 2…N)
                        ▼
                   Observado ──(estudiante corrige)──▶ En revisión
                        │
                        ▼
                   Rechazado (fin; puede registrar un nuevo proyecto)
```

Reglas de transición (máquina de estados en `ProjectState.php`, nunca se cambia el estado “a mano” en SQL):

| Desde | Hacia | Quién | Condición |
|---|---|---|---|
| registrado | en_revision | sistema | al guardar el registro completo |
| en_revision | aprobado / observado / rechazado | docente | observación obligatoria si no aprueba |
| observado | en_revision | estudiante | al editar y reenviar |
| aprobado | tecnologias_definidas | estudiante | al menos 1 lenguaje y 1 base de datos/almacenamiento |
| tecnologias_definidas | en_desarrollo | sistema | al iniciar la semana 2 de la convocatoria |
| en_desarrollo | finalizado | docente | cierre del seminario |

Cada transición queda en `project_status_log` (quién, cuándo, comentario) → historial visible para ambos.

---

## 4. Estructura del seminario

La convocatoria define el calendario; nada de semanas ni clases queda escrito en código.

- `weeks_total`: número de semanas (propuesto 12: semana 1 = definición del proyecto + 11 de desarrollo).
- `class_days`: días de clase (ej. lun–jue), `class_start` 18:00, `class_end` 20:00.
- Al crear la convocatoria se **generan automáticamente** las sesiones (`sessions`): semana × clase, con fecha real. El docente puede mover una sesión (feriados) o marcarla como suspendida.

### Bitácora por clase
Por cada sesión el estudiante registra:
- Actividad realizada (texto, mín. 80 caracteres para evitar “avancé el proyecto”).
- Tipo de evidencia: código / interfaz / base de datos / documentación.
- **1 a 3 capturas obligatorias** (código o proyecto).
- Enlace opcional a commit o repositorio.

Ventana de carga configurable: desde el inicio de la clase hasta *N* horas después (por defecto 48 h). Fuera de plazo queda marcado como “tardío”, no se bloquea (decisión a confirmar).

### Reporte semanal PDF
Al completar las 4 clases de la semana (o al cerrarse la semana) se habilita “Descargar reporte”. Contenido:
1. Encabezado: institución, convocatoria, estudiante, cédula, proyecto, semana y fechas.
2. Tabla resumen de las 4 clases (fecha, actividad, tipo, estado a tiempo/tardío).
3. Detalle por clase con sus capturas.
4. Pie con código de verificación (hash corto) y URL `/verificar/{codigo}` para que el docente compruebe que el PDF no fue alterado al recibirlo en el EVA.

---

## 5. Registro del proyecto

| Campo | Obligatorio | Notas |
|---|:-:|---|
| Nombres, apellidos, cédula, correo institucional, teléfono | ✔ | correo validado por dominio si se define |
| Título del proyecto | ✔ | máx. 200 caracteres |
| Descripción breve | — | máx. 1 000 caracteres |
| Objetivo general | — | |
| Objetivos específicos | — | lista dinámica, 0–6 |
| Capturas (login u otras páginas) | — | hasta 4 imágenes |
| **Tecnologías** | ✔ *después* de aprobado | ver §6 |

Al pasar a revisión, el docente ve todo en una vista de lectura con botones Aprobar / Observar / Rechazar y un campo de comentario.

---

### Cambios después de la aprobación
| Momento | Estudiante | Docente |
|---|---|---|
| Aprobado, antes de la semana de congelamiento (3 por defecto) | Completa campos vacíos al instante; solicita cambios en campos llenos con motivo; repositorio y capturas libres | Acepta o rechaza solicitudes; edita cualquier campo |
| Desde la semana de congelamiento | Solo lectura | Edita cualquier campo, incluidos los datos personales |

Todo cambio se guarda en `proyecto_cambios` (campo, valor anterior, valor nuevo, tipo, estado, motivo, quién y cuándo) y se muestra como antes/después en la ficha.

## 6. Catálogo de tecnologías (base del tablero)

Las tecnologías se eligen de un **catálogo normalizado** (no texto libre) para que las estadísticas sean confiables. Si falta alguna, el estudiante la propone y el docente la aprueba o la fusiona con una existente.

Categorías: Lenguaje · Framework backend · Framework frontend · Base de datos · Plataforma/destino (web, móvil, escritorio, API) · Hosting/infraestructura · Control de versiones · Otras herramientas.

### Tablero del docente
- Proyectos por estado (embudo del workflow).
- Top lenguajes, bases de datos, frameworks y plataformas (barras horizontales ordenadas).
- Combinaciones de stack más frecuentes (ej. PHP + MySQL + Bootstrap).
- Cumplimiento de bitácora: % de clases registradas por estudiante y por semana (mapa de calor estudiante × semana).
- Alertas: estudiantes sin registro en las últimas 2 clases, proyectos observados sin respuesta > 3 días.
- Exportar CSV.

---

## 7. Arquitectura técnica

Pensada para subirse a hosting compartido gratuito o barato: **sin frameworks pesados, sin Node en el servidor, sin colas**.

| Capa | Elección | Motivo |
|---|---|---|
| Lenguaje | PHP 8.1+ | soportado por cualquier hosting |
| Patrón | MVC ligero propio + front controller (`public/index.php`) | se despliega subiendo archivos; los estudiantes pueden leer el código |
| Base de datos | MySQL / MariaDB vía PDO, consultas preparadas | lo que trae todo hosting |
| Vistas | Plantillas PHP + CSS propio (tokens) + JS vanilla | sin build step |
| PDF | FPDF (un solo archivo en `lib/`, sin Composer) | funciona en cualquier hosting gratuito |
| Imágenes | GD: redimensionar a máx. 1600 px y convertir a WebP/JPEG | hosting gratuito tiene poco disco |
| Gráficos del tablero | Chart.js (CDN) | sin dependencias de servidor |
| Correo | PHPMailer + SMTP (opcional) | notificar aprobación/observación |

### Estructura de carpetas
```
seminario/
├─ public/                 ← único directorio expuesto (document root)
│  ├─ index.php            ← front controller / router
│  ├─ assets/{css,js,img}
│  └─ .htaccess            ← reescritura de URLs
├─ app/
│  ├─ Controllers/         Auth, Convocatoria, Proyecto, Bitacora, Reporte, Dashboard, Admin
│  ├─ Models/              Usuario, Convocatoria, Sesion, Proyecto, Tecnologia, Avance …
│  ├─ Services/            ProjectState, PdfReport, ImageUploader, Mailer, Stats
│  ├─ Middleware/          Auth, Role, Csrf
│  └─ Views/               layouts/, estudiante/, admin/, pdf/
├─ config/config.php       ← credenciales (fuera de public/)
├─ storage/uploads/        ← imágenes, servidas por controlador con control de acceso
├─ storage/logs/
├─ database/schema.sql     + seed_tecnologias.sql
└─ lib/fpdf/
```

Si el hosting no permite elegir el document root, se sube todo dentro de `public_html/` y se protege `app/`, `config/` y `storage/` con `.htaccess` (`Require all denied`).

### Modelo de datos
```
users(id, rol[admin|estudiante], nombres, apellidos, cedula UNIQUE, email UNIQUE,
      telefono, password_hash, activo, created_at)

convocatorias(id, nombre, periodo, carrera, codigo_inscripcion UNIQUE, fecha_inicio,
      weeks_total, class_days, class_start, class_end, upload_window_h,
      estado[borrador|abierta|en_curso|cerrada], created_by)

inscripciones(id, convocatoria_id, user_id, created_at, UNIQUE(convocatoria_id,user_id))

sesiones(id, convocatoria_id, semana, numero_clase, fecha, estado[normal|suspendida])

proyectos(id, convocatoria_id, user_id, titulo, descripcion, objetivo_general,
      estado, created_at, updated_at)
proyecto_objetivos(id, proyecto_id, orden, texto)
proyecto_capturas(id, proyecto_id, ruta, descripcion)
project_status_log(id, proyecto_id, de, a, user_id, comentario, created_at)

tecnologias(id, nombre, categoria, aprobada, alias_de NULL)
proyecto_tecnologias(proyecto_id, tecnologia_id, PRIMARY KEY(ambos))

avances(id, proyecto_id, sesion_id, actividad, tipo_evidencia, enlace_repo,
      tardio, created_at, UNIQUE(proyecto_id,sesion_id))
avance_capturas(id, avance_id, ruta)
avance_comentarios(id, avance_id, user_id, texto, created_at)

reportes(id, proyecto_id, semana, codigo_verificacion UNIQUE, hash_contenido, generado_at)
```

### Seguridad mínima
- `password_hash` / `password_verify`; sesiones con `httponly`, `samesite=Lax`, regeneración de ID al iniciar sesión.
- Token CSRF en todo formulario POST.
- Subidas: verificar MIME real con `finfo`, re-codificar la imagen con GD (elimina contenido incrustado), nombres aleatorios, máx. 3 MB.
- Archivos servidos por controlador que comprueba que el usuario sea dueño o admin.
- Escape de salida con helper `e()` en todas las vistas.
- Límite de intentos de login por IP/correo.

---

## 8. Diseño de interfaz (UI/UX)

Dirección: **minimalismo suizo, herramienta académica sobria**. Debe sentirse como un instrumento de trabajo, no como una landing de startup. Se generó una base con la skill UI/UX Pro Max y se ajustó para evitar el aspecto “plantilla de IA” (azul #2563EB + naranja, degradados, tarjetas con emoji).

### Principios
1. Papel y tinta: fondo cálido casi blanco, texto casi negro, **un solo color de acento**.
2. Jerarquía por tipografía y espacio, no por cajas de colores.
3. Los colores de estado solo aparecen donde significan algo (workflow, insignias, alertas).
4. Densidad media-alta en el panel docente; más aire en los formularios del estudiante.
5. Sin degradados, sin glassmorphism, sin ilustraciones genéricas, sin emojis como iconos (Lucide SVG).

### Tokens
| Rol | Valor | Uso |
|---|---|---|
| `--paper` | `#F7F6F2` | fondo general |
| `--surface` | `#FFFFFF` | paneles y formularios |
| `--ink` | `#1C1B19` | texto principal |
| `--ink-muted` | `#5F5D57` | texto secundario (contraste ≥ 4.5:1 sobre paper) |
| `--line` | `#DEDBD2` | bordes 1 px |
| `--accent` | `#1F4E5F` | azul petróleo: botones principales, enlace, etapa actual |
| `--ok` | `#2F6B3B` | aprobado / a tiempo |
| `--warn` | `#9A6400` | observado / tardío |
| `--danger` | `#A3302A` | rechazado / error |

Tipografía: **IBM Plex Sans** (interfaz) + **IBM Plex Mono** (códigos, cédulas, semanas, códigos de verificación). Base 16 px, interlineado 1.5, escala 13 / 16 / 20 / 28 / 36.
Espaciado en múltiplos de 4 px. Radio de borde 6 px. Sombras: ninguna, salvo menús desplegables.

### Pantallas
**Estudiante**
1. Acceso / registro (con código de convocatoria).
2. Panel: barra de workflow arriba → tarjeta del proyecto → **cuadrícula de semanas** (12 columnas en escritorio, lista en móvil). Cada semana muestra 4 casillas de clase: vacía, registrada, tardía, suspendida. Semana actual resaltada con el acento.
3. Formulario del proyecto (pasos: datos personales → proyecto → objetivos → capturas).
4. Selección de tecnologías (chips agrupados por categoría con buscador).
5. Registro de avance de una clase (texto + arrastrar capturas + vista previa).
6. Detalle de semana con botón “Descargar reporte PDF”.

**Docente**
1. Convocatorias: lista y asistente de creación (datos → calendario → vista previa de sesiones generadas).
2. Bandeja de revisión de proyectos (filtros por estado; vista dividida lista/detalle).
3. Ficha de estudiante: proyecto, historial de estados, bitácora completa, comentarios.
4. Tablero (§6).
5. Catálogo de tecnologías (aprobar propuestas, fusionar duplicados).

### Accesibilidad y respuesta
Contraste AA, foco visible, etiquetas visibles en todos los campos, errores junto al campo, objetivos táctiles ≥ 44 px, `prefers-reduced-motion` respetado, probado en 375 / 768 / 1024 / 1440 px. El workflow no depende solo del color: cada etapa lleva número, nombre y estado en texto.

---

## 9. Despliegue de pruebas (sin hosting contratado)

| Opción | Costo | Ventajas | Limitaciones |
|---|---|---|---|
| Hosting gratuito PHP+MySQL (InfinityFree, AwardSpace u otro similar) | 0 | panel tipo cPanel, subdominio gratis | límites de disco/CPU, SMTP restringido, anuncios o suspensiones por inactividad |
| VPS que ya administras (subdominio, p. ej. `seminario.<dominio>`) con Nginx + PHP-FPM + MariaDB | 0 adicional | control total, HTTPS con Let’s Encrypt, sin límites artificiales | requiere configurarlo y convivir con lo que ya corre ahí |
| Equipo local (XAMPP/Laragon) + túnel (Cloudflare Tunnel / ngrok) | 0 | listo en minutos para demostrar | depende de que tu PC esté encendida |
| Nivel gratuito de nube (Oracle Cloud Always Free, etc.) | 0 | VPS real | registro con tarjeta, más configuración |

Recomendación: desarrollar en local y publicar en un subdominio de un VPS ya disponible; usar el hosting gratuito solo si no hay VPS. El código no cambia entre opciones: solo `config/config.php`.

---

## 10. Fases de construcción

1. **Base**: esquema SQL, router, autenticación, roles, layout y tokens de diseño.
2. **Convocatorias**: CRUD, código de inscripción, generación de sesiones.
3. **Proyecto**: registro, capturas, revisión del docente, máquina de estados y barra de workflow.
4. **Tecnologías**: catálogo semilla, selección, propuestas.
5. **Bitácora**: avances por clase, capturas, comentarios, cuadrícula de semanas.
6. **PDF**: reporte semanal con código de verificación.
7. **Tablero docente**: métricas, gráficos, exportación CSV.
8. **Pulido**: accesibilidad, móvil, correos, respaldo de BD.

---

## 11. Decisiones pendientes

1. ¿11 o 12 semanas? (propuesta: 12 = 1 de definición + 11 de desarrollo).
2. ¿Qué días son las 4 clases? (¿lunes a jueves?) y fecha de inicio de la primera convocatoria.
3. ¿Proyectos individuales o también en pareja/grupo? Si es grupal, cada integrante sube su propia bitácora.
4. ¿Se bloquea el registro de una clase fuera de plazo o solo se marca como tardío? ¿Cuántas horas de ventana?
5. ¿En la semana 1 (definición) también se suben 4 avances, o solo el registro y aprobación del proyecto?
6. ¿Logo, nombre de institución y carrera exactos para el encabezado del PDF y la interfaz?
7. ¿Correo institucional obligatorio (dominio específico) y se envían notificaciones por correo?
8. ¿El estudiante debe registrar URL del repositorio Git? (recomendado para evidenciar autoría).
9. ¿Cuántos estudiantes aproximadamente por convocatoria? (dimensiona disco para capturas).
10. ¿Dónde se publicará: hosting gratuito o un VPS existente?
