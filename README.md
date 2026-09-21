# Nuwek KPI Challenge

Solución al reto técnico de Grupo Nuwek para la importación y análisis de indicadores comerciales.

## Requisitos Previos

- **Docker y Docker Compose**: La solución está completamente dockerizada. No necesitas tener PHP o MySQL instalados localmente.
- **Git**: Para clonar el repositorio.

## Instalación y Configuración

1. Clona el repositorio:
   ```bash
   git clone https://github.com/09193919307/nuwek-kpi-challenge.git
   cd nuwek-kpi-challenge
   ```
2. Levanta los contenedores y construye la imagen (esto iniciará PHP 8.2 con Apache y dos instancias de MySQL: una para la app y otra para pruebas):
   ```bash
   docker-compose up -d --build
   ```
3. Instala las dependencias del proyecto dentro del contenedor:
   ```bash
   docker exec nuwek_app composer install
   ```
4. Genera la llave de la aplicación y ejecuta las migraciones:
   ```bash
   docker exec nuwek_app php artisan key:generate
   docker exec nuwek_app php artisan migrate
   ```

## Importación de Datos

Se ha creado un comando Artisan para limpiar, normalizar e importar los datos de manera idempotente (evita duplicados).

```bash
docker exec nuwek_app php artisan ventas data/ventas.csv
```

**Comprobaciones de la regla de negocio implementada:**
- Convierte diferentes formatos de fechas y omite las inválidas o vacías.
- Normaliza los montos, removiendo caracteres no numéricos o comas y convirtiéndolos en formato válido, descartando los que no tengan sentido numérico.
- Unifica las regiones y estatus sin importar mayúsculas, minúsculas o acentos.

## API de Resumen de Ventas

Endpoint para consultar el total acumulado y conteo de transacciones, considerando **exclusivamente** las ventas con estatus `cerrada`.

**Endpoint:** `GET /api/ventas/resumen`

### Ejemplos de uso (cURL)

**1. Histórico completo:**
```bash
curl -s http://localhost:8000/api/ventas/resumen
```

**2. Filtrado por fechas:**
```bash
curl -s "http://localhost:8000/api/ventas/resumen?fecha_inicio=2026-01-01&fecha_fin=2026-03-31"
```

## Pruebas (Testing)

Se configuró un entorno aislado mediante una base de datos MySQL exclusiva para pruebas (`nuwek_test`), levantada automáticamente por Docker.

1. Prepara la base de datos de pruebas (solo la primera vez):
   ```bash
   docker exec nuwek_app php artisan migrate --env=testing
   ```
2. Ejecuta la suite de pruebas unitarias y de integración:
   ```bash
   docker exec nuwek_app php artisan test --env=testing
   ```

## Decisiones Técnicas Relevantes

- **Desacoplamiento (Clean Code):** La lógica pesada de lectura del CSV y la normalización de cada registro se abstrajeron a la clase de servicio `VentasCleaner`, permitiendo poder testear la limpieza aislada sin depender del comando ni de la base de datos.
- **Entorno de Pruebas Aislado:** Se agregó `mysql-test` en Docker para garantizar que `php artisan test` nunca corrompa ni vacíe la base de datos local principal, resolviendo de forma limpia la interferencia de variables de entorno de Docker.
- **Validación del API:** Se reemplazó el tradicional error HTTP 422 de Laravel por un código 400 personalizado al validar las fechas de entrada, como fue requerido explícitamente en el reto.
