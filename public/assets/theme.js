(function () {
    "use strict";

    const stockageTheme = "pgcc-theme";
    const themes = [
        { id: "institutionnel", nom: "Institutionnel" },
        { id: "sombre", nom: "Sombre" }
    ];

    function themeValide(id) {
        return themes.find(theme => theme.id === id) || themes[0];
    }

    let themeActuel = themes[0];
    try {
        themeActuel = themeValide(window.localStorage.getItem(stockageTheme));
    } catch (erreur) {
        // Le thème par défaut reste disponible si le navigateur bloque le stockage local.
    }

    function etiqueterBoutons() {
        const position = themes.findIndex(theme => theme.id === themeActuel.id);
        const prochain = themes[(position + 1) % themes.length];
        document.querySelectorAll("[data-theme-toggle]").forEach(bouton => {
            const libelle = "Changer le thème. Thème actuel : " + themeActuel.nom
                + ". Prochain thème : " + prochain.nom + ".";
            bouton.setAttribute("aria-label", libelle);
            bouton.title = libelle;
        });
    }

    function notifierChangement() {
        let notification = document.querySelector(".theme-notification");
        if (!notification) {
            notification = document.createElement("div");
            notification.className = "theme-notification";
            notification.setAttribute("role", "status");
            notification.setAttribute("aria-live", "polite");
            document.body.appendChild(notification);
        }

        notification.textContent = "Thème appliqué : " + themeActuel.nom;
        notification.hidden = false;
        window.clearTimeout(notification.timer);
        notification.timer = window.setTimeout(function () {
            notification.hidden = true;
        }, 2200);
    }

    function appliquerTheme(id, afficherConfirmation) {
        themeActuel = themeValide(id);
        document.documentElement.dataset.theme = themeActuel.id;
        try {
            window.localStorage.setItem(stockageTheme, themeActuel.id);
        } catch (erreur) {
            // Le choix s'applique à la page courante même sans stockage local.
        }

        etiqueterBoutons();
        if (afficherConfirmation && document.body) notifierChangement();
    }

    function changerTheme() {
        const position = themes.findIndex(theme => theme.id === themeActuel.id);
        appliquerTheme(themes[(position + 1) % themes.length].id, true);
    }

    document.documentElement.dataset.theme = themeActuel.id;
    document.addEventListener("DOMContentLoaded", function () {
        etiqueterBoutons();
        document.querySelectorAll("[data-theme-toggle]").forEach(bouton => {
            bouton.addEventListener("click", changerTheme);
        });

    });
})();