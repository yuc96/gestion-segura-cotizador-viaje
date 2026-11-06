# Gestión Segura — Cotizador de seguro de viaje

Aplicación para cotizar y contratar un seguro de viaje. El cliente ingresa sus datos y el viaje, recibe la prima, descarga la cotización en PDF y puede confirmar la contratación. Las cotizaciones y los contratos se consultan en un listado con filtros y paginación.

La prima es **USD 3 por día**, más un recargo según la región del destino. No hay pasarela de pago: al contratar, el registro pasa de **Cotizado** a **Contratado**.

## Requisitos

- PHP 8.3 con `mbstring`, `xml`, `curl`, `zip`, `pdo_mysql`, `bcmath`, `intl` y `gd`
- Composer 2
- Node.js 22 y npm
- MySQL 8

## Instalación

```bash
git clone <repositorio> seguro-viaje
cd seguro-viaje
composer install
cp .env.example .env
php artisan key:generate
```

Crea la base de datos y el usuario. El `.env.example` usa estos valores locales, que no son credenciales de producción:

```sql
CREATE DATABASE gestion_segura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'seguro'@'localhost' IDENTIFIED BY 'seguro';
GRANT ALL PRIVILEGES ON gestion_segura.* TO 'seguro'@'localhost';
```

Ajusta `DB_*` en `.env` si tu MySQL usa otro usuario. Después:

```bash
php artisan migrate --seed
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=43123
```

Abre [http://127.0.0.1:43123](http://127.0.0.1:43123).

`migrate --seed` carga el catálogo de países y doce cotizaciones de ejemplo. Para rehacer los datos: `php artisan migrate:fresh --seed`.

Durante el desarrollo del frontend se puede usar `npm run dev` en otra terminal. Laravel toma los assets de Vite mientras ese proceso está activo.

## Docker

```bash
docker compose up --build
docker compose exec app php artisan db:seed
```

La aplicación queda en [http://127.0.0.1:43123](http://127.0.0.1:43123) y MySQL en el puerto `33060`. La contraseña local del contenedor también es `seguro`.

## Pruebas

```bash
php artisan test
```

Pest ejecuta las pruebas sobre SQLite en memoria. El esquema no usa tipos exclusivos de MySQL, así que la suite no necesita un servidor de base de datos. La aplicación en sí corre sobre MySQL. La integración con REST Countries se prueba con `Http::fake()`: tiempos de espera, HTTP de error, cuerpo deprecado, payload de demostración y respuestas que no son JSON.

## Cómo se usa

1. **Cotizar.** Nombres, apellidos, identificación, correo, nacimiento, país, salida y regreso.
2. La pantalla siguiente muestra días, tarifa base, porcentaje, recargo y total.
3. **Descargar PDF** genera el documento de esa cotización.
4. **Contratar seguro** guarda la fecha de contratación y cambia el estado. No se puede contratar dos veces.
5. **Contrataciones** lista cliente, identificación, destino, fechas, valor, estado y fecha de creación. Se puede buscar, filtrar por estado y por rango de salida, y paginar.

## Arquitectura

La interfaz es una SPA de Vue servida por Laravel. El navegador habla con un API JSON en `/api`. Así los controladores no renderizan HTML de negocio y las pruebas de Pest ejercitan el mismo contrato que usa la pantalla.

```
app/Domain/Pricing        Tarifa, días y región. Sin HTTP ni Eloquent.
app/Domain/Countries      Datos de país y errores del proveedor.
app/Domain/Quotes         Estados Cotizado y Contratado.
app/Actions/Quotes        Crear la cotización, contratarla y renderizar el PDF.
app/Services/Countries    Cliente HTTP, normalizador y catálogo con respaldo.
app/Queries               Listado filtrado.
app/Http                  Form Requests, controladores delgados y resources.
resources/js              Vue 3, Vue Router y Pinia. Sin cálculo de prima.
```

Los controladores reciben el request, llaman a una acción o a una consulta y devuelven un resource. Vue valida el formulario para responder al instante, pero el precio, los días y la región salen siempre del servidor.

Cada cotización guarda su propio asegurado (`insureds` 1—N `quotes`). Es una foto del momento: una cotización posterior no reescribe el nombre ni la prima de la anterior. En la cotización también quedan el código y el nombre del destino, la región tarifaria, los días, la tarifa diaria aplicada, la base, el porcentaje, el recargo y el total. Si mañana la tarifa deja de ser USD 3, las cotizaciones viejas no cambian.

Los montos se guardan en centavos. Con USD 3.00 y recargos de 0, 15, 20 y 25 por ciento la división es exacta; de todos modos el redondeo es half-up al centavo.

Los días incluyen la salida y el regreso. Del 1 al 10 son 10 días: `10 × 3 = 30`. España (Europa, 20 %) queda en `30 + 6 = 36`.

## REST Countries

La v3 pública de [REST Countries](https://restcountries.com/) responde hoy que esa versión fue retirada. La v5 vive en `https://api.restcountries.com/countries/v5` y pide `Authorization: Bearer`. La key de demostración publicada en su documentación no devuelve el catálogo: devuelve un objeto de ejemplo marcado como `_demo`.

El cliente hace lo siguiente:

1. Si `RESTCOUNTRIES_API_KEY` tiene valor, consulta la v5 con timeout de 8 segundos, un reintento ante fallo de conexión y páginas de 100.
2. Acepta el sobre v5 (`data.objects`) y, si apareciera, el arreglo legado con `cca2`.
3. Rechaza `success: false`, el payload de demostración, un cuerpo que no es JSON, un HTTP de error y una lista sin países utilizables. Las filas mal formadas se saltan.
4. Si la lectura sirve, se guarda en la tabla `countries` y en caché durante 12 horas.
5. Si falla, se usa esa tabla. Si todavía está vacía, se usa `database/data/countries.json`.
6. El fallo se cachea solo 10 minutos, para volver a intentar la API pronto, y se escribe un log con el motivo (`timeout`, `http_error`, `unexpected`, `unconfigured`).

Sin key no se llama a la red: el catálogo local alcanza para cotizar. Con una key real, el mismo código refresca países, región y subregión. La pantalla avisa cuando está mostrando caché o el archivo local.

La API agrupa América en una sola región. La tarifa no. `RegionResolver` usa la subregión:

| Subregión o región | Tarifa | Recargo |
| --- | --- | --- |
| South America | Sudamérica | 0 % |
| North America, Northern America, Central America, Caribbean | Norteamérica | 15 % |
| Europe | Europa | 20 % |
| Africa | África | 20 % |
| Asia | Asia | 25 % |
| Oceania | Oceanía | 25 % |
| Antártida u otra región desconocida | Otras regiones | 25 % |

Centroamérica y el Caribe no tienen fila propia en la tabla del enunciado. Se tarifican con Norteamérica, que es el recargo mayor dentro de América. Antártida usa el recargo más alto, para no cotizar una región desconocida a 0 %.

El archivo de respaldo sale del conjunto público de REST Countries (nombres en español, código ISO, región y subregión) y queda atribuido en el propio JSON.

## Validaciones

En el formulario y en el `StoreQuoteRequest`:

- Nombres y apellidos obligatorios, de 2 a 80 caracteres, solo letras (se aceptan tildes, espacios, apóstrofos y guiones).
- Identificación de 5 a 20 letras o números. Sirve para cédula o pasaporte.
- Correo con formato válido.
- Nacimiento en el pasado. Entre 18 y 85 años en la fecha de salida.
- Destino con código ISO existente en el catálogo.
- Salida hoy o después. Regreso igual o posterior a la salida. Máximo 365 días.

## Decisiones

- **Laravel 13, Vue 3 y MySQL 8.** Son el stack pedido, en las versiones estables actuales. PHP 8.3 alcanza; Pest 5 ya pide PHP 8.4, así que la suite usa Pest 4.
- **API JSON + SPA**, en lugar de Blade o Inertia, porque las pruebas de API y la pantalla consumen el mismo contrato. Pinia guarda países, tarifa y cotizaciones; los componentes no recalculan la prima.
- **Dompdf** para el PDF. No depende de un navegador y el documento usa los montos ya guardados.
- **Caché de archivo y sesión de archivo.** El catálogo no necesita Redis para esta escala.
- **Zona `America/Guayaquil`.** La fecha de contratación se muestra en la hora de la compañía.
- **SQLite solo en pruebas.** MySQL sigue siendo la base de la aplicación y de Docker.
- **Sin autenticación ni pagos.** El enunciado no los pide. Agregarlos ahora habría sido superficie de más.

## Mejoras si esto pasara a producción

- Autenticación del cliente y un perfil distinto para quien consulta el listado interno.
- Pasarela de pago y emisión real de la póliza, con el PDF firmado o numerado por un secuencial propio.
- Separar el cliente maestro de la foto guardada en cada cotización, y permitir varios viajeros por póliza.
- Editar la tarifa sin desplegar código, con vigencia, para no mezclar precios viejos y nuevos.
- Sincronizar países con un job programado, en vez de hacerlo en la primera lectura del request.
- Límites de tasa, idempotencia al contratar y una cola para el PDF.
- Más cobertura de extremo a extremo en el navegador y observabilidad sobre los fallbacks de la API.

## Qué quedó fuera

El flujo pedido está completo: cotización, validaciones, PDF, países, tarifa, contratación, listado, migraciones, seeders, factories, Pest, Docker y CI. No hay login ni cobro porque el enunciado los deja fuera.
