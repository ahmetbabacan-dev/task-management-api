# Task Management API

Task Manager, kullanıcı kaydı, JWT kimlik doğrulamasıyla oturum açma ve her kimliği doğrulanmış kullanıcının kendine özel görevlerini yönetmek için kullanılabileceği bir RESTful API'dır.

## Kullanılan Teknolojiler

*   **Backend:** PHP (Version 8.3.1) - Vanilla PHP
*   **Database:** MySQL
*   **Local Server Environment:** MAMP 5.0.6
*   **Apache Version:** Apache/2.4.33 (Win64)
*   **Authentication:** `firebase/php-jwt` kütüphanesi kullanılarak JSON Web Tokens (JWT)
*   **Dependency Management:** Composer
*   **API Testing:** Postman

## Kurulum Adımları

1.  **Ön Koşullar:**
    *   XAMPP veya MAMP gibi içinde PHP olan bir web server environment'ı.
    *   MySQL server.
    *   Composer (PHP dependency'leri için).
    *   Git (repo'yu klonlamak için).

2.  **Repo'yu Klonlama:**
    ```bash
    git clone https://github.com/ahmetbabacan-dev/task-management-api
    cd task-management-api
    ```

3.  **Dependency'leri Kurma:**
    Gerekli dependency'ler (`firebase/php-jwt`) proje klasörünün içindeki `vendor/` klasöründe olup tekrar kurulmalarına gerek yoktur.
    Ama eğer tekrar kurulması gerekirse projenin root directory'sinden (`composer.json` dosyasının olduğu yer):

    ```bash
    composer install
    ```

    komutu çalıştırılabilinir.

4.  **Veri Tabanı Kurulumu:**
    *   MySQL sunucusunun çalıştığından emin olun.
    *   Yeni ve boş bir veri tabanı oluşturun (`task_manager_db`).
    *   Projenin root'unda yer alan `task_manager_schema.sql` dosyasında verilen veri tabanı şeması ve örnek veriyi veri tabanınıza aktarın:
        *   **phpMyAdmin:** Yeni veri tabanınızı seçin, yukarıdaki "İçe Aktar" seçeneğine tıklayın ve `task_manager_schema.sql` dosyasını yükleyin.
    *   Bu SQL dosyası bu API için gerekli olan 2 tabloyu (`users`, `tasks`) oluşturup içlerini örnek veri ile dolduracaktır.

5.  **Veri Tabanı Bağlantısı:**
    *   `db.php` dosyasını açın ve içindeki sunucu değişkenlerini kendi MySQL sunucu detaylarıyla değiştirin: 
        ```php
        $servername = "mysql_server_hostunuz"; // örn. "localhost"
        $username = "mysql_kullanıcı_adınız"; // örn. "root"
        $password = "mysql_şifreniz"; // örn. "root"
        $dbname = "veri_tabanı_adınız"; // 4. adımda kullandığınız veri tabanı adı
        ```

6.  **JWT Secret Key:**
    *   `auth_api.php` ve `tasks_api.php` dosyalarını açın.
    *   `$secret_key` değişkeninin tanımlandığından emin olun. Mevcut olan örnek anahtarı değiştirmeyin.
        ```php
        $secret_key = "eaf21a16908fbdd7c9c33aa9938213ec0bf39e262036a6856660b4c235438e68";
        ```

7.  **URL Konfigürasyonu:**
    *   API endpoint'lerine PHP script'lerinin konumuna göreceli olarak erişilecektir (örn., `http://localhost/task_manager/register_login.php?action=login`).

## API Endpoint'leri

Temel 2 script `register_login.php` (kullanıcı doğrulaması) ve `tasks.php` (task yönetimi) olarak yazılmıştır.

### Kullanıcı Eylemleri (`register_login.php`)

*   **Kullanıcı Kaydet:**
    *   **Method:** `POST`
    *   **Endpoint:** `/register`
    *   **Request Body (JSON):**
        ```json
        {
            "username": "newuser",
            "password": "newpassword123"
        }
        ```
    *   **Success Response (201 Created):**
        ```json
        {
            "message": "User has been registered successfully."
        }
        ```
*   **Kullanıcı Girişi:**
    *   **Method:** `POST`
    *   **Endpoint:** `/login`
    *   **Request Body (JSON):**
        ```json
        {
            "username": "testuser",
            "password": "password123"
        }
        ```
    *   **Success Response (200 OK):**
        ```json
        {
            "message": "Login successful.",
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "expiresIn": 1678123456
        }
        ```

### Task Eylemleri (`tasks.php` - JWT Bearer Token Doğrulaması gerekir)

*   **Bütün Task'ları Listele (sadece doğrulanmış kullanıcılar):**
    *   **Method:** `GET`
    *   **Endpoint:** `/tasks`
    *   **İsteğe Bağlı Sorgu Parametreleri:**
        *   `status` (örn., `/tasks?status=pending`)
        *   `date` (örn., `/tasks?date=2024-12-31`)
    *   **Headers:** `Authorization: Bearer <JWT_TOKEN>`
*   **Belirli Bir Task'ı Al:**
    *   **Method:** `GET`
    *   **Endpoint:** `/tasks.php?id={id}` (örn., `/tasks.php?id=14`)
    *   **Headers:** `Authorization: Bearer <JWT_TOKEN>`
*   **Yeni Task Ekle:**
    *   **Method:** `POST`
    *   **Endpoint:** `/tasks`
    *   **Headers:** `Authorization: Bearer <JWT_TOKEN>`
    *   **Request Body (JSON):**
        ```json
        {
            "title": "My New Task",
            "description": "Details about the task.",
            "status": "pending",
            "due_date": "2025-12-31"
        }
        ```
*   **Task Güncelle:**
    *   **Method:** `PUT`
    *   **Endpoint:** `/tasks.php?id={id}`
    *   **Headers:** `Authorization: Bearer <JWT_TOKEN>`
    *   **Request Body (JSON):** (Fields to update)
        ```json
        {
            "title": "Updated Task Title",
            "status": "in_progress"
        }
        ```
*   **Task Sil (Soft Delete):**
    *   **Method:** `DELETE`
    *   **Endpoint:** `/tasks.php?id={id}` (örn., `/tasks.php?id=14`)
    *   **Headers:** `Authorization: Bearer <JWT_TOKEN>`

*(Her bir endpoint'in detaylı başarı/hata cevapları için Postman koleksiyonunu deneyin.)*

## Login İçin Örnek Kullanıcı

İçe aktardığınız veri tabanı dump'ı (`task_manager_schema.sql`) içinde örnek bir kullanıcı içerir. 
Aşağıdaki bilgilerle giriş yapabilirsiniz:

*   **Username:** `ahmet`
*   **Password:** `1145`

## Postman Collection ve Örnek Token

*   **Postman Collection:**
    *   Bütün API endpoint'lerini test etmek için gereken Postman collection `postman/Task_Management_API.postman_collection.json` direktifinde bulunabilir.
    *   API'yı test ederken 4. aşamada (4. LoginUserSuccess) başarılı bir şekilde log in yaptıktan sonra sonuç olarak size döndürülen JWT token'inin "token" değeri
    karşısındaki uzun şifreyi tırnak işaretleri olmadan kopyalayın ve Authorization sekmesine gelip Auth Type'ı Bearer Token olarak seçtikten sonra
    sağda çıkan forma şifreyi yapıştırın. *Token geçerliliği 1 saattir.*
    *   **Örnek JWT Token:** `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJsb2NhbGhvc3QiLCJhdWQiOiJsb2NhbGhvc3QiLCJpYXQiOjE3NDY3MjM5NzcsIm5iZiI6MTc0NjcyMzk3NywiZXhwIjoxNzQ2NzI3NTc3LCJkYXRhIjp7ImlkIjo1LCJ1c2VybmFtZSI6ImFobWV0In19.2ooi8VUbwuTsrnN15yBFPsFPV1x2UlV_1nTqwWQB8VY`

---

**Geliştiri Notları:**

*   PHP'de authorization header'ları default olarak kapalı olduğu için başta `$_SERVER['HTTP_AUTHORIZATION']` superglobal'ine erişim sağlayamadım. Bunu düzeltmek için proje root'undaki `.htaccess` dosyasındaki 3 satır kodu kullandım.
*   Proje gereksinimlerinde /register, /login gibi URL path'ı kullanarak doğru işleme route etmemizi istediniz fakat localhost olarak sunucu açınca PHP'nin `$_SERVER['PATH_INFO']` superglobal'i null olarak kaldı, bu nedenle URL'i de parse'layamadım. Buna yönelik bulduğum çözümler MAMP'ın proje klasörü dışındaki dosyalarında düzenleme gerektirdiği ve bunu repo'ya ekleme yolu bulamadığım için saf URL path'ı yerine URL query parametreleri (örn., `/register` yerine `/register_login.php?action=login`) kullandım.
