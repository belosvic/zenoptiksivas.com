<?php 
require_once '../config/db.php'; 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zen Optik - Yönetim Paneli</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome İkonları -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --zen-gold: #cfa858;
            --zen-gold-hover: #b89348;
            --zen-black: #0a0a0a;
        }
        
        body { background-color: #f8f9fc; }
        
        /* Sidebar Siyah & Gold Tasarımı */
        .sidebar { 
            min-height: 100vh; 
            background-color: var(--zen-black); 
            color: white; 
            border-right: 2px solid var(--zen-gold);
        }
        
        .sidebar h4 {
            color: var(--zen-gold);
            font-family: "Times New Roman", Times, serif; /* Logodaki font hissiyatı için */
            letter-spacing: 2px;
            border-bottom: 1px solid #222 !important;
            text-transform: uppercase;
        }
        
        .sidebar a { 
            color: #d1d1d1; 
            text-decoration: none; 
            padding: 12px 15px; 
            display: block; 
            border-radius: 5px; 
            margin-bottom: 5px; 
            transition: all 0.3s ease;
        }
        
        .sidebar a i { 
            color: var(--zen-gold); 
            width: 20px; 
            text-align: center;
        }
        
        .sidebar a:hover { 
            background-color: #1a1a1a; 
            color: var(--zen-gold); 
            padding-left: 20px; /* Üzerine gelince hafif sağa kayma efekti */
        }
        
        /* İçerik ve İstatistik Kartları */
        .content-area { padding: 20px; }
        .card-stat { border-left: 4px solid var(--zen-gold) !important; }
        
        /* Primary Butonları Mavi Yerine Gold Yapma */
        .btn-primary { 
            background-color: var(--zen-gold); 
            border-color: var(--zen-gold); 
            color: #000; 
            font-weight: 500;
        }
        
        .btn-primary:hover, .btn-primary:focus { 
            background-color: var(--zen-gold-hover); 
            border-color: var(--zen-gold-hover); 
            color: #000; 
        }
        
        .text-primary { color: var(--zen-gold) !important; }
        .bg-primary { background-color: var(--zen-gold) !important; }
        .btn-outline-primary {
            color: var(--zen-gold);
            border-color: var(--zen-gold);
        }
        .btn-outline-primary:hover {
            background-color: var(--zen-gold);
            color: #000;
            border-color: var(--zen-gold);
        }
    </style>
</head>
<body>
<div class="d-flex">