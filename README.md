# Seminario de Titulación — Plataforma PHP + MySQL

Plataforma para gestionar los proyectos de titulación de un seminario de programación: convocatorias, registro y aprobación de proyectos, flujo visual de estados, tecnologías, bitácora por clase con capturas y reporte PDF semanal verificable.

**Requisitos:** PHP 8.1 o superior (con las extensiones `pdo_mysql`, `gd`, `fileinfo` y `mbstring`) y MySQL 5.7+ o MariaDB 10.3+. No necesita Composer ni Node: todo el código va incluido.

---

## 1. Instalación local (XAMPP / Laragon)

1. Copia la carpeta `seminario/` dentro de `htdocs/` (XAMPP) o `www/` (Laragon).
2. En phpMyAdmin crea la base `seminario` (cotejamiento `utf8mb4_unicode_ci`) e importa `database/schema.sql`.
3. Copia `config/config.example.php` como `config/config.php` y escribe los datos de la base, el nombre de la institución y la carrera.
4. Abre `http://localhost/seminario/public/index.php?r=instalar` y crea la cuenta del docente.
5. Pon `'instalador_activo' => false` en `config/config.php`.

## 2. Publicar en un hosting de pruebas

### a) Hosting gratuito tipo cPanel (InfinityFree, AwardSpace…)
1. Crea la base MySQL desde el panel e importa `database/schema.sql` con phpMyAdmin.
2. Sube **todo** el contenido de `seminario/` a `htdocs/` o `public_html/` por FTP o con el administrador de archivos.
3. Edita `config/config.php` con el host, el usuario, la contraseña y el nombre de la base que te dio el panel. Normalmente el host **no** es `localhost`, sino algo como `sqlXXX.hosting.com`.
4. Entra a `https://tu-subdominio/index.php?r=instalar`.

El `.htaccess` de la raíz redirige todo a `public/` y bloquea `app/`, `config/`, `storage/`, `database/` y `lib/`.

### b) VPS con Nginx + PHP-FPM
Apunta el `root` del sitio a `seminario/public`. Así las demás carpetas quedan fuera del alcance web:

```nginx
server {
    server_name seminario.tudominio.com;
    root /var/www/seminario/public;
    index index.php;
    client_max_body_size 20M;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
}
```

Da permisos de escritura a `storage/` para el usuario del servidor web (`chown -R www-data storage`). Luego activa HTTPS con `certbot --nginx`.

### Ajustes de PHP recomendados
`upload_max_filesize = 8M`, `post_max_size = 25M`, `max_file_uploads = 10`.

---

## 3. Uso

**Docente**
1. *Convocatorias → Nueva convocatoria*: nombre, periodo, fecha de inicio, semanas, días y horario. El calendario se genera automáticamente; la semana 1 es la de definición.
2. Comparte con los estudiantes el **código** o el **enlace directo**.
3. En *Calendario* puedes cambiar la fecha de una clase, ponerle tema o marcarla como suspendida.
4. En *Proyectos* aparecen las propuestas por revisar. Puedes **Aprobar**, **Observar** (el estudiante corrige y reenvía) o **Rechazar**.
5. En el *Tablero* ves: el embudo de estados, las tecnologías por categoría, los stacks más usados, el mapa de cumplimiento de la bitácora y alertas. También puedes exportar todo a CSV.
6. En *Tecnologías* apruebas o fusionas las tecnologías que propongan los estudiantes.

**Estudiante**
1. Crea su cuenta con el código de la convocatoria y registra su proyecto. Solo el título es obligatorio; la descripción, los objetivos y las capturas son opcionales.
2. Sigue el estado en la barra superior: Registro → Revisión → Aprobado → Tecnologías → Desarrollo → Finalizado.
3. Cuando el docente aprueba el proyecto, define las tecnologías. Debe elegir al menos un lenguaje, una base de datos y una plataforma.
4. En cada clase registra lo que hizo (mínimo 80 caracteres) con 1 a 3 capturas. Lo que se registre después del plazo queda marcado como *tardío*.
5. Al completar las clases de la semana, o al terminar esta, descarga el **PDF semanal** para subirlo al EVA.

**Cambios a la ficha después de aprobada**
- *Antes de la semana de congelamiento* (por defecto la 3; se configura en cada convocatoria): el estudiante, desde *Mi proyecto → Ajustar ficha*, puede **completar** campos que dejó vacíos (se guardan al instante) y **solicitar** cambios en campos ya llenos, con un motivo. Tú los aceptas o los rechazas desde la ficha del proyecto, el filtro *Cambios pendientes* o el tablero. El repositorio y las capturas son libres.
- *Desde la semana de congelamiento*: la ficha queda cerrada para el estudiante y solo tú editas (*Editar ficha*), incluidos los datos personales (cédula, correo).
- El **enlace del repositorio** se puede actualizar siempre, incluso con la ficha cerrada. La plataforma revisa si el repositorio de GitHub es público; si no lo es, le pide al estudiante que agregue el correo del docente como colaborador.
- Todo queda en el **registro de cambios** con el valor anterior, el nuevo, quién lo hizo y el motivo. El estudiante también lo ve.

**Entregables:** manuales, video, demo y presentación se entregan como **enlaces** (Google Drive, OneDrive, YouTube, GitHub, Canva); la plataforma no guarda esos archivos. El estudiante los comparte con el correo del docente como Lector. El docente los revisa desde la ficha del proyecto, con vista previa incrustada, y los aprueba, los observa o marca "No tengo acceso". La lista y las semanas se configuran en cada convocatoria; el tablero muestra quién entregó qué.

**Usuarios:** en *Usuarios* (menú de docente) ves todas las cuentas, con buscador y filtros por convocatoria, rol y estado. Desde la ficha de cada uno puedes editar sus datos, restablecer su contraseña, cambiarlo de convocatoria (solo si aún no registra avances) y desactivar o reactivar su cuenta. Desactivar impide el ingreso, pero conserva el proyecto y la bitácora. No hay opción de eliminar, a propósito.

**Contraseñas:** cada usuario cambia la suya con el ícono de la llave, en la barra superior. Si un estudiante la olvida, el docente genera una contraseña temporal desde *Convocatorias → la convocatoria → Estudiantes inscritos → Contraseña*, o desde *Editar ficha* del proyecto. La temporal se muestra una sola vez y el restablecimiento queda en el registro de cambios.

**Modo oscuro:** el botón de sol/luna, en la barra superior y en las pantallas de acceso, cambia entre modo claro y oscuro. La elección se guarda en el navegador de cada usuario; si nunca eligió, se usa el modo del sistema operativo. Los colores están definidos como variables en `public/assets/css/app.css` (bloque `:root[data-theme="dark"]`).

**Verificación de PDF:** cada reporte lleva un código al pie. En `index.php?r=verificar&c=CODIGO` se comprueba que el reporte existe y si la bitácora cambió después de generarlo.

---

## 4. Estructura

```
public/        index.php (router), assets/ (css, js)  ← raíz web
app/           controllers/, views/, núcleo (DB, Auth, ProjectState, Calendar, Upload, Report, Repo)
config/        config.php (credenciales)
database/      schema.sql (tablas + catálogo inicial de tecnologías)
lib/fpdf/      generador de PDF (licencia libre)
storage/       uploads/ (capturas en JPEG ≤1600 px), logs/
docs/          ARQUITECTURA.md
```

## 5. Configuración (`config/config.php`)

| Clave | Para qué sirve |
|---|---|
| `app.institucion`, `app.carrera` | Aparecen en la interfaz y en el PDF |
| `app.correo_docente` | Correo al que los estudiantes deben dar acceso si su repositorio de GitHub es privado |
| `app.url` | URL pública. Déjala vacía para que se detecte sola |
| `app.max_mb` | Peso máximo por imagen (por defecto 5) |
| `app.min_actividad` | Mínimo de caracteres en la descripción de cada avance |
| `app.instalador_activo` | Ponla en `false` después de crear la cuenta del docente |
| `app.debug` | Ponla en `true` solo en local, para ver los errores |

## 6. Actualizar una instalación anterior
Si ya tenías la plataforma instalada, ejecuta en phpMyAdmin, una sola vez y en orden, las migraciones que aún no hayas aplicado: `database/migracion_001_cambios.sql` (registro de cambios) `database/migracion_002_usuarios.sql` (último ingreso) y `database/migracion_003_entregables.sql` (entregables). En una instalación nueva no hace falta, porque `schema.sql` ya lo incluye.

## 7. Respaldo
Haz respaldo de la base (`mysqldump seminario > respaldo.sql`) y de la carpeta `storage/uploads/`.
