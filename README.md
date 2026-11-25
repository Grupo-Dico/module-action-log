# GDMexico_AdminActionLog (module-action-log)

Registro de actividad (audit trail) del admin para Magento 2 Community Edition. Guarda quién hizo qué, cuándo y desde qué IP.
Compatible con Magento 2.4.2 a 2.4.9 (PHP >= 7.4).

## Qué registra

Una fila por acción del admin (mismo formato que el Action Log de Adobe Commerce): **Time, Action Group, Action, IP Address, User Name, Result, Full Action Name, Details y Full Details**.

- Guardados, eliminaciones, acciones masivas, exportaciones/importaciones, limpieza de caché, inicios y cierres de sesión (exitosos y fallidos). Opcionalmente también vistas y listados (*Log Views and Listings*, apagado por defecto por volumen).
- **Full Details** (enlace *View*): todos los modelos modificados por la acción, con valor anterior y nuevo por campo. Los cambios de Stores > Configuration se registran con la ruta y el scope.
- Valores sensibles (contraseñas, tokens, API keys, campos cifrados) se guardan enmascarados (`********`).
- **Result** es *Failure* si la acción muestra un mensaje de error o responde con código HTTP >= 400.

## Qué NO registra

- Cambios hechos fuera del área admin (CLI, cron, API REST, importaciones por consola).
- Escrituras directas por SQL que no pasan por modelos (p. ej. algunas acciones masivas de precio o importaciones CSV). Para productos, ver `LeanCommerce_ProductAudit`.
- Campos que no son escalares (arrays/objetos) ni los listados en `ignoredFields`.

## Consulta, exportación y permisos

- **System > Tools > Action Log**: filtros por fecha, usuario, IP, acción, entidad. Botón **Export** (CSV/XML) respeta los filtros aplicados.
- ACL: `GDMexico_AdminActionLog::actionlog` (ver el log) y `GDMexico_AdminActionLog::config` (configuración). Asignarlos solo a roles de confianza.
- El log es de solo lectura: no hay opción para editar o borrar registros desde el admin.

## Configuración y retención

**Stores > Configuration > GD México > Admin Action Log**

| Campo | Valores |
|---|---|
| Enable Action Log | Sí / No (default Sí) |
| Log Views and Listings | Sí / No (default No) |
| Keep Records For | 1 mes, 2 meses, 3 meses (default), 6 meses, 1 año, o siempre |
| Cleanup Time | Hora del día en que corre la limpieza (default 03:30) |

Un cron diario (`gdm_admin_action_log_cleanup`, a la hora configurada) borra en lotes de 10,000 los registros más antiguos que el periodo elegido. Requiere que el cron de Magento esté activo.

## Ajustes por sitio

Modelos y campos ignorados se definen en `etc/di.xml` (`ChangeDetector`: `ignoredClasses`, `ignoredFields`). Para ampliarlos en un sitio, sobrescribir el argumento desde un `di.xml` propio.

La IP se obtiene con `Magento\Framework\HTTP\PhpEnvironment\RemoteAddress`. Si el sitio está detrás de proxy/CDN, verificar en test que se registra la IP del cliente y no la del proxy; de lo contrario, indicar el header real (p. ej. `HTTP_X_FORWARDED_FOR`) en el argumento `alternativeHeaders` de `RemoteAddress` vía `di.xml`.

## Instalación

```bash
composer config repositories.action-log vcs git@github.com:Grupo-Dico/module-action-log.git
composer require gdmexico/module-action-log:^1.0
php bin/magento module:enable GDMexico_AdminActionLog
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f es_MX en_US
php bin/magento cache:flush
```

En el repo `dico` se declara igual que los demás módulos propios: entrada `vcs` en `repositories` y `require` en `composer.json`.

Compatibilidad: Magento 2.4.2 a 2.4.9, PHP 7.4 a 8.5 (el código evita sintaxis de PHP 8 para seguir funcionando en 7.4).

## Validación en test

1. Editar un producto, una página CMS y un valor de configuración; confirmar que aparecen con usuario, IP y valores anterior/nuevo.
2. Eliminar un bloque CMS de prueba; confirmar acción `delete`.
3. Iniciar sesión con contraseña incorrecta; confirmar `login_failed`.
4. Exportar a CSV con un filtro de fecha.
5. Cambiar retención a 1 mes, insertar un registro con `created_at` de hace 2 meses y ejecutar `php bin/magento cron:run`; confirmar que se elimina.
6. Medir el tiempo de guardado de un producto con y sin el módulo.
