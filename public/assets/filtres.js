/**
 * Recherche et filtrage automatiques : les formulaires marqués
 * « data-auto-filtre » se soumettent eux-mêmes dès que l'utilisateur a fini
 * de saisir (délai court) ou qu'il change un menu déroulant — sans avoir à
 * cliquer sur « Filtrer », qui reste disponible comme soumission manuelle.
 */
(function () {
    "use strict";

    const DELAI = 450; // ms après la dernière frappe

    document.querySelectorAll("form[data-auto-filtre]").forEach(function (form) {
        // Mémorise la valeur initiale de chaque champ visible pour ne
        // soumettre que si la saisie change réellement.
        form.querySelectorAll("input[name], select[name]").forEach(function (champ) {
            if (champ.type === "hidden") return;
            champ.dataset.valeurInitiale = champ.value;
        });

        let minuteur = null;

        function soumettreSiChange(champ) {
            if (champ && champ.dataset && champ.dataset.valeurInitiale === champ.value) return;
            form.submit();
        }

        // Champs texte / recherche : soumission différée après la frappe.
        form.addEventListener("input", function (e) {
            const champ = e.target;
            if (champ.type === "hidden" || champ.tagName === "SELECT") return;
            if (minuteur) window.clearTimeout(minuteur);
            minuteur = window.setTimeout(function () {
                minuteur = null;
                soumettreSiChange(champ);
            }, DELAI);
        });

        // Menus déroulants : filtrage immédiat dès le changement.
        form.addEventListener("change", function (e) {
            if (e.target.tagName !== "SELECT") return;
            if (minuteur) { window.clearTimeout(minuteur); minuteur = null; }
            form.submit();
        });

        // Soumission manuelle (bouton « Filtrer » ou touche Entrée) :
        // annule l'auto-soumission en attente pour éviter un double envoi.
        form.addEventListener("submit", function () {
            if (minuteur) { window.clearTimeout(minuteur); minuteur = null; }
        });
    });
})();
