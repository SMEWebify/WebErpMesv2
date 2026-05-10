@extends('adminlte::page')

@section('title', 'RGPD — Politique de confidentialité')

@section('content_header')
    <h1>RGPD <small class="text-muted">Politique de confidentialité</small></h1>
@stop

@section('content')

<div class="float-right">
    <img src="/{{ config('branding.logo_img') }}" alt="{{ config('branding.logo_alt') }}" class="brand-image elevation-3 ml-2">
</div>

{{-- =====================================================================
     SECTION FRANÇAISE
     ===================================================================== --}}

<x-adminlte-card title="[FR] Responsable du traitement" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-building text-white" collapsible="collapsed">

    <p>
        L'ERP <strong>WebErpMesv2</strong> est un logiciel déployé en instance isolée pour chaque entreprise cliente.
        Le <strong>responsable du traitement</strong> des données est l'entreprise qui exploite cette instance,
        et non l'éditeur du logiciel ({{ config('branding.publisher_name') }}).
    </p>
    <p>
        Pour toute question relative à vos données personnelles, contactez l'administrateur de votre instance
        ou adressez un email à <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[FR] Données personnelles collectées" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-database text-white" collapsible="collapsed">

    <p>L'application collecte et traite les catégories de données suivantes :</p>

    <h5 class="mt-3"><i class="fas fa-users mr-1"></i> Utilisateurs internes (employés)</h5>
    <table class="table table-sm table-bordered">
        <thead class="thead-light">
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
            <tr><td>Dates d'embauche / fin de contrat</td><td>Gestion RH</td><td>Non</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-address-book mr-1"></i> Contacts B2B (clients / fournisseurs)</h5>
    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr><th>Donnée</th><th>Finalité</th><th>Chiffrement</th></tr>
        </thead>
        <tbody>
            <tr><td>Prénom, nom</td><td>Gestion commerciale</td><td>Non</td></tr>
            <tr><td>Adresse email professionnelle</td><td>Communication, portail client</td><td>Non</td></tr>
            <tr><td>Téléphone professionnel / mobile</td><td>Communication commerciale</td><td>Non</td></tr>
            <tr><td>Fonction / poste</td><td>Gestion commerciale</td><td>Non</td></tr>
            <tr><td>Adresse postale de l'entreprise</td><td>Facturation, livraison</td><td>Non</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-file-invoice mr-1"></i> Données contractuelles et comptables</h5>
    <p>
        Les devis, commandes, factures et bons de livraison associés à un contact sont conservés pour
        satisfaire aux obligations légales comptables (voir durées ci-dessous). Ces données ne peuvent
        pas être supprimées ; elles font l'objet d'une <strong>anonymisation</strong> sur demande
        d'exercice du droit à l'effacement.
    </p>

    <h5 class="mt-3"><i class="fas fa-envelope mr-1"></i> Journaux de communication</h5>
    <p>
        Les emails envoyés depuis l'application (confirmations, devis, factures PDF) sont tracés dans
        un journal interne (destinataire, objet, corps du message). Ces journaux sont automatiquement
        purgés après <strong>12 mois</strong>.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[FR] Durées de conservation" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-clock text-white" collapsible="collapsed">

    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr><th>Type de données</th><th>Durée</th><th>Base légale</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Pièces comptables (factures, commandes, BL)</td>
                <td><strong>10 ans</strong></td>
                <td>Code de commerce, art. L.123-22</td>
            </tr>
            <tr>
                <td>Données RH (contrats, salaires, absences)</td>
                <td><strong>5 ans</strong> après fin de contrat</td>
                <td>Code du travail</td>
            </tr>
            <tr>
                <td>Contacts clients actifs</td>
                <td>Durée de la relation commerciale</td>
                <td>Exécution du contrat</td>
            </tr>
            <tr>
                <td>Contacts prospects (sans commande)</td>
                <td><strong>3 ans</strong> après dernier contact</td>
                <td>Recommandation CNIL — prospection B2B</td>
            </tr>
            <tr>
                <td>Journaux d'activité (audit trail)</td>
                <td><strong>12 mois</strong></td>
                <td>Recommandation ANSSI</td>
            </tr>
            <tr>
                <td>Journaux d'emails sortants</td>
                <td><strong>12 mois</strong></td>
                <td>Proportionnalité</td>
            </tr>
            <tr>
                <td>Tokens de réinitialisation de mot de passe</td>
                <td><strong>1 heure</strong></td>
                <td>Sécurité</td>
            </tr>
            <tr>
                <td>Enregistrements supprimés (corbeille)</td>
                <td><strong>90 jours</strong>, puis effacement définitif</td>
                <td>Fenêtre de restauration d'urgence</td>
            </tr>
        </tbody>
    </table>

    <div class="callout callout-info mt-2">
        <p>
            <i class="fas fa-info-circle mr-1"></i>
            La purge automatique des données expirées est exécutée chaque semaine par une tâche planifiée.
        </p>
    </div>

</x-adminlte-card>

<x-adminlte-card title="[FR] Vos droits RGPD" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-balance-scale text-white" collapsible="collapsed">

    <div class="row">
        <div class="col-md-6">
            <h5><i class="fas fa-eye mr-1 text-primary"></i> Droit d'accès (Art. 15)</h5>
            <p>
                Vous pouvez demander une copie de l'ensemble des données vous concernant.
                L'export est fourni au format JSON dans un délai de <strong>30 jours</strong>.
            </p>

            <h5><i class="fas fa-pen mr-1 text-warning"></i> Droit de rectification (Art. 16)</h5>
            <p>
                Vous pouvez corriger toute donnée inexacte directement dans l'application
                (profil utilisateur, fiche contact) ou en contactant l'administrateur.
            </p>

            <h5><i class="fas fa-trash mr-1 text-danger"></i> Droit à l'effacement (Art. 17)</h5>
            <p>
                Lorsqu'aucune obligation légale de conservation ne s'applique, vos données
                sont supprimées. Dans le cas contraire (pièces comptables sur 10 ans),
                elles sont <strong>anonymisées</strong> : les données identifiantes sont effacées,
                les pièces comptables sont conservées sans lien nominal.
            </p>
        </div>
        <div class="col-md-6">
            <h5><i class="fas fa-pause-circle mr-1 text-secondary"></i> Droit à la limitation (Art. 18)</h5>
            <p>
                Vous pouvez demander la suspension du traitement de vos données pendant la durée
                d'examen d'une contestation.
            </p>

            <h5><i class="fas fa-file-export mr-1 text-success"></i> Droit à la portabilité (Art. 20)</h5>
            <p>
                Vous pouvez obtenir vos données dans un format structuré et lisible par machine (JSON).
                Contactez l'administrateur de votre instance pour en faire la demande.
            </p>

            <h5><i class="fas fa-hand-paper mr-1 text-info"></i> Droit d'opposition (Art. 21)</h5>
            <p>
                Vous pouvez vous opposer au traitement de vos données à des fins de prospection commerciale
                à tout moment.
            </p>
        </div>
    </div>

    <div class="callout callout-warning mt-2">
        <p>
            <i class="fas fa-envelope mr-1"></i>
            Pour exercer vos droits, contactez l'administrateur de l'instance ou écrivez à
            <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
            Délai de réponse : <strong>30 jours maximum</strong> (Art. 12 RGPD).
        </p>
    </div>

</x-adminlte-card>

<x-adminlte-card title="[FR] Sécurité des données" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-shield-alt text-white" collapsible="collapsed">

    <h5>Mesures techniques mises en place</h5>
    <ul>
        <li><strong>Chiffrement au repos</strong> — les données sensibles des employés (NSS, adresses, téléphones, date de naissance) sont chiffrées en base de données (AES-256 via Laravel Crypt).</li>
        <li><strong>Mots de passe hashés</strong> — tous les mots de passe sont hashés avec bcrypt (facteur de coût 10+), jamais stockés en clair.</li>
        <li><strong>Isolation par instance</strong> — chaque entreprise cliente dispose de sa propre base de données et de son propre environnement Docker. Il n'y a aucun partage de données entre instances.</li>
        <li><strong>Contrôle d'accès par rôle</strong> — chaque utilisateur accède uniquement aux données nécessaires à sa fonction (RBAC via Spatie Permission).</li>
        <li><strong>Audit trail</strong> — les modifications sur les données sensibles (contacts, utilisateurs, entreprises) sont tracées avec horodatage et identification de l'auteur.</li>
        <li><strong>HTTPS</strong> — les communications sont chiffrées en transit (TLS).</li>
        <li><strong>Tokens temporaires</strong> — les tokens de réinitialisation de mot de passe expirent après 1 heure.</li>
    </ul>

</x-adminlte-card>

<x-adminlte-card title="[FR] Sous-traitants techniques" theme="warning" body-class="bg-white"
    theme-mode="full" icon="fas fa-server text-white" collapsible="collapsed">

    <p>
        Les sous-traitants suivants peuvent être impliqués dans le traitement des données,
        selon la configuration de votre instance. Chaque entreprise cliente est responsable
        de la vérification des DPA (Data Processing Agreements) avec les services qu'elle active.
    </p>

    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr><th>Sous-traitant</th><th>Finalité</th><th>Données transmises</th><th>Localisation</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Serveur SMTP (configurable)</td>
                <td>Envoi d'emails (devis, factures, notifications)</td>
                <td>Nom, email, contenu des documents</td>
                <td>Variable selon configuration</td>
            </tr>
            <tr>
                <td>IONOS <em>(optionnel)</em></td>
                <td>Stockage des fichiers uploadés</td>
                <td>Fichiers joints, pièces jointes</td>
                <td>EU</td>
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
            <tr>
                <td>Service VIES (Commission européenne)</td>
                <td>Validation des numéros de TVA intracommunautaire</td>
                <td>Numéro de TVA (donnée entreprise, non nominale)</td>
                <td>Union européenne</td>
            </tr>
        </tbody>
    </table>

</x-adminlte-card>

<x-adminlte-card title="[FR] Politique de confidentialité complète" body-class="bg-white"
    theme="warning" theme-mode="full" collapsible="collapsed" removable>

    <p>Les informations recueillies dans les formulaires sont enregistrées dans un fichier informatisé par <strong>{{ config('branding.publisher_name') }}</strong>, éditeur du logiciel WebErpMesv2. Le responsable du traitement est l'entreprise qui exploite l'instance.</p>

    <p>Les données collectées le sont sur la base légale suivante selon leur nature :</p>
    <ul>
        <li><strong>Exécution du contrat</strong> — données nécessaires à la gestion commerciale (contacts, commandes, factures)</li>
        <li><strong>Obligation légale</strong> — données comptables conservées 10 ans (Code de commerce)</li>
        <li><strong>Intérêt légitime</strong> — données RH des salariés de l'entreprise exploitante</li>
    </ul>

    <p>Les données ne sont jamais revendues ni transmises à des tiers non listés dans les sous-traitants techniques ci-dessus.</p>

    <p>Elles sont conservées selon les durées définies dans la section « Durées de conservation » de cette page.</p>

    <p>Conformément au RGPD (Règlement UE 2016/679), vous disposez des droits d'accès, de rectification, d'effacement, de portabilité et d'opposition. Pour exercer ces droits, contactez <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.</p>

    <p>Si vous estimez, après nous avoir contactés, que vos droits ne sont pas respectés, vous pouvez adresser une réclamation à la <a href="https://www.cnil.fr" target="_blank" rel="noopener">CNIL</a> (Commission Nationale de l'Informatique et des Libertés).</p>

    <p>Cette politique peut être mise à jour. La date de dernière révision est indiquée en bas de page.</p>
    <p class="text-muted small">Dernière mise à jour : {{ date('d/m/Y') }}</p>

</x-adminlte-card>

{{-- =====================================================================
     ENGLISH SECTION
     ===================================================================== --}}

<x-adminlte-card title="[EN] Data Controller" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-building text-white" collapsible="collapsed">

    <p>
        <strong>WebErpMesv2</strong> is software deployed as an isolated instance for each client company.
        The <strong>data controller</strong> is the company operating this instance, not the software
        publisher ({{ config('branding.publisher_name') }}).
    </p>
    <p>
        For any questions about your personal data, contact your instance administrator or email
        <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[EN] Personal Data Collected" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-database text-white" collapsible="collapsed">

    <h5 class="mt-3"><i class="fas fa-users mr-1"></i> Internal Users (Employees)</h5>
    <table class="table table-sm table-bordered">
        <thead class="thead-light">
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
            <tr><td>Hire / termination dates</td><td>HR management</td><td>No</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-address-book mr-1"></i> B2B Contacts (Customers / Suppliers)</h5>
    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr><th>Data</th><th>Purpose</th><th>Encrypted</th></tr>
        </thead>
        <tbody>
            <tr><td>First name, last name</td><td>Commercial management</td><td>No</td></tr>
            <tr><td>Professional email address</td><td>Communication, customer portal</td><td>No</td></tr>
            <tr><td>Professional / mobile phone</td><td>Commercial communication</td><td>No</td></tr>
            <tr><td>Job title / function</td><td>Commercial management</td><td>No</td></tr>
            <tr><td>Company postal address</td><td>Invoicing, delivery</td><td>No</td></tr>
        </tbody>
    </table>

    <h5 class="mt-3"><i class="fas fa-file-invoice mr-1"></i> Accounting & Contractual Data</h5>
    <p>
        Quotes, orders, invoices and delivery notes linked to a contact are retained to meet legal
        accounting obligations. These cannot be deleted; upon an erasure request, they are
        <strong>anonymised</strong>: identifying data is removed while accounting documents are preserved.
    </p>

</x-adminlte-card>

<x-adminlte-card title="[EN] Retention Periods" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-clock text-white" collapsible="collapsed">

    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr><th>Data Type</th><th>Period</th><th>Legal Basis</th></tr>
        </thead>
        <tbody>
            <tr><td>Accounting documents (invoices, orders)</td><td><strong>10 years</strong></td><td>French Commercial Code, art. L.123-22</td></tr>
            <tr><td>HR data (contracts, salaries, absences)</td><td><strong>5 years</strong> after end of contract</td><td>French Labour Code</td></tr>
            <tr><td>Active client contacts</td><td>Duration of commercial relationship</td><td>Contract performance</td></tr>
            <tr><td>Prospect contacts (no orders)</td><td><strong>3 years</strong> after last contact</td><td>CNIL recommendation — B2B prospecting</td></tr>
            <tr><td>Activity logs (audit trail)</td><td><strong>12 months</strong></td><td>ANSSI recommendation</td></tr>
            <tr><td>Outbound email logs</td><td><strong>12 months</strong></td><td>Proportionality</td></tr>
            <tr><td>Password reset tokens</td><td><strong>1 hour</strong></td><td>Security</td></tr>
            <tr><td>Soft-deleted records</td><td><strong>90 days</strong>, then permanently deleted</td><td>Emergency restore window</td></tr>
        </tbody>
    </table>

</x-adminlte-card>

<x-adminlte-card title="[EN] Your GDPR Rights" theme="primary" body-class="bg-white"
    theme-mode="full" icon="fas fa-balance-scale text-white" collapsible="collapsed">

    <div class="row">
        <div class="col-md-6">
            <h5><i class="fas fa-eye mr-1 text-primary"></i> Right of Access (Art. 15)</h5>
            <p>You may request a copy of all data held about you. The export is provided in JSON format within <strong>30 days</strong>.</p>

            <h5><i class="fas fa-pen mr-1 text-warning"></i> Right to Rectification (Art. 16)</h5>
            <p>You may correct inaccurate data directly in the application (user profile, contact record) or by contacting the administrator.</p>

            <h5><i class="fas fa-trash mr-1 text-danger"></i> Right to Erasure (Art. 17)</h5>
            <p>When no legal retention obligation applies, your data is deleted. Otherwise (accounting documents retained for 10 years), data is <strong>anonymised</strong>: identifying fields are erased while accounting records are preserved without a nominal link.</p>
        </div>
        <div class="col-md-6">
            <h5><i class="fas fa-pause-circle mr-1 text-secondary"></i> Right to Restriction (Art. 18)</h5>
            <p>You may request suspension of processing during the examination of a dispute.</p>

            <h5><i class="fas fa-file-export mr-1 text-success"></i> Right to Portability (Art. 20)</h5>
            <p>You may obtain your data in a structured, machine-readable format (JSON). Contact your instance administrator to submit a request.</p>

            <h5><i class="fas fa-hand-paper mr-1 text-info"></i> Right to Object (Art. 21)</h5>
            <p>You may object to the processing of your data for direct marketing purposes at any time.</p>
        </div>
    </div>

    <div class="callout callout-warning mt-2">
        <p>
            <i class="fas fa-envelope mr-1"></i>
            To exercise your rights, contact your instance administrator or write to
            <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.
            Response time: <strong>maximum 30 days</strong> (Art. 12 GDPR).
        </p>
    </div>

</x-adminlte-card>

<x-adminlte-card title="[EN] Technical Sub-processors" theme="warning" body-class="bg-white"
    theme-mode="full" icon="fas fa-server text-white" collapsible="collapsed">

    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr><th>Sub-processor</th><th>Purpose</th><th>Data transferred</th><th>Location</th></tr>
        </thead>
        <tbody>
            <tr><td>SMTP server (configurable)</td><td>Email delivery (quotes, invoices, notifications)</td><td>Name, email, document content</td><td>Depends on configuration</td></tr>
            <tr><td>IONOS<em>(optional)</em></td><td>File storage</td><td>Uploaded files, attachments</td><td>EU (if eu-west region configured)</td></tr>
            <tr><td>Pusher <em>(optional)</em></td><td>Real-time notifications (WebSocket)</td><td>Session ID (no nominal data)</td><td>EU / USA depending on cluster</td></tr>
            <tr><td>Redis</td><td>Cache, sessions, queues</td><td>User sessions (temporary data)</td><td>Self-hosted (same server)</td></tr>
            <tr><td>LDAP / Active Directory <em>(optional)</em></td><td>Centralised authentication</td><td>Company user accounts</td><td>Company internal infrastructure</td></tr>
            <tr><td>VIES (European Commission)</td><td>EU VAT number validation</td><td>VAT number (company data, not personal)</td><td>European Union</td></tr>
        </tbody>
    </table>

</x-adminlte-card>

<x-adminlte-card title="[EN] Privacy Policy" theme="warning" body-class="bg-white"
    theme-mode="full" collapsible="collapsed" removable>

    <p>Information collected through forms is recorded in a computerised file by <strong>{{ config('branding.publisher_name') }}</strong>, publisher of the WebErpMesv2 software. The data controller is the company operating the instance.</p>

    <p>Data is processed on the following legal bases depending on its nature:</p>
    <ul>
        <li><strong>Contract performance</strong> — data required for commercial management (contacts, orders, invoices)</li>
        <li><strong>Legal obligation</strong> — accounting data retained for 10 years (French Commercial Code)</li>
        <li><strong>Legitimate interest</strong> — HR data of the operating company's employees</li>
    </ul>

    <p>Data is never sold or shared with third parties not listed in the technical sub-processors section above.</p>

    <p>Data is retained according to the periods defined in the Retention Periods section of this page.</p>

    <p>In accordance with the GDPR (EU Regulation 2016/679), you have the right to access, rectify, erase, port and object to your data. To exercise these rights, contact <a href="mailto:{{ config('branding.contact_email') }}">{{ config('branding.contact_email') }}</a>.</p>

    <p>If you believe, after contacting us, that your rights have not been respected, you may lodge a complaint with your national supervisory authority (e.g. <a href="https://www.cnil.fr" target="_blank" rel="noopener">CNIL</a> in France).</p>

    <p class="text-muted small">Last updated: {{ date('d/m/Y') }}</p>

</x-adminlte-card>

@stop

@section('css')
@stop

@section('js')
@stop
