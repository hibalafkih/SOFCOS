<?php
// includes/EmailManager.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Charger la configuration
require __DIR__ . '/../config.php';

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

public static function envoyerConfirmationCommande($emailClient, $nomClient, $commandeId, $total) {
    try {
        $mail = self::getMailer();
        $mail->addAddress($emailClient);
        $mail->Subject = "Confirmation de votre commande #$commandeId - SOFCOS";

        $mail->Body = "
            <div style='font-family: Montserrat, sans-serif; color: #333;'>
                <h2 style='color: #1A3C34;'>Merci pour votre commande, $nomClient !</h2>
                <p>Votre commande <strong>#$commandeId</strong> a bien été enregistrée.</p>
                <p>Montant total : <strong>" . number_format($total, 2) . " DH</strong></p>
                <p>Nous préparons votre colis avec le plus grand soin.</p>
                <hr>
                <p>Vous pouvez suivre votre commande sur votre compte client.</p>
                <br>
                <a href='http://localhost/SOFCOS/suivi.php' style='background-color:#C5A059; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Suivre ma commande</a>
            </div>
        ";
        
        $mail->AltBody = "Merci $nomClient. Votre commande #$commandeId de " . number_format($total, 2) . " DH est confirmée.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

} // Fin de la classe EmailManager
?>