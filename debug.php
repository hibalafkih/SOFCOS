<?php
// debug.php
session_start();
require_once 'config.php';

echo "<h1>🔍 Diagnostic Commandes</h1>";

// 1. Vérifier qui est connecté
if (isset($_SESSION['client_id'])) {
    echo "<p style='color:green'>✅ Vous êtes connecté avec l'ID Client : <strong>" . $_SESSION['client_id'] . "</strong></p>";
    $mon_id = $_SESSION['client_id'];
} else {
    echo "<p style='color:red'>❌ Vous n'êtes pas connecté ! Connectez-vous d'abord.</p>";
    $mon_id = 0;
}

try {
    if(!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
    }

    // 2. Regarder les 10 dernières commandes dans la BDD
    echo "<h3>📊 Les 10 dernières commandes enregistrées dans la base :</h3>";
    $stmt = $pdo->query("SELECT id, client_id, date_commande, total FROM commandes ORDER BY id DESC LIMIT 10");
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background:#eee'><th>ID Commande</th><th>Client ID (Base de données)</th><th>Date</th><th>Est-ce la vôtre ?</th></tr>";

    foreach ($commandes as $cmd) {
        $is_mine = ($cmd['client_id'] == $mon_id) ? "<span style='color:green; font-weight:bold;'>OUI (S'affiche)</span>" : "<span style='color:red;'>NON (Masqué)</span>";
        
        // Si le client_id est 0 ou vide, c'est le problème !
        if (empty($cmd['client_id'])) {
            $client_col = "<span style='background:red; color:white; padding:3px;'>⚠️ VIDE (0)</span>";
        } else {
            $client_col = $cmd['client_id'];
        }

        echo "<tr>";
        echo "<td>#" . $cmd['id'] . "</td>";
        echo "<td>" . $client_col . "</td>";
        echo "<td>" . $cmd['date_commande'] . "</td>";
        echo "<td>" . $is_mine . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Erreur SQL : " . $e->getMessage();
}
?>