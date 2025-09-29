# Task A – Bulk CSV Import & Image Upload with FilePond

This project demonstrates a Laravel 12 application that implements:

- **Bulk CSV Product Import** with upsert logic and background job processing  
- **Drag-and-drop Image Upload** using [FilePond](https://pqina.nl/filepond/) with chunked/resumable uploads  
- **Queued Image Variant Generation** (256px, 512px, 1024px) using Intervention Image  
- **Database-backed storage** of products, images, and their variants  

---

## ⚡ Features

### 1. Bulk CSV Import
- Upload a CSV file with ≥10,000 rows of products.  
- Each row contains: `sku`, `name`, `price`, `stock`.  
- Data is **upserted** (creates new products or updates existing ones by `sku`).  
- Heavy processing runs in a **queued job** for scalability.  

### 2. Drag-and-Drop Image Upload
- Users can drag & drop images into the browser.  
- FilePond handles:
  - Chunked uploads (resumable on network failure)  
  - Client-side validation (file type, size limit)  
- Laravel saves the file to disk (`storage/app/public/uploads`) and dispatches a job.  
- A queued job generates variants (256px, 512px, 1024px) and stores metadata in DB.  

---

## 🛠 Tech Stack
- **Laravel 12** (PHP 8.3+)  
- **MySQL 8+** (or MariaDB)  
- **FilePond.js** for file uploads  
- **Intervention Image** for image processing  
- **Laravel Queue (database driver)** for background jobs  

---

## 🚀 Setup & Installation

1. **Clone the repository**
    ```bash
    git clone https://github.com/mehulkoradiya/laravel-task-a.git
    cd laravel-task-a
    ```

2. **Install dependencies**
    ```bash
    composer install
    npm install && npm run build
    ```

3. **Configure environment**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Set DB and queue settings in [.env](http://_vscodecontentref_/0):
    ```
    DB_CONNECTION=mysql
    DB_DATABASE=laravel_task_a
    DB_USERNAME=root
    DB_PASSWORD=

    QUEUE_CONNECTION=database
    ```

4. **Publish and Run migrations**
    ```bash
    php artisan vendor:publish --provider="RahulHaque\\Filepond\\FilepondServiceProvider"
    php artisan migrate
    ```

5. **Start queue worker**
    ```bash
    php artisan queue:work
    ```

6. **Run server**
    ```bash
    npm run dev
    # or
    php artisan serve
    ```

7. **Testing**
    ```bash
    php artisan test
    ```

---
