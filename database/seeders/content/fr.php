<?php

declare(strict_types=1);

/*
 * Contenu initial en français. Les adresses (slugs) reprennent celles de l’anglais et peuvent être modifiées par page
 * dans l’administration. Les parties entre [crochets] sont à compléter ou à confirmer par GATE Lebanon.
 */
return [
    'pages' => [
        'home' => [
            'slug' => '', 'nav_label' => 'Accueil', 'title' => 'Renforcer les communautés au Liban',
            'meta_title' => 'GATE Lebanon — Aide humanitaire, développement et paix',
            'meta_description' => 'GATE Lebanon est une ONG libanaise indépendante qui agit depuis 2014 dans l’éducation, la protection, la cohésion sociale et les moyens de subsistance auprès des communautés libanaises et déplacées les plus vulnérables.',
        ],
        'about' => [
            'slug' => 'about-us', 'nav_label' => 'Qui sommes-nous', 'label' => 'À propos de GATE Lebanon',
            'title' => 'Une organisation locale, ancrée dans les communautés que nous servons',
            'intro' => 'GATE Lebanon, anciennement RET Liban, est une ONG libanaise indépendante, neutre, non confessionnelle et apolitique, engagée pour l’aide humanitaire, le développement et la paix.',
            'body' => '<p>Depuis 2014, GATE Lebanon répond aux besoins les plus urgents en matière d’<strong>éducation, de protection, de cohésion sociale et de moyens de subsistance</strong>. Nous soutenons les populations déplacées et les communautés d’accueil vulnérables au Liban, avec une attention particulière aux jeunes et aux femmes.</p>'
                . '<p>Nous nous engageons à protéger les communautés en renforçant la résilience des personnes touchées par les déplacements, la violence, les conflits armés et les catastrophes. Nous travaillons avec les municipalités, les associations locales et des partenaires internationaux ; la plupart de nos programmes se déroulent aujourd’hui dans l’Akkar, au Liban-Nord.</p>',
            'meta_title' => 'Qui sommes-nous | GATE Lebanon',
            'meta_description' => 'Découvrez GATE Lebanon : ONG libanaise indépendante pour l’aide humanitaire, le développement et la paix, anciennement RET Liban, active depuis 2014.',
        ],
        'who' => [
            'slug' => 'who-we-are', 'nav_label' => 'Notre organisation', 'label' => 'Qui sommes-nous', 'title' => 'Notre organisation',
            'intro' => 'Une ONG libanaise enregistrée, anciennement RET Liban, aux côtés des communautés vulnérables depuis 2014.',
            'body' => '<p>GATE Lebanon est une organisation non gouvernementale libanaise locale et enregistrée. Elle était auparavant connue sous le nom de <strong>RET Liban</strong> et continue de travailler étroitement avec RET Germany en tant que partenaire de mise en œuvre.</p>'
                . '<h2>Indépendante et neutre</h2><p>Nous sommes indépendants, neutres, non confessionnels et apolitiques. Nous servons les personnes selon leurs seuls besoins, quelles que soient leur nationalité, leur origine ou leurs convictions.</p>'
                . '<h2>Proches du terrain</h2><p>Notre équipe travaille directement avec les communautés, ainsi qu’avec les municipalités, les écoles, les groupes scouts, les académies sportives et les associations locales qui font partie de leur quotidien.</p>'
                . '<h2>Notre équipe</h2><p>[Présentation de l’équipe et du conseil d’administration de GATE Lebanon, à fournir par GATE Lebanon.]</p>',
            'meta_title' => 'Notre organisation | GATE Lebanon',
            'meta_description' => 'GATE Lebanon, anciennement RET Liban, est une ONG libanaise indépendante, neutre, non confessionnelle et apolitique.',
        ],
        'mission' => [
            'slug' => 'mission-and-vision', 'nav_label' => 'Mission et vision', 'label' => 'Qui sommes-nous', 'title' => 'Mission et vision',
            'intro' => 'Protéger les communautés en renforçant la résilience des personnes les plus vulnérables au Liban.',
            'body' => '<h2>Notre mission</h2><p>GATE Lebanon s’engage à protéger les communautés en assurant la résilience des personnes vulnérables touchées par les déplacements, la violence, les conflits armés et les catastrophes. Nous répondons aux lacunes les plus importantes en matière d’éducation, de protection, de cohésion sociale et de moyens de subsistance, en ciblant les membres les plus vulnérables des communautés, avec une attention particulière aux jeunes et aux femmes.</p>'
                . '<h2>Notre vision</h2><p>[La vision officielle de GATE Lebanon, à fournir.]</p>'
                . '<h2>Nos valeurs</h2><ul><li><strong>Humanité</strong> : la dignité de chaque personne passe en premier.</li><li><strong>Neutralité et indépendance</strong> : nous ne prenons pas parti et agissons selon les besoins.</li><li><strong>L’éducation comme protection</strong> : un apprentissage fondé sur les droits humains protège les enfants et les jeunes.</li><li><strong>Partenariat</strong> : un changement durable se construit avec les acteurs locaux.</li></ul>'
                . '<h2>Notre mandat</h2><ul><li>Diffuser la culture et l’éducation auprès de toutes les catégories sociales.</li><li>Assurer la protection par une éducation fondée sur les droits humains.</li><li>Contribuer à la création de centres éducatifs et de formation et d’ateliers d’artisanat.</li><li>Lutter contre l’analphabétisme et les difficultés scolaires.</li></ul>',
            'meta_title' => 'Mission et vision | GATE Lebanon',
            'meta_description' => 'La mission, les valeurs et le mandat de GATE Lebanon : protection, éducation, cohésion sociale et moyens de subsistance pour les communautés vulnérables.',
        ],
        'profile' => [
            'slug' => 'organisational-profile', 'nav_label' => 'Profil institutionnel', 'label' => 'Qui sommes-nous', 'title' => 'Profil institutionnel',
            'intro' => 'L’essentiel sur GATE Lebanon pour les partenaires et les bailleurs.',
            'body' => '<h2>En bref</h2><ul><li><strong>Nom :</strong> GATE Lebanon (anciennement RET Liban)</li><li><strong>Statut :</strong> ONG libanaise enregistrée — n° d’enregistrement :registration</li><li><strong>Active depuis :</strong> 2014</li><li><strong>Siège :</strong> :address</li><li><strong>Principale zone d’intervention :</strong> Akkar, Liban-Nord</li><li><strong>Secteurs :</strong> éducation, protection, cohésion sociale, moyens de subsistance, réponse d’urgence, gouvernance locale</li><li><strong>Partenaire principal :</strong> RET Germany, avec un financement du ministère fédéral allemand de la Coopération économique et du Développement (BMZ)</li></ul>'
                . '<h2>Notre façon de travailler</h2><p>Nous concevons et mettons en œuvre nos projets avec les municipalités, les associations locales, les écoles, les groupes scouts et les académies sportives, afin que l’aide passe par les structures en qui les habitants ont confiance.</p>'
                . '<h2>Documents</h2><p>[Le profil institutionnel, le certificat d’enregistrement et les rapports annuels peuvent être publiés dans la rubrique Publications.]</p>'
                . '<p>Pour toute proposition de partenariat, écrivez-nous à <a href="mailto::email">:email</a>.</p>',
            'meta_title' => 'Profil institutionnel | GATE Lebanon',
            'meta_description' => 'L’essentiel sur GATE Lebanon : statut, zones d’intervention, secteurs et partenaires.',
        ],
        'expertise' => [
            'slug' => 'our-expertise', 'nav_label' => 'Nos domaines', 'label' => 'Nos domaines d’expertise', 'title' => 'Là où nous faisons la différence',
            'intro' => 'Six domaines d’action qui répondent aux besoins des communautés vulnérables à travers le Liban.',
            'meta_title' => 'Nos domaines d’expertise | GATE Lebanon',
            'meta_description' => 'Éducation, protection, cohésion sociale, moyens de subsistance, réponse d’urgence et gouvernance locale : les domaines d’action de GATE Lebanon.',
        ],
        'projects' => [
            'slug' => 'projects', 'nav_label' => 'Projets', 'label' => 'Projets et programmes', 'title' => 'Nos projets',
            'intro' => 'Les projets en cours et terminés de GATE Lebanon, pour la plupart dans l’Akkar, au Liban-Nord.',
            'meta_title' => 'Projets | GATE Lebanon',
            'meta_description' => 'Les projets et programmes de GATE Lebanon dans l’éducation, la protection, la cohésion sociale et les moyens de subsistance.',
        ],
        'news' => [
            'slug' => 'news', 'nav_label' => 'Actualités', 'label' => 'Actualités', 'title' => 'Nouvelles du terrain',
            'intro' => 'Les dernières nouvelles de nos activités, de nos partenaires et des communautés avec lesquelles nous travaillons.',
            'meta_title' => 'Actualités | GATE Lebanon',
            'meta_description' => 'Les dernières actualités de GATE Lebanon.',
        ],
        'publications' => [
            'slug' => 'publications', 'nav_label' => 'Publications', 'label' => 'Publications', 'title' => 'Rapports et publications',
            'intro' => 'Rapports annuels, études et autres documents de GATE Lebanon à lire ou à télécharger.',
            'meta_title' => 'Publications | GATE Lebanon',
            'meta_description' => 'Rapports, études et documents publiés par GATE Lebanon.',
        ],
        'gallery' => [
            'slug' => 'gallery', 'nav_label' => 'Galerie', 'label' => 'Galerie', 'title' => 'Galerie photo',
            'intro' => 'Des moments de nos activités avec les communautés à travers le Liban.',
            'meta_title' => 'Galerie photo | GATE Lebanon',
            'meta_description' => 'Photos des activités de GATE Lebanon.',
        ],
        'partners' => [
            'slug' => 'partners-and-donors', 'nav_label' => 'Partenaires et bailleurs', 'label' => 'Partenaires et bailleurs', 'title' => 'Agir ensemble',
            'intro' => 'Notre action est possible grâce aux partenaires et bailleurs qui partagent notre engagement envers les communautés vulnérables du Liban.',
            'body' => '<p>Nous construisons des partenariats durables avec des organisations internationales, des institutions publiques et des acteurs locaux. Vous souhaitez soutenir notre action ou devenir partenaire ? <a href="mailto::email">Contactez-nous</a>.</p>',
            'meta_title' => 'Partenaires et bailleurs | GATE Lebanon',
            'meta_description' => 'Les partenaires et bailleurs de GATE Lebanon.',
        ],
        'contact' => [
            'slug' => 'contact', 'nav_label' => 'Contact', 'label' => 'Contact', 'title' => 'Nous contacter',
            'intro' => 'Questions sur notre action, propositions de partenariat ou demandes des médias : nous serons ravis de vous lire.',
            'meta_title' => 'Contact | GATE Lebanon',
            'meta_description' => 'Contactez GATE Lebanon à Achrafieh, Beyrouth : adresse, téléphone, e-mail et formulaire de contact.',
        ],
        'privacy' => [
            'slug' => 'privacy-policy', 'nav_label' => 'Confidentialité', 'title' => 'Politique de confidentialité',
            'body' => '<p>Cette politique explique comment :organisation (« nous ») traite les données personnelles sur ce site, conformément à la loi libanaise n° 81 de 2018 sur les transactions électroniques et les données à caractère personnel.</p>'
                . '<h2>Les données que nous recueillons</h2><ul><li><strong>Formulaire de contact :</strong> votre nom, votre adresse e-mail et votre message, et éventuellement votre numéro de téléphone et votre organisation.</li><li><strong>Lettre d’information :</strong> votre adresse e-mail, la langue choisie et la date de confirmation de votre abonnement.</li><li><strong>Données techniques :</strong> pour protéger les formulaires contre les abus, nous conservons un code à sens unique de votre adresse IP, jamais l’adresse elle-même.</li></ul>'
                . '<h2>Pourquoi nous les utilisons</h2><p>Nous utilisons vos données uniquement pour répondre à votre message ou vous envoyer la lettre d’information demandée. Nous ne vendons ni ne partageons vos données, sauf avec les prestataires qui hébergent ce site et envoient ses e-mails.</p>'
                . '<h2>Durée de conservation</h2><p>Les messages sont conservés le temps nécessaire à leur suivi, puis supprimés ou archivés. Les adresses de la lettre d’information sont conservées jusqu’à votre désabonnement, possible via le lien présent dans chaque e-mail.</p>'
                . '<h2>Vos droits</h2><p>Vous pouvez à tout moment demander à consulter, corriger ou supprimer vos données en écrivant à <a href="mailto::email">:email</a>.</p>'
                . '<h2>Contact</h2><p>:organisation, :address.</p>',
            'meta_title' => 'Politique de confidentialité | GATE Lebanon',
            'meta_description' => 'Comment GATE Lebanon traite les données personnelles du formulaire de contact, de la lettre d’information et des visites du site.',
        ],
        'cookies' => [
            'slug' => 'cookie-policy', 'nav_label' => 'Cookies', 'title' => 'Politique en matière de cookies',
            'body' => '<p>Ce site utilise un petit nombre de cookies.</p>'
                . '<h2>Cookies nécessaires</h2><ul><li><strong>gate_session</strong> : sécurise les formulaires (protection contre les envois falsifiés). Supprimé à la fermeture du navigateur.</li><li><strong>gate_lang</strong> : mémorise la langue choisie pendant un an.</li><li><strong>gate_consent</strong> : mémorise votre choix concernant les cookies pendant six mois.</li></ul>'
                . '<h2>Cookies de mesure d’audience</h2><p>Uniquement si vous les acceptez, nous utilisons des cookies de mesure d’audience pour compter les visites et savoir quelles pages sont utiles. Vous pouvez modifier votre choix à tout moment via le lien « Paramètres des cookies » en bas de chaque page.</p>',
            'meta_title' => 'Politique en matière de cookies | GATE Lebanon',
            'meta_description' => 'Les cookies utilisés par le site de GATE Lebanon et comment modifier votre choix.',
        ],
        'terms' => [
            'slug' => 'terms-of-use', 'nav_label' => 'Conditions d’utilisation', 'title' => 'Conditions d’utilisation',
            'body' => '<p>Ce site est publié par :organisation, :address.</p>'
                . '<h2>Contenu</h2><p>Nous veillons à publier des informations exactes, mais elles peuvent évoluer. Les textes, photos et publications de ce site appartiennent à GATE Lebanon ou à ses partenaires. Vous pouvez les partager à des fins non commerciales en citant la source.</p>'
                . '<h2>Photos</h2><p>Les personnes figurant sur nos photos ont donné leur accord. Si vous apparaissez sur une photo et souhaitez qu’elle soit retirée, écrivez à <a href="mailto::email">:email</a>.</p>'
                . '<h2>Liens</h2><p>Nous ne sommes pas responsables du contenu des sites externes vers lesquels nous renvoyons.</p>',
            'meta_title' => 'Conditions d’utilisation | GATE Lebanon',
            'meta_description' => 'Conditions d’utilisation du site de GATE Lebanon.',
        ],
    ],

    'sections' => [
        'hero' => [
            'label' => 'Aide humanitaire, développement et paix',
            'title' => 'Renforcer les communautés au Liban',
            'intro' => 'Depuis 2014, nous agissons aux côtés des communautés libanaises et déplacées les plus vulnérables dans l’éducation, la protection, la cohésion sociale et les moyens de subsistance, avec une attention particulière aux jeunes et aux femmes.',
        ],
        'about' => [
            'label' => 'À propos de GATE Lebanon',
            'title' => 'Une organisation locale, ancrée dans les communautés que nous servons',
            'intro' => 'GATE Lebanon, anciennement RET Liban, est une ONG libanaise indépendante, neutre, non confessionnelle et apolitique. Nous répondons aux besoins les plus urgents des personnes touchées par les déplacements, la violence et les crises.',
            'extra' => [
                'badge_value' => '2014',
                'badge_label' => 'Au Liban depuis',
                'points' => [
                    ['icon' => 'target', 'title' => 'Neutres et indépendants', 'text' => 'Nous agissons selon les seuls besoins des personnes.'],
                    ['icon' => 'users', 'title' => 'Les jeunes et les femmes d’abord', 'text' => 'Des programmes conçus avec les personnes les plus exposées.'],
                    ['icon' => 'hands', 'title' => 'Fondés sur le partenariat', 'text' => 'Avec les municipalités, les associations locales et des partenaires internationaux.'],
                ],
            ],
        ],
        'expertise' => [
            'label' => 'Nos domaines d’expertise',
            'title' => 'Là où nous faisons la différence',
            'intro' => 'Six domaines d’action qui répondent aux besoins des communautés vulnérables à travers le Liban.',
        ],
        'stats' => ['label' => 'Notre impact'],
        'projects' => ['label' => 'Projets et programmes', 'title' => 'Nos actions récentes sur le terrain'],
        'map' => [
            'label' => 'Où nous agissons',
            'title' => 'Présents là où les besoins sont les plus grands',
            'intro' => 'Nos programmes se concentrent dans l’Akkar, au Liban-Nord, avec des activités dans d’autres gouvernorats selon les besoins et les partenariats.',
            'extra' => [
                'main' => 'akkar',
                'notes' => ['akkar' => 'Principale zone d’intervention', 'beirut' => 'Siège'],
            ],
        ],
        'news' => ['label' => 'Actualités', 'title' => 'Les dernières nouvelles de GATE Lebanon'],
        'partners' => ['title' => 'Nos partenaires et bailleurs'],
        'cta' => [
            'title' => 'Construisons ensemble des communautés résilientes',
            'intro' => 'Devenez partenaire de GATE Lebanon pour des programmes d’éducation, de protection et de moyens de subsistance, ou contactez-nous pour en savoir plus sur notre action.',
        ],
    ],

    'expertise' => [
        'education' => [
            'slug' => 'education', 'title' => 'Éducation',
            'summary' => 'Soutien scolaire, alphabétisation et espaces sûrs pour que les enfants et les jeunes restent scolarisés.',
            'body' => '<p>L’éducation est au cœur du mandat de GATE Lebanon. Nous aidons les enfants et les jeunes à poursuivre leur apprentissage, luttons contre l’analphabétisme et soutenons la création de centres éducatifs et de formation.</p><ul><li>Soutien scolaire et aide aux devoirs</li><li>Cours d’alphabétisation et de calcul</li><li>Espaces d’apprentissage sûrs et accueillants</li><li>Centres de formation et ateliers d’artisanat</li></ul>',
            'meta_description' => 'Les programmes d’éducation de GATE Lebanon : soutien scolaire, alphabétisation et centres de formation.',
        ],
        'protection' => [
            'slug' => 'protection', 'title' => 'Protection',
            'summary' => 'Une protection fondée sur les droits humains et une sensibilisation au harcèlement, à la sécurité numérique et à l’égalité des genres.',
            'body' => '<p>Nous protégeons les enfants, les jeunes et les femmes par une éducation fondée sur les droits humains et en les sensibilisant aux risques auxquels ils sont exposés.</p><ul><li>Séances de sensibilisation au harcèlement, au cyberharcèlement et au chantage en ligne</li><li>Sécurité numérique pour les jeunes</li><li>Séances sur l’égalité des genres et la protection avec les chefs scouts</li><li>Orientation des personnes à risque vers des services spécialisés</li></ul>',
            'meta_description' => 'L’action de GATE Lebanon en matière de protection : sensibilisation au harcèlement, sécurité numérique et égalité des genres.',
        ],
        'social-cohesion' => [
            'slug' => 'social-cohesion', 'title' => 'Cohésion sociale',
            'summary' => 'Rapprocher les communautés libanaises et déplacées grâce au sport, au scoutisme et à la culture.',
            'body' => '<p>Le sport, le scoutisme et les activités culturelles réunissent des enfants et des jeunes des communautés libanaises et déplacées et renforcent la confiance entre eux.</p><p>Dans l’Akkar, nous soutenons des académies sportives et des groupes scouts qui touchent des milliers d’enfants et de jeunes.</p>',
            'meta_description' => 'L’action de GATE Lebanon pour la cohésion sociale par le sport, le scoutisme et la culture.',
        ],
        'livelihoods' => [
            'slug' => 'livelihoods', 'title' => 'Moyens de subsistance',
            'summary' => 'Des formations professionnelles et artisanales qui ouvrent des perspectives de revenus, en particulier pour les femmes.',
            'body' => '<p>Les compétences ouvrent des portes. Nos formations professionnelles et artisanales aident les femmes et les jeunes à acquérir des savoir-faire qui mènent à un revenu et à l’autonomie.</p><p>Parmi les formations récentes : l’art de la mosaïque pour des femmes de l’Akkar.</p>',
            'meta_description' => 'Les programmes de moyens de subsistance de GATE Lebanon : formation professionnelle et artisanale.',
        ],
        'emergency' => [
            'slug' => 'emergency-response', 'title' => 'Réponse d’urgence',
            'summary' => 'Renforcer les acteurs locaux de la réponse, comme les centres de la Défense civile, pour protéger les communautés.',
            'body' => '<p>Lorsqu’une crise survient, les acteurs locaux sont les premiers à intervenir. Nous renforçons leurs capacités, par exemple en équipant les centres de la Défense civile de l’Akkar de matériel de lutte contre l’incendie.</p>',
            'meta_description' => 'L’action de GATE Lebanon en matière de réponse d’urgence avec des acteurs locaux comme la Défense civile.',
        ],
        'governance' => [
            'slug' => 'local-governance', 'title' => 'Gouvernance locale',
            'summary' => 'Accompagner les municipalités et les associations locales dans la planification et la conduite du développement communautaire.',
            'body' => '<p>Des institutions locales solides rendent les communautés plus résilientes. Nous travaillons avec les élus municipaux ainsi que les ONG et associations locales de l’Akkar pour planifier, coordonner et conduire le développement communautaire.</p>',
            'meta_description' => 'L’action de GATE Lebanon en matière de gouvernance locale avec les municipalités et les associations locales.',
        ],
    ],

    'stats' => [
        ['value' => '12+', 'label' => 'Années au Liban'],
        ['value' => '2 600+', 'label' => 'Enfants et jeunes touchés par le sport'],
        ['value' => '24', 'label' => 'Académies sportives équipées dans l’Akkar'],
        ['value' => '46', 'label' => 'Jeunes scouts formés à la protection'],
    ],

    'partners' => [
        'RET Germany' => 'Partenaire international de GATE Lebanon ; ensemble, nous mettons en œuvre des projets dans l’Akkar.',
        'BMZ' => 'Le ministère fédéral allemand de la Coopération économique et du Développement finance plusieurs projets mis en œuvre par RET Germany avec GATE Lebanon.',
        'National Education Scouts' => 'Scouts de l’Éducation nationale : partenaire des séances de sensibilisation et des camps d’été avec les jeunes scouts de l’Akkar.',
        'Lebanese Civil Defense' => 'Défense civile libanaise : partenaire pour le renforcement des capacités de lutte contre l’incendie dans l’Akkar.',
    ],

    'entries' => [
        'civil-defense' => [
            'slug' => 'firefighting-equipment-civil-defense-akkar', 'title' => 'Du matériel anti-incendie pour les centres de la Défense civile',
            'summary' => 'Des outils et équipements spécialisés pour renforcer les centres de la Défense civile dans le gouvernorat de l’Akkar.',
            'location' => 'Gouvernorat de l’Akkar',
            'body' => '<p>Dans le cadre d’un projet financé par le ministère fédéral allemand de la Coopération économique et du Développement (BMZ) et mis en œuvre par RET Germany en partenariat avec GATE Lebanon, les centres de la Défense civile de l’Akkar reçoivent du matériel spécialisé de lutte contre l’incendie.</p><p>L’accord a été conclu avec le directeur général de la Défense civile libanaise afin de renforcer les capacités des centres qui protègent les communautés de l’Akkar.</p>',
        ],
        'sport' => [
            'slug' => 'empowering-youth-through-sport', 'title' => 'Renforcer les jeunes par le sport',
            'summary' => 'Du matériel pour 24 académies sportives, au bénéfice d’environ 2 600 enfants et jeunes libanais et syriens de 6 à 18 ans.',
            'location' => 'Gouvernorat de l’Akkar',
            'body' => '<p>Le sport rassemble les jeunes. Grâce à un financement du BMZ, RET Germany et GATE Lebanon fournissent du matériel à <strong>24 académies sportives</strong> dans tout l’Akkar.</p><p>La distribution bénéficie à environ <strong>2 600 enfants et jeunes de 6 à 18 ans</strong>, issus des communautés libanaise et syrienne.</p>',
        ],
        'mosaic' => [
            'slug' => 'mosaic-art-training-for-women', 'title' => 'Formation à l’art de la mosaïque pour les femmes',
            'summary' => 'Une formation pratique à la mosaïque qui ouvre de nouvelles perspectives de revenus aux femmes du Liban-Nord.',
            'location' => 'Akkar',
            'body' => '<p>Des femmes de l’Akkar apprennent l’art de la mosaïque : un savoir-faire créatif qui peut devenir une source de revenus.</p><p>[Nombre de participantes, durée et résultats à compléter par GATE Lebanon.]</p>',
        ],
        'scouts' => [
            'slug' => 'safer-summer-camps-for-scouts', 'title' => 'Des camps d’été plus sûrs pour les jeunes scouts',
            'summary' => 'Des séances de sensibilisation au harcèlement, à la sécurité numérique et à la protection pour 46 jeunes campeurs à Dawra, dans l’Akkar.',
            'location' => 'Dawra, Akkar',
            'body' => '<p>Avec les Scouts de l’Éducation nationale, RET Germany et GATE Lebanon ont organisé des séances de sensibilisation pour <strong>46 jeunes campeurs</strong> à Dawra, dans l’Akkar.</p><ul><li>L’association Key of Life a animé des séances interactives sur le harcèlement, la violence numérique, le cyberharcèlement, le chantage en ligne et la sécurité numérique.</li><li>Les chefs scouts ont animé des séances sur l’égalité des genres et la protection.</li></ul>',
        ],
        'news-mosaic' => [
            'slug' => 'mosaic-art-training-opens-new-doors-for-women', 'title' => 'La mosaïque ouvre de nouvelles portes aux femmes de l’Akkar',
            'summary' => 'Des femmes de l’Akkar apprennent l’art de la mosaïque, un savoir-faire créatif et une nouvelle source de revenus.',
            'body' => '<p>Une nouvelle formation à l’art de la mosaïque réunit des femmes de l’Akkar autour d’un savoir-faire créatif qui peut devenir une source de revenus.</p><p>[Détails et témoignages des participantes à ajouter par GATE Lebanon.]</p>',
        ],
        'news-camps' => [
            'slug' => 'building-safer-summer-camps-for-youth-scouts', 'title' => 'Des camps d’été plus sûrs pour les scouts du Liban-Nord',
            'summary' => '46 jeunes campeurs de Dawra ont participé à des séances sur le harcèlement, la sécurité numérique et la protection.',
            'body' => '<p>Pendant leur camp d’été à Dawra, dans l’Akkar, 46 jeunes scouts ont participé à des séances de sensibilisation au harcèlement, à la violence numérique et à la sécurité en ligne animées par l’association Key of Life, ainsi qu’à des séances sur l’égalité des genres et la protection animées par leurs chefs scouts.</p>',
        ],
        'news-sport' => [
            'slug' => 'sports-equipment-for-24-academies-in-akkar', 'title' => 'Renforcer les jeunes par le sport dans l’Akkar',
            'summary' => 'Du matériel sportif pour 24 académies et environ 2 600 enfants et jeunes.',
            'body' => '<p>Des académies sportives de tout l’Akkar reçoivent du nouveau matériel, au bénéfice d’environ 2 600 enfants et jeunes libanais et syriens âgés de 6 à 18 ans.</p>',
        ],
        'news-civil-defense' => [
            'slug' => 'agreement-to-boost-civil-defense-in-akkar', 'title' => 'Un accord pour renforcer la Défense civile dans l’Akkar',
            'summary' => 'RET Germany et GATE Lebanon s’accordent avec la Défense civile libanaise pour équiper des centres de l’Akkar.',
            'body' => '<p>RET Germany et GATE Lebanon ont rencontré le directeur général de la Défense civile libanaise pour finaliser un accord prévoyant la fourniture de matériel spécialisé de lutte contre l’incendie aux centres de la Défense civile du gouvernorat de l’Akkar.</p>',
        ],
    ],
];
