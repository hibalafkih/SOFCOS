<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION['livreur_id'])) { header("Location: login.php"); exit(); }

if(isset($_POST['id_cmd']) && isset($_POST['message'])) {
    $id = (int)$_POST['id_cmd'];
    $msg = htmlspecialchars($_POST['message']);
    
    // On enregistre le message dans la commande
    $stmt = $pdo->prepare("UPDATE commandes SET rapport_livreur = ? WHERE id = ?");
    $stmt->execute([$msg, $id]);
    
    // On redirige avec succès
    header("Location: index.php?msg=envoye");
} else {
    header("Location: index.php");
}
?>