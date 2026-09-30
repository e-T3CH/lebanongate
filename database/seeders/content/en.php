<?php

declare(strict_types=1);

/*
 * English starting content (the default language). Facts come from GATE Lebanon's public descriptions and the
 * published updates of its partner RET Germany; [bracketed] parts are for GATE Lebanon to complete or confirm.
 * Bodies use the rich-text allowlist: h2, h3, p, br, ul, ol, li, strong, em, a.
 */
return [
    'pages' => [
        'home' => [
            'slug' => '', 'nav_label' => 'Home', 'title' => 'Empowering Communities in Lebanon',
            'meta_title' => 'GATE Lebanon — For Humanitarian Aid, Development & Peace',
            'meta_description' => 'GATE Lebanon is an independent Lebanese NGO working since 2014 in education, protection, social cohesion and livelihoods with vulnerable Lebanese and displaced communities.',
        ],
        'about' => [
            'slug' => 'about-us', 'nav_label' => 'About Us', 'label' => 'About GATE Lebanon',
            'title' => 'A local organisation, rooted in the communities we serve',
            'intro' => 'GATE Lebanon, formerly RET Liban, is an independent, neutral, non-religious and non-political Lebanese NGO working for humanitarian aid, development and peace.',
            'body' => '<p>Since 2014, GATE Lebanon has responded to the most pressing gaps in <strong>education, protection, social cohesion and livelihoods</strong>. We support displaced populations and vulnerable host communities in Lebanon, with particular attention to youth and women.</p>'
                . '<p>We are committed to protecting communities by strengthening the resilience of people affected by displacement, violence, armed conflict and disasters. Our work is carried out with municipalities, local associations and international partners, and most of our programmes today take place in Akkar, North Lebanon.</p>',
            'meta_title' => 'About us | GATE Lebanon',
            'meta_description' => 'Who GATE Lebanon is: an independent Lebanese NGO for humanitarian aid, development and peace, formerly RET Liban, active since 2014.',
        ],
        'who' => [
            'slug' => 'who-we-are', 'nav_label' => 'Who We Are', 'label' => 'About us', 'title' => 'Who we are',
            'intro' => 'A registered Lebanese NGO, formerly RET Liban, working with vulnerable communities since 2014.',
            'body' => '<p>GATE Lebanon is a local, registered Lebanese non-governmental organisation. We were previously known as <strong>RET Liban</strong> and continue to work closely with RET Germany as an implementing partner.</p>'
                . '<h2>Independent and neutral</h2><p>We are independent, neutral, non-religious and non-political. We serve people on the basis of need alone, whatever their nationality, background or beliefs.</p>'
                . '<h2>Close to the field</h2><p>Our team works directly with the communities we serve, and with the municipalities, schools, scout groups, sports academies and local associations that are part of their daily lives.</p>'
                . '<h2>Our team</h2><p>[A short presentation of the GATE Lebanon team and board, to be provided by GATE Lebanon.]</p>',
            'meta_title' => 'Who we are | GATE Lebanon',
            'meta_description' => 'GATE Lebanon, formerly RET Liban, is an independent, neutral, non-religious and non-political Lebanese NGO.',
        ],
        'mission' => [
            'slug' => 'mission-and-vision', 'nav_label' => 'Mission & Vision', 'label' => 'About us', 'title' => 'Mission & vision',
            'intro' => 'Protecting communities by strengthening the resilience of the most vulnerable people in Lebanon.',
            'body' => '<h2>Our mission</h2><p>GATE Lebanon is committed to protecting communities by ensuring the resilience of vulnerable people affected by displacement, violence, armed conflict and disasters. We respond to the most substantial gaps in education, protection, social cohesion and livelihoods, targeting the most vulnerable community members, with particular attention to youth and women.</p>'
                . '<h2>Our vision</h2><p>[The official vision statement of GATE Lebanon, to be provided.]</p>'
                . '<h2>What we stand for</h2><ul><li><strong>Humanity</strong>: the dignity of every person comes first.</li><li><strong>Neutrality and independence</strong>: we take no side and serve on the basis of need.</li><li><strong>Education as protection</strong>: learning, grounded in human rights, protects children and youth.</li><li><strong>Partnership</strong>: lasting change is built with local actors.</li></ul>'
                . '<h2>Our mandate</h2><ul><li>Spreading culture and education among all social groups.</li><li>Ensuring protection through education based on human rights.</li><li>Helping create educational and training centres and handicraft workshops.</li><li>Fighting illiteracy and educational problems.</li></ul>',
            'meta_title' => 'Mission & vision | GATE Lebanon',
            'meta_description' => 'The mission, values and mandate of GATE Lebanon: protection, education, social cohesion and livelihoods for vulnerable communities.',
        ],
        'profile' => [
            'slug' => 'organisational-profile', 'nav_label' => 'Organisational Profile', 'label' => 'About us', 'title' => 'Organisational profile',
            'intro' => 'Key facts about GATE Lebanon for partners and donors.',
            'body' => '<h2>At a glance</h2><ul><li><strong>Name:</strong> GATE Lebanon (formerly RET Liban)</li><li><strong>Status:</strong> registered Lebanese NGO — registration no. :registration</li><li><strong>Active since:</strong> 2014</li><li><strong>Head office:</strong> :address</li><li><strong>Main area of operation:</strong> Akkar, North Lebanon</li><li><strong>Sectors:</strong> education, protection, social cohesion, livelihoods, emergency response, local governance</li><li><strong>Key partner:</strong> RET Germany, with funding from the German Federal Ministry for Economic Cooperation and Development (BMZ)</li></ul>'
                . '<h2>How we work</h2><p>We design and implement projects together with municipalities, local associations, schools, scout groups and sports academies, so that support reaches people through the structures they already trust.</p>'
                . '<h2>Documents</h2><p>[Organisational profile, registration certificate and annual reports can be published in the Publications section.]</p>'
                . '<p>For partnership enquiries, please write to <a href="mailto::email">:email</a>.</p>',
            'meta_title' => 'Organisational profile | GATE Lebanon',
            'meta_description' => 'Key facts about GATE Lebanon: legal status, areas of work, sectors and partners.',
        ],
        'expertise' => [
            'slug' => 'our-expertise', 'nav_label' => 'Our Expertise', 'label' => 'Our expertise', 'title' => 'Where we make a difference',
            'intro' => 'Six areas of work that respond to the needs of vulnerable communities across Lebanon.',
            'meta_title' => 'Our expertise | GATE Lebanon',
            'meta_description' => 'Education, protection, social cohesion, livelihoods, emergency response and local governance: the areas of work of GATE Lebanon.',
        ],
        'projects' => [
            'slug' => 'projects', 'nav_label' => 'Projects', 'label' => 'Projects & programmes', 'title' => 'Our projects',
            'intro' => 'Current and completed projects of GATE Lebanon, most of them in Akkar, North Lebanon.',
            'meta_title' => 'Projects | GATE Lebanon',
            'meta_description' => 'The projects and programmes of GATE Lebanon in education, protection, social cohesion and livelihoods.',
        ],
        'news' => [
            'slug' => 'news', 'nav_label' => 'News', 'label' => 'News & updates', 'title' => 'News from the field',
            'intro' => 'Updates on our activities, our partners and the communities we work with.',
            'meta_title' => 'News & updates | GATE Lebanon',
            'meta_description' => 'The latest news and updates from GATE Lebanon.',
        ],
        'publications' => [
            'slug' => 'publications', 'nav_label' => 'Publications', 'label' => 'Publications', 'title' => 'Reports & publications',
            'intro' => 'Annual reports, studies and other documents of GATE Lebanon to read or download.',
            'meta_title' => 'Publications | GATE Lebanon',
            'meta_description' => 'Reports, studies and documents published by GATE Lebanon.',
        ],
        'gallery' => [
            'slug' => 'gallery', 'nav_label' => 'Gallery', 'label' => 'Gallery', 'title' => 'Photo gallery',
            'intro' => 'Moments from our activities with communities across Lebanon.',
            'meta_title' => 'Photo gallery | GATE Lebanon',
            'meta_description' => 'Photos from the activities of GATE Lebanon.',
        ],
        'partners' => [
            'slug' => 'partners-and-donors', 'nav_label' => 'Partners & Donors', 'label' => 'Partners & donors', 'title' => 'Working together',
            'intro' => 'Our work is made possible by the partners and donors who share our commitment to vulnerable communities in Lebanon.',
            'body' => '<p>We build long-term partnerships with international organisations, public institutions and local actors. Would you like to support our work or partner with us? <a href="mailto::email">Contact us</a>.</p>',
            'meta_title' => 'Partners & donors | GATE Lebanon',
            'meta_description' => 'The partners and donors of GATE Lebanon.',
        ],
        'contact' => [
            'slug' => 'contact', 'nav_label' => 'Contact', 'label' => 'Contact', 'title' => 'Get in touch',
            'intro' => 'Questions about our work, partnership proposals or media requests: we are happy to hear from you.',
            'meta_title' => 'Contact | GATE Lebanon',
            'meta_description' => 'Contact GATE Lebanon in Ashrafieh, Beirut: address, phone, email and contact form.',
        ],
        'privacy' => [
            'slug' => 'privacy-policy', 'nav_label' => 'Privacy', 'title' => 'Privacy policy',
            'body' => '<p>This policy explains how :organisation (“we”) processes personal data through this website, in line with Lebanese Law No. 81 of 2018 on Electronic Transactions and Personal Data.</p>'
                . '<h2>What we collect</h2><ul><li><strong>Contact form:</strong> your name, email address and message, and optionally your phone number and organisation.</li><li><strong>Newsletter:</strong> your email address and the language you chose, and the date you confirmed your subscription.</li><li><strong>Technical data:</strong> to protect the forms against abuse we keep a one-way code of your IP address, never the address itself.</li></ul>'
                . '<h2>Why we use it</h2><p>We use your details only to answer your message or to send you the newsletter you asked for. We do not sell or share your data, except with the service providers that host this website and send its emails.</p>'
                . '<h2>How long we keep it</h2><p>Messages are kept as long as needed to follow up on them and are then deleted or archived. Newsletter addresses are kept until you unsubscribe, which you can do with the link in every email.</p>'
                . '<h2>Your rights</h2><p>You can ask to see, correct or delete your personal data at any time by writing to <a href="mailto::email">:email</a>.</p>'
                . '<h2>Contact</h2><p>:organisation, :address.</p>',
            'meta_title' => 'Privacy policy | GATE Lebanon',
            'meta_description' => 'How GATE Lebanon processes personal data from the contact form, the newsletter and website visits.',
        ],
        'cookies' => [
            'slug' => 'cookie-policy', 'nav_label' => 'Cookies', 'title' => 'Cookie policy',
            'body' => '<p>This website uses a small number of cookies.</p>'
                . '<h2>Necessary cookies</h2><ul><li><strong>gate_session</strong>: keeps forms secure (protection against forged submissions). Deleted when you close your browser.</li><li><strong>gate_lang</strong>: remembers the language you chose, for one year.</li><li><strong>gate_consent</strong>: remembers your cookie choice, for six months.</li></ul>'
                . '<h2>Analytics cookies</h2><p>Only if you accept them, we use analytics cookies to count visits and understand which pages are useful. You can change your choice at any time with the “Cookie settings” link at the bottom of every page.</p>',
            'meta_title' => 'Cookie policy | GATE Lebanon',
            'meta_description' => 'The cookies used by the GATE Lebanon website and how to change your choice.',
        ],
        'terms' => [
            'slug' => 'terms-of-use', 'nav_label' => 'Terms', 'title' => 'Terms of use',
            'body' => '<p>This website is published by :organisation, :address.</p>'
                . '<h2>Content</h2><p>We take care to publish accurate information, but it may change over time. Texts, photos and publications on this website belong to GATE Lebanon or its partners. You may share them for non-commercial purposes when you mention the source.</p>'
                . '<h2>Photos</h2><p>People appear in our photos with their consent. If you appear in a photo and want it removed, write to <a href="mailto::email">:email</a>.</p>'
                . '<h2>Links</h2><p>We are not responsible for the content of external websites we link to.</p>',
            'meta_title' => 'Terms of use | GATE Lebanon',
            'meta_description' => 'Terms of use of the GATE Lebanon website.',
        ],
    ],

    'sections' => [
        'hero' => [
            'label' => 'For Humanitarian Aid, Development & Peace',
            'title' => 'Empowering Communities in Lebanon',
            'intro' => 'Since 2014 we have worked alongside vulnerable Lebanese and displaced communities in education, protection, social cohesion and livelihoods, with a special focus on youth and women.',
        ],
        'about' => [
            'label' => 'About GATE Lebanon',
            'title' => 'A local organisation, rooted in the communities we serve',
            'intro' => 'GATE Lebanon, formerly RET Liban, is an independent, neutral, non-religious and non-political Lebanese NGO. We respond to the most pressing gaps affecting people touched by displacement, violence and crisis.',
            'extra' => [
                'badge_value' => '2014',
                'badge_label' => 'Working in Lebanon since',
                'points' => [
                    ['icon' => 'target', 'title' => 'Neutral and independent', 'text' => 'We serve people on the basis of need alone.'],
                    ['icon' => 'users', 'title' => 'Youth and women first', 'text' => 'Programmes designed with the people most at risk.'],
                    ['icon' => 'hands', 'title' => 'Built on partnership', 'text' => 'With municipalities, local associations and international partners.'],
                ],
            ],
        ],
        'expertise' => [
            'label' => 'Our expertise',
            'title' => 'Where we make a difference',
            'intro' => 'Six areas of work that respond to the needs of vulnerable communities across Lebanon.',
        ],
        'stats' => ['label' => 'Our impact'],
        'projects' => ['label' => 'Projects & programmes', 'title' => 'Recent work in the field'],
        'map' => [
            'label' => 'Where we work',
            'title' => 'Present where needs are greatest',
            'intro' => 'Our programmes are concentrated in Akkar, North Lebanon, with activities in other governorates depending on needs and partnerships.',
            'extra' => [
                'main' => 'akkar',
                'notes' => ['akkar' => 'Main area of operation', 'beirut' => 'Head office'],
            ],
        ],
        'news' => ['label' => 'News & updates', 'title' => 'Latest from GATE Lebanon'],
        'partners' => ['title' => 'Our partners & donors'],
        'cta' => [
            'title' => 'Let’s build resilient communities together',
            'intro' => 'Partner with GATE Lebanon on education, protection and livelihoods programmes, or get in touch to learn more about our work.',
        ],
    ],

    'expertise' => [
        'education' => [
            'slug' => 'education', 'title' => 'Education',
            'summary' => 'Learning support, literacy and safe spaces that keep children and youth in education.',
            'body' => '<p>Education is at the heart of GATE Lebanon’s mandate. We help children and young people stay in learning, fight illiteracy and support the creation of educational and training centres.</p><ul><li>Learning and homework support</li><li>Literacy and numeracy classes</li><li>Safe, welcoming learning spaces</li><li>Training centres and handicraft workshops</li></ul>',
            'meta_description' => 'Education programmes of GATE Lebanon: learning support, literacy and training centres.',
        ],
        'protection' => [
            'slug' => 'protection', 'title' => 'Protection',
            'summary' => 'Human-rights-based protection and awareness on bullying, digital safety and gender equality.',
            'body' => '<p>We protect children, youth and women through education based on human rights, and by raising awareness of the risks they face.</p><ul><li>Awareness sessions on bullying, cyberbullying and online blackmail</li><li>Digital safety for young people</li><li>Gender equality and protection sessions with scout leaders</li><li>Referral of people at risk to specialised services</li></ul>',
            'meta_description' => 'Protection work of GATE Lebanon: awareness on bullying, digital safety and gender equality.',
        ],
        'social-cohesion' => [
            'slug' => 'social-cohesion', 'title' => 'Social Cohesion',
            'summary' => 'Bringing Lebanese and displaced communities together through sport, scouting and culture.',
            'body' => '<p>Sport, scouting and cultural activities bring children and young people from Lebanese and displaced communities together, and build trust between them.</p><p>In Akkar, we support sports academies and scout groups that reach thousands of children and youth.</p>',
            'meta_description' => 'Social cohesion work of GATE Lebanon through sport, scouting and culture.',
        ],
        'livelihoods' => [
            'slug' => 'livelihoods', 'title' => 'Livelihoods',
            'summary' => 'Vocational and handicraft training that opens income opportunities, especially for women.',
            'body' => '<p>Skills open doors. Our vocational and handicraft trainings help women and young people build skills that lead to income and independence.</p><p>Recent trainings include mosaic art for women in Akkar.</p>',
            'meta_description' => 'Livelihoods programmes of GATE Lebanon: vocational and handicraft training.',
        ],
        'emergency' => [
            'slug' => 'emergency-response', 'title' => 'Emergency Response',
            'summary' => 'Strengthening local responders, such as Civil Defense centres, to protect communities.',
            'body' => '<p>When crises strike, local responders are the first to act. We strengthen their capacity, for example by providing Civil Defense centres in Akkar with specialised firefighting equipment.</p>',
            'meta_description' => 'Emergency response work of GATE Lebanon with local responders such as the Civil Defense.',
        ],
        'governance' => [
            'slug' => 'local-governance', 'title' => 'Local Governance',
            'summary' => 'Supporting municipalities and local associations to plan and lead community development.',
            'body' => '<p>Strong local institutions make communities more resilient. We work with municipal leaders and local NGOs and associations in Akkar to plan, coordinate and lead community development.</p>',
            'meta_description' => 'Local governance work of GATE Lebanon with municipalities and local associations.',
        ],
    ],

    'stats' => [
        ['value' => '12+', 'label' => 'Years in Lebanon'],
        ['value' => '2,600+', 'label' => 'Children & youth reached through sport'],
        ['value' => '24', 'label' => 'Sports academies equipped in Akkar'],
        ['value' => '46', 'label' => 'Young scouts trained on protection'],
    ],

    'partners' => [
        'RET Germany' => 'International partner of GATE Lebanon; together we implement projects in Akkar.',
        'BMZ' => 'The German Federal Ministry for Economic Cooperation and Development funds several projects implemented by RET Germany with GATE Lebanon.',
        'National Education Scouts' => 'Partner for awareness sessions and summer camps with young scouts in Akkar.',
        'Lebanese Civil Defense' => 'Partner in strengthening firefighting capacity in Akkar.',
    ],

    'entries' => [
        'civil-defense' => [
            'slug' => 'firefighting-equipment-civil-defense-akkar', 'title' => 'Firefighting equipment for Civil Defense centres',
            'summary' => 'Specialised firefighting tools and supplies to strengthen Civil Defense centres across Akkar Governorate.',
            'location' => 'Akkar Governorate',
            'body' => '<p>Under a project funded by the German Federal Ministry for Economic Cooperation and Development (BMZ) and implemented by RET Germany in partnership with GATE Lebanon, Civil Defense centres in Akkar receive specialised firefighting equipment.</p><p>The agreement was reached with the Director General of the Lebanese Civil Defense, to strengthen the capacity of the centres that protect communities in Akkar.</p>',
        ],
        'sport' => [
            'slug' => 'empowering-youth-through-sport', 'title' => 'Empowering youth through sport',
            'summary' => 'Equipment for 24 sports academies, reaching about 2,600 Lebanese and Syrian children and youth aged 6 to 18.',
            'location' => 'Akkar Governorate',
            'body' => '<p>Sport brings young people together. With funding from BMZ, RET Germany and GATE Lebanon provide equipment to <strong>24 sports academies</strong> across Akkar.</p><p>The distribution benefits about <strong>2,600 children and youth aged 6 to 18</strong>, from both Lebanese and Syrian communities.</p>',
        ],
        'mosaic' => [
            'slug' => 'mosaic-art-training-for-women', 'title' => 'Mosaic art training for women',
            'summary' => 'Hands-on mosaic training that opens new income opportunities for women in North Lebanon.',
            'location' => 'Akkar',
            'body' => '<p>Women in Akkar learn the craft of mosaic art: a creative skill that can become a source of income.</p><p>[Number of participants, duration and results to be completed by GATE Lebanon.]</p>',
        ],
        'scouts' => [
            'slug' => 'safer-summer-camps-for-scouts', 'title' => 'Safer summer camps for youth scouts',
            'summary' => 'Awareness sessions on bullying, digital safety and protection for 46 young campers in Dawra, Akkar.',
            'location' => 'Dawra, Akkar',
            'body' => '<p>Together with the National Education Scouts, RET Germany and GATE Lebanon organised awareness sessions for <strong>46 young campers</strong> in Dawra, Akkar.</p><ul><li>The Key of Life Association led interactive sessions on bullying, digital violence, cyberbullying, online blackmail and digital safety.</li><li>Scout leaders held sessions on gender equality and protection.</li></ul>',
        ],
        'news-mosaic' => [
            'slug' => 'mosaic-art-training-opens-new-doors-for-women', 'title' => 'Mosaic art training opens new doors for women in Akkar',
            'summary' => 'Women in Akkar are learning mosaic art, a creative craft and a new source of income.',
            'body' => '<p>A new mosaic art training brings women in Akkar together to learn a creative craft that can become a source of income.</p><p>[Details and quotes from participants to be added by GATE Lebanon.]</p>',
        ],
        'news-camps' => [
            'slug' => 'building-safer-summer-camps-for-youth-scouts', 'title' => 'Building safer summer camps for youth scouts in North Lebanon',
            'summary' => '46 young campers in Dawra took part in sessions on bullying, digital safety and protection.',
            'body' => '<p>During their summer camp in Dawra, Akkar, 46 young scouts took part in awareness sessions on bullying, digital violence and online safety, led by the Key of Life Association, and in sessions on gender equality and protection led by their scout leaders.</p>',
        ],
        'news-sport' => [
            'slug' => 'sports-equipment-for-24-academies-in-akkar', 'title' => 'Empowering youth through sport across Akkar',
            'summary' => 'Sports equipment reaches 24 academies and about 2,600 children and youth.',
            'body' => '<p>Sports academies across Akkar are receiving new equipment, benefiting about 2,600 Lebanese and Syrian children and youth aged 6 to 18.</p>',
        ],
        'news-civil-defense' => [
            'slug' => 'agreement-to-boost-civil-defense-in-akkar', 'title' => 'Agreement to boost Civil Defense firefighting capacity in Akkar',
            'summary' => 'RET Germany and GATE Lebanon agree with the Lebanese Civil Defense to equip centres in Akkar.',
            'body' => '<p>RET Germany and GATE Lebanon met the Director General of the Lebanese Civil Defense to finalise an agreement providing specialised firefighting equipment to Civil Defense centres in Akkar Governorate.</p>',
        ],
    ],
];
