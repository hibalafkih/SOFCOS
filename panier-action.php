<?php
// panier-action.php
session_start();

// Initialisation du panier si inexistant
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Récupération sécurisée des paramètres
$action = $_REQUEST['action'] ?? null;
$id = (int)($_REQUEST['id'] ?? 0);
$quantite = (int)($_REQUEST['quantite'] ?? 1);

// Gestion des actions
if ($id > 0) {
    switch ($action) {
        case 'ajout':
            // Ajoute ou incrémente
            if (isset($_SESSION['panier'][$id])) {
                $_SESSION['panier'][$id] += $quantite;
            } else {
                $_SESSION['panier'][$id] = $quantite;
            }
            break;

        case 'update':
            // Modifie la quantité exacte
            if ($quantite > 0) {
                $_SESSION['panier'][$id] = $quantite;
            } else {
                unset($_SESSION['panier'][$id]); // Si 0, on supprime
            }
            break;

        case 'suppression':
            // Retire le produit
            unset($_SESSION['panier'][$id]);
            break;
    }
}

// Action spéciale pour tout vider
if ($action === 'vider') {
    $_SESSION['panier'] = [];
}

// Retour au panier
header("Location: panier.php");
exit();
?>