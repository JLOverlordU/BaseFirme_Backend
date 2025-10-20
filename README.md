# 🚀 Sistema de Stock Rápido - BaseFirme_Backend

Este proyecto es un **sistema de gestión de stock** desarrollado con **Laravel**. Su objetivo principal es ofrecer una solución ágil y escalable para controlar productos, inventario, compras, ventas y usuarios, permitiendo una administración eficiente del almacén o inventario de una empresa.

---

## 📦 Características Principales

- Gestión de productos y stock.
- Registro de compras y ventas.
- Control de roles y usuarios.
- Exportación de reportes.
- API REST para conexión con el frontend.

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** Laravel
- **Base de datos:** MySQL / MariaDB
- **Autenticación:** Laravel Sanctum o Passport (dependiendo del setup)
- **Exportación de datos:** Laravel Excel / PDF (opcional)

---

## 📁 Estructura de Carpetas Relevante

app/
├── Http/
│   ├── Controllers/
│   │   ├── ClientController.php
│   │   ├── ProductController.php
│   │   ├── PurchaseController.php
│   │   ├── SaleController.php
│   │   ├── UserController.php
│   │   └── ...
│   ├── Middleware/
│   └── Requests/
├── Models/
└── Providers/