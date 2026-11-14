# Cliente Web PHP para Consumo de API REST — Aula API Lab

Este proyecto consiste en el desarrollo de una interfaz de cliente web desarrollada en **PHP native** y **Bootstrap 5**, diseñada para interactuar con la **API REST remota** del laboratorio (`https://darkred-rook-511555.hostingersite.com/api/v1`).

El sistema implementa la librería **cURL** en el servidor local para realizar peticiones HTTP orientadas a la consulta de inventarios, verificación de estado del servicio y procesamiento de pedidos con serialización estricta JSON y autenticación por cabeceras.

---

## 🚀 Características del Proyecto

- **Consumo de Endpoints REST**: Integración directa mediante cURL sin librerías externas.
- **Autenticación por Cabeceras**: Envíos de credenciales de seguridad utilizando el encabezado HTTP `X-API-KEY`.
- **Procesamiento de Métodos HTTP**:
  - `GET /health.php`: Verificación pública del estado del servicio.
  - `GET /products.php`: Obtención en tiempo real del catálogo de productos y stock.
  - `GET /product.php?id={id}`: Consulta detallada de un producto por su identificador.
  - `POST /orders.php`: Creación e inserción remota de pedidos con estructuración anidada `items`.
- **Manejo Dinámico de Respuestas HTTP**:
  - Intercepción y procesamiento de códigos de respuesta (`200 OK`, `201 Created`, `401 Unauthorized`, `422 Unprocessable Entity`, `405 Method Not Allowed`).
  - Renderizado de consolas de diagnóstico *Raw Response* para auditoría de payloads JSON.
- **Búsqueda y Filtrado**: Filtrado dinámico local por categorías y coincidencia de SKU/nombre de productos.

---

## 🛠️ Tecnologías Utilizadas

- **Lenguaje Principal**: PHP 8.x (cURL extension activada).
- **Servidor Web Local**: Apache 2.4 (Entorno XAMPP en Windows).
- **Frontend & UI**: HTML5, CSS3, Bootstrap 5.3, FontAwesome 6.4.
- **Formato de Datos**: JSON (`application/json`).

---

## ⚙️ Estructura del Payload para Pedidos (`POST /orders.php`)

Para el envío correcto de transacciones al backend remoto, el cliente cURL genera y transmite el siguiente esquema JSON:

```json
{
  "customer_name": "Kristel Benavides",
  "items": [
    {
      "product_id": 12,
      "quantity": 1
    }
  ]
}