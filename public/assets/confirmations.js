/**
 * Confirmation des actions sensibles : une interface accessible au clavier
 * et utilisable avec le dialogue natif si <dialog> n'est pas pris en charge.
 */
(function () {
    const dialog = document.createElement("dialog");
    dialog.className = "confirmation-dialog";
    dialog.setAttribute("aria-labelledby", "confirmation-title");
    dialog.setAttribute("aria-describedby", "confirmation-message");
    dialog.innerHTML = [
        '<section class="confirmation-card">',
        '<div class="confirmation-icon" aria-hidden="true">!</div>',
        '<div class="confirmation-content">',
        '<p class="confirmation-kicker">Confirmation</p>',
        '<h2 id="confirmation-title">Confirmer cette action</h2>',
        '<p id="confirmation-message"></p>',
        '<div class="confirmation-actions">',
        '<button type="button" class="bouton bouton-secondaire" data-confirm-cancel>Annuler</button>',
        '<button type="button" class="bouton bouton-confirm" data-confirm-accept>Confirmer</button>',
        '</div></div></section>'
    ].join("");
    document.body.appendChild(dialog);

    const title = dialog.querySelector("#confirmation-title");
    const message = dialog.querySelector("#confirmation-message");
    const cancelButton = dialog.querySelector("[data-confirm-cancel]");
    const acceptButton = dialog.querySelector("[data-confirm-accept]");
    let actionEnAttente = null;

    function fermerConfirmation(confirmed) {
        if (!actionEnAttente) return;

        const action = actionEnAttente;
        actionEnAttente = null;
        if (dialog.open) dialog.close();

        if (confirmed) action.onConfirm();
        else if (action.onCancel) action.onCancel();

        if (action.focusRetour && action.focusRetour.isConnected) {
            requestAnimationFrame(() => action.focusRetour.focus({ preventScroll: true }));
        }
    }

    function demanderConfirmation(options) {
        if (actionEnAttente) return;

        const action = { ...options, focusRetour: document.activeElement };
        if (typeof dialog.showModal !== "function") {
            if (window.confirm(options.message)) action.onConfirm();
            else if (action.onCancel) action.onCancel();
            return;
        }

        actionEnAttente = action;
        title.textContent = options.title || "Confirmer cette action";
        message.textContent = options.message;
        acceptButton.textContent = options.label || "Confirmer";
        acceptButton.classList.toggle("bouton-danger", options.tone === "danger");
        dialog.dataset.confirmTone = options.tone || "default";
        dialog.showModal();
        cancelButton.focus();
    }

    function soumettreConfirme(form, submitter) {
        if (!form || !form.isConnected) return false;
        if (typeof form.reportValidity === "function" && !form.reportValidity()) return false;

        if (typeof form.requestSubmit === "function") {
            form.dataset.confirmBypass = "true";
            try {
                if (submitter) form.requestSubmit(submitter);
                else form.requestSubmit();
            } finally {
                // requestSubmit ne déclenche pas submit si la validation échoue.
                // On retire donc toujours le contournement après cet appel.
                delete form.dataset.confirmBypass;
            }
        } else {
            form.submit();
        }
        return true;
    }

    document.addEventListener("submit", function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.confirmBypass === "true") {
            delete form.dataset.confirmBypass;
            return;
        }

        const confirmation = form.dataset.confirm;
        if (!confirmation) return;

        event.preventDefault();
        if (actionEnAttente) return;

        demanderConfirmation({
            title: form.dataset.confirmTitle,
            message: confirmation,
            label: form.dataset.confirmLabel,
            tone: form.dataset.confirmTone,
            onConfirm: () => soumettreConfirme(form, event.submitter || null)
        });
    });

    document.addEventListener("change", function (event) {
        const select = event.target;
        if (!(select instanceof HTMLSelectElement) || !select.dataset.confirmChange) return;

        const valeurInitiale = select.dataset.etatInitial || "";
        const nouvelleValeur = select.value;
        if (nouvelleValeur === valeurInitiale || actionEnAttente) return;

        const optionChoisie = select.options[select.selectedIndex];
        const libelleNouvelEtat = optionChoisie ? optionChoisie.textContent.trim() : nouvelleValeur;

        demanderConfirmation({
            title: select.dataset.confirmTitle || "Confirmer le changement",
            message: select.dataset.confirmChange.replace("{etat}", "« " + libelleNouvelEtat + " »"),
            label: "Enregistrer",
            onConfirm: function () {
                if (soumettreConfirme(select.form, null)) {
                    select.dataset.etatInitial = nouvelleValeur;
                } else {
                    select.value = valeurInitiale;
                }
            },
            onCancel: function () {
                select.value = valeurInitiale;
            }
        });
    });

    cancelButton.addEventListener("click", () => fermerConfirmation(false));
    acceptButton.addEventListener("click", () => fermerConfirmation(true));
    dialog.addEventListener("cancel", function (event) {
        event.preventDefault();
        fermerConfirmation(false);
    });
    dialog.addEventListener("click", function (event) {
        if (event.target === dialog) fermerConfirmation(false);
    });

    function estChampSaisissable(champ) {
        return champ instanceof HTMLInputElement
            || champ instanceof HTMLSelectElement
            || champ instanceof HTMLTextAreaElement;
    }

    function libelleDuChamp(champ) {
        const libelleAssocie = champ.labels
            ? Array.from(champ.labels).map((label) => label.textContent.trim()).find(Boolean)
            : "";
        if (libelleAssocie) return libelleAssocie.replace(/\s*\*+\s*$/, "").trim();
        if (champ.getAttribute("aria-label")) return champ.getAttribute("aria-label");

        const libellesParNom = {
            email: "adresse e-mail",
            nom: "nom",
            prenom: "prénom",
            sexe: "sexe",
            matricule: "matricule (IM)",
            motDePasse: "mot de passe",
            confirmation: "confirmation du mot de passe",
            poste: "poste",
            sigleAG: "sigle AG",
            bureau: "numéro de bureau",
            reponse: "réponse à la question secrète",
            annee: "année",
            num_ordre: "numéro d’ordre",
            num_dossier_PGCS: "numéro de dossier PGCS",
            nom_liste: "nom de la liste",
            sigle: "sigle de l’Avocat Général",
            motDePasseActuel: "mot de passe actuel",
            motDePasseNouveau: "nouveau mot de passe",
            motDePasseConfirm: "confirmation du nouveau mot de passe"
        };
        const nom = champ.name || champ.id || "";
        if (libellesParNom[nom]) return libellesParNom[nom];
        if (!nom) return "";
        const lisible = nom
            .replace(/([a-z])([A-Z])/g, "$1 $2")
            .replace(/[_-]+/g, " ")
            .trim();
        return lisible.charAt(0).toLocaleUpperCase("fr-FR") + lisible.slice(1);
    }

    function messageValidationFrancais(champ, validite) {
        if (validite.valueMissing) {
            const libelle = libelleDuChamp(champ);
            return libelle
                ? "Le champ « " + libelle + " » est obligatoire."
                : "Veuillez renseigner ce champ.";
        }
        if (validite.typeMismatch && champ.type === "email") {
            return "Veuillez saisir une adresse e-mail valide.";
        }
        if (validite.typeMismatch && champ.type === "url") {
            return "Veuillez saisir une adresse Web valide.";
        }
        if (validite.badInput) return "Veuillez saisir une valeur valide.";
        if (validite.rangeUnderflow) return "La valeur doit être supérieure ou égale à " + champ.min + ".";
        if (validite.rangeOverflow) return "La valeur doit être inférieure ou égale à " + champ.max + ".";
        if (validite.tooShort) return "Saisissez au moins " + champ.minLength + " caractères.";
        if (validite.tooLong) return "Saisissez au maximum " + champ.maxLength + " caractères.";
        if (validite.stepMismatch) return "Veuillez choisir une valeur autorisée.";
        if (validite.patternMismatch) return "Le format saisi n’est pas valide.";
        return "La valeur saisie n’est pas valide.";
    }

    document.addEventListener("invalid", function (event) {
        const champ = event.target;
        if (!estChampSaisissable(champ)) return;

        // Efface un ancien message avant de lire les contraintes natives.
        champ.setCustomValidity("");
        const validite = champ.validity;
        if (validite.valid) return;

        champ.setCustomValidity(messageValidationFrancais(champ, validite));
        champ.setAttribute("aria-invalid", "true");
    }, true);

    function effacerErreurPersonnalisee(event) {
        const champ = event.target;
        if (!estChampSaisissable(champ)) return;

        champ.setCustomValidity("");
        if (champ.validity.valid) champ.removeAttribute("aria-invalid");
    }

    document.addEventListener("input", effacerErreurPersonnalisee, true);
    document.addEventListener("change", effacerErreurPersonnalisee, true);
})();

(function () {
    const toasts = document.querySelectorAll("[data-toast]");
    if (toasts.length === 0) return;

    toasts.forEach(function (toast) {
        const close = toast.querySelector("[data-toast-close]");
        const duration = parseInt(toast.dataset.toastDuration || "6500", 10);
        let remaining = Number.isFinite(duration) ? duration : 6500;
        let timer = null;
        let startedAt = 0;
        let survol = false;
        let focus = false;

        function retirer() {
            if (timer !== null) window.clearTimeout(timer);
            timer = null;
            toast.classList.add("notification-sortie");
            window.setTimeout(function () { toast.remove(); }, 240);
        }

        function demarrer() {
            startedAt = Date.now();
            toast.classList.remove("est-en-pause");
            timer = window.setTimeout(retirer, remaining);
        }

        function suspendre() {
            if (timer === null) return;
            window.clearTimeout(timer);
            timer = null;
            remaining -= Date.now() - startedAt;
            if (remaining < 0) remaining = 0;
            toast.classList.add("est-en-pause");
        }

        function reprendre() {
            if (survol || focus || timer !== null || remaining <= 0) return;
            demarrer();
        }

        if (close) close.addEventListener("click", retirer);
        toast.addEventListener("mouseenter", function () {
            survol = true;
            suspendre();
        });
        toast.addEventListener("mouseleave", function () {
            survol = false;
            reprendre();
        });
        toast.addEventListener("focusin", function () {
            focus = true;
            suspendre();
        });
        toast.addEventListener("focusout", function (event) {
            if (!toast.contains(event.relatedTarget)) {
                focus = false;
                reprendre();
            }
        });
        demarrer();
    });
})();

/* Bascule « Afficher / Masquer » : chaque champ de mot de passe gagne un
   bouton en forme d'œil permettant de vérifier sa saisie. Un attribut
   data-bascule-mdp évite d'équiper deux fois le même champ. */
(function () {
    const iconeOeil = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
    const iconeOeilBarre = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    function installer(champ) {
        if (champ.dataset.basculeMdp) return;
        champ.dataset.basculeMdp = "1";

        const enveloppe = document.createElement("span");
        enveloppe.className = "champ-mdp";
        champ.parentNode.insertBefore(enveloppe, champ);
        enveloppe.appendChild(champ);

        const bouton = document.createElement("button");
        bouton.type = "button";
        bouton.className = "bascule-mdp";
        bouton.innerHTML = iconeOeil;
        bouton.setAttribute("aria-label", "Afficher le mot de passe");
        bouton.addEventListener("click", function () {
            const visible = champ.type === "password";
            champ.type = visible ? "text" : "password";
            bouton.innerHTML = visible ? iconeOeilBarre : iconeOeil;
            bouton.setAttribute(
                "aria-label",
                visible ? "Masquer le mot de passe" : "Afficher le mot de passe"
            );
            champ.focus({ preventScroll: true });
        });
        enveloppe.appendChild(bouton);
    }

    document.querySelectorAll('input[type="password"]').forEach(installer);
})();
