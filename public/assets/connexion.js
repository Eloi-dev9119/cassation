(function () {
    const cleAdresse = 'pgcc-adresse-connexion';

    document.addEventListener('DOMContentLoaded', function () {
        const champEmail = document.getElementById('email');
        const caseMemoriser = document.getElementById('memoriserEmail');

        if (!champEmail || !caseMemoriser) return;

        try {
            const adresseEnregistree = localStorage.getItem(cleAdresse);
            if (adresseEnregistree && !champEmail.value.trim()) {
                champEmail.value = adresseEnregistree;
            }
            caseMemoriser.checked = Boolean(
                adresseEnregistree && champEmail.value.trim() === adresseEnregistree
            );
        } catch (erreur) {
            caseMemoriser.checked = false;
        }

        function enregistrerAdresse() {
            try {
                const adresse = champEmail.value.trim();
                if (caseMemoriser.checked && adresse) {
                    localStorage.setItem(cleAdresse, adresse);
                } else {
                    localStorage.removeItem(cleAdresse);
                }
            } catch (erreur) {
                // Le formulaire reste utilisable si le navigateur bloque le stockage local.
            }
        }

        caseMemoriser.addEventListener('change', enregistrerAdresse);
        champEmail.addEventListener('input', enregistrerAdresse);

        const formulaire = champEmail.closest('form');
        if (formulaire) formulaire.addEventListener('submit', enregistrerAdresse);
    });
})();