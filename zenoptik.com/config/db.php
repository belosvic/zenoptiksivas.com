<?php
// Hata raporlamayı açalım (Geliştirme aşamasında hataları görmek için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bilgileri
$host     = 'localhost'; // M2 Mac'te genelde localhost veya 127.0.0.1
$dbname   = 'zenoptik'; // Az önce açtığın veritabanı adı
$username = 'root';      // XAMPP/MAMP varsayılan kullanıcı adı
$password = '';          // MAMP kullanıyorsan şifre genelde 'root' olur, XAMPP için boştur

try {
    // PDO Bağlantısını oluşturma ve karakter setini UTF-8 ayarlama
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Hata modunu exception (istisna) olarak ayarlayalım
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Varsayılan fetch modunu Object (nesne) olarak ayarlayalım (Kullanımı daha kolaydır: $row->name)
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

} catch (PDOException $e) {
    // Bağlantı hatası olursa ekrana basalım ve işlemi durduralım
    die("Veritabanı Bağlantı Hatası: " . $e->getMessage());
}
?>