<?php
// includes/EmailManager.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Charger la configuration
// Utilise require_once pour éviter les inclusions multiples et la redéclaration de fonctions
require_once __DIR__ . '/../config.php';

// Le chemin doit remonter d'un cran (..) pour trouver le dossier vendor
require __DIR__ . '/../vendor/autoload.php';

class EmailManager {

    // --- CONFIGURATION ---
    private static function getMailer() {
        $mail = new PHPMailer(true);
        
        // 1. On active le DEBUG pour voir l'erreur exacte si ça ne marche pas
        $mail->SMTPDebug = 0; 
        $mail->Debugoutput = 'html';

        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;       // Depuis config.php (.env)
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;       // Depuis config.php (.env)
        $mail->Password   = SMTP_PASS;       // Depuis config.php (.env)
        
        // 3. FIX SPECIAL LOCALHOST (Pour régler le problème SSL sur XAMPP)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;       // Depuis config.php (.env)
        
        $mail->setFrom(SMTP_USER, EMAIL_FROM_NAME);  // Depuis config.php
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        return $mail;
    }

    // --- FONCTION 1 : Inscription ---
    public static function envoyerBienvenue($emailClient, $nomClient, $motDePasseClair) {
        try {
            $mail = self::getMailer();
            $mail->addAddress($emailClient, $nomClient);

            $mail->Subject = 'Bienvenue sur SOFCOS - Vos identifiants';
            
            $body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h1>Bienvenue chez SOFCOS, $nomClient !</h1>
                    <p>Votre compte a été créé avec succès.</p>
                    <div style='background: #f4f4f4; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p><strong>Votre Email :</strong> $emailClient</p>
                        <p><strong>Votre Mot de passe :</strong> <span style='font-size: 1.2em; color: #d9534f;'>$motDePasseClair</span></p>
                    </div>
                    <p>Vous pouvez vous connecter dès maintenant.</p>
                    <a href='http://localhost/SOFCOS/connexion.php' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Se connecter</a>
                </div>
            ";

            $mail->Body = $body;
            $mail->AltBody = "Bienvenue. Votre mot de passe est : $motDePasseClair";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // On affiche l'erreur technique pour comprendre
            echo "Erreur d'envoi : " . $mail->ErrorInfo;
            return false;
        }
    } 

    // --- FONCTION 2 : Mot de passe oublié ---
    public static function envoyerResetPassword($emailClient, $token) {
        try {
            $mail = self::getMailer();
            $mail->addAddress($emailClient);
            $mail->Subject = 'Réinitialisation de votre mot de passe - SOFCOS';

            // Lien vers la page reset_password.php
            $lien = "http://localhost/SOFCOS/reset_password.php?token=$token";

            $mail->Body = "
                <div style='font-family: Arial, sans-serif;'>
                    <h3>Mot de passe oublié ?</h3>
                    <p>Pas de panique, cela arrive à tout le monde.</p>
                    <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                    <p style='margin: 20px 0;'>
                        <a href='$lien' style='background-color:#d4af37; color:white; padding:12px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Réinitialiser mon mot de passe</a>
                    </p>
                    <p><small>Ce lien est valide pendant 1 heure.</small></p>
                </div>
            ";
            
            $mail->AltBody = "Copiez ce lien pour réinitialiser : $lien";

            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Erreur d'envoi : " . $mail->ErrorInfo;
            return false;
        }
    }
    // DANS EmailManager.php
public static function envoyerConfirmationCommande(
    $emailClient, 
    $nomClient, 
    $commandeId, 
    $dateCommande, 
    $methodePaiement, 
    $produits, // Doit être un tableau (array) contenant les articles
    $sousTotal, 
    $fraisLivraison, 
    $total, 
    $adresseLivraison, 
    $telephone
) {
    try {
        $mail = self::getMailer();
        $mail->addAddress($emailClient, $nomClient);
        $mail->Subject = "Confirmation de votre commande #$commandeId - SOFCOS";

        // Nettoyage et formatage
        $nom = htmlspecialchars(strtoupper($nomClient), ENT_QUOTES, 'UTF-8');
        
        // Construction des lignes du tableau des produits
        $lignesProduitsHtml = "";
        foreach ($produits as $produit) {
            $nomProduit = htmlspecialchars($produit['nom'], ENT_QUOTES, 'UTF-8');
            $qte = (int)$produit['qte'];
            $prix = number_format((float)$produit['prix'], 2, '.', ' ');
            
            $lignesProduitsHtml .= "
            <tr>
                <td style='padding: 12px 0; border-bottom: 1px solid #eee; color: #333; font-size: 14px;'>$nomProduit</td>
                <td style='padding: 12px 0; border-bottom: 1px solid #eee; text-align: center; color: #666; font-size: 14px;'>$qte</td>
                <td style='padding: 12px 0; border-bottom: 1px solid #eee; text-align: right; color: #333; font-weight:bold; font-size: 14px;'>$prix DH</td>
            </tr>";
        }

        // Formatage des totaux
        $sousTotalFmt = number_format((float)$sousTotal, 2, '.', ' ');
        $totalFmt = number_format((float)$total, 2, '.', ' ');
        
        // Gestion de l'affichage de la livraison (Texte vert si "OFFERTE", sinon affichage du prix)
        $livraisonAffichage = (strtoupper($fraisLivraison) === 'OFFERTE' || $fraisLivraison == 0) 
            ? "<span style='color: #008000; font-weight: bold;'>OFFERTE</span>" 
            : number_format((float)$fraisLivraison, 2, '.', ' ') . " DH";

        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Confirmation de Commande</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f4f4f4; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; color: #333;'>
            <table role='presentation' width='100%' border='0' cellspacing='0' cellpadding='0'>
                <tr>
                    <td align='center' style='padding: 40px 0;'>
                        <!-- Main Container -->
                        <table role='presentation' width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                            
                            <!-- Header -->
                            <tr>
                                <td align='center' style='background-color: #1A3C34; padding: 35px;'>
                                    <h1 style='color: #C5A059; margin: 0; font-family: \"Times New Roman\", serif; letter-spacing: 3px; font-size: 28px; text-transform: uppercase;'>SOFCOS</h1>
                                    <p style='color: #a3b5b0; margin: 8px 0 0; font-size: 11px; text-transform: uppercase; letter-spacing: 2px;'>L'élégance Naturelle</p>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <h2 style='color: #1A3C34; margin-top: 0; font-size: 22px; font-weight: 400;'>Merci pour votre commande, $nom !</h2>
                                    <p style='color: #666; line-height: 1.6; font-size: 15px;'>
                                        Nous avons bien reçu votre commande <strong>#$commandeId</strong> du $dateCommande.
                                        Elle est actuellement en cours de préparation par nos soins avec la plus grande attention.
                                    </p>

                                    <!-- Order Info Box -->
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin: 30px 0; background-color: #fcfcfc; border-radius: 6px; border: 1px solid #eee;'>
                                        <tr>
                                            <td style='padding: 20px; vertical-align: top;'>
                                                <p style='margin: 0; font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;'>Livraison</p>
                                                <p style='margin: 8px 0 0; font-size: 14px; color: #333; line-height: 1.5;'>
                                                    <strong>$nom</strong><br>
                                                    " . nl2br(htmlspecialchars($adresseLivraison)) . "<br>
                                                    Tél: " . htmlspecialchars($telephone) . "
                                                </p>
                                            </td>
                                            <td style='padding: 20px; border-left: 1px solid #eee; vertical-align: top;'>
                                                <p style='margin: 0; font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;'>Paiement</p>
                                                <p style='margin: 8px 0 0; font-size: 14px; color: #333;'><strong>$methodePaiement</strong></p>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Products Table -->
                                    <h3 style='color: #1A3C34; font-size: 16px; border-bottom: 2px solid #C5A059; padding-bottom: 10px; margin-bottom: 20px; display: inline-block; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;'>Récapitulatif</h3>
                                    
                                    <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse: collapse;'>
                                        <thead>
                                            <tr>
                                                <th align='left' style='padding: 10px 0; border-bottom: 1px solid #eee; color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;'>Produit</th>
                                                <th align='center' style='padding: 10px 0; border-bottom: 1px solid #eee; color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;'>Qté</th>
                                                <th align='right' style='padding: 10px 0; border-bottom: 1px solid #eee; color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;'>Prix</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            $lignesProduitsHtml
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan='2' align='right' style='padding-top: 20px; color: #666; font-size: 14px;'>Sous-total :</td>
                                                <td align='right' style='padding-top: 20px; color: #333; font-size: 14px;'>$sousTotalFmt DH</td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='right' style='padding-top: 8px; color: #666; font-size: 14px;'>Livraison :</td>
                                                <td align='right' style='padding-top: 8px; color: #333; font-size: 14px;'>$livraisonAffichage</td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='right' style='padding-top: 15px; color: #1A3C34; font-size: 18px; font-weight: bold;'>Total :</td>
                                                <td align='right' style='padding-top: 15px; color: #1A3C34; font-size: 18px; font-weight: bold;'>$totalFmt DH</td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <div style='margin-top: 40px; text-align: center;'>
                                        <a href='http://localhost/SOFCOS/mon-compte.php' style='background-color: #1A3C34; color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 4px; font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Suivre ma commande</a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td align='center' style='background-color: #f8f8f8; padding: 25px; border-top: 1px solid #eee;'>
                                    <p style='margin: 0; font-size: 13px; color: #888;'>Besoin d'aide ? Contactez-nous à <a href='mailto:contact@sofcos.ma' style='color: #1A3C34; text-decoration: none; font-weight: bold;'>contact@sofcos.ma</a></p>
                                    <p style='margin: 10px 0 0; font-size: 11px; color: #ccc;'>&copy; " . date('Y') . " SOFCOS. Tous droits réservés.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Bonjour $nom,\n\nMerci pour votre commande #$commandeId.\nTotal: $totalFmt DH.\nElle est en cours de préparation.";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
}

// Fin de la classe EmailManager
?>