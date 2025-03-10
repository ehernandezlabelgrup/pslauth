# PSLAuth - Módulo de Autenticación Avanzada para PrestaShop

## Descripción

PSLAuth (PrestaShop Login Authentication) es un módulo avanzado de autenticación para PrestaShop que proporciona una solución completa para el inicio de sesión, registro y gestión de cuentas de usuario. Incluye autenticación social, APIs REST para integración con aplicaciones móviles o servicios externos, y funcionalidades adicionales de gestión de cuentas.

## Características principales

- Sistema personalizado de inicio de sesión y registro
- Autenticación social con Google y otros proveedores
- API REST completa para operaciones de autenticación
- Eliminación de cuentas de usuario (GDPR compliant)
- Interfaz moderna y personalizable
- Integración nativa con PrestaShop

## Requisitos

- PrestaShop 1.7.x o superior
- PHP 7.2 o superior
- Configuración de API de Google (para autenticación social)

## Instalación

1. Descargue el archivo ZIP del módulo
2. Vaya a su panel de administración de PrestaShop > Módulos > Cargar un módulo
3. Seleccione el archivo ZIP descargado
4. Siga las instrucciones de instalación en pantalla

## Estructura del Proyecto

```
pslauth/
│
├── classes/
│   ├── PSLAuthAPI.php            # Gestión de respuestas API
│   ├── PSLAuthGoogleProvider.php # Proveedor de autenticación Google
│   ├── PSLAuthSocialProvider.php # Clase base para proveedores sociales
│   └── PSLAuthUser.php           # Modelo y gestión de usuarios
│
├── controllers/front/
│   ├── callback.php              # Manejo de callbacks OAuth
│   ├── deleteaccount.php         # Eliminación de cuenta (frontend)
│   ├── deleteaccountapi.php      # Eliminación de cuenta (API)
│   ├── login.php                 # Login (frontend)
│   ├── loginapi.php              # Login (API)
│   ├── meapi.php                 # Información de usuario actual (API)
│   ├── register.php              # Registro (frontend)
│   ├── registerapi.php           # Registro (API)
│   └── social.php                # Inicio de flujo de autenticación social
│
├── sql/
│   ├── install.php               # Scripts de instalación BD
│   └── uninstall.php             # Scripts de desinstalación BD
│
├── views/
│   ├── css/
│   │   └── front.css             # Estilos personalizados
│   │
│   ├── js/
│   │   ├── delete_account.js     # JavaScript para eliminación de cuenta
│   │   ├── front.js              # JavaScript común
│   │   ├── login.js              # JavaScript para login
│   │   └── ...                   # Otros archivos JS
│   │
│   └── templates/                # Plantillas Smarty
│
├── index.php                     # Previene acceso directo
├── pslauth.php                   # Archivo principal del módulo
└── README.md                     # Este archivo
```

## Endpoints de API

### Autenticación

```
POST /module/pslauth/loginapi
```
Parámetros:
- `email`: Correo electrónico del usuario
- `password`: Contraseña del usuario
- `stay_logged_in`: (opcional) Mantener sesión iniciada

### Registro

```
POST /module/pslauth/registerapi
```
Parámetros:
- `email`: Correo electrónico
- `password`: Contraseña
- `firstname`: Nombre
- `lastname`: Apellido
- `newsletter`: (opcional) Suscripción al boletín

### Información del Usuario

```
GET /module/pslauth/meapi
```
Headers requeridos:
- `Authorization: Bearer {token}`

### Eliminación de Cuenta

```
POST /module/pslauth/deleteaccountapi
```
Headers requeridos:
- `Authorization: Bearer {token}`
Parámetros:
- `password`: Contraseña del usuario para confirmación

### Autenticación Social

```
GET /module/pslauth/social?provider=google
```
Parámetros:
- `provider`: Proveedor de autenticación (google, facebook, etc.)

## Configuración

### Configuración General

1. Acceda al panel de administración de PrestaShop
2. Vaya a Módulos > Gestor de módulos
3. Encuentre "PSLAuth" y haga clic en "Configurar"

### Configuración de Proveedores Sociales

1. Obtenga las credenciales API del proveedor (Google, etc.)
2. Añada las credenciales en la configuración del módulo
3. Configure la URL de callback: `https://su-tienda.com/module/pslauth/callback`

## Personalización

El módulo permite la personalización de:
- Estilos visuales mediante CSS
- Flujos de autenticación
- Campos de formulario obligatorios
- Mensajes y textos mediante el sistema de traducción de PrestaShop

## Seguridad

- Todos los endpoints están protegidos contra CSRF
- Contraseñas almacenadas con hash seguro
- Protección contra inyección SQL
- Tokens JWT para autenticación API

## Soporte

Para soporte técnico, consultas o personalización, contacte a:
- Email: soporte@ejemplo.com
- Web: https://www.ejemplo.com/soporte

## Licencia

Este módulo está distribuido bajo la Academic Free License (AFL 3.0)
© 2023 OKOI AGENCY S.L.