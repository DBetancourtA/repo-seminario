# Publicar con GitHub → InfinityFree

InfinityFree no tiene SSH ni Git, así que en el servidor no se puede hacer `git pull`. En su lugar, **GitHub sube los cambios por FTP** cada vez que haces `git push` a la rama `main`. También puedes lanzarlo a mano desde la pestaña *Actions*. El archivo que lo hace es `.github/workflows/deploy.yml`.

```
Tu PC ──git push──▶ GitHub ──Action FTP──▶ InfinityFree (htdocs)
```

Solo se suben los archivos que cambiaron. **Nunca** se tocan `config/config.php` ni las capturas que suben los estudiantes, porque no están en el repositorio.

---

## Configuración inicial (una sola vez)

### 1. Repositorio en GitHub
1. En github.com → **New repository** → nombre `seminario-titulacion` → **Private** → sin README → *Create*.
2. En tu PC, abre una terminal (Git Bash, PowerShell o la terminal de VS Code) **dentro de la carpeta del proyecto** y ejecuta:

```bash
git init
git branch -M main
git add .
git status                      # config/config.php NO debe aparecer en la lista
git commit -m "Versión inicial de la plataforma"
git remote add origin https://github.com/TU_USUARIO/seminario-titulacion.git
git push -u origin main
```

Si es la primera vez que usas Git en esa PC, antes configura tu nombre:
`git config --global user.name "Diego"` y `git config --global user.email "tu@correo"`.

### 2. Cuenta en InfinityFree
1. Crea la cuenta de hosting y elige el subdominio.
2. **MySQL Databases** → crea la base (por ejemplo `seminario`). Anota estos cuatro datos: host (`sqlXXX.infinityfree.com`), nombre (`if0_XXXXXXX_seminario`), usuario (`if0_XXXXXXX`) y contraseña.
3. **phpMyAdmin** → abre esa base → *Importar* → `database/schema.sql`.
4. **FTP Details** → anota el host (`ftpupload.net`), el usuario (`if0_XXXXXXX`) y la contraseña (la misma de la cuenta de hosting).

### 3. Secretos en GitHub
En el repositorio: **Settings → Secrets and variables → Actions → New repository secret**. Crea estos cuatro:

| Nombre | Valor |
|---|---|
| `FTP_SERVER` | `ftpupload.net` |
| `FTP_USERNAME` | `if0_XXXXXXX` |
| `FTP_PASSWORD` | la contraseña de la cuenta de hosting |
| `FTP_DIR` | `/htdocs/` (si usas un dominio adicional: `/tudominio.com/htdocs/`) |

### 4. Primer despliegue
En el repositorio → **Actions** → *Desplegar en InfinityFree* → **Run workflow**. La primera vez sube todo (unos 100 archivos) y tarda unos minutos. Si InfinityFree corta la conexión FTP, vuelve a ejecutarlo: sigue desde donde quedó.

### 5. Crear `config.php` en el servidor
En InfinityFree → **File Manager** → `htdocs/config/` → nuevo archivo `config.php`. Copia el contenido de `config.example.php` y cambia:

```php
'db' => [
    'host' => 'sqlXXX.infinityfree.com',
    'port' => 3306,
    'name' => 'if0_XXXXXXX_seminario',
    'user' => 'if0_XXXXXXX',
    'pass' => 'TU_CONTRASEÑA',
],
```

Este archivo vive solo en el servidor y el despliegue nunca lo sobrescribe.

### 6. Crear tu cuenta de docente
Abre `https://tu-subdominio/index.php?r=instalar`, crea la cuenta y luego, en el mismo `config.php` del servidor, pon `'instalador_activo' => false`.

---

## Día a día

```bash
git add .
git commit -m "Describe el cambio"
git push
```

En uno o dos minutos el cambio está en InfinityFree. El avance se ve en la pestaña **Actions**: verde significa publicado. Para volver a publicar sin cambios, usa **Run workflow**.

Desde VS Code: panel *Source Control* → escribe el mensaje → **Commit** → **Sync Changes**.

### Reglas
- **No edites archivos directamente en InfinityFree.** El siguiente despliegue que toque ese archivo pisará tu edición. La única excepción es `config/config.php`.
- **Cambios en la base de datos:** el despliegue no toca MySQL. Si una versión trae un archivo `database/migracion_XXX.sql`, ejecútalo una vez en phpMyAdmin.
- **El paso "Revisar sintaxis PHP":** si un archivo tiene un error de sintaxis, el despliegue se detiene antes de subir nada. Así un error no rompe el sitio en producción.
- **Respaldo:** de vez en cuando exporta la base desde phpMyAdmin y descarga `htdocs/storage/uploads/`.
