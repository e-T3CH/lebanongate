<?php

declare(strict_types=1);

/*
 * English seed content: the approved mock-up copy (desktop version) and [bracket] placeholders.
 * Legal texts are templates: :company, :address, :vat, :email, :phone and :website are filled from the settings,
 * everything in [brackets] must be completed by the owner before going live.
 */
return [
    'pages' => [
        'home' => [
            'slug' => '', 'nav_label' => 'Home', 'label' => '', 'title' => 'BM-Matic',
            'meta_title' => 'Automatic transmission repair in Aalst | BM-Matic',
            'meta_description' => 'Diagnosis, repair and overhaul of automatic, DSG and CVT transmissions for every brand in Aalst. We test first and give you a clear quote.',
        ],
        'services' => [
            'slug' => 'services', 'nav_label' => 'Services', 'label' => 'Services', 'title' => 'Every automatic.', 'highlight' => 'One specialist.',
            'intro' => 'From a first fault reading to a complete overhaul: every automatic, dual-clutch and CVT transmission, for every brand. We always diagnose before we quote.',
            'meta_title' => 'Automatic gearbox services | BM-Matic Aalst',
            'meta_description' => 'Diagnostics, gearbox overhaul, ATF flush, torque converter, mechatronic and DSG/CVT repair in Aalst. Clear quote after diagnosis.',
        ],
        'transmissions' => [
            'slug' => 'transmissions', 'nav_label' => 'Transmissions', 'label' => 'Transmissions', 'title' => 'Transmissions', 'highlight' => 'we work on.',
            'intro' => 'Torque-converter automatics, dual-clutch gearboxes and CVTs from every major manufacturer. Not in the list? Call us: we most likely know your gearbox.',
            'meta_title' => 'Automatic, DSG and CVT transmissions we repair | BM-Matic',
            'meta_description' => 'ZF, Aisin, Mercedes 7G/9G-Tronic, VAG DSG, Ford PowerShift, Jatco CVT and more: the automatic transmissions BM-Matic diagnoses and repairs.',
        ],
        'about' => [
            'slug' => 'about', 'nav_label' => 'About', 'label' => 'About', 'title' => 'Automatic transmissions,', 'highlight' => 'nothing else.',
            'intro' => 'BM-Matic is a specialist workshop in Aalst for the diagnosis, repair and overhaul of automatic transmissions.',
            'body' => '<h2>A specialist, not a general garage</h2><p>Automatic gearboxes are complex: hydraulics, electronics and mechanics work together in a few litres of fluid. That is all we work on, every day, for [XX] years. That focus is what lets us find the real cause of a fault instead of replacing parts until it goes away.</p><h2>How we work</h2><p>Every repair starts with a diagnosis: a road test, live data, fault codes and a fluid check. You get the findings and a fixed quote, and nothing starts without your approval. After the repair we road test again and give [X] months warranty.</p><h2>The workshop</h2><p>[Short description of the workshop, the team and the equipment — to be completed by the owner.]</p>',
            'meta_title' => 'About BM-Matic | Automatic transmission specialist in Aalst',
            'meta_description' => 'A specialist workshop for automatic transmissions in Aalst: diagnosis first, a clear quote, repair with warranty.',
        ],
        'reviews' => [
            'slug' => 'reviews', 'nav_label' => 'Reviews', 'label' => 'Google reviews', 'title' => 'Drivers who', 'highlight' => 'shift smoothly again.',
            'intro' => 'What our customers say on Google.',
            'meta_title' => 'Customer reviews | BM-Matic Aalst',
            'meta_description' => 'Google reviews from drivers whose automatic, DSG or CVT gearbox was repaired by BM-Matic in Aalst.',
        ],
        'contact' => [
            'slug' => 'contact', 'nav_label' => 'Contact', 'label' => 'Contact', 'title' => 'Gearbox slipping, jerking', 'highlight' => 'or in limp mode?',
            'intro' => 'Send us your car and the symptoms. We reply within one working day with an appointment for diagnosis.',
            'meta_title' => 'Contact and appointments | BM-Matic Aalst',
            'meta_description' => 'Request an appointment for a gearbox diagnosis at BM-Matic in Aalst. We reply within one working day.',
        ],
        'privacy' => [
            'slug' => 'privacy-policy', 'nav_label' => 'Privacy policy', 'label' => 'Legal', 'title' => 'Privacy policy',
            'intro' => 'Last updated: [date]',
            'body' => '<p><strong>[TEMPLATE — have this text checked by a lawyer or your accountant, complete every [bracket], then delete this line.]</strong></p><h2>Who is responsible</h2><p>:company, :address, enterprise number BE :vat (“we”) is the controller of the personal data processed through :website. Questions about privacy: :email or :phone.</p><h2>What we collect, why, and on which legal basis</h2><ul><li><strong>Appointment requests</strong> — name, phone number, email address, car make and model, gearbox type and the symptoms you describe. We use them to answer your request, plan the diagnosis and, if you ask for it, the repair. Legal basis: steps taken at your request before entering into a contract (Article 6(1)(b) GDPR).</li><li><strong>Invoices and warranty</strong> — when a repair follows, the data needed for the invoice and the warranty. Legal basis: the contract, and our obligations under Belgian accounting and tax law (Article 6(1)(b) and (c) GDPR).</li><li><strong>Protection against abuse</strong> — a one-way hash of your IP address and your browser type with each request, and a security log of sign-ins to our administration panel. Legal basis: our legitimate interest in keeping the website and your data secure (Article 6(1)(f) GDPR).</li><li><strong>Visit statistics</strong> — only when you accept analytics cookies: anonymous statistics about how the website is used. Legal basis: your consent (Article 6(1)(a) GDPR), which you can withdraw at any time.</li></ul><p>We do not use your data for automated decisions or profiling, and we do not send you advertising.</p><h2>How long we keep it</h2><ul><li>Appointment requests without a follow-up repair: [X] months after the last contact.</li><li>Invoices and the data on them: as long as Belgian accounting and tax law requires (currently ten years).</li><li>Security log: at most [X] days.</li></ul><h2>Who receives it</h2><p>Only the people at :company who handle your request, and the service providers who process data on our behalf under a data-processing agreement: our hosting provider [name, country] and our email provider [name, country]. [Analytics provider, if enabled, and its country.] We never sell your data. If a provider stores data outside the European Economic Area, that transfer is covered by the European Commission’s standard contractual clauses or an adequacy decision.</p><p>Google reviews shown on this website are fetched by our server; your visit does not send any data to Google.</p><h2>Your rights</h2><p>You can ask us to access, correct or delete your data, to restrict or object to its processing, and to receive it in a portable format. Where processing is based on consent, you can withdraw it at any time. Send your request to :email; we answer within one month.</p><p>If you are not satisfied with our answer, you can file a complaint with the Belgian Data Protection Authority, Rue de la Presse 35, 1000 Brussels, www.dataprotectionauthority.be.</p><h2>Security</h2><p>The website is only available over an encrypted connection, access to the administration panel is protected by strong passwords and optional two-factor authentication, and stored secrets are encrypted.</p><h2>Changes</h2><p>We may update this policy; the date at the top shows the latest version. It is governed by the General Data Protection Regulation (EU) 2016/679 and the Belgian Act of 30 July 2018 on the protection of natural persons with regard to the processing of personal data.</p><h2>Cookies</h2><p>See our cookie policy.</p>',
            'meta_title' => 'Privacy policy | BM-Matic',
            'meta_description' => 'How BM-Matic processes personal data from appointment requests and website visits.',
        ],
        'cookies' => [
            'slug' => 'cookie-policy', 'nav_label' => 'Cookie policy', 'label' => 'Legal', 'title' => 'Cookie policy',
            'intro' => 'Last updated: [date]',
            'body' => '<p><strong>[TEMPLATE — check the list below against the website as it is published, then delete this line.]</strong></p><p>Cookies are small files a website stores in your browser. Under the Belgian Act of 13 June 2005 on electronic communications and the GDPR, we only place cookies that are strictly necessary without asking; all others need your consent first.</p><h2>Strictly necessary cookies (always on)</h2><ul><li><strong>bm_lang</strong> — remembers the language you chose. 1 year.</li><li><strong>bm_consent</strong> — remembers your cookie choice, so we do not ask again on every page. 180 days.</li><li><strong>bm_session</strong> (<strong>__Host-bm_session</strong> over HTTPS) — keeps the appointment form secure against misuse. Deleted when you close your browser.</li></ul><h2>Analytics cookies (only with your consent)</h2><p>If you accept analytics, we measure visits with [analytics provider — name, country, cookie names and lifetimes]. These cookies are only placed after you accept, and removed when you withdraw your consent.</p><p>We do not use advertising or social-media cookies. Google reviews and reviewer photos on this website are served from our own domain and place no cookies.</p><h2>Changing your choice</h2><p>Use “Cookie settings” at the bottom of every page to accept or withdraw consent at any time. You can also delete cookies in your browser settings.</p><p>Questions: :email. More about how we handle personal data is in our privacy policy.</p>',
            'meta_title' => 'Cookie policy | BM-Matic',
            'meta_description' => 'Which cookies the BM-Matic website uses and how to change your choice.',
        ],
        'terms' => [
            'slug' => 'terms', 'nav_label' => 'Terms', 'label' => 'Legal', 'title' => 'Terms and conditions',
            'intro' => 'Last updated: [date]',
            'body' => '<p><strong>[TEMPLATE — have these terms checked by a lawyer, complete every [bracket], then delete this line.]</strong></p><h2>Who we are</h2><p>:company, :address, enterprise number BE :vat, :email, :phone.</p><h2>Scope</h2><p>These terms apply to every diagnosis, quote and repair we carry out. Where they differ from terms you propose, ours apply unless we agreed otherwise in writing. Nothing in these terms limits the rights that consumers have under Belgian law.</p><h2>Prices, diagnosis and quotes</h2><p>All prices for consumers include VAT. A diagnosis costs [amount incl. VAT], which is [deducted from the repair if you have it carried out by us]. A quote is valid for [X] days. We only start a repair after you approved the quote, in writing or verbally; if extra work turns out to be necessary, we contact you first and only carry it out with your approval.</p><h2>Your vehicle and the replaced parts</h2><p>Replaced parts are available to you on request when you collect the vehicle, unless they are exchanged under a manufacturer’s exchange scheme. A vehicle not collected within [X] days of our notice that it is ready may be charged storage at [amount] per day.</p><h2>Payment</h2><p>Invoices are payable [on collection / within X days]. [Late-payment terms — for consumers, reminders, interest and fees are limited by Book XIX of the Code of Economic Law.] [Right of retention: we may keep the vehicle until the invoice has been paid.]</p><h2>Warranty</h2><p>Repairs carry a commercial warranty of [X] months on parts and labour, provided the vehicle is used normally and serviced according to the manufacturer’s schedule. [Warranty exclusions.] This warranty comes on top of, and never limits, the legal guarantee that consumers have on the parts supplied (two years for new goods).</p><h2>Liability</h2><p>[Liability clause — Belgian law does not allow excluding liability for intent, gross negligence, or death or bodily injury.]</p><h2>Complaints and disputes</h2><p>Please report a complaint to :email as soon as possible; we answer within [X] working days. Consumers can also contact the Consumer Ombudsman Service (www.consumerombudsman.be). Belgian law applies. Disputes with businesses are handled by the courts of [judicial district]; consumers keep the right to go to the court their place of residence gives them under the law.</p>',
            'meta_title' => 'Terms and conditions | BM-Matic',
            'meta_description' => 'Terms and conditions for diagnoses, quotes and repairs by BM-Matic.',
        ],
    ],
    'sections' => [
        'topbar' => [],
        'header' => [],
        'hero' => [
            'label' => 'Automatic transmission specialists · Aalst',
            'title' => 'Gearbox repair, engineered to', 'highlight' => 'shift perfectly.',
            'intro' => 'Diagnosis, repair and overhaul of automatic, DSG and CVT transmissions for every brand. We test first, explain what we find, and give you a clear quote.',
            'extra' => ['cta' => 'Book a diagnosis', 'call' => 'Call the workshop', 'rating' => 'from :count Google reviews', 'caption' => 'SECTION A–A · SCHEMATIC', 'schematic' => 'Transmission schematic', 'legend' => ['01 Torque converter', '02 Planetary gearsets', '03 Valve body', '04 TCU software']],
        ],
        'stats' => ['label' => 'Key figures'],
        'services' => ['label' => 'Services', 'title' => 'Every automatic.', 'highlight' => 'One specialist.', 'extra' => ['link' => 'All services']],
        'process' => ['label' => 'How we work', 'title' => 'Diagnose first.', 'highlight' => 'Then we quote.'],
        'transmissions' => ['label' => 'TRANSMISSIONS WE WORK ON'],
        'reviews' => ['label' => 'Google reviews', 'title' => 'Drivers who', 'highlight' => 'shift smoothly again.', 'extra' => ['count' => ':count reviews on Google', 'read_all' => 'Read all', 'empty_title' => 'Reviews are on their way', 'empty_text' => 'Our Google reviews appear here soon. Until then, read them on Google.', 'google' => 'Read our reviews on Google']],
        'contact' => ['label' => 'Contact', 'title' => 'Gearbox slipping, jerking or in limp mode?', 'intro' => 'Send us your car and the symptoms. We reply within one working day with an appointment for diagnosis.', 'extra' => ['form_title' => 'Request an appointment', 'submit' => 'Send request', 'map' => 'MAP — AALST']],
        'footer' => [],
    ],
    'services' => [
        'diagnostics' => [
            'slug' => 'diagnostics', 'title' => 'Diagnostics & fault reading', 'menu_title' => 'Diagnostics', 'menu_sub' => 'Fault reading & road test', 'short_title' => 'Diagnostics',
            'summary' => 'Road test, live data and error codes read before anything is opened.',
            'body' => '<p>Most gearbox complaints — slipping, harsh or late shifts, shudder, a warning light or limp mode — can have several causes. Replacing parts on a guess is expensive. We find the cause first.</p><h2>What the diagnosis includes</h2><ul><li>A road test to reproduce the complaint</li><li>Reading fault codes and live data from the transmission control unit</li><li>Fluid level and condition check</li><li>Adaptation values and software version</li></ul><p>You get a clear explanation of what we found and a fixed quote for the repair. Nothing starts without your approval.</p>',
            'meta_title' => 'Gearbox diagnosis and fault reading | BM-Matic Aalst',
            'meta_description' => 'Road test, fault codes, live data and fluid check before any repair. A clear diagnosis and fixed quote for your automatic gearbox.',
        ],
        'overhaul' => [
            'slug' => 'gearbox-overhaul', 'title' => 'Gearbox overhaul', 'menu_title' => 'Gearbox overhaul', 'menu_sub' => 'Complete revision', 'short_title' => 'Gearbox overhaul',
            'summary' => 'Complete revision of automatic transmissions with new wear parts.',
            'body' => '<p>When internal wear causes the problem, a complete overhaul brings the gearbox back to specification — usually at a fraction of the price of a new unit.</p><h2>What we do</h2><ul><li>Removal, complete disassembly and cleaning</li><li>New clutches, seals, gaskets and filters</li><li>Inspection and replacement of worn hard parts</li><li>Reassembly, fluid fill, adaptation and road test</li></ul><p>Every overhaul comes with [X] months warranty.</p>',
            'meta_title' => 'Automatic gearbox overhaul | BM-Matic Aalst',
            'meta_description' => 'Complete revision of automatic transmissions with new clutches, seals and filters, adaptation and road test. [X] months warranty.',
        ],
        'flush' => [
            'slug' => 'atf-flush', 'title' => 'Dynamic ATF flush', 'menu_title' => 'ATF flush', 'menu_sub' => 'Dynamic oil exchange', 'short_title' => 'ATF flush',
            'summary' => 'Full fluid exchange with the correct oil spec for your gearbox.',
            'body' => '<p>Automatic transmission fluid wears out: it loses its friction properties and collects wear particles. A normal drain replaces only part of it. A dynamic flush exchanges nearly all of it while the gearbox runs.</p><h2>Why it matters</h2><ul><li>Smoother shifts and less shudder</li><li>Less wear on clutches and valve body</li><li>The exact fluid specification the manufacturer requires</li></ul><p>We check the fluid condition first and advise whether a flush makes sense for your gearbox.</p>',
            'meta_title' => 'Dynamic ATF flush | BM-Matic Aalst',
            'meta_description' => 'Near-complete automatic transmission fluid exchange with the correct specification for your gearbox.',
        ],
        'converter' => [
            'slug' => 'torque-converter-repair', 'title' => 'Torque converter repair', 'menu_title' => 'Torque converter', 'menu_sub' => 'Shudder & slip', 'short_title' => 'Torque converter',
            'summary' => 'Shudder, slip or overheating traced and repaired.',
            'body' => '<p>A worn torque converter lock-up clutch causes shudder at constant speed, higher engine revs and overheating fluid. We confirm the cause with live data before any work starts.</p><h2>Repair</h2><ul><li>Removal and inspection of the converter</li><li>Replacement or revision of the lock-up clutch and seals</li><li>Fluid exchange and adaptation</li></ul>',
            'meta_title' => 'Torque converter repair | BM-Matic Aalst',
            'meta_description' => 'Shudder, slip or overheating traced to the torque converter and repaired.',
        ],
        'mechatronic' => [
            'slug' => 'mechatronic-valve-body', 'title' => 'Mechatronic & valve body', 'menu_title' => 'Mechatronic & valve body', 'menu_sub' => 'Solenoids & control units', 'short_title' => 'Mechatronic',
            'summary' => 'Solenoids, pressure regulation and control units tested and repaired.',
            'body' => '<p>The valve body and mechatronic unit control every shift: solenoids, pressure regulators and sensors. Faults here cause harsh shifts, limp mode and error codes.</p><h2>Repair</h2><ul><li>Solenoid and pressure tests</li><li>Cleaning and revision of the valve body</li><li>Repair or replacement of the mechatronic unit, with programming and adaptation</li></ul>',
            'meta_title' => 'Mechatronic and valve body repair | BM-Matic Aalst',
            'meta_description' => 'Solenoids, pressure regulation and transmission control units tested, repaired and adapted.',
        ],
        'dsg' => [
            'slug' => 'dsg-dct-cvt', 'title' => 'DSG, DCT & CVT', 'menu_title' => 'DSG, DCT & CVT', 'menu_sub' => 'Dual-clutch & CVT', 'short_title' => 'DSG & CVT',
            'summary' => 'Dual-clutch and continuously variable transmissions, including clutch packs.',
            'body' => '<p>Dual-clutch gearboxes (DSG, DCT, PowerShift) and CVTs need their own expertise. We repair dry and wet dual-clutch systems and CVT belts, pulleys and valve bodies.</p><h2>Typical work</h2><ul><li>Clutch pack replacement and basic settings</li><li>Mechatronic repair for DSG DQ200, DQ250 and DQ381</li><li>CVT diagnosis, fluid service and repair</li></ul>',
            'meta_title' => 'DSG, DCT and CVT repair | BM-Matic Aalst',
            'meta_description' => 'Dual-clutch (DSG, DCT, PowerShift) and CVT transmissions diagnosed and repaired, including clutch packs.',
        ],
    ],
    'transmission_types' => [
        ['label' => 'ZF 6HP / 8HP', 'description' => 'BMW, Audi, Jaguar, Land Rover and more.'],
        ['label' => 'Aisin', 'description' => 'Volvo, Toyota, Peugeot, Citroën, Mini and others.'],
        ['label' => 'Mercedes 7G / 9G-Tronic', 'description' => 'Mercedes-Benz passenger cars and vans.'],
        ['label' => 'VAG DSG', 'description' => 'Volkswagen, Audi, Škoda and Seat dual-clutch gearboxes.'],
        ['label' => 'Ford PowerShift', 'description' => 'Ford dual-clutch transmissions.'],
        ['label' => 'Jatco CVT', 'description' => 'Nissan, Renault, Mitsubishi and Suzuki CVTs.'],
        ['label' => 'BMW Steptronic', 'description' => 'BMW automatic transmissions.'],
        ['label' => 'Volvo Geartronic', 'description' => 'Volvo automatic transmissions.'],
    ],
    'process_steps' => [
        ['title' => 'Book online', 'text' => 'Choose a time slot or call us. Tell us the car and the symptoms.'],
        ['title' => 'Test & scan', 'text' => 'Road test, fault codes and fluid check — before any repair.'],
        ['title' => 'Clear quote', 'text' => 'You get the diagnosis and a fixed quote. Nothing starts without your OK.'],
        ['title' => 'Repair & warranty', 'text' => 'We repair, road test again, and give [X] months warranty.'],
    ],
    'stats' => [
        ['value' => '[XX]+', 'label' => 'Years of experience'],
        ['value' => '[X,XXX]+', 'label' => 'Gearboxes repaired'],
        ['value' => '[X] months', 'label' => 'Warranty on repairs'],
        ['value' => 'All brands', 'label' => 'Automatic, DSG & CVT'],
    ],
];
