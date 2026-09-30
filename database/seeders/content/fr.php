<?php

declare(strict_types=1);

/*
 * Contenu initial en français (texte des maquettes approuvées, version desktop) avec les [placeholders].
 * Les textes juridiques sont des modèles : :company, :address, :vat, :email, :phone et :website proviennent des
 * paramètres ; tout ce qui est entre [crochets] doit être complété avant la mise en ligne.
 */
return [
    'pages' => [
        'home' => [
            'slug' => '', 'nav_label' => 'Accueil', 'label' => '', 'title' => 'BM-Matic',
            'meta_title' => 'Réparation de boîtes automatiques à Alost | BM-Matic',
            'meta_description' => 'Diagnostic, réparation et révision de boîtes automatiques, DSG et CVT de toutes marques à Alost. Nous testons d’abord et vous remettons un devis clair.',
        ],
        'services' => [
            'slug' => 'services', 'nav_label' => 'Services', 'label' => 'Services', 'title' => 'Toutes les boîtes automatiques.', 'highlight' => 'Un seul spécialiste.',
            'intro' => 'De la première lecture des défauts à la révision complète : boîtes automatiques, à double embrayage et CVT, de toutes marques. Nous posons toujours un diagnostic avant le devis.',
            'meta_title' => 'Services boîtes automatiques | BM-Matic Alost',
            'meta_description' => 'Diagnostic, révision de boîte, rinçage ATF, convertisseur de couple, mécatronique et réparation DSG/CVT à Alost. Devis clair après diagnostic.',
        ],
        'transmissions' => [
            'slug' => 'transmissions', 'nav_label' => 'Transmissions', 'label' => 'Transmissions', 'title' => 'Les transmissions', 'highlight' => 'que nous réparons.',
            'intro' => 'Boîtes automatiques à convertisseur, boîtes à double embrayage et CVT de tous les grands constructeurs. Votre boîte n’est pas dans la liste ? Appelez-nous : nous la connaissons très probablement.',
            'meta_title' => 'Boîtes automatiques, DSG et CVT que nous réparons | BM-Matic',
            'meta_description' => 'ZF, Aisin, Mercedes 7G/9G-Tronic, VAG DSG, Ford PowerShift, Jatco CVT et plus : les transmissions automatiques que BM-Matic diagnostique et répare.',
        ],
        'about' => [
            'slug' => 'a-propos', 'nav_label' => 'À propos', 'label' => 'À propos', 'title' => 'Les boîtes automatiques,', 'highlight' => 'rien d’autre.',
            'intro' => 'BM-Matic est un atelier spécialisé à Alost dans le diagnostic, la réparation et la révision de boîtes de vitesses automatiques.',
            'body' => '<h2>Un spécialiste, pas un garage généraliste</h2><p>Une boîte automatique est complexe : hydraulique, électronique et mécanique travaillent ensemble dans quelques litres d’huile. C’est notre seul métier, chaque jour, depuis [XX] ans. Cette spécialisation nous permet de trouver la vraie cause d’une panne plutôt que de remplacer des pièces au hasard.</p><h2>Notre méthode</h2><p>Chaque réparation commence par un diagnostic : essai routier, données en temps réel, codes défauts et contrôle de l’huile. Vous recevez nos constats et un devis fixe, et rien ne commence sans votre accord. Après la réparation, nous refaisons un essai routier et accordons [X] mois de garantie.</p><h2>L’atelier</h2><p>[Courte description de l’atelier, de l’équipe et de l’équipement — à compléter par le propriétaire.]</p>',
            'meta_title' => 'À propos de BM-Matic | Spécialiste des boîtes automatiques à Alost',
            'meta_description' => 'Un atelier spécialisé en boîtes automatiques à Alost : diagnostic d’abord, devis clair, réparation avec garantie.',
        ],
        'reviews' => [
            'slug' => 'avis', 'nav_label' => 'Avis', 'label' => 'Avis Google', 'title' => 'Des conducteurs qui', 'highlight' => 'passent à nouveau les vitesses en douceur.',
            'intro' => 'Ce que nos clients disent sur Google.',
            'meta_title' => 'Avis clients | BM-Matic Alost',
            'meta_description' => 'Avis Google de conducteurs dont la boîte automatique, DSG ou CVT a été réparée par BM-Matic à Alost.',
        ],
        'contact' => [
            'slug' => 'contact', 'nav_label' => 'Contact', 'label' => 'Contact', 'title' => 'Votre boîte patine, donne des à-coups', 'highlight' => 'ou passe en mode dégradé ?',
            'intro' => 'Indiquez-nous votre voiture et les symptômes. Nous répondons dans un délai d’un jour ouvrable avec un rendez-vous pour le diagnostic.',
            'meta_title' => 'Contact et rendez-vous | BM-Matic Alost',
            'meta_description' => 'Demandez un rendez-vous pour le diagnostic de votre boîte de vitesses chez BM-Matic à Alost. Réponse en un jour ouvrable.',
        ],
        'privacy' => [
            'slug' => 'politique-de-confidentialite', 'nav_label' => 'Politique de confidentialité', 'label' => 'Mentions légales', 'title' => 'Politique de confidentialité',
            'intro' => 'Dernière mise à jour : [date]',
            'body' => '<p><strong>[MODÈLE — faites vérifier ce texte par un juriste ou votre comptable, complétez chaque [crochet], puis supprimez cette ligne.]</strong></p><h2>Responsable du traitement</h2><p>:company, :address, numéro d’entreprise BE :vat (« nous ») est responsable du traitement des données personnelles traitées via :website. Questions sur la vie privée : :email ou :phone.</p><h2>Ce que nous collectons, pourquoi et sur quelle base légale</h2><ul><li><strong>Demandes de rendez-vous</strong> — nom, numéro de téléphone, adresse e-mail, marque et modèle du véhicule, type de boîte de vitesses et symptômes décrits. Nous les utilisons pour répondre à votre demande, planifier le diagnostic et, si vous le souhaitez, la réparation. Base légale : mesures précontractuelles prises à votre demande (article 6, 1, b) du RGPD).</li><li><strong>Factures et garantie</strong> — lorsqu’une réparation suit, les données nécessaires à la facture et à la garantie. Base légale : le contrat et nos obligations comptables et fiscales belges (article 6, 1, b) et c) du RGPD).</li><li><strong>Protection contre les abus</strong> — une empreinte irréversible (hachage) de votre adresse IP et le type de navigateur pour chaque demande, ainsi qu’un journal de sécurité des connexions à notre espace d’administration. Base légale : notre intérêt légitime à sécuriser le site et vos données (article 6, 1, f) du RGPD).</li><li><strong>Statistiques de visite</strong> — uniquement si vous acceptez les cookies d’analyse : des statistiques anonymes sur l’utilisation du site. Base légale : votre consentement (article 6, 1, a) du RGPD), que vous pouvez retirer à tout moment.</li></ul><p>Nous n’utilisons pas vos données pour des décisions automatisées ou du profilage, et nous ne vous envoyons pas de publicité.</p><h2>Durée de conservation</h2><ul><li>Demandes de rendez-vous sans réparation : [X] mois après le dernier contact.</li><li>Factures et données qui y figurent : aussi longtemps que l’exige le droit comptable et fiscal belge (actuellement dix ans).</li><li>Journal de sécurité : au maximum [X] jours.</li></ul><h2>Destinataires</h2><p>Uniquement les personnes de :company qui traitent votre demande, et les prestataires qui traitent des données pour notre compte dans le cadre d’un contrat de sous-traitance : notre hébergeur [nom, pays] et notre fournisseur d’e-mail [nom, pays]. [Fournisseur d’analyse, s’il est activé, et son pays.] Nous ne vendons jamais vos données. Si un prestataire stocke des données hors de l’Espace économique européen, ce transfert est couvert par les clauses contractuelles types de la Commission européenne ou par une décision d’adéquation.</p><p>Les avis Google affichés sur ce site sont récupérés par notre serveur ; votre visite n’envoie aucune donnée à Google.</p><h2>Vos droits</h2><p>Vous pouvez demander l’accès à vos données, leur rectification ou leur effacement, la limitation du traitement ou vous y opposer, et recevoir vos données dans un format portable. Lorsque le traitement repose sur votre consentement, vous pouvez le retirer à tout moment. Adressez votre demande à :email ; nous répondons dans un délai d’un mois.</p><p>Si notre réponse ne vous satisfait pas, vous pouvez introduire une plainte auprès de l’Autorité de protection des données, rue de la Presse 35, 1000 Bruxelles, www.autoriteprotectiondonnees.be.</p><h2>Sécurité</h2><p>Le site n’est accessible que via une connexion chiffrée, l’accès à l’espace d’administration est protégé par des mots de passe robustes et une authentification à deux facteurs facultative, et les secrets enregistrés sont chiffrés.</p><h2>Modifications</h2><p>Nous pouvons adapter cette politique ; la date en haut indique la dernière version. Elle est régie par le Règlement général sur la protection des données (UE) 2016/679 et la loi belge du 30 juillet 2018 relative à la protection des personnes physiques à l’égard des traitements de données à caractère personnel.</p><h2>Cookies</h2><p>Voir notre politique en matière de cookies.</p>',
            'meta_title' => 'Politique de confidentialité | BM-Matic',
            'meta_description' => 'Comment BM-Matic traite les données personnelles des demandes de rendez-vous et des visites du site.',
        ],
        'cookies' => [
            'slug' => 'politique-cookies', 'nav_label' => 'Politique en matière de cookies', 'label' => 'Mentions légales', 'title' => 'Politique en matière de cookies',
            'intro' => 'Dernière mise à jour : [date]',
            'body' => '<p><strong>[MODÈLE — vérifiez la liste ci-dessous par rapport au site tel qu’il est publié, puis supprimez cette ligne.]</strong></p><p>Les cookies sont de petits fichiers qu’un site enregistre dans votre navigateur. Conformément à la loi belge du 13 juin 2005 relative aux communications électroniques et au RGPD, nous ne plaçons sans votre accord que les cookies strictement nécessaires ; tous les autres requièrent d’abord votre consentement.</p><h2>Cookies strictement nécessaires (toujours actifs)</h2><ul><li><strong>bm_lang</strong> — retient la langue choisie. 1 an.</li><li><strong>bm_consent</strong> — retient votre choix en matière de cookies, pour ne pas vous le redemander à chaque page. 180 jours.</li><li><strong>bm_session</strong> (<strong>__Host-bm_session</strong> en HTTPS) — protège le formulaire de rendez-vous contre les abus. Supprimé à la fermeture du navigateur.</li></ul><h2>Cookies d’analyse (uniquement avec votre consentement)</h2><p>Si vous acceptez l’analyse, nous mesurons les visites avec [fournisseur d’analyse — nom, pays, noms et durées des cookies]. Ces cookies ne sont placés qu’après votre accord et sont supprimés lorsque vous retirez votre consentement.</p><p>Nous n’utilisons pas de cookies publicitaires ni de réseaux sociaux. Les avis Google et les photos des auteurs sont servis depuis notre propre domaine et ne placent aucun cookie.</p><h2>Modifier votre choix</h2><p>Utilisez « Paramètres des cookies » en bas de chaque page pour accepter ou retirer votre consentement à tout moment. Vous pouvez aussi supprimer les cookies dans les réglages de votre navigateur.</p><p>Questions : :email. Notre politique de confidentialité explique comment nous traitons les données personnelles.</p>',
            'meta_title' => 'Politique en matière de cookies | BM-Matic',
            'meta_description' => 'Les cookies utilisés par le site de BM-Matic et comment modifier votre choix.',
        ],
        'terms' => [
            'slug' => 'conditions-generales', 'nav_label' => 'Conditions générales', 'label' => 'Mentions légales', 'title' => 'Conditions générales',
            'intro' => 'Dernière mise à jour : [date]',
            'body' => '<p><strong>[MODÈLE — faites vérifier ces conditions par un juriste, complétez chaque [crochet], puis supprimez cette ligne.]</strong></p><h2>Qui sommes-nous</h2><p>:company, :address, numéro d’entreprise BE :vat, :email, :phone.</p><h2>Champ d’application</h2><p>Ces conditions s’appliquent à chaque diagnostic, devis et réparation que nous réalisons. Lorsqu’elles diffèrent de conditions que vous proposez, les nôtres s’appliquent, sauf accord écrit contraire. Rien dans ces conditions ne limite les droits que la loi belge accorde aux consommateurs.</p><h2>Prix, diagnostic et devis</h2><p>Tous les prix pour les consommateurs s’entendent TVA comprise. Un diagnostic coûte [montant TVAC], [déduit de la réparation si vous nous la confiez]. Un devis est valable [X] jours. Nous ne commençons une réparation qu’après votre accord écrit ou verbal sur le devis ; si des travaux supplémentaires s’avèrent nécessaires, nous vous contactons d’abord et ne les réalisons qu’avec votre accord.</p><h2>Votre véhicule et les pièces remplacées</h2><p>Les pièces remplacées sont à votre disposition sur demande lors de l’enlèvement du véhicule, sauf si elles sont échangées dans le cadre d’un programme d’échange du fabricant. Un véhicule non enlevé dans les [X] jours suivant notre avis qu’il est prêt peut entraîner des frais de gardiennage de [montant] par jour.</p><h2>Paiement</h2><p>Les factures sont payables [à l’enlèvement / dans les X jours]. [Conditions en cas de retard de paiement — pour les consommateurs, rappels, intérêts et frais sont limités par le livre XIX du Code de droit économique.] [Droit de rétention : nous pouvons conserver le véhicule jusqu’au paiement de la facture.]</p><h2>Garantie</h2><p>Les réparations bénéficient d’une garantie commerciale de [X] mois sur les pièces et la main-d’œuvre, à condition que le véhicule soit utilisé normalement et entretenu selon le programme du constructeur. [Exclusions de garantie.] Cette garantie s’ajoute à la garantie légale dont bénéficient les consommateurs sur les pièces fournies (deux ans pour les biens neufs) et ne la limite jamais.</p><h2>Responsabilité</h2><p>[Clause de responsabilité — le droit belge ne permet pas d’exclure la responsabilité pour dol, faute grave, décès ou dommage corporel.]</p><h2>Plaintes et litiges</h2><p>Signalez toute plainte dès que possible à :email ; nous répondons dans les [X] jours ouvrables. Les consommateurs peuvent également s’adresser au Service de Médiation pour le Consommateur (www.mediationconsommateur.be). Le droit belge est applicable. Les litiges avec des entreprises relèvent des tribunaux de [arrondissement judiciaire] ; les consommateurs conservent le droit de saisir le tribunal que la loi leur attribue selon leur domicile.</p>',
            'meta_title' => 'Conditions générales | BM-Matic',
            'meta_description' => 'Conditions générales pour les diagnostics, devis et réparations de BM-Matic.',
        ],
    ],
    'sections' => [
        'topbar' => [],
        'header' => [],
        'hero' => [
            'label' => 'Spécialistes des boîtes automatiques · Alost',
            'title' => 'Réparation de boîtes de vitesses, conçue pour', 'highlight' => 'passer parfaitement.',
            'intro' => 'Diagnostic, réparation et révision de boîtes automatiques, DSG et CVT de toutes marques. Nous testons d’abord, expliquons ce que nous trouvons et vous remettons un devis clair.',
            'extra' => ['cta' => 'Réserver un diagnostic', 'call' => 'Appeler l’atelier', 'rating' => 'sur :count avis Google', 'caption' => 'COUPE A–A · SCHÉMA', 'schematic' => 'Schéma de la transmission', 'legend' => ['01 Convertisseur de couple', '02 Trains épicycloïdaux', '03 Bloc hydraulique', '04 Logiciel TCU']],
        ],
        'stats' => ['label' => 'Chiffres clés'],
        'services' => ['label' => 'Services', 'title' => 'Toutes les boîtes automatiques.', 'highlight' => 'Un seul spécialiste.', 'extra' => ['link' => 'Tous les services']],
        'process' => ['label' => 'Notre méthode', 'title' => 'Le diagnostic d’abord.', 'highlight' => 'Ensuite le devis.'],
        'transmissions' => ['label' => 'LES TRANSMISSIONS QUE NOUS RÉPARONS'],
        'reviews' => ['label' => 'Avis Google', 'title' => 'Des conducteurs qui', 'highlight' => 'passent à nouveau les vitesses en douceur.', 'extra' => ['count' => ':count avis sur Google', 'read_all' => 'Tout lire', 'empty_title' => 'Les avis arrivent bientôt', 'empty_text' => 'Nos avis Google s’afficheront ici prochainement. En attendant, lisez-les sur Google.', 'google' => 'Lire nos avis sur Google']],
        'contact' => ['label' => 'Contact', 'title' => 'Votre boîte patine, donne des à-coups ou passe en mode dégradé ?', 'intro' => 'Indiquez-nous votre voiture et les symptômes. Nous répondons dans un délai d’un jour ouvrable avec un rendez-vous pour le diagnostic.', 'extra' => ['form_title' => 'Demander un rendez-vous', 'submit' => 'Envoyer la demande', 'map' => 'CARTE — ALOST']],
        'footer' => [],
    ],
    'services' => [
        'diagnostics' => [
            'slug' => 'diagnostic', 'title' => 'Diagnostic et lecture des défauts', 'menu_title' => 'Diagnostic', 'menu_sub' => 'Lecture des défauts et essai routier', 'short_title' => 'Diagnostic',
            'summary' => 'Essai routier, données en temps réel et codes défauts lus avant toute ouverture.',
            'body' => '<p>La plupart des plaintes — patinage, passages brutaux ou tardifs, vibrations, voyant allumé ou mode dégradé — peuvent avoir plusieurs causes. Remplacer des pièces au hasard coûte cher. Nous trouvons d’abord la cause.</p><h2>Ce que comprend le diagnostic</h2><ul><li>Un essai routier pour reproduire le problème</li><li>La lecture des codes défauts et des données du calculateur de boîte</li><li>Le contrôle du niveau et de l’état de l’huile</li><li>Les valeurs d’adaptation et la version du logiciel</li></ul><p>Vous recevez une explication claire et un devis fixe pour la réparation. Rien ne commence sans votre accord.</p>',
            'meta_title' => 'Diagnostic de boîte de vitesses | BM-Matic Alost',
            'meta_description' => 'Essai routier, codes défauts, données en temps réel et contrôle de l’huile avant toute réparation. Diagnostic clair et devis fixe.',
        ],
        'overhaul' => [
            'slug' => 'revision-boite-de-vitesses', 'title' => 'Révision de boîte de vitesses', 'menu_title' => 'Révision de boîte', 'menu_sub' => 'Révision complète', 'short_title' => 'Révision de boîte',
            'summary' => 'Révision complète des boîtes automatiques avec pièces d’usure neuves.',
            'body' => '<p>Quand l’usure interne est en cause, une révision complète remet la boîte à niveau — généralement pour une fraction du prix d’une boîte neuve.</p><h2>Ce que nous faisons</h2><ul><li>Dépose, démontage complet et nettoyage</li><li>Embrayages, joints et filtres neufs</li><li>Contrôle et remplacement des pièces mécaniques usées</li><li>Remontage, remplissage, adaptation et essai routier</li></ul><p>Chaque révision est garantie [X] mois.</p>',
            'meta_title' => 'Révision de boîte automatique | BM-Matic Alost',
            'meta_description' => 'Révision complète de boîtes automatiques avec embrayages, joints et filtres neufs, adaptation et essai routier. Garantie [X] mois.',
        ],
        'flush' => [
            'slug' => 'rincage-atf', 'title' => 'Rinçage ATF dynamique', 'menu_title' => 'Rinçage ATF', 'menu_sub' => 'Remplacement dynamique de l’huile', 'short_title' => 'Rinçage ATF',
            'summary' => 'Remplacement complet de l’huile avec la spécification adaptée à votre boîte.',
            'body' => '<p>L’huile de boîte automatique s’use : elle perd ses propriétés de friction et accumule des particules. Une simple vidange n’en remplace qu’une partie. Un rinçage dynamique la remplace presque entièrement, boîte en fonctionnement.</p><h2>Pourquoi c’est important</h2><ul><li>Passages plus doux et moins de vibrations</li><li>Moins d’usure des embrayages et du bloc hydraulique</li><li>La spécification d’huile exacte exigée par le constructeur</li></ul><p>Nous contrôlons d’abord l’état de l’huile et vous conseillons si un rinçage est utile.</p>',
            'meta_title' => 'Rinçage ATF dynamique | BM-Matic Alost',
            'meta_description' => 'Remplacement quasi complet de l’huile de boîte automatique avec la spécification adaptée.',
        ],
        'converter' => [
            'slug' => 'reparation-convertisseur-de-couple', 'title' => 'Réparation du convertisseur de couple', 'menu_title' => 'Convertisseur de couple', 'menu_sub' => 'Vibrations et patinage', 'short_title' => 'Convertisseur',
            'summary' => 'Vibrations, patinage ou surchauffe diagnostiqués et réparés.',
            'body' => '<p>Un embrayage de pontage usé provoque des vibrations à vitesse constante, un régime moteur plus élevé et une huile qui surchauffe. Nous confirmons la cause avec les données en temps réel avant toute intervention.</p><h2>Réparation</h2><ul><li>Dépose et contrôle du convertisseur</li><li>Remplacement ou révision de l’embrayage de pontage et des joints</li><li>Remplacement de l’huile et adaptation</li></ul>',
            'meta_title' => 'Réparation du convertisseur de couple | BM-Matic Alost',
            'meta_description' => 'Vibrations, patinage ou surchauffe liés au convertisseur de couple, diagnostiqués et réparés.',
        ],
        'mechatronic' => [
            'slug' => 'mecatronique-bloc-hydraulique', 'title' => 'Mécatronique et bloc hydraulique', 'menu_title' => 'Mécatronique', 'menu_sub' => 'Électrovannes et calculateurs', 'short_title' => 'Mécatronique',
            'summary' => 'Électrovannes, régulation de pression et calculateurs testés et réparés.',
            'body' => '<p>Le bloc hydraulique et la mécatronique pilotent chaque passage : électrovannes, régulateurs de pression et capteurs. Une panne provoque des passages brutaux, le mode dégradé et des codes défauts.</p><h2>Réparation</h2><ul><li>Tests des électrovannes et des pressions</li><li>Nettoyage et révision du bloc hydraulique</li><li>Réparation ou remplacement de la mécatronique, avec programmation et adaptation</li></ul>',
            'meta_title' => 'Réparation mécatronique et bloc hydraulique | BM-Matic Alost',
            'meta_description' => 'Électrovannes, régulation de pression et calculateurs de boîte testés, réparés et adaptés.',
        ],
        'dsg' => [
            'slug' => 'dsg-dct-cvt', 'title' => 'DSG, DCT et CVT', 'menu_title' => 'DSG, DCT et CVT', 'menu_sub' => 'Double embrayage et CVT', 'short_title' => 'DSG et CVT',
            'summary' => 'Boîtes à double embrayage et à variation continue, embrayages compris.',
            'body' => '<p>Les boîtes à double embrayage (DSG, DCT, PowerShift) et les CVT demandent une expertise propre. Nous réparons les doubles embrayages à sec et à bain d’huile ainsi que les courroies, poulies et blocs hydrauliques CVT.</p><h2>Interventions courantes</h2><ul><li>Remplacement des embrayages et réglages de base</li><li>Réparation de la mécatronique DSG DQ200, DQ250 et DQ381</li><li>Diagnostic, entretien de l’huile et réparation CVT</li></ul>',
            'meta_title' => 'Réparation DSG, DCT et CVT | BM-Matic Alost',
            'meta_description' => 'Boîtes à double embrayage (DSG, DCT, PowerShift) et CVT diagnostiquées et réparées, embrayages compris.',
        ],
    ],
    'transmission_types' => [
        ['label' => 'ZF 6HP / 8HP', 'description' => 'BMW, Audi, Jaguar, Land Rover et d’autres.'],
        ['label' => 'Aisin', 'description' => 'Volvo, Toyota, Peugeot, Citroën, Mini et d’autres.'],
        ['label' => 'Mercedes 7G / 9G-Tronic', 'description' => 'Voitures et utilitaires Mercedes-Benz.'],
        ['label' => 'VAG DSG', 'description' => 'Boîtes à double embrayage Volkswagen, Audi, Škoda et Seat.'],
        ['label' => 'Ford PowerShift', 'description' => 'Boîtes à double embrayage Ford.'],
        ['label' => 'Jatco CVT', 'description' => 'CVT Nissan, Renault, Mitsubishi et Suzuki.'],
        ['label' => 'BMW Steptronic', 'description' => 'Boîtes automatiques BMW.'],
        ['label' => 'Volvo Geartronic', 'description' => 'Boîtes automatiques Volvo.'],
    ],
    'process_steps' => [
        ['title' => 'Réservez en ligne', 'text' => 'Choisissez un créneau ou appelez-nous. Indiquez la voiture et les symptômes.'],
        ['title' => 'Test et lecture', 'text' => 'Essai routier, codes défauts et contrôle de l’huile — avant toute réparation.'],
        ['title' => 'Devis clair', 'text' => 'Vous recevez le diagnostic et un devis fixe. Rien ne commence sans votre accord.'],
        ['title' => 'Réparation et garantie', 'text' => 'Nous réparons, refaisons un essai routier et accordons [X] mois de garantie.'],
    ],
    'stats' => [
        ['value' => '[XX]+', 'label' => 'Années d’expérience'],
        ['value' => '[X.XXX]+', 'label' => 'Boîtes réparées'],
        ['value' => '[X] mois', 'label' => 'Garantie sur les réparations'],
        ['value' => 'Toutes marques', 'label' => 'Automatique, DSG et CVT'],
    ],
];
