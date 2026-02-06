// Gestion du panier
let panier = JSON.parse(localStorage.getItem('panier')) || [];

// Mettre à jour le compteur du panier
function mettreAJourCompteur() {
    const compteur = document.getElementById('cart-count');
    if (compteur) {
        const total = panier.reduce((sum, item) => sum + item.quantite, 0);
        compteur.textContent = total;
    }
}

// Ajouter un produit au panier
function ajouterAuPanier(produitId, quantite = 1) {
    // Récupérer les informations du produit via AJAX
    fetch(`api/get_produit.php?id=${produitId}`)
        .then(response => response.json())
        .then(produit => {
            if (produit.error) {
                afficherNotification('Erreur: ' + produit.error, 'error');
                return;
            }

            // Vérifier si le produit existe déjà dans le panier
            const index = panier.findIndex(item => item.id === produitId);

            if (index !== -1) {
                // Augmenter la quantité
                panier[index].quantite += quantite;
            } else {
                // Ajouter le nouveau produit
                panier.push({
                    id: produit.id,
                    nom: produit.nom,
                    prix: produit.prix_promo || produit.prix,
                    image: produit.image,
                    quantite: quantite
                });
            }

            // Sauvegarder dans localStorage
            localStorage.setItem('panier', JSON.stringify(panier));

            // Mettre à jour l'affichage
            mettreAJourCompteur();
            afficherNotification('Produit ajouté au panier!', 'success');
        })
        .catch(error => {
            console.error('Erreur:', error);
            afficherNotification('Erreur lors de l\'ajout au panier', 'error');
        });
}

// Retirer un produit du panier
function retirerDuPanier(produitId) {
    panier = panier.filter(item => item.id !== produitId);
    localStorage.setItem('panier', JSON.stringify(panier));
    mettreAJourCompteur();
    afficherPanier();
    afficherNotification('Produit retiré du panier', 'info');
}

// Modifier la quantité d'un produit
function modifierQuantite(produitId, nouvelleQuantite) {
    const index = panier.findIndex(item => item.id === produitId);

    if (index !== -1) {
        if (nouvelleQuantite <= 0) {
            retirerDuPanier(produitId);
        } else {
            panier[index].quantite = nouvelleQuantite;
            localStorage.setItem('panier', JSON.stringify(panier));
            afficherPanier();
        }
    }
}

// Vider le panier
function viderPanier() {
    if (confirm('Voulez-vous vraiment vider votre panier?')) {
        panier = [];
        localStorage.setItem('panier', JSON.stringify(panier));
        mettreAJourCompteur();
        afficherPanier();
        afficherNotification('Panier vidé', 'info');
    }
}

// Afficher le panier (pour la page panier.php)
function afficherPanier() {
    const container = document.getElementById('panier-container');
    if (!container) return;

    if (panier.length === 0) {
        container.innerHTML = `
            <div class="panier-vide">
                <i class="fas fa-shopping-cart" style="font-size: 80px; color: #ccc;"></i>
                <h2>Votre panier est vide</h2>
                <p>Découvrez nos produits et ajoutez-les à votre panier</p>
                <a href="produits.php" class="btn btn-primary">Voir les produits</a>
            </div>
        `;
        return;
    }

    let total = 0;
    let html = '<div class="panier-items">';

    panier.forEach(item => {
        const sousTotal = item.prix * item.quantite;
        total += sousTotal;

        html += `
            <div class="panier-item">
                <img src="images/produits/${item.image}" alt="${item.nom}" onerror="this.src='images/placeholder.jpg'">
                <div class="item-details">
                    <h3>${item.nom}</h3>
                    <p class="item-prix">${item.prix.toFixed(2)} MAD</p>
                </div>
                <div class="item-quantite">
                    <button onclick="modifierQuantite(${item.id}, ${item.quantite - 1})">-</button>
                    <input type="number" value="${item.quantite}" 
                           onchange="modifierQuantite(${item.id}, parseInt(this.value))"
                           min="1">
                    <button onclick="modifierQuantite(${item.id}, ${item.quantite + 1})">+</button>
                </div>
                <div class="item-total">
                    ${sousTotal.toFixed(2)} MAD
                </div>
                <button class="btn-supprimer" onclick="retirerDuPanier(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });

    html += '</div>';

    html += `
        <div class="panier-resume">
            <h3>Résumé de la commande</h3>
            <div class="resume-ligne">
                <span>Sous-total:</span>
                <span>${total.toFixed(2)} MAD</span>
            </div>
            <div class="resume-ligne">
                <span>Livraison:</span>
                <span>Gratuite</span>
            </div>
            <div class="resume-ligne total">
                <span>Total:</span>
                <span>${total.toFixed(2)} MAD</span>
            </div>
            <button class="btn btn-primary" onclick="window.location.href='commander.php'">
                Passer la commande
            </button>
            <button class="btn btn-secondary" onclick="viderPanier()">
                Vider le panier
            </button>
        </div>
    `;

    container.innerHTML = html;
}

// Système de notifications
function afficherNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existantes = document.querySelectorAll('.notification');
    existantes.forEach(n => n.remove());

    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;

    const icones = {
        success: 'check-circle',
        error: 'exclamation-circle',
        info: 'info-circle',
        warning: 'exclamation-triangle'
    };

    notification.innerHTML = `
        <i class="fas fa-${icones[type]}"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(notification);

    // Animation d'entrée
    setTimeout(() => notification.classList.add('show'), 10);

    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Recherche en temps réel
function rechercherProduits(query) {
    if (query.length < 2) return;

    fetch(`api/recherche.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(resultats => {
            afficherResultatsRecherche(resultats);
        })
        .catch(error => console.error('Erreur de recherche:', error));
}

// Ajouter aux favoris
function ajouterAuxFavoris(produitId) {
    let favoris = JSON.parse(localStorage.getItem('favoris')) || [];

    if (!favoris.includes(produitId)) {
        favoris.push(produitId);
        localStorage.setItem('favoris', JSON.stringify(favoris));
        afficherNotification('Ajouté aux favoris', 'success');
    } else {
        favoris = favoris.filter(id => id !== produitId);
        localStorage.setItem('favoris', JSON.stringify(favoris));
        afficherNotification('Retiré des favoris', 'info');
    }
}

// Filtrer les produits
function filtrerProduits() {
    const prix = document.getElementById('filtre-prix') ? .value;
    const categorie = document.getElementById('filtre-categorie') ? .value;
    const tri = document.getElementById('filtre-tri') ? .value;

    let params = new URLSearchParams();
    if (prix) params.append('prix', prix);
    if (categorie) params.append('categorie', categorie);
    if (tri) params.append('tri', tri);

    window.location.href = `produits.php?${params.toString()}`;
}

// Validation de formulaire
function validerFormulaire(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const champs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let valide = true;

    champs.forEach(champ => {
        if (!champ.value.trim()) {
            champ.classList.add('error');
            valide = false;
        } else {
            champ.classList.remove('error');
        }
    });

    if (!valide) {
        afficherNotification('Veuillez remplir tous les champs obligatoires', 'error');
    }

    return valide;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour le compteur du panier
    mettreAJourCompteur();

    // Afficher le panier si on est sur la page panier
    if (document.getElementById('panier-container')) {
        afficherPanier();
    }

    // Recherche en temps réel
    const searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                rechercherProduits(this.value);
            }, 500);
        });
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Style pour les notifications (à ajouter au CSS)
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 80px;
        right: 20px;
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left: 4px solid #4caf50;
    }
    
    .notification-error {
        border-left: 4px solid #f44336;
    }
    
    .notification-info {
        border-left: 4px solid #2196f3;
    }
    
    .notification-warning {
        border-left: 4px solid #ff9800;
    }
    
    .notification i {
        font-size: 20px;
    }
    
    .notification-success i {
        color: #4caf50;
    }
    
    .notification-error i {
        color: #f44336;
    }
    
    .notification-info i {
        color: #2196f3;
    }
    
    .notification-warning i {
        color: #ff9800;
    }
`;
document.head.appendChild(style);