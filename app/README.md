# Fixpe App MVP

## Configuración

1. Edita `app/config.php`.
2. Cambia `db_pass` por la contraseña real de MySQL de Hostinger.
3. Ejecuta el SQL de `app/sql/marketplace_v2.sql` en phpMyAdmin.
4. Sube la carpeta `app` al hosting.
5. Abre `app/setup-admin.php` para crear el primer admin si aún no existe.

## Flujo inicial

- `app/index.php`: portada mínima
- `app/setup-admin.php`: alta única del primer admin
- `app/register.php`: registro de clientes y proveedores
- `app/login.php`: acceso
- `app/admin.php`: panel admin
- `app/client.php`: panel cliente
- `app/provider.php`: panel proveedor
- `app/explore-providers.php`: directorio de especialistas para clientes
- `app/provider-profile.php`: perfil público del partner y solicitud directa
- `app/browse-requests.php`: tablero de requerimientos para partners
- `app/request-detail.php`: detalle del requerimiento y cotizaciones

## Nota

`register.php` es registro público. No crea admins.
