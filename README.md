PSLAuth - Módulo de Autenticación para PrestaShop

## Descripción

PSLAuth (PrestaShop Login Authentication) es un módulo que proporciona un sistema de autenticación personalizado para tiendas PrestaShop. Permite a los usuarios iniciar sesión y registrarse utilizando formularios personalizados con soporte para API REST, lo que facilita su integración con aplicaciones móviles o servicios externos.

## Requisitos

- PrestaShop 8.0 o superior
- PHP 7.2 o superior
- Módulo compatible con el tema predeterminado de PrestaShop

## Instalación

1. Descarga el archivo ZIP del módulo
2. Ve al panel de administración de PrestaShop > Módulos > Cargar un módulo
3. Selecciona el archivo ZIP descargado
4. Una vez instalado, ve a la configuración del módulo para personalizarlo

## Estructura de Archivos

```
pslauth/
│
├── classes/
│   ├── PSLAuthAPI.php        # Clase para gestionar respuestas API
│   ├── PSLAuthUser.php       # Modelo de usuario y autenticación
│   └── index.php
│
├── controllers/
│   ├── front/
│   │   ├── login.php         # Controlador página de login
│   │   ├── loginapi.php      # Controlador API de login
│   │   ├── register.php      # Controlador página de registro
│   │   ├── registerapi.php   # Controlador API de registro
│   │   └── index.php
│   └── index.php
│
├── sql/
│   ├── install.php           # Script de instalación base de datos
│   ├── unistall.php          # Script de desinstalación
│   └── index.php
│
├── views/
│   ├── css/
│   │   ├── front.css         # Estilos del front-end
│   │   └── index.php
│   │
│   ├── js/
│   │   ├── front.js          # JavaScript común
│   │   ├── login.js          # JavaScript para página de login
│   │   ├── register.js       # JavaScript para página de registro
│   │   └── index.php
│   │
│   ├── templates/
│   │   ├── admin/
│   │   │   └── configure.tpl # Plantilla de configuración
│   │   │
│   │   ├── front/
│   │   │   ├── login.tpl     # Plantilla de login
│   │   │   ├── register.tpl  # Plantilla de registro
│   │   │   └── index.php
│   │   │
│   │   ├── hook/
│   │   │   └── header.tpl    # Plantilla para el hook de header
│   │   │
│   │   └── index.php
│   │
│   └── index.php
│
├── index.php
├── pslauth.php              # Archivo principal del módulo
└── README.md
```

## Rutas API

El módulo proporciona las siguientes rutas de API que pueden ser utilizadas para aplicaciones móviles o servicios externos:


**Requisito importante**: Para todas las llamadas a la API, es necesario incluir el siguiente encabezado:
```
X-API-Request: true
```


### Autenticación de Usuario (Login)

```
POST /module/pslauth/loginapi
```

**Parámetros del cuerpo (JSON):**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "contraseña",
  "stay_logged_in": true,
  "back": "https://mitienda.com/mi-cuenta"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Authentication successful",
  "data": {
    "token": "BASE64_TOKEN.SIGNATURE",
    "customer_id": 123,
    "email": "usuario@ejemplo.com",
    "firstname": "Nombre",
    "lastname": "Apellido",
    "redirect_url": "https://mitienda.com/mi-cuenta"
  }
}
```

### Registro de Usuario

```
POST /module/pslauth/registerapi
```

**Parámetros del cuerpo (JSON):**
```json
{
  "email": "nuevousuario@ejemplo.com",
  "password": "contraseña",
  "firstname": "Nombre",
  "lastname": "Apellido",
  "id_gender": 1,
  "birthday": "1990-01-01",
  "newsletter": true,
  "psgdpr": true,
  "back": "https://mitienda.com/mi-cuenta"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token": "BASE64_TOKEN.SIGNATURE",
    "customer_id": 124,
    "email": "nuevousuario@ejemplo.com",
    "firstname": "Nombre",
    "lastname": "Apellido",
    "redirect_url": "https://mitienda.com/mi-cuenta"
  }
}
```

## Funcionalidades

- Sistema personalizado de inicio de sesión y registro
- Compatibilidad con la API REST para aplicaciones móviles
- Sistema de tokens para autenticación
- Soporte para usuarios de PrestaShop existentes
- Integración con sistema de newsletter
- Validación del lado del cliente y servidor
- Opciones de "recordarme" para sesiones más largas

## Configuración

Después de instalar el módulo, puedes configurarlo desde el panel de administración de PrestaShop:

1. Ve a Módulos > Gestor de módulos
2. Busca "PSLAuth" o "PrestaShop Login Authentication"
3. Haz clic en "Configurar" para acceder a las opciones del módulo

## Seguridad

- Las contraseñas se almacenan utilizando hash seguro (password_hash)
- Validación completa en el cliente y servidor
- Protección contra redirecciones no autorizadas
- Tokens firmados para autenticación API

## Licencia

Este módulo está bajo la Academic Free License (AFL 3.0).

## Autor

Desarrollado por Emilio Hernandez para OKOI AGENCY S.L.