@extends('adminlte::page')

@section('title', 'RGPD - Politique de confidentialité')

@section('content_header')
    <h1>RGPD <small class="text-muted">Politique de confidentialité</small></h1>
@stop

@section('content')

{{-- Date de révision écrite à la main : à changer à chaque modification du contenu. --}}
@php($revisedAt = '27/11/2026')

<div class="float-end">
    <img src="/{{ config('branding.logo_img') }}" alt="{{ config('branding.logo_alt') }}" class="brand-image shadow ms-2">
</div>

{{-- =====================================================================
     SECTION FRANÇAISE
     ===================================================================== --}}

<x-adminlte-card title="[FR] Responsable du traitement" theme="primary" body-class="bg-body"
    icon="fas fa-building text-white" collapsible="collapsed">

    <p>
        L'ERP <strong>{{ config('branding.app_name_full') }}</strong> est un logiciel déployé en instance isolée pour chaque entreprise cliente.
        Le <strong>responsable du traitement</strong> des données est l'entreprise qui exploite cette instance,
        et non l'éditeur du logiciel ({{ config('branding.publisher_name') }}).
    </p>
    <p>
        Pour toute question relative à vos données personnelles, contactez l'administrateur de votre instance
        ou adressez un email à <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[FR] Données personnelles collectées" theme="primary" body-class="bg-body"
    icon="fas fa-database text-white" collapsible="collapsed">

    <p>L'application collecte et traite les catégories de données suivantes, selon les modules utilisés :</p>

    <h5 class="mt-3"><i class="fas fa-users me-1"></i> Utilisateurs internes (salariés)</h5>
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Donnée</th><th>Finalité</th><th>Chiffrement</th></tr>
        </thead>
        <tbody>
            <tr><td>Nom, email</td><td>Authentification, identification</td><td>Non (nécessaire à la connexion)</td></tr>
            <tr><td>Téléphones (perso, domicile, mobile)</td><td>Ressources humaines</td><td>Oui (AES-256)</td></tr>
            <tr><td>Adresse postale complète</td><td>Ressources humaines</td><td>Oui (AES-256)</td></tr>
            <tr><td>Date de naissance</td><td>Ressources humaines</td><td>Oui (AES-256)</td></tr>
            <tr><td>Numéro de sécurité sociale (NSS)</td><td>Paie / RH</td><td>Oui (AES-256)</td></tr>
            <tr><td>Numéro de carte nationale d'identité</td><td>Ressources humaines</td><td>Oui (AES-256)</td></tr>
            <tr><td>Permis de conduire</td><td>Ressources humaines</td><td>Oui (AES-256)</td></tr>
            <tr><td>Email privé</td><td>Contact d'urgence</td><td>Oui (AES-256)</td></tr>
            <tr><td>Nationalité, genre, situation familiale</td><td>Ressources humaines</td><td>Non</td></tr>
            <tr><td>Dates d'embauche / fin de contrat, matricule de paie</td><td>Gestion RH, export paie</td><td>Non</td></tr>
            <tr><td>Code de badge</td><td>Pointage par scan</td><td>Non</td></tr>
            <tr><td>Pointages (entrées / sorties), temps passé sur les tâches</td><td>Temps de présence, export paie, suivi de production</td><td>Non</td></tr>
            <tr><td>Absences et soldes de congés</td><td>Gestion des congés, export paie</td><td>Non</td></tr>
            <tr><td>Notes de frais et justificatifs</td><td>Remboursement des frais</td><td>Non (accès limité au salarié et aux RH)</td></tr>
            <tr><td>Dossier salarié (contrat, bulletins, arrêts, diplômes, pièce d'identité)</td><td>Gestion RH</td><td>Non (accès limité au salarié et aux RH)</td></tr>
            <tr><td>Formations et habilitations</td><td>Matrice de polyvalence</td><td>Non</td></tr>
            <tr><td>Humeur du jour <em>(facultatif)</em></td><td>Indicateur d'ambiance</td><td>Non</td></tr>
            <tr><td>Signature d'e-mail</td><td>E-mails envoyés depuis l'application</td><td>Non</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-address-book me-1"></i> Contacts B2B (clients / fournisseurs / prospects)</h5>
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Donnée</th><th>Finalité</th><th>Chiffrement</th></tr>
        </thead>
        <tbody>
            <tr><td>Prénom, nom</td><td>Gestion commerciale</td><td>Non</td></tr>
            <tr><td>Adresse email professionnelle</td><td>Communication, portail client</td><td>Non</td></tr>
            <tr><td>Téléphone professionnel / mobile</td><td>Communication commerciale</td><td>Non</td></tr>
            <tr><td>Fonction / poste</td><td>Gestion commerciale</td><td>Non</td></tr>
            <tr><td>Adresse postale de l'entreprise</td><td>Facturation, livraison</td><td>Non</td></tr>
            <tr><td>E-mails reçus (expéditeur, objet, texte, pièces jointes)</td><td>Demandes entrantes (pistes commerciales)</td><td>Non</td></tr>
            <tr><td>Nom et e-mail du signataire</td><td>Signature électronique des devis et des dérogations</td><td>Non</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-file-invoice me-1"></i> Données contractuelles et comptables</h5>
    <p>
        Les devis, commandes, factures et bons de livraison associés à un contact sont conservés pour
        satisfaire aux obligations légales comptables (voir durées ci-dessous). Ces données ne peuvent
        pas être supprimées ; elles font l'objet d'une <strong>anonymisation</strong> sur demande
        d'exercice du droit à l'effacement.
    </p>

    <h5 class="mt-3"><i class="fas fa-history me-1"></i> Journaux</h5>
    <ul>
        <li><strong>Journal des modifications</strong> : qui a modifié quoi et quand (documents, visas, qualité, utilisateurs, sociétés).</li>
        <li><strong>Journal des consultations</strong> <em>(si activé par l'entreprise)</em> : utilisateur, fiche consultée, date et adresse IP.</li>
        <li><strong>E-mails envoyés</strong> depuis l'application (devis, factures, relances) : destinataire, objet et corps du message.</li>
    </ul>

    <h5 class="mt-3"><i class="fas fa-microphone me-1"></i> Visites sur site et dictée</h5>
    <p>
        Les photos prises lors d'une visite sont rangées avec l'opportunité. Les mémos vocaux et la dictée
        de devis, de commande ou de piste sont transcrits puis <strong>jamais conservés</strong> sous forme
        audio par l'application.
    </p>
    <p>
        La <strong>dictée d'une piste</strong> envoie au fournisseur d'IA le texte dicté, avec les noms,
        e-mails et téléphones qu'il contient. Elle n'est proposée que si l'entreprise l'a autorisé
        (Intégrations → IA, autorisation tracée et retirée à chaque changement de fournisseur). Société et
        contact sont rapprochés de la base par l'application : aucune donnée de la base ne part.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[FR] Durées de conservation" theme="primary" body-class="bg-body"
    icon="fas fa-clock text-white" collapsible="collapsed">

    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Type de données</th><th>Durée</th><th>Base légale</th><th>Mise en œuvre</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Pièces comptables (factures, commandes, BL)</td>
                <td><strong>10 ans</strong> minimum</td>
                <td>Code de commerce, art. L.123-22</td>
                <td>Jamais supprimées par l'application</td>
            </tr>
            <tr>
                <td>Données RH (contrats, salaires, absences)</td>
                <td><strong>5 ans</strong> après fin de contrat</td>
                <td>Code du travail</td>
                <td>Suppression du salarié par l'entreprise, puis purge (ligne « enregistrements supprimés »)</td>
            </tr>
            <tr>
                <td>Contacts clients actifs</td>
                <td>Durée de la relation commerciale</td>
                <td>Exécution du contrat</td>
                <td>Suppression par l'entreprise</td>
            </tr>
            <tr>
                <td>Contacts prospects (sans commande)</td>
                <td><strong>3 ans</strong> après dernier contact</td>
                <td>Recommandation CNIL - prospection B2B</td>
                <td>Suppression par l'entreprise</td>
            </tr>
            <tr>
                <td>Journal des modifications et visas</td>
                <td>Sans limite par défaut (durée réglable par l'entreprise)</td>
                <td>Traçabilité des enregistrements qualité (ISO 9001, ISO 19443)</td>
                <td>Purge mensuelle automatique si une durée est réglée</td>
            </tr>
            <tr>
                <td>Journal des consultations</td>
                <td><strong>365 jours</strong> (réglable)</td>
                <td>Sécurité, traçabilité des accès</td>
                <td>Purge mensuelle automatique</td>
            </tr>
            <tr>
                <td>Journaux d'emails sortants</td>
                <td><strong>12 mois</strong></td>
                <td>Proportionnalité</td>
                <td>Purge hebdomadaire automatique</td>
            </tr>
            <tr>
                <td>Tokens de réinitialisation de mot de passe</td>
                <td><strong>1 heure</strong></td>
                <td>Sécurité</td>
                <td>Purge hebdomadaire automatique</td>
            </tr>
            <tr>
                <td>Enregistrements supprimés (utilisateurs, sociétés, contacts, adresses)</td>
                <td><strong>90 jours</strong>, puis effacement définitif</td>
                <td>Fenêtre de restauration d'urgence</td>
                <td>Purge hebdomadaire automatique. Une personne citée par un document ou un visa est
                    <strong>anonymisée</strong> au lieu d'être effacée ; une société ou une adresse citée
                    par un document est conservée.</td>
            </tr>
            <tr>
                <td>Sauvegardes</td>
                <td>Jusqu'à <strong>2 ans</strong> (de moins en moins nombreuses avec l'âge)</td>
                <td>Continuité d'activité</td>
                <td>Une donnée effacée reste dans les sauvegardes antérieures jusqu'à leur suppression</td>
            </tr>
        </tbody>
    </table>

    <div class="callout callout-info mt-2">
        <p>
            <i class="fas fa-info-circle me-1"></i>
            Les durées marquées « suppression par l'entreprise » ne sont pas appliquées automatiquement :
            l'entreprise qui exploite l'instance supprime les fiches concernées, et la purge les efface ensuite.
        </p>
    </div>

</x-adminlte-card>

<x-adminlte-card title="[FR] Vos droits RGPD" theme="primary" body-class="bg-body"
    icon="fas fa-balance-scale text-white" collapsible="collapsed">

    <div class="row">
        <div class="col-md-6">
            <h5><i class="fas fa-eye me-1 text-primary"></i> Droit d'accès (Art. 15)</h5>
            <p>
                Vous pouvez obtenir une copie des données vous concernant. Un utilisateur interne la
                télécharge lui-même au format JSON depuis la page
                <a href="{{ route('me.rgpd.index') }}">Mes données personnelles</a> ; un contact client
                depuis le portail client, ou sur demande dans un délai de <strong>30 jours</strong>.
            </p>

            <h5><i class="fas fa-pen me-1 text-warning"></i> Droit de rectification (Art. 16)</h5>
            <p>
                Vous pouvez corriger toute donnée inexacte directement dans l'application
                (profil utilisateur, fiche contact) ou en contactant l'administrateur.
            </p>

            <h5><i class="fas fa-trash me-1 text-danger"></i> Droit à l'effacement (Art. 17)</h5>
            <p>
                La demande se fait depuis la même page ; elle est transmise à l'administrateur, qui la traite.
                Lorsqu'aucune obligation légale de conservation ne s'applique, vos données
                sont supprimées. Dans le cas contraire (pièces comptables sur 10 ans, visas qualité),
                elles sont <strong>anonymisées</strong> : les données identifiantes sont effacées,
                les documents sont conservés sans lien nominal.
            </p>
        </div>
        <div class="col-md-6">
            <h5><i class="fas fa-pause-circle me-1 text-secondary"></i> Droit à la limitation (Art. 18)</h5>
            <p>
                Vous pouvez demander la suspension du traitement de vos données pendant la durée
                d'examen d'une contestation.
            </p>

            <h5><i class="fas fa-file-export me-1 text-success"></i> Droit à la portabilité (Art. 20)</h5>
            <p>
                Vous pouvez obtenir vos données dans un format structuré et lisible par machine (JSON),
                par le même export que le droit d'accès.
            </p>

            <h5><i class="fas fa-hand-paper me-1 text-info"></i> Droit d'opposition (Art. 21)</h5>
            <p>
                Vous pouvez vous opposer au traitement de vos données à des fins de prospection commerciale
                à tout moment.
            </p>
        </div>
    </div>

    <div class="callout callout-warning mt-2">
        <p>
            <i class="fas fa-envelope me-1"></i>
            Pour exercer vos droits, contactez l'administrateur de l'instance ou écrivez à
            <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
            Délai de réponse : <strong>30 jours maximum</strong> (Art. 12 RGPD).
        </p>
    </div>

</x-adminlte-card>

<x-adminlte-card title="[FR] Sécurité des données" theme="primary" body-class="bg-body"
    icon="fas fa-shield-alt text-white" collapsible="collapsed">

    <h5>Mesures techniques mises en place</h5>
    <ul>
        <li><strong>Chiffrement au repos</strong> - les données sensibles des salariés (NSS, pièce d'identité, permis, adresses, téléphones, date de naissance, email privé) et les clés des services connectés sont chiffrées en base de données (AES-256 via Laravel Crypt).</li>
        <li><strong>Mots de passe hashés</strong> - tous les mots de passe sont hashés avec bcrypt (facteur de coût 10+), jamais stockés en clair.</li>
        <li><strong>Isolation par instance</strong> - chaque entreprise cliente dispose de sa propre instance et de sa propre base de données. Il n'y a aucun partage de données entre instances.</li>
        <li><strong>Fichiers hors du web</strong> - les fichiers déposés sont stockés hors de la racine publique du serveur et servis seulement aux utilisateurs connectés autorisés.</li>
        <li><strong>Contrôle d'accès par rôle</strong> - chaque utilisateur accède uniquement aux données nécessaires à sa fonction (RBAC via Spatie Permission). Le dossier salarié et les notes de frais ne sont lisibles que par le salarié concerné et les RH.</li>
        <li><strong>Journal des modifications</strong> - les modifications des données sensibles sont tracées avec horodatage et identification de l'auteur.</li>
        <li><strong>HTTPS</strong> - les communications sont chiffrées en transit (TLS).</li>
        <li><strong>Tokens temporaires</strong> - les tokens de réinitialisation de mot de passe expirent après 1 heure.</li>
    </ul>

</x-adminlte-card>

<x-adminlte-card title="[FR] Sous-traitants techniques" theme="warning" body-class="bg-body"
    icon="fas fa-server text-white" collapsible="collapsed">

    <p>
        Les services suivants peuvent être impliqués dans le traitement des données,
        <strong>seulement s'ils sont activés</strong> dans la configuration de l'instance. Chaque entreprise
        cliente est responsable de la vérification des DPA (Data Processing Agreements) avec les services
        qu'elle active.
    </p>

    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Sous-traitant</th><th>Finalité</th><th>Données transmises</th><th>Localisation</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Serveur SMTP (configurable)</td>
                <td>Envoi d'emails (devis, factures, relances, notifications)</td>
                <td>Nom, email, contenu des documents</td>
                <td>Selon le fournisseur choisi</td>
            </tr>
            <tr>
                <td>Stockage objet compatible S3 <em>(optionnel)</em></td>
                <td>Stockage des fichiers déposés ou des sauvegardes</td>
                <td>Fichiers joints, sauvegardes</td>
                <td>Selon le fournisseur et la région choisis</td>
            </tr>
            <tr>
                <td>Anthropic (Claude) <em>(optionnel)</em></td>
                <td>Fonctions d'intelligence artificielle : assistant, dictée de devis, de commande et de piste, réécriture de textes, lecture de justificatifs et de cotes, compte rendu de visite</td>
                <td>Texte saisi ou dicté (noms et coordonnées compris pour la dictée de piste, si autorisée), image du justificatif ou de la zone de plan concernée</td>
                <td>États-Unis</td>
            </tr>
            <tr>
                <td>OVHcloud AI Endpoints <em>(optionnel)</em></td>
                <td>Mêmes fonctions d'IA, et transcription des mémos vocaux</td>
                <td>Texte saisi, image, enregistrement audio (non conservé par l'application)</td>
                <td>France</td>
            </tr>
            <tr>
                <td>DocuSign <em>(optionnel)</em></td>
                <td>Signature électronique des devis et des dérogations</td>
                <td>Document à signer, nom et e-mail du signataire</td>
                <td>Selon le compte DocuSign (UE ou États-Unis)</td>
            </tr>
            <tr>
                <td>Microsoft 365 / SharePoint <em>(optionnel)</em></td>
                <td>Copie des fichiers de la gestion documentaire</td>
                <td>Fichiers déposés (jamais le dossier salarié ni les notes de frais)</td>
                <td>Selon le tenant Microsoft de l'entreprise</td>
            </tr>
            <tr>
                <td>Plateforme de dématérialisation partenaire (PDP) <em>(optionnel)</em></td>
                <td>Facturation électronique (émission et réception)</td>
                <td>Factures, identité et coordonnées des parties</td>
                <td>Selon la plateforme choisie</td>
            </tr>
            <tr>
                <td>API Recherche d'entreprises (État français) <em>(optionnel)</em></td>
                <td>Préremplissage des fiches sociétés</td>
                <td>Texte recherché (SIRET, SIREN ou nom de l'entreprise)</td>
                <td>France</td>
            </tr>
            <tr>
                <td>Service VIES (Commission européenne)</td>
                <td>Validation des numéros de TVA intracommunautaire</td>
                <td>Numéro de TVA (donnée entreprise, non nominale)</td>
                <td>Union européenne</td>
            </tr>
            <tr>
                <td>Configurateurs externes, simulation de pliage, MES <em>(optionnels)</em></td>
                <td>Configuration de pièces, simulation, pointage atelier</td>
                <td>Article, quantité, fichier de la pièce, nom du client (sans adresse) ; pour le MES, ordres de fabrication et opérateurs</td>
                <td>Selon le prestataire</td>
            </tr>
            <tr>
                <td>Pusher <em>(optionnel)</em></td>
                <td>Notifications temps réel (WebSocket)</td>
                <td>Identifiant de session (aucune donnée nominale)</td>
                <td>UE / USA selon cluster</td>
            </tr>
            <tr>
                <td>Redis</td>
                <td>Cache, sessions, files d'attente</td>
                <td>Sessions utilisateurs (données temporaires)</td>
                <td>Auto-hébergé (même serveur)</td>
            </tr>
            <tr>
                <td>Annuaire LDAP / Active Directory <em>(optionnel)</em></td>
                <td>Authentification centralisée</td>
                <td>Comptes utilisateurs de l'entreprise</td>
                <td>Infrastructure interne de l'entreprise</td>
            </tr>
        </tbody>
    </table>

</x-adminlte-card>

<x-adminlte-card title="[FR] Politique de confidentialité complète" body-class="bg-body"
    theme="warning" collapsible="collapsed" removable>

    <p>Les informations recueillies dans les formulaires sont enregistrées dans un fichier informatisé par l'entreprise qui exploite l'instance, responsable du traitement. Le logiciel {{ config('branding.app_name_full') }} est édité par <strong>{{ config('branding.publisher_name') }}</strong>.</p>

    <p>Les données collectées le sont sur la base légale suivante selon leur nature :</p>
    <ul>
        <li><strong>Exécution du contrat</strong> - données nécessaires à la gestion commerciale (contacts, commandes, factures) et au contrat de travail</li>
        <li><strong>Obligation légale</strong> - données comptables conservées 10 ans (Code de commerce), données de paie</li>
        <li><strong>Intérêt légitime</strong> - données RH des salariés de l'entreprise exploitante, traçabilité qualité, prospection B2B</li>
    </ul>

    <p>Les données ne sont jamais revendues ni transmises à des tiers non listés dans les sous-traitants techniques ci-dessus.</p>

    <p>Elles sont conservées selon les durées définies dans la section « Durées de conservation » de cette page.</p>

    <p>Conformément au RGPD (Règlement UE 2016/679), vous disposez des droits d'accès, de rectification, d'effacement, de limitation, de portabilité et d'opposition. Pour exercer ces droits, contactez <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.</p>

    <p>Si vous estimez, après nous avoir contactés, que vos droits ne sont pas respectés, vous pouvez adresser une réclamation à la <a href="https://www.cnil.fr" target="_blank" rel="noopener">CNIL</a> (Commission Nationale de l'Informatique et des Libertés).</p>

    <p>Cette politique peut être mise à jour. La date de dernière révision est indiquée ci-dessous.</p>
    <p class="text-muted small">Dernière révision : {{ $revisedAt }}</p>

</x-adminlte-card>

{{-- =====================================================================
     ENGLISH SECTION
     ===================================================================== --}}

<x-adminlte-card title="[EN] Data Controller" theme="primary" body-class="bg-body"
    icon="fas fa-building text-white" collapsible="collapsed">

    <p>
        <strong>{{ config('branding.app_name_full') }}</strong> is software deployed as an isolated instance for each client company.
        The <strong>data controller</strong> is the company operating this instance, not the software
        publisher ({{ config('branding.publisher_name') }}).
    </p>
    <p>
        For any questions about your personal data, contact your instance administrator or email
        <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[EN] Personal Data Collected" theme="primary" body-class="bg-body"
    icon="fas fa-database text-white" collapsible="collapsed">

    <p>Depending on the modules in use, the application collects and processes the following data:</p>

    <h5 class="mt-3"><i class="fas fa-users me-1"></i> Internal Users (Employees)</h5>
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Data</th><th>Purpose</th><th>Encrypted</th></tr>
        </thead>
        <tbody>
            <tr><td>Name, email</td><td>Authentication, identification</td><td>No (required for login)</td></tr>
            <tr><td>Phone numbers (personal, home, mobile)</td><td>Human resources</td><td>Yes (AES-256)</td></tr>
            <tr><td>Full postal address</td><td>Human resources</td><td>Yes (AES-256)</td></tr>
            <tr><td>Date of birth</td><td>Human resources</td><td>Yes (AES-256)</td></tr>
            <tr><td>Social Security Number</td><td>Payroll / HR</td><td>Yes (AES-256)</td></tr>
            <tr><td>National ID card number</td><td>Human resources</td><td>Yes (AES-256)</td></tr>
            <tr><td>Driving license</td><td>Human resources</td><td>Yes (AES-256)</td></tr>
            <tr><td>Private email</td><td>Emergency contact</td><td>Yes (AES-256)</td></tr>
            <tr><td>Nationality, gender, marital status</td><td>Human resources</td><td>No</td></tr>
            <tr><td>Hire / termination dates, payroll number</td><td>HR management, payroll export</td><td>No</td></tr>
            <tr><td>Badge code</td><td>Scan-based time tracking</td><td>No</td></tr>
            <tr><td>Clock-in / clock-out records, time spent on tasks</td><td>Attendance, payroll export, production tracking</td><td>No</td></tr>
            <tr><td>Absences and leave balances</td><td>Leave management, payroll export</td><td>No</td></tr>
            <tr><td>Expense reports and receipts</td><td>Expense reimbursement</td><td>No (restricted to the employee and HR)</td></tr>
            <tr><td>Employee file (contract, payslips, sick leave, diplomas, ID document)</td><td>HR management</td><td>No (restricted to the employee and HR)</td></tr>
            <tr><td>Training and authorisations</td><td>Skills matrix</td><td>No</td></tr>
            <tr><td>Daily mood <em>(optional)</em></td><td>Workplace mood indicator</td><td>No</td></tr>
            <tr><td>Email signature</td><td>Emails sent from the application</td><td>No</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-address-book me-1"></i> B2B Contacts (Customers / Suppliers / Prospects)</h5>
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Data</th><th>Purpose</th><th>Encrypted</th></tr>
        </thead>
        <tbody>
            <tr><td>First name, last name</td><td>Commercial management</td><td>No</td></tr>
            <tr><td>Professional email address</td><td>Communication, customer portal</td><td>No</td></tr>
            <tr><td>Professional / mobile phone</td><td>Commercial communication</td><td>No</td></tr>
            <tr><td>Job title / function</td><td>Commercial management</td><td>No</td></tr>
            <tr><td>Company postal address</td><td>Invoicing, delivery</td><td>No</td></tr>
            <tr><td>Received emails (sender, subject, body, attachments)</td><td>Inbound requests (sales leads)</td><td>No</td></tr>
            <tr><td>Signer name and email</td><td>Electronic signature of quotes and deviation requests</td><td>No</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-file-invoice me-1"></i> Accounting & Contractual Data</h5>
    <p>
        Quotes, orders, invoices and delivery notes linked to a contact are retained to meet legal
        accounting obligations. These cannot be deleted; upon an erasure request, they are
        <strong>anonymised</strong>: identifying data is removed while accounting documents are preserved.
    </p>

    <h5 class="mt-3"><i class="fas fa-history me-1"></i> Logs</h5>
    <ul>
        <li><strong>Change log</strong>: who changed what and when (documents, approvals, quality, users, companies).</li>
        <li><strong>Access log</strong> <em>(if enabled by the company)</em>: user, record viewed, date and IP address.</li>
        <li><strong>Sent emails</strong> from the application (quotes, invoices, reminders): recipient, subject and body.</li>
    </ul>

    <h5 class="mt-3"><i class="fas fa-microphone me-1"></i> Site visits and dictation</h5>
    <p>
        Photos taken during a site visit are stored with the opportunity. Voice memos and quote, order or
        lead dictation are transcribed and the audio is <strong>never kept</strong> by the application.
    </p>
    <p>
        <strong>Lead dictation</strong> sends the dictated text to the AI provider, including the names,
        emails and phone numbers it contains. It is only offered if the company has allowed it
        (Integrations → AI, permission recorded and withdrawn whenever the provider changes). Company and
        contact are matched against the database by the application: no database data is sent.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[EN] Retention Periods" theme="primary" body-class="bg-body"
    icon="fas fa-clock text-white" collapsible="collapsed">

    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Data Type</th><th>Period</th><th>Legal Basis</th><th>How it is applied</th></tr>
        </thead>
        <tbody>
            <tr><td>Accounting documents (invoices, orders, delivery notes)</td><td><strong>10 years</strong> minimum</td><td>French Commercial Code, art. L.123-22</td><td>Never deleted by the application</td></tr>
            <tr><td>HR data (contracts, salaries, absences)</td><td><strong>5 years</strong> after end of contract</td><td>French Labour Code</td><td>Employee deleted by the company, then purged (see "deleted records")</td></tr>
            <tr><td>Active client contacts</td><td>Duration of commercial relationship</td><td>Contract performance</td><td>Deleted by the company</td></tr>
            <tr><td>Prospect contacts (no orders)</td><td><strong>3 years</strong> after last contact</td><td>CNIL recommendation - B2B prospecting</td><td>Deleted by the company</td></tr>
            <tr><td>Change log and approvals</td><td>Unlimited by default (period set by the company)</td><td>Traceability of quality records (ISO 9001, ISO 19443)</td><td>Automatic monthly purge if a period is set</td></tr>
            <tr><td>Access log</td><td><strong>365 days</strong> (configurable)</td><td>Security, access traceability</td><td>Automatic monthly purge</td></tr>
            <tr><td>Outbound email logs</td><td><strong>12 months</strong></td><td>Proportionality</td><td>Automatic weekly purge</td></tr>
            <tr><td>Password reset tokens</td><td><strong>1 hour</strong></td><td>Security</td><td>Automatic weekly purge</td></tr>
            <tr><td>Deleted records (users, companies, contacts, addresses)</td><td><strong>90 days</strong>, then permanently deleted</td><td>Emergency restore window</td><td>Automatic weekly purge. A person referenced by a document or an approval is <strong>anonymised</strong> instead of deleted; a company or address referenced by a document is kept.</td></tr>
            <tr><td>Backups</td><td>Up to <strong>2 years</strong> (fewer copies as they age)</td><td>Business continuity</td><td>Erased data remains in earlier backups until they are deleted</td></tr>
        </tbody>
    </table>

    <div class="callout callout-info mt-2">
        <p>
            <i class="fas fa-info-circle me-1"></i>
            Periods marked "deleted by the company" are not applied automatically: the company operating
            the instance deletes the records concerned, and the purge then erases them.
        </p>
    </div>

</x-adminlte-card>

<x-adminlte-card title="[EN] Your GDPR Rights" theme="primary" body-class="bg-body"
    icon="fas fa-balance-scale text-white" collapsible="collapsed">

    <div class="row">
        <div class="col-md-6">
            <h5><i class="fas fa-eye me-1 text-primary"></i> Right of Access (Art. 15)</h5>
            <p>You may obtain a copy of the data held about you. An internal user downloads it in JSON format from the <a href="{{ route('me.rgpd.index') }}">My personal data</a> page; a customer contact from the customer portal, or on request within <strong>30 days</strong>.</p>

            <h5><i class="fas fa-pen me-1 text-warning"></i> Right to Rectification (Art. 16)</h5>
            <p>You may correct inaccurate data directly in the application (user profile, contact record) or by contacting the administrator.</p>

            <h5><i class="fas fa-trash me-1 text-danger"></i> Right to Erasure (Art. 17)</h5>
            <p>Requests are made from the same page and forwarded to the administrator, who handles them. When no legal retention obligation applies, your data is deleted. Otherwise (accounting documents retained for 10 years, quality approvals), data is <strong>anonymised</strong>: identifying fields are erased while documents are preserved without a nominal link.</p>
        </div>
        <div class="col-md-6">
            <h5><i class="fas fa-pause-circle me-1 text-secondary"></i> Right to Restriction (Art. 18)</h5>
            <p>You may request suspension of processing during the examination of a dispute.</p>

            <h5><i class="fas fa-file-export me-1 text-success"></i> Right to Portability (Art. 20)</h5>
            <p>You may obtain your data in a structured, machine-readable format (JSON), through the same export as the right of access.</p>

            <h5><i class="fas fa-hand-paper me-1 text-info"></i> Right to Object (Art. 21)</h5>
            <p>You may object to the processing of your data for direct marketing purposes at any time.</p>
        </div>
    </div>

    <div class="callout callout-warning mt-2">
        <p>
            <i class="fas fa-envelope me-1"></i>
            To exercise your rights, contact your instance administrator or write to
            <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
            Response time: <strong>maximum 30 days</strong> (Art. 12 GDPR).
        </p>
    </div>

</x-adminlte-card>

<x-adminlte-card title="[EN] Data Security" theme="primary" body-class="bg-body"
    icon="fas fa-shield-alt text-white" collapsible="collapsed">

    <h5>Technical measures in place</h5>
    <ul>
        <li><strong>Encryption at rest</strong> - sensitive employee data (social security number, ID document, driving licence, addresses, phone numbers, date of birth, private email) and the keys of connected services are encrypted in the database (AES-256 via Laravel Crypt).</li>
        <li><strong>Hashed passwords</strong> - all passwords are hashed with bcrypt (cost factor 10+) and never stored in plain text.</li>
        <li><strong>Instance isolation</strong> - each client company has its own instance and its own database. No data is shared between instances.</li>
        <li><strong>Files outside the web root</strong> - uploaded files are stored outside the server's public directory and served only to authorised, logged-in users.</li>
        <li><strong>Role-based access control</strong> - each user only accesses the data required for their role (RBAC via Spatie Permission). The employee file and expense reports are readable only by the employee concerned and HR.</li>
        <li><strong>Change log</strong> - changes to sensitive data are recorded with a timestamp and the author.</li>
        <li><strong>HTTPS</strong> - communications are encrypted in transit (TLS).</li>
        <li><strong>Temporary tokens</strong> - password reset tokens expire after 1 hour.</li>
    </ul>

</x-adminlte-card>

<x-adminlte-card title="[EN] Technical Sub-processors" theme="warning" body-class="bg-body"
    icon="fas fa-server text-white" collapsible="collapsed">

    <p>
        The following services may be involved in processing data <strong>only if they are enabled</strong>
        in the instance configuration. Each client company is responsible for checking the DPAs
        (Data Processing Agreements) of the services it enables.
    </p>

    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Sub-processor</th><th>Purpose</th><th>Data transferred</th><th>Location</th></tr>
        </thead>
        <tbody>
            <tr><td>SMTP server (configurable)</td><td>Email delivery (quotes, invoices, reminders, notifications)</td><td>Name, email, document content</td><td>Depends on the chosen provider</td></tr>
            <tr><td>S3-compatible object storage <em>(optional)</em></td><td>Storage of uploaded files or backups</td><td>Uploaded files, backups</td><td>Depends on the chosen provider and region</td></tr>
            <tr><td>Anthropic (Claude) <em>(optional)</em></td><td>AI features: assistant, quote, order and lead dictation, text rewriting, reading receipts and drawing dimensions, site visit reports</td><td>Text typed or dictated (names and contact details included for lead dictation, if allowed), image of the receipt or of the drawing area concerned</td><td>United States</td></tr>
            <tr><td>OVHcloud AI Endpoints <em>(optional)</em></td><td>Same AI features, plus voice memo transcription</td><td>Typed text, image, audio recording (not kept by the application)</td><td>France</td></tr>
            <tr><td>DocuSign <em>(optional)</em></td><td>Electronic signature of quotes and deviation requests</td><td>Document to sign, signer name and email</td><td>Depends on the DocuSign account (EU or United States)</td></tr>
            <tr><td>Microsoft 365 / SharePoint <em>(optional)</em></td><td>Copy of document management files</td><td>Uploaded files (never the employee file or expense reports)</td><td>Depends on the company's Microsoft tenant</td></tr>
            <tr><td>Certified e-invoicing platform (PDP) <em>(optional)</em></td><td>Electronic invoicing (sending and receiving)</td><td>Invoices, identity and contact details of the parties</td><td>Depends on the chosen platform</td></tr>
            <tr><td>French government company search API <em>(optional)</em></td><td>Pre-filling company records</td><td>Search text (SIRET, SIREN or company name)</td><td>France</td></tr>
            <tr><td>VIES (European Commission)</td><td>EU VAT number validation</td><td>VAT number (company data, not personal)</td><td>European Union</td></tr>
            <tr><td>External configurators, bending simulation, MES <em>(optional)</em></td><td>Part configuration, simulation, shop-floor time tracking</td><td>Item, quantity, part file, customer name (no address); for the MES, work orders and operators</td><td>Depends on the provider</td></tr>
            <tr><td>Pusher <em>(optional)</em></td><td>Real-time notifications (WebSocket)</td><td>Session ID (no nominal data)</td><td>EU / USA depending on cluster</td></tr>
            <tr><td>Redis</td><td>Cache, sessions, queues</td><td>User sessions (temporary data)</td><td>Self-hosted (same server)</td></tr>
            <tr><td>LDAP / Active Directory <em>(optional)</em></td><td>Centralised authentication</td><td>Company user accounts</td><td>Company internal infrastructure</td></tr>
        </tbody>
    </table>

</x-adminlte-card>

<x-adminlte-card title="[EN] Privacy Policy" theme="warning" body-class="bg-body"
    collapsible="collapsed" removable>

    <p>Information collected through forms is recorded in a computerised file by the company operating the instance, which is the data controller. The {{ config('branding.app_name_full') }} software is published by <strong>{{ config('branding.publisher_name') }}</strong>.</p>

    <p>Data is processed on the following legal bases depending on its nature:</p>
    <ul>
        <li><strong>Contract performance</strong> - data required for commercial management (contacts, orders, invoices) and for employment contracts</li>
        <li><strong>Legal obligation</strong> - accounting data retained for 10 years (French Commercial Code), payroll data</li>
        <li><strong>Legitimate interest</strong> - HR data of the operating company's employees, quality traceability, B2B prospecting</li>
    </ul>

    <p>Data is never sold or shared with third parties not listed in the technical sub-processors section above.</p>

    <p>Data is retained according to the periods defined in the Retention Periods section of this page.</p>

    <p>In accordance with the GDPR (EU Regulation 2016/679), you have the right to access, rectify, erase, restrict, port and object to the processing of your data. To exercise these rights, contact <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.</p>

    <p>If you believe, after contacting us, that your rights have not been respected, you may lodge a complaint with your national supervisory authority (e.g. <a href="https://www.cnil.fr" target="_blank" rel="noopener">CNIL</a> in France).</p>

    <p class="text-muted small">Last revised: {{ $revisedAt }}</p>

</x-adminlte-card>

@stop

@section('css')
@stop

@section('js')
@stop
