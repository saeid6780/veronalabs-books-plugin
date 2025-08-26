# Book Info Plugin

[![Total Downloads](https://img.shields.io/packagist/dt/veronalabs/plugin.svg)](https://packagist.org/packages/veronalabs/plugin)
[![Latest Stable Version](https://img.shields.io/packagist/v/veronalabs/plugin.svg)](https://packagist.org/packages/veronalabs/plugin)


## About
A custom WordPress plugin developed with [Rabbit Framework](https://github.com/veronalabs/rabbit) that provides advanced management for books, including custom post types, taxonomies, and a dedicated database table for storing ISBNs.

## Requirements

1. PHP 7.4 or higher.
2. Composer

---

## 📖 Features

- **Custom Database Table**
    - Automatically creates `books_info` table on activation.
    - Stores ISBN numbers mapped to WordPress posts.

- **Custom Post Type: "Book"**
    - Dedicated post type for books.
    - Includes two taxonomies:
        - **Publisher**
        - **Authors**

- **Meta Box for ISBN**
    - ISBN field integrated into the Book edit screen.
    - On save, ISBN is validated and stored in the custom table.

- **Admin Table View**
    - Custom admin page displaying all records from `books_info`.
    - Implemented with `WP_List_Table` for native WordPress UI consistency.

---

## 🛠 Development Guidelines Followed

- **Framework & Architecture**
    - Built with **Rabbit Framework**.
    - Implemented **Dependency Injection** and **Service Architecture**.

- **Version Control**
    - Full development tracked with **Git**.
    - Regular commits and clean history maintained.

- **Internationalization (i18n)**
    - All strings wrapped in WordPress translation functions.
    - Ready for multilingual environments.

- **Security**
    - All inputs/outputs sanitized and validated.
    - Follows WordPress coding standards and security best practices.

---

## 🌍 Extra Features

- **Multilingual Compatibility**
    - Fully internationalized, translation-ready.
- **Code Quality & Design Patterns**
    - Uses **Dependency Injection** and **Singleton Pattern** where applicable.
- **Maintainability**
    - Modular and clean service-oriented architecture.

---

## 📦 Installation

1. Upload the plugin files to the `/wp-content/plugins/books-manager` directory, or install via WordPress Plugin Manager.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. A new post type **"Book"** and taxonomies **Publisher** and **Authors** will be available.
4. Manage ISBNs directly in the admin panel.

---