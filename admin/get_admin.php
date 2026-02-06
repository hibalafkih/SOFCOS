<?php
// Fichier: admin/get_admin.php
session_start();
require_once '../config.php';

// Sécurité : Vérifier si l'admin est connecté
if(!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Non autorisé']));
}

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // On récupère les infos (sauf le mot de passe pour la sécurité)
    $stmt = $pdo->prepare("SELECT id, nom, email, role, actif FROM administrateurs WHERE id = ?");
    $stmt->execute([$id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // On renvoie en JSON
    header('Content-Type: application/json');
    echo json_encode($admin);
}
?>