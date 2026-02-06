<?php
session_start();
if(!isset($_SESSION['livreur_id'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Commande</title>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>  
    <style>
        body { background: #000; margin: 0; display: flex; flex-direction: column; height: 100vh; color: white; font-family: sans-serif; }
        #reader { width: 100%; flex: 1; background: black; }
        .controls { padding: 20px; text-align: center; background: #1A3C34; }
        .btn-cancel { color: white; text-decoration: none; font-size: 16px; border: 1px solid white; padding: 10px 30px; border-radius: 20px; display: inline-block; margin-top: 10px;}
        h2 { margin: 0 0 10px 0; font-size: 18px; }
    </style>
</head>
<body>

    <div class="controls">
        <h2>Cadrez le QR Code</h2>
        <div style="font-size:12px; color:#aaa;">La validation sera automatique</div>
    </div>

    <div id="reader"></div>

    <div class="controls">
        <a href="index.php" class="btn-cancel">Annuler / Retour</a>
    </div>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            // Le texte du QR est "Commande #123 | Client..."
            // On veut juste le "123"
            
            // Regex pour trouver le premier nombre dans le texte
            let matches = decodedText.match(/(\d+)/);
            
            if (matches) {
                let id_commande = matches[0];
                
                // Rediriger vers la page de validation
                window.location.href = "valider_scan.php?id=" + id_commande;
            } else {
                alert("QR Code non reconnu : " + decodedText);
            }
        }

        // Configuration du scanner
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { fps: 10, qrbox: {width: 250, height: 250} },
            /* verbose= */ false
        );
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>