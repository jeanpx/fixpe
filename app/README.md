# Fixpe App MVP

## Configuracion

1. Edita `app/config.php`.
2. Cambia `db_pass` por la contrasena real de MySQL de Hostinger.
3. Sube la carpeta `app` al hosting.
4. Abre `app/setup-admin.php` para crear el primer admin.

## Flujo inicial

- `app/index.php`: portada minima
- `app/setup-admin.php`: alta unica del primer admin
- `app/register.php`: registro de clientes y proveedores
- `app/login.php`: acceso
- `app/admin.php`: panel admin
- `app/client.php`: panel cliente
- `app/provider.php`: panel proveedor

## Nota

`register.php` es registro publico. No crea admins.
