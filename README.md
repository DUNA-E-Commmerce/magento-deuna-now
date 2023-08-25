# Módulo Deuna Now
![Magento](https://img.shields.io/badge/Magento-2.4.5-blue.svg)

El módulo Deuna Now agrega el metodo deuna now a tu tienda Magento.

## Características
- Integración transparente de un checkout interno en Magento.
- Personalización del flujo de pago según los requisitos del proveedor externo.
- Interfaz de administración para configurar los parámetros de integración.
- Validación de datos y manipulación segura de pagos externos.
- Compatibilidad con Magento 2.4.5

## Requisitos

- Magento 2.4.5 instalado y funcionando correctamente.

## Instalación

1. Instalacion via Composer en el root del proyecto magento:

```sh
   composer require deuna/magento-deuna-now:dev-develop
```

## Uso
Una vez instalado y habilitado el módulo, el método de pago "Deuna Now Payment" estará disponible en el proceso de pago de la tienda.

## Configuración
Inicia sesión en el panel de administración de Magento.

Ve a "Stores" (Tiendas) > "Configuration" (Configuración) > "Sales" (Ventas) > "Payment Methods" (Métodos de Pago).

Configura las opciones relacionadas con el método de pago "Deuna Now Payment".
