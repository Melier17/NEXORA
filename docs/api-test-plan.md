# NEXORA — Plan de pruebas manuales de integración

Este plan se ejecuta **cuando el backend esté instalado localmente y las migraciones hayan sido aplicadas**. No requiere publicar NEXORA ni usar datos de personas reales. Utiliza cuentas ficticias y una base de datos local de pruebas.

## 1. Preparación

- Confirmar PHP 8.3+, Composer, MySQL y extensiones PDO MySQL y mbstring.
- Crear una base vacía `nexora_db`.
- Configurar `.env` a partir de `.env.example`; no compartir ni subir ese archivo.
- Ejecutar `composer install`.
- Aplicar las migraciones SQL en orden numérico.
- Configurar Apache con `backend/public` como raíz pública.
- Ejecutar `composer test`.

Definir la URL local de la API, por ejemplo `http://localhost/api/v1`, y comprobar `GET /health`.

## 2. Flujo principal de extremo a extremo

Usar Postman, Insomnia o `curl`. En las peticiones JSON incluir `Content-Type: application/json`.

1. Registrar una cuenta ficticia con `POST /auth/register`.
2. Iniciar sesión con `POST /auth/login` y conservar el token solo en el entorno local de pruebas.
3. Consultar `GET /auth/me` enviando `Authorization: Bearer <token>`.
4. Crear un espacio con `POST /workspaces`; guardar el identificador devuelto.
5. Listar espacios con `GET /workspaces` y consultar el espacio creado.
6. Crear un proyecto con `POST /workspaces/{workspaceId}/projects`.
7. Crear una tarea con `POST /workspaces/{workspaceId}/projects/{projectId}/tasks`.
8. Consultar las tareas y confirmar que la recién creada aparece.
9. Agregar un comentario a la tarea y comprobarlo con el endpoint GET de comentarios.
10. Crear una etiqueta, asignarla a la tarea y consultar las etiquetas asignadas.
11. Consultar `GET /workspaces/{workspaceId}/activity` y verificar que aparecen los eventos del flujo.
12. Cerrar sesión con `POST /auth/logout`.
13. Repetir `GET /auth/me` con el token revocado y confirmar que el acceso se rechaza.

## 3. Pruebas de permisos

Crear una segunda cuenta ficticia y agregarla al espacio por correo.

- Un miembro puede consultar el espacio, proyectos y tareas.
- Un miembro no puede crear ni modificar proyectos.
- Un miembro no puede administrar etiquetas.
- Un administrador puede gestionar miembros regulares, pero no asignar el rol admin.
- Un usuario que no pertenece al espacio no debe poder consultar sus recursos.
- Un usuario no puede asignar una tarea a alguien que no sea miembro del espacio.
- El owner no puede ser retirado ni degradado mediante las rutas de miembros.

## 4. Validaciones y errores

- Registro con correo inválido o campos requeridos ausentes: respuesta de validación.
- Registro con correo ya existente: conflicto, sin duplicar la cuenta.
- Inicio de sesión con contraseña incorrecta: rechazo sin revelar si el correo existe.
- Petición protegida sin token, con token inválido o revocado: rechazo de autenticación.
- Proyecto o tarea con estado no permitido: respuesta de validación.
- Fecha imposible o con formato incorrecto: respuesta de validación.
- Nombre de etiqueta duplicado en el mismo espacio: conflicto.
- Identificador de recurso inexistente o ajeno al espacio: no debe revelar recursos privados.

## 5. Criterios para dar el MVP por verificado

- [ ] Migraciones ejecutadas sin errores en una base vacía.
- [ ] Conexión PDO confirmada.
- [ ] Registro, login, consulta de usuario y logout verificados.
- [ ] Creación y consulta de espacios y membresías verificadas.
- [ ] Creación, consulta y actualización de proyectos y tareas verificadas.
- [ ] Comentarios, etiquetas e historial verificados.
- [ ] Pruebas de permisos y acceso entre espacios verificadas.
- [ ] No se filtran errores internos, contraseñas ni tokens en respuestas o logs.
- [ ] Frontend conectado a la API o, si se conserva como demo independiente, claramente identificado como prototipo.
- [ ] README y documentación reflejan el estado real del código.

**Importante:** que GitHub Actions pase las pruebas unitarias y la sintaxis PHP no sustituye estas pruebas de integración. El MVP se considera verificado cuando el flujo completo funciona contra MySQL local.
