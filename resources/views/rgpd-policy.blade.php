@extends('adminlte::page')

@section('title', 'RGPD')

@section('content_header')
    <h1>RGPD</h1>
@stop

@section('content')
    <div class="float-right"><img src="/vendor/adminlte/dist/img/simple-logo -R.PNG" alt="WEM" class="brand-image  elevation-3  ml-2"></div>

    <x-adminlte-card title="[FR] RGPD" theme="primary" body-class="bg-white" theme-mode="full" icon="fas fa-chart-bar text-white"  collapsible="collapsed" removable >
        <h2>Consentement explicite</h2>
        <p>En utilisant notre site, vous consentez de manière libre, informée et sans équivoque à la collecte et au traitement de vos données personnelles conformément à cette Politique de confidentialité.</p>

        <h2>Droit à l'information</h2>
        <p>Nous vous informons de manière transparente sur la collecte et le traitement de vos données personnelles à travers notre politique de confidentialité accessible facilement.</p>

        <h2>Droit d'accès et de rectification</h2>
        <p>Vous avez le droit d'accéder à vos données personnelles et de les corriger si elles sont inexactes. Vous pouvez le faire en vous connectant à votre compte sur notre plateforme.</p>

        <h2>Limitation de la collecte des données</h2>
        <p>Nous ne collectons que les données personnelles nécessaires à des fins spécifiques. Vous avez la possibilité de choisir les données que vous souhaitez partager avec nous.</p>

        <h2>Durée de conservation</h2>
        <p>Nous conservons vos données personnelles uniquement pendant la période nécessaire à la réalisation des finalités pour lesquelles elles ont été collectées.</p>

        <h2>Sécurité des données</h2>
        <p>Nous avons mis en place des mesures de sécurité appropriées pour protéger vos données personnelles contre la perte, la divulgation non autorisée et l'accès non autorisé.</p>

        <h2>Portabilité des données</h2>
        <p>Dans la mesure du possible, nous vous fournissons un moyen de récupérer vos propres données personnelles. Vous pouvez le faire en contactant notre service client.</p>
    </x-adminlte-card>

    <x-adminlte-card title="[FR]  Politique de confidentialité" body-class="bg-white" theme="warning" theme-mode="full" collapsible="collapsed" removable >
            <p>Les informations recueillies dans les formulaires sont enregistrées dans un fichier informatisé par <a href="//wem-project.org/">SMEWebify</a>.</p>
    
            <p>Les données marquées par un astérisque dans le questionnaire doivent obligatoirement être fournies. Dans le cas contraire, les enregistrement ne sont pas effectués.</p>
    
            <p>Les données collectées seront communiquées aux seuls clients inclue dans la base de données.</p>
    
            <p>Elles sont conservées pendant l'existance de l'application sous contrat.</p>
    
            <p>Vous pouvez accéder aux données vous concernant, les rectifier, demander leur effacement.</p>
    
            <p>Pour exercer ces droits ou pour toute question sur le traitement de vos données dans ce dispositif, vous pouvez nous <a href="mailto:contact@wem-project.org">contacter</a>.</p>
    
            <p>Nous nous engageons à assurer la protection de vos données personnelles et votre vie privée. Nous mettons en place toutes les mesures nécessaires pour garantir la sécurité de vos données.</p>
    
            <p>Cette politique de confidentialité peut être mise à jour périodiquement. Nous vous encourageons à consulter régulièrement cette page pour rester informé(e) de toute modification.</p>

    </x-adminlte-card>

    <x-adminlte-card title="[EN] GDPR" theme="primary" body-class="bg-white" theme-mode="full" collapsible="collapsed" removable >
            <h2>Explicit Consent</h2>
            <p>By using our site, you consent freely, informedly, and unequivocally to the collection and processing of your personal data in accordance with this Privacy Policy.</p>

            <h2>Right to Information</h2>
            <p>We transparently inform you about the collection and processing of your personal data through our easily accessible privacy policy.</p>

            <h2>Access and Rectification Rights</h2>
            <p>You have the right to access your personal data and correct it if inaccurate. You can do this by logging into your account on our platform.</p>

            <h2>Limitation of Data Collection</h2>
            <p>We only collect personal data necessary for specific purposes. You have the option to choose which data you want to share with us.</p>

            <h2>Retention Period</h2>
            <p>We retain your personal data only for the period necessary to achieve the purposes for which it was collected.</p>

            <h2>Data Security</h2>
            <p>We have implemented appropriate security measures to protect your personal data against loss, unauthorized disclosure, and unauthorized access.</p>

            <h2>Data Portability</h2>
            <p>Where possible, we provide you with a means to retrieve your own personal data. You can do this by contacting our customer service.</p>
    </x-adminlte-card>

    <x-adminlte-card title="[EN] Privacy Policy" theme="warning" body-class="bg-white" theme-mode="full" collapsible="collapsed" removable >
            <p>The information collected in the forms is recorded in a computerized file by <a href="//wem-project.org/">SMEWebify</a>.</p>

            <p>Data marked with an asterisk in the questionnaire must be provided. Otherwise, the records are not made.</p>

            <p>The collected data will only be communicated to clients included in the database.</p>

            <p>They are kept for the duration of the application under contract.</p>

            <p>You can access your data, rectify it, or request its deletion.</p>

            <p>To exercise these rights or for any questions about the processing of your data in this system, you can <a href="mailto:contact@wem-project.org">contact us</a>.</p>

            <p>We are committed to ensuring the protection of your personal data and your privacy. We implement all necessary measures to ensure the security of your data.</p>

            <p>This privacy policy may be updated periodically. We encourage you to regularly check this page for any changes.</p>
    </x-adminlte-card>

@stop

@section('css')
@stop

@section('js')
@stop