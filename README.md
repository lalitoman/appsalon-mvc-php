# AppSalon — Sistema de Citas para Salón de Belleza

Proyecto base del curso de Juan Pablo de la Torre Valdez (Código con Juan),
con una revisión de seguridad aplicada (ver abajo) antes de usarlo como
demo o base para un cliente real.

## 🆕 Mejoras funcionales agregadas

- **"Mis Citas"**: los clientes ven su historial de citas (fecha, hora,
  servicios, total) y pueden cancelarlas desde `/mis-citas`.
- **Correo de confirmación de cita**: al agendar, el cliente recibe un
  correo con el detalle (usa las mismas credenciales SMTP del `.env`).
  Si el envío falla, no bloquea la creación de la cita.
- **Anti doble-reserva**: si dos clientes intentan agendar la misma
  fecha y hora, el segundo recibe un error claro en vez de crear una
  cita duplicada.
- **Página 404 real** en vez del texto plano genérico.
- **Confirmaciones antes de borrar** (citas y servicios) para evitar
  borrados accidentales.
- **Mensajes de éxito** al crear/actualizar/borrar un servicio.
- Validación HTML5 (`required`, `min`) en el formulario de servicios.
- CSRF también en las peticiones `fetch` de `/api/citas` (antes solo
  los formularios HTML clásicos lo tenían).

## 🔒 Qué se corrigió (ronda de seguridad inicial)

- `isAuth()` / `isAdmin()` no detenían la ejecución tras redirigir — se
  agregó `exit;`. Antes, contenido protegido (admin, servicios, citas) se
  podía ver sin sesión.
- `/api/citas` y `/api/eliminar` no tenían ningún chequeo de sesión —
  cualquiera podía crear o borrar citas de cualquier usuario.
- SQL concatenado sin escapar en el reporte de citas por fecha (posible
  inyección SQL) — se agregó validación estricta + escape.
- Tokens de confirmación/recuperación de cuenta generados con `uniqid()`
  (predecible) — se cambió a `random_bytes()` (criptográficamente seguro).
- Sin protección CSRF en los formularios — se agregó token por sesión.
- Mensaje de login revelaba si un email existía o no — ahora es genérico.
- Sin rate limiting en login — se agregó límite básico de intentos.
- Cookies de sesión sin flags de seguridad — se agregó `httponly`,
  `samesite` y `secure` (condicionado a `APP_ENV=production`).

## 🧰 Requisitos

- PHP 8.1+ con extensión `mysqli`
- Composer
- Node.js + npm (solo si vas a modificar SCSS/JS; el CSS/JS ya compilado
  viene incluido en `public/build/`)
- MySQL / MariaDB

## 🚀 Correrlo en local

1. **Clona e instala dependencias**
   ```bash
   composer install
   npm install
   ```

2. **Base de datos**
   - Crea una base de datos vacía, por ejemplo `appsalon`.
   - Importa el esquema: `mysql -u root -p appsalon < appsalon.sql`
     (o desde phpMyAdmin: Importar → seleccionar `appsalon.sql`).

3. **Variables de entorno**
   ```bash
   cp .env.example includes/.env
   ```
   Edita `includes/.env` con tus datos reales de MySQL. Para el correo,
   si no quieres configurar SMTP real todavía, crea una cuenta gratis en
   [Mailtrap.io](https://mailtrap.io) y usa esas credenciales de sandbox
   — así puedes probar registro/confirmación sin mandar correos reales.

4. **Levanta el servidor**

   Con el servidor embebido de PHP (rápido, para probar):
   ```bash
   php -S localhost:8000 -t public
   ```
   Abre `http://localhost:8000`.

   O si prefieres Laragon/XAMPP: apunta el virtual host a la carpeta
   `public/` del proyecto (no a la raíz del proyecto).

5. Si vas a tocar los estilos (`src/scss`) o el JS (`src/js`), compílalos con:
   ```bash
   npx gulp
   ```

## 👤 Crear tu primer usuario admin

El registro normal (`/crear-cuenta`) crea usuarios `admin = 0`. Para tener
acceso al panel de administración, después de registrarte y confirmar tu
cuenta por correo, entra a tu base de datos y corre:
```sql
UPDATE usuarios SET admin = 1 WHERE email = 'tu@email.com';
```

## 🌐 Subirlo a un hosting gratis para mostrar una demo

Recomendado para esto: **InfinityFree** (soporta PHP 8, MySQL, gratis,
sin tarjeta). Pasos:

1. Crea cuenta en [infinityfree.net](https://infinityfree.net) y un
   nuevo hosting (te da un subdominio tipo `tuapp.infinityfreeapp.com`).

2. En el panel (cPanel), crea una base de datos MySQL y anota host,
   usuario, password y nombre de BD que te asigna InfinityFree.

3. Importa `appsalon.sql` desde phpMyAdmin (dentro del mismo panel).

4. **En tu compu**, antes de subir:
   - Corre `composer install --no-dev` y `npx gulp` para tener
     `vendor/` y `public/build/` listos (InfinityFree no tiene terminal,
     así que subes todo ya construido).
   - Crea `includes/.env` con los datos de MySQL que te dio InfinityFree
     y con `APP_URL` apuntando a tu subdominio, `APP_ENV=production`.

5. Sube **todo el proyecto** (incluyendo `vendor/` esta vez, aunque esté
   en `.gitignore` para git — eso es solo para no subirlo a GitHub, aquí
   sí lo necesitas) vía FTP (FileZilla) a la carpeta `htdocs`, pero con
   un detalle importante: **el `document root` de InfinityFree debe
   apuntar a tu carpeta `public/`**, no a la raíz del proyecto. Si el
   panel no te deja cambiar el document root, sube el contenido de
   `public/` directo a `htdocs/` y el resto del proyecto (`controllers/`,
   `models/`, `includes/`, `vendor/`, etc.) un nivel arriba de `htdocs/`
   — igual que están organizados ahora mismo en este repo.

6. Verifica que `includes/.env` **no quede accesible públicamente**
   (InfinityFree por defecto no expone nada fuera de `htdocs/`, pero
   confírmalo).

**Alternativa más cómoda** (si no te quieres pelear con FTP y estructura
de carpetas): un VPS barato (Hostinger, DigitalOcean ~$4-6/mes) con
acceso SSH — ahí sí puedes correr `git clone`, `composer install`, `npx
gulp` directo en el servidor, y usar Nginx/Apache apuntando limpio a
`public/`. Dado que ya manejas infraestructura, esta ruta probablemente
te va a ahorrar más dolores de cabeza a mediano plazo que el hosting
gratis.

## ⚠️ Pendientes que valen la pena para producción real

- `ActiveRecord` no soporta consultas preparadas (`bind_param`) — se
  usa escape manual donde hacía falta, pero lo ideal a mediano plazo es
  migrar todo el ORM a prepared statements.
- El correo de confirmación de cita usa el mismo remitente
  (`citas@appsalon.com`) que las demás notificaciones — para producción
  real conviene verificar ese dominio en tu proveedor SMTP para evitar
  que los correos caigan en spam.
- No hay selector de horarios disponibles: el cliente escribe la hora
  libremente (validada solo entre 10am-6pm) — un selector con los
  huecos ya ocupados marcados se sentiría más profesional.
