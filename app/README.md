# Fixpe App MVP

## Configuracion

1. Edita `app/config.php`.
2. Cambia `db_pass` por la contrasena real de MySQL de Hostinger.
3. Sube la carpeta `app` al hosting.

## Flujo inicial

- `app/index.php`: portada minima
- `app/register.php`: registro de clientes y proveedores
- `app/login.php`: acceso
- `app/admin.php`: panel admin
- `app/client.php`: panel cliente
- `app/provider.php`: panel proveedor

## Admin inicial

Inserta manualmente un admin con un hash generado por PHP:

```php
<?php echo password_hash('TU_PASSWORD_SEGURA', PASSWORD_DEFAULT); ?>
```

Luego guarda ese hash en la tabla `users`.
