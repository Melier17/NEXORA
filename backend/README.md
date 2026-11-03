# NEXORA Backend

REST API de NEXORA, construida con PHP 8.3+, PDO y MySQL 8+. El backend está organizado por capas (controladores, servicios y repositorios) y expone respuestas JSON bajo el prefijo `/api/v1`.

## Requisitos

- PHP 8.3+
- MySQL 8.0.16+
- Composer
- Apache con `mod_rewrite` (para la configuración local con XAMPP)
- Extensiones PHP: PDO, PDO MySQL, OpenSSL y mbstring

## Instalación local

1. Crear una base de datos vacía llamada `nexora_db` con codificación `utf8mb4`.
2. Copiar `.env.example` como `.env` y configurar las credenciales locales.
3. Desde esta carpeta, ejecutar `composer install`.
4. Ejecutar los archivos SQL de `database/migrations/` en orden numérico, una sola vez y sobre la base vacía.
5. Configurar Apache para que el document root sea exclusivamente `backend/public`.
6. Consultar `/api/v1/health` para comprobar que el punto de entrada responde.
7. Ejecutar `composer test` para correr las pruebas unitarias.

Nunca subir el archivo real `.env`, contraseñas, tokens ni credenciales al repositorio.

## Autenticación

Todas las rutas protegidas requieren un token Bearer activo en la cabecera `Authorization: Bearer <token>`.

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/v1/auth/register` | Registrar usuario con `name`, `email`, `password` y `password_confirmation`. |
| POST | `/api/v1/auth/login` | Iniciar sesión con correo y contraseña. |
| GET | `/api/v1/auth/me` | Consultar el usuario autenticado. |
| POST | `/api/v1/auth/logout` | Revocar el token actual. |

Los tokens son valores opacos generados con `random_bytes()`; la base de datos almacena únicamente su hash SHA-256. La duración predeterminada es de 60 minutos y se configura mediante `ACCESS_TOKEN_TTL_MINUTES` (mínimo 5).

## Espacios de trabajo y miembros

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/workspaces` | Listar espacios del usuario. |
| POST | `/api/v1/workspaces` | Crear espacio; el creador obtiene el rol owner. |
| GET | `/api/v1/workspaces/{workspaceId}` | Consultar un espacio al que pertenece el usuario. |
| GET | `/api/v1/workspaces/{workspaceId}/members` | Listar miembros. |
| POST | `/api/v1/workspaces/{workspaceId}/members` | Agregar una cuenta activa existente por correo. |
| PATCH | `/api/v1/workspaces/{workspaceId}/members/{userId}` | Cambiar el rol de un miembro. |
| DELETE | `/api/v1/workspaces/{workspaceId}/members/{userId}` | Retirar un miembro según los permisos. |

Roles: `owner` administra el espacio; `admin` puede gestionar miembros regulares; `member` tiene acceso de miembro. El owner no puede ser retirado ni degradado mediante estas rutas. Las invitaciones por correo y las invitaciones pendientes aún no están implementadas.

## Proyectos y tareas

| Método | Ruta | Descripción |
|---|---|---|
| GET / POST | `/api/v1/workspaces/{workspaceId}/projects` | Listar o crear proyectos. |
| GET / PATCH | `/api/v1/workspaces/{workspaceId}/projects/{projectId}` | Consultar o actualizar un proyecto. |
| GET / POST | `/api/v1/workspaces/{workspaceId}/projects/{projectId}/tasks` | Listar o crear tareas del proyecto. |
| PATCH / DELETE | `/api/v1/workspaces/{workspaceId}/tasks/{taskId}` | Actualizar o eliminar una tarea. |

Los proyectos admiten estados `planning`, `active`, `on_hold`, `completed` y `archived`. Las tareas admiten estados `todo`, `in_progress`, `review` y `done`, y prioridades `low`, `medium`, `high` y `urgent`. La persona asignada debe pertenecer al espacio de trabajo.

## Comentarios, etiquetas e historial

| Método | Ruta | Descripción |
|---|---|---|
| GET / POST | `/api/v1/workspaces/{workspaceId}/tasks/{taskId}/comments` | Consultar o agregar comentarios. |
| DELETE | `/api/v1/workspaces/{workspaceId}/comments/{commentId}` | Eliminar un comentario según permisos. |
| GET / POST | `/api/v1/workspaces/{workspaceId}/labels` | Listar o crear etiquetas. |
| PATCH / DELETE | `/api/v1/workspaces/{workspaceId}/labels/{labelId}` | Actualizar o eliminar etiquetas. |
| GET / POST | `/api/v1/workspaces/{workspaceId}/tasks/{taskId}/labels` | Consultar o asignar etiquetas a una tarea. |
| DELETE | `/api/v1/workspaces/{workspaceId}/tasks/{taskId}/labels/{labelId}` | Quitar una etiqueta de una tarea. |
| GET | `/api/v1/workspaces/{workspaceId}/activity` | Consultar el historial de actividad; admite `limit` y `before_id`. |

## Estado real del proyecto

- Implementados en código: autenticación, tokens, espacios de trabajo, membresías y roles, proyectos, tareas, comentarios, etiquetas e historial de actividad.
- Seguridad implementada en el código: consultas PDO preparadas, hash de contraseñas, almacenamiento de hashes de tokens, validación de entradas y comprobación de membresía/roles.
- Migraciones SQL creadas, pero **su ejecución en la base MySQL local aún está pendiente de verificación**.
- GitHub Actions ejecuta comprobaciones de sintaxis PHP y pruebas unitarias. Las ejecuciones recientes finalizaron correctamente.
- Las pruebas automatizadas actuales cubren el validador; todavía faltan pruebas de integración contra MySQL y recorridos completos de la API.
- La interfaz de `frontend/` es una demostración visual independiente y **todavía no está conectada a esta API**.
- No implementado todavía: verificación de correo, recuperación de contraseña, rotación de refresh tokens, invitaciones por correo y limitación de intentos de inicio de sesión.

El código del backend se mantiene fuera del directorio público. Apache debe servir únicamente `public/`. No usar `APP_DEBUG=true` en un entorno expuesto.

## Documentación adicional

- [Arquitectura de la API](../docs/api-architecture.md)
- [Proyectos y tareas](../docs/projects-and-tasks-api.md)
- [Comentarios, etiquetas e historial](../docs/comments-labels-activity-api.md)
