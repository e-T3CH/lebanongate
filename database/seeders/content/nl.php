<?php

declare(strict_types=1);

/*
 * Nederlandse startinhoud (tekst van de goedgekeurde mock-ups, desktopversie) met [placeholders].
 * De juridische teksten zijn sjablonen: :company, :address, :vat, :email, :phone en :website komen uit de
 * instellingen; alles tussen [haken] moet de eigenaar invullen vóór de website live gaat.
 */
return [
    'pages' => [
        'home' => [
            'slug' => '', 'nav_label' => 'Home', 'label' => '', 'title' => 'BM-Matic',
            'meta_title' => 'Herstelling van automatische versnellingsbakken in Aalst | BM-Matic',
            'meta_description' => 'Diagnose, herstelling en revisie van automatische, DSG- en CVT-versnellingsbakken van alle merken in Aalst. Eerst testen, dan een duidelijke offerte.',
        ],
        'services' => [
            'slug' => 'diensten', 'nav_label' => 'Diensten', 'label' => 'Diensten', 'title' => 'Elke automaat.', 'highlight' => 'Eén specialist.',
            'intro' => 'Van een eerste foutcode-uitlezing tot een volledige revisie: automatische, dubbelekoppelings- en CVT-versnellingsbakken van alle merken. We stellen altijd eerst een diagnose.',
            'meta_title' => 'Diensten voor automatische versnellingsbakken | BM-Matic Aalst',
            'meta_description' => 'Diagnose, revisie, ATF-spoeling, koppelomvormer, mechatronica en DSG/CVT-herstelling in Aalst. Duidelijke offerte na diagnose.',
        ],
        'transmissions' => [
            'slug' => 'transmissies', 'nav_label' => 'Transmissies', 'label' => 'Transmissies', 'title' => 'Transmissies', 'highlight' => 'waar we aan werken.',
            'intro' => 'Automaten met koppelomvormer, dubbelekoppelingsbakken en CVT’s van alle grote merken. Staat uw bak er niet bij? Bel ons: we kennen hem waarschijnlijk wel.',
            'meta_title' => 'Automatische, DSG- en CVT-transmissies die we herstellen | BM-Matic',
            'meta_description' => 'ZF, Aisin, Mercedes 7G/9G-Tronic, VAG DSG, Ford PowerShift, Jatco CVT en meer: de automatische transmissies die BM-Matic diagnosticeert en herstelt.',
        ],
        'about' => [
            'slug' => 'over-ons', 'nav_label' => 'Over ons', 'label' => 'Over ons', 'title' => 'Automatische versnellingsbakken,', 'highlight' => 'niets anders.',
            'intro' => 'BM-Matic is een gespecialiseerde werkplaats in Aalst voor de diagnose, herstelling en revisie van automatische versnellingsbakken.',
            'body' => '<h2>Een specialist, geen algemene garage</h2><p>Een automatische versnellingsbak is complex: hydrauliek, elektronica en mechaniek werken samen in enkele liters olie. Dat is ons enige werk, elke dag, al [XX] jaar. Door die focus vinden we de echte oorzaak van een defect in plaats van onderdelen te vervangen tot het probleem verdwijnt.</p><h2>Hoe we werken</h2><p>Elke herstelling begint met een diagnose: een testrit, live data, foutcodes en een oliecontrole. U krijgt onze bevindingen en een vaste offerte, en er begint niets zonder uw akkoord. Na de herstelling rijden we opnieuw een testrit en geven we [X] maanden garantie.</p><h2>De werkplaats</h2><p>[Korte beschrijving van de werkplaats, het team en de uitrusting — in te vullen door de eigenaar.]</p>',
            'meta_title' => 'Over BM-Matic | Specialist in automatische versnellingsbakken in Aalst',
            'meta_description' => 'Een gespecialiseerde werkplaats voor automatische versnellingsbakken in Aalst: eerst diagnose, een duidelijke offerte, herstelling met garantie.',
        ],
        'reviews' => [
            'slug' => 'reviews', 'nav_label' => 'Reviews', 'label' => 'Google-reviews', 'title' => 'Bestuurders die', 'highlight' => 'weer soepel schakelen.',
            'intro' => 'Wat onze klanten zeggen op Google.',
            'meta_title' => 'Klantenreviews | BM-Matic Aalst',
            'meta_description' => 'Google-reviews van bestuurders van wie de automatische, DSG- of CVT-versnellingsbak door BM-Matic in Aalst werd hersteld.',
        ],
        'contact' => [
            'slug' => 'contact', 'nav_label' => 'Contact', 'label' => 'Contact', 'title' => 'Slipt, schokt of staat uw bak', 'highlight' => 'in noodloop?',
            'intro' => 'Laat ons uw auto en de klachten weten. We antwoorden binnen één werkdag met een afspraak voor diagnose.',
            'meta_title' => 'Contact en afspraken | BM-Matic Aalst',
            'meta_description' => 'Vraag een afspraak aan voor de diagnose van uw versnellingsbak bij BM-Matic in Aalst. Antwoord binnen één werkdag.',
        ],
        'privacy' => [
            'slug' => 'privacybeleid', 'nav_label' => 'Privacybeleid', 'label' => 'Juridisch', 'title' => 'Privacybeleid',
            'intro' => 'Laatst bijgewerkt: [datum]',
            'body' => '<p><strong>[SJABLOON — laat deze tekst nakijken door een jurist of uw boekhouder, vul elke [haak] in en verwijder dan deze regel.]</strong></p><h2>Wie is verantwoordelijk</h2><p>:company, :address, ondernemingsnummer BE :vat (“wij”) is verwerkingsverantwoordelijke voor de persoonsgegevens die via :website worden verwerkt. Vragen over privacy: :email of :phone.</p><h2>Wat we verzamelen, waarom en op welke rechtsgrond</h2><ul><li><strong>Afspraakaanvragen</strong> — naam, telefoonnummer, e-mailadres, merk en model van de wagen, type versnellingsbak en de klachten die u beschrijft. We gebruiken ze om uw aanvraag te beantwoorden, de diagnose en, als u dat wenst, de herstelling in te plannen. Rechtsgrond: precontractuele maatregelen op uw verzoek (artikel 6, lid 1, b) AVG).</li><li><strong>Facturen en garantie</strong> — als er een herstelling volgt, de gegevens die nodig zijn voor de factuur en de garantie. Rechtsgrond: de overeenkomst en onze verplichtingen onder het Belgische boekhoud- en fiscaal recht (artikel 6, lid 1, b) en c) AVG).</li><li><strong>Bescherming tegen misbruik</strong> — een onomkeerbare hash van uw IP-adres en uw browsertype bij elke aanvraag, en een beveiligingslogboek van aanmeldingen op ons beheerpaneel. Rechtsgrond: ons gerechtvaardigd belang om de website en uw gegevens te beveiligen (artikel 6, lid 1, f) AVG).</li><li><strong>Bezoekstatistieken</strong> — enkel als u analytische cookies aanvaardt: anonieme statistieken over het gebruik van de website. Rechtsgrond: uw toestemming (artikel 6, lid 1, a) AVG), die u op elk moment kunt intrekken.</li></ul><p>We gebruiken uw gegevens niet voor geautomatiseerde beslissingen of profilering, en we sturen u geen reclame.</p><h2>Hoe lang we ze bewaren</h2><ul><li>Afspraakaanvragen zonder herstelling: [X] maanden na het laatste contact.</li><li>Facturen en de gegevens erop: zolang het Belgische boekhoud- en fiscaal recht dat vereist (momenteel tien jaar).</li><li>Beveiligingslogboek: hoogstens [X] dagen.</li></ul><h2>Wie ze ontvangt</h2><p>Enkel de medewerkers van :company die uw aanvraag behandelen, en de dienstverleners die in onze opdracht gegevens verwerken onder een verwerkersovereenkomst: onze hostingprovider [naam, land] en onze e-mailprovider [naam, land]. [Analyseprovider, indien ingeschakeld, en het land.] We verkopen uw gegevens nooit. Als een dienstverlener gegevens buiten de Europese Economische Ruimte bewaart, is die doorgifte gedekt door de modelcontractbepalingen van de Europese Commissie of een adequaatheidsbesluit.</p><p>De Google-reviews op deze website worden door onze server opgehaald; uw bezoek stuurt geen gegevens naar Google.</p><h2>Uw rechten</h2><p>U kunt ons vragen om uw gegevens in te zien, te verbeteren of te wissen, de verwerking te beperken of er bezwaar tegen te maken, en ze in een overdraagbaar formaat te ontvangen. Als de verwerking op toestemming steunt, kunt u die op elk moment intrekken. Stuur uw verzoek naar :email; we antwoorden binnen een maand.</p><p>Bent u niet tevreden met ons antwoord, dan kunt u klacht indienen bij de Gegevensbeschermingsautoriteit, Drukpersstraat 35, 1000 Brussel, www.gegevensbeschermingsautoriteit.be.</p><h2>Beveiliging</h2><p>De website is enkel bereikbaar via een versleutelde verbinding, de toegang tot het beheerpaneel is beschermd met sterke wachtwoorden en optionele tweestapsverificatie, en opgeslagen geheimen zijn versleuteld.</p><h2>Wijzigingen</h2><p>We kunnen dit beleid aanpassen; de datum bovenaan toont de laatste versie. Het valt onder de Algemene Verordening Gegevensbescherming (EU) 2016/679 en de Belgische wet van 30 juli 2018 betreffende de bescherming van natuurlijke personen met betrekking tot de verwerking van persoonsgegevens.</p><h2>Cookies</h2><p>Zie ons cookiebeleid.</p>',
            'meta_title' => 'Privacybeleid | BM-Matic',
            'meta_description' => 'Hoe BM-Matic persoonsgegevens uit afspraakaanvragen en websitebezoeken verwerkt.',
        ],
        'cookies' => [
            'slug' => 'cookiebeleid', 'nav_label' => 'Cookiebeleid', 'label' => 'Juridisch', 'title' => 'Cookiebeleid',
            'intro' => 'Laatst bijgewerkt: [datum]',
            'body' => '<p><strong>[SJABLOON — vergelijk de lijst hieronder met de website zoals ze gepubliceerd is en verwijder dan deze regel.]</strong></p><p>Cookies zijn kleine bestanden die een website in uw browser bewaart. Volgens de Belgische wet van 13 juni 2005 betreffende de elektronische communicatie en de AVG plaatsen we zonder te vragen enkel cookies die strikt noodzakelijk zijn; voor alle andere vragen we eerst uw toestemming.</p><h2>Strikt noodzakelijke cookies (altijd aan)</h2><ul><li><strong>bm_lang</strong> — onthoudt de taal die u koos. 1 jaar.</li><li><strong>bm_consent</strong> — onthoudt uw cookiekeuze, zodat we het niet op elke pagina opnieuw vragen. 180 dagen.</li><li><strong>bm_session</strong> (<strong>__Host-bm_session</strong> via HTTPS) — beveiligt het afspraakformulier tegen misbruik. Verdwijnt wanneer u de browser sluit.</li></ul><h2>Analytische cookies (enkel met uw toestemming)</h2><p>Als u analyse aanvaardt, meten we bezoeken met [analyseprovider — naam, land, cookienamen en bewaartermijnen]. Deze cookies worden pas geplaatst nadat u aanvaardt en verwijderd wanneer u uw toestemming intrekt.</p><p>We gebruiken geen reclame- of socialemediacookies. Google-reviews en de foto’s van de schrijvers komen van ons eigen domein en plaatsen geen cookies.</p><h2>Uw keuze wijzigen</h2><p>Gebruik “Cookie-instellingen” onderaan elke pagina om op elk moment toestemming te geven of in te trekken. U kunt cookies ook verwijderen in de instellingen van uw browser.</p><p>Vragen: :email. Hoe we met persoonsgegevens omgaan, leest u in ons privacybeleid.</p>',
            'meta_title' => 'Cookiebeleid | BM-Matic',
            'meta_description' => 'Welke cookies de website van BM-Matic gebruikt en hoe u uw keuze wijzigt.',
        ],
        'terms' => [
            'slug' => 'algemene-voorwaarden', 'nav_label' => 'Algemene voorwaarden', 'label' => 'Juridisch', 'title' => 'Algemene voorwaarden',
            'intro' => 'Laatst bijgewerkt: [datum]',
            'body' => '<p><strong>[SJABLOON — laat deze voorwaarden nakijken door een jurist, vul elke [haak] in en verwijder dan deze regel.]</strong></p><h2>Wie wij zijn</h2><p>:company, :address, ondernemingsnummer BE :vat, :email, :phone.</p><h2>Toepassingsgebied</h2><p>Deze voorwaarden gelden voor elke diagnose, offerte en herstelling die wij uitvoeren. Wijken ze af van voorwaarden die u voorstelt, dan gelden de onze, tenzij we schriftelijk iets anders overeenkwamen. Niets in deze voorwaarden beperkt de rechten die consumenten hebben onder het Belgische recht.</p><h2>Prijzen, diagnose en offertes</h2><p>Alle prijzen voor consumenten zijn inclusief btw. Een diagnose kost [bedrag incl. btw], [dat in mindering wordt gebracht als u de herstelling door ons laat uitvoeren]. Een offerte is [X] dagen geldig. We beginnen pas aan een herstelling nadat u de offerte schriftelijk of mondeling goedkeurde; blijkt er extra werk nodig, dan nemen we eerst contact op en voeren we het enkel met uw akkoord uit.</p><h2>Uw wagen en de vervangen onderdelen</h2><p>Vervangen onderdelen liggen op verzoek voor u klaar wanneer u de wagen ophaalt, tenzij ze in een ruilprogramma van de fabrikant worden omgeruild. Voor een wagen die niet wordt opgehaald binnen [X] dagen nadat we meldden dat hij klaar is, kunnen we stallingskosten van [bedrag] per dag aanrekenen.</p><h2>Betaling</h2><p>Facturen zijn betaalbaar [bij afhaling / binnen X dagen]. [Voorwaarden bij laattijdige betaling — voor consumenten zijn herinneringen, interesten en kosten beperkt door boek XIX van het Wetboek van economisch recht.] [Retentierecht: we mogen de wagen bijhouden tot de factuur betaald is.]</p><h2>Garantie</h2><p>Op herstellingen geldt een commerciële garantie van [X] maanden op onderdelen en werkuren, op voorwaarde dat de wagen normaal gebruikt en volgens het schema van de fabrikant onderhouden wordt. [Uitsluitingen.] Deze garantie komt bovenop de wettelijke garantie die consumenten hebben op de geleverde onderdelen (twee jaar voor nieuwe goederen) en beperkt die nooit.</p><h2>Aansprakelijkheid</h2><p>[Aansprakelijkheidsclausule — het Belgische recht laat niet toe aansprakelijkheid uit te sluiten voor opzet, zware fout, of overlijden of lichamelijk letsel.]</p><h2>Klachten en geschillen</h2><p>Meld een klacht zo snel mogelijk aan :email; we antwoorden binnen [X] werkdagen. Consumenten kunnen ook terecht bij de Consumentenombudsdienst (www.consumentenombudsdienst.be). Het Belgische recht is van toepassing. Geschillen met ondernemingen worden behandeld door de rechtbanken van [gerechtelijk arrondissement]; consumenten behouden het recht om zich te wenden tot de rechtbank die de wet hun volgens hun woonplaats toekent.</p>',
            'meta_title' => 'Algemene voorwaarden | BM-Matic',
            'meta_description' => 'Algemene voorwaarden voor diagnoses, offertes en herstellingen door BM-Matic.',
        ],
    ],
    'sections' => [
        'topbar' => [],
        'header' => [],
        'hero' => [
            'label' => 'Specialisten in automatische versnellingsbakken · Aalst',
            'title' => 'Herstelling van versnellingsbakken, gebouwd om', 'highlight' => 'perfect te schakelen.',
            'intro' => 'Diagnose, herstelling en revisie van automatische, DSG- en CVT-versnellingsbakken van alle merken. We testen eerst, leggen uit wat we vinden en geven u een duidelijke offerte.',
            'extra' => ['cta' => 'Diagnose boeken', 'call' => 'Bel de werkplaats', 'rating' => 'uit :count Google-reviews', 'caption' => 'DOORSNEDE A–A · SCHEMA', 'schematic' => 'Schema van de transmissie', 'legend' => ['01 Koppelomvormer', '02 Planetaire tandwielsets', '03 Klepblok', '04 TCU-software']],
        ],
        'stats' => ['label' => 'Kerncijfers'],
        'services' => ['label' => 'Diensten', 'title' => 'Elke automaat.', 'highlight' => 'Eén specialist.', 'extra' => ['link' => 'Alle diensten']],
        'process' => ['label' => 'Hoe we werken', 'title' => 'Eerst diagnose.', 'highlight' => 'Dan de offerte.'],
        'transmissions' => ['label' => 'TRANSMISSIES WAAR WE AAN WERKEN'],
        'reviews' => ['label' => 'Google-reviews', 'title' => 'Bestuurders die', 'highlight' => 'weer soepel schakelen.', 'extra' => ['count' => ':count reviews op Google', 'read_all' => 'Alles lezen', 'empty_title' => 'Reviews komen eraan', 'empty_text' => 'Onze Google-reviews verschijnen hier binnenkort. Lees ze intussen op Google.', 'google' => 'Lees onze reviews op Google']],
        'contact' => ['label' => 'Contact', 'title' => 'Slipt, schokt of staat uw bak in noodloop?', 'intro' => 'Laat ons uw auto en de klachten weten. We antwoorden binnen één werkdag met een afspraak voor diagnose.', 'extra' => ['form_title' => 'Afspraak aanvragen', 'submit' => 'Aanvraag versturen', 'map' => 'KAART — AALST']],
        'footer' => [],
    ],
    'services' => [
        'diagnostics' => [
            'slug' => 'diagnose', 'title' => 'Diagnose en foutcodes uitlezen', 'menu_title' => 'Diagnose', 'menu_sub' => 'Foutcodes en testrit', 'short_title' => 'Diagnose',
            'summary' => 'Testrit, live data en foutcodes uitgelezen voordat er iets wordt geopend.',
            'body' => '<p>De meeste klachten — slippen, harde of late schakelingen, trillingen, een waarschuwingslampje of noodloop — kunnen verschillende oorzaken hebben. Op goed geluk onderdelen vervangen is duur. Wij zoeken eerst de oorzaak.</p><h2>Wat de diagnose omvat</h2><ul><li>Een testrit om de klacht te reproduceren</li><li>Foutcodes en live data van de transmissiecomputer uitlezen</li><li>Controle van oliepeil en -toestand</li><li>Adaptatiewaarden en softwareversie</li></ul><p>U krijgt een duidelijke uitleg en een vaste offerte voor de herstelling. Er begint niets zonder uw akkoord.</p>',
            'meta_title' => 'Diagnose van uw versnellingsbak | BM-Matic Aalst',
            'meta_description' => 'Testrit, foutcodes, live data en oliecontrole vóór elke herstelling. Een duidelijke diagnose en vaste offerte voor uw automaat.',
        ],
        'overhaul' => [
            'slug' => 'revisie-versnellingsbak', 'title' => 'Revisie van versnellingsbakken', 'menu_title' => 'Revisie versnellingsbak', 'menu_sub' => 'Volledige revisie', 'short_title' => 'Revisie',
            'summary' => 'Volledige revisie van automatische versnellingsbakken met nieuwe slijtdelen.',
            'body' => '<p>Is interne slijtage de oorzaak, dan brengt een volledige revisie de bak terug naar specificatie — meestal voor een fractie van de prijs van een nieuwe.</p><h2>Wat we doen</h2><ul><li>Demontage, volledig uit elkaar halen en reinigen</li><li>Nieuwe koppelingen, keerringen, pakkingen en filters</li><li>Controle en vervanging van versleten onderdelen</li><li>Montage, olie vullen, adaptatie en testrit</li></ul><p>Op elke revisie geven we [X] maanden garantie.</p>',
            'meta_title' => 'Revisie van automatische versnellingsbakken | BM-Matic Aalst',
            'meta_description' => 'Volledige revisie van automaten met nieuwe koppelingen, keerringen en filters, adaptatie en testrit. [X] maanden garantie.',
        ],
        'flush' => [
            'slug' => 'atf-spoeling', 'title' => 'Dynamische ATF-spoeling', 'menu_title' => 'ATF-spoeling', 'menu_sub' => 'Dynamische olieverversing', 'short_title' => 'ATF-spoeling',
            'summary' => 'Volledige olieverversing met de juiste oliespecificatie voor uw versnellingsbak.',
            'body' => '<p>Olie in een automatische versnellingsbak verslijt: ze verliest haar wrijvingseigenschappen en verzamelt slijtdeeltjes. Een gewone aftap ververst maar een deel. Een dynamische spoeling ververst bijna alles terwijl de bak draait.</p><h2>Waarom het belangrijk is</h2><ul><li>Soepeler schakelen en minder trillingen</li><li>Minder slijtage aan koppelingen en klepblok</li><li>Exact de oliespecificatie die de constructeur voorschrijft</li></ul><p>We controleren eerst de olietoestand en adviseren of een spoeling zinvol is.</p>',
            'meta_title' => 'Dynamische ATF-spoeling | BM-Matic Aalst',
            'meta_description' => 'Bijna volledige verversing van de automaatolie met de juiste specificatie voor uw versnellingsbak.',
        ],
        'converter' => [
            'slug' => 'koppelomvormer-herstelling', 'title' => 'Herstelling koppelomvormer', 'menu_title' => 'Koppelomvormer', 'menu_sub' => 'Trillingen en slippen', 'short_title' => 'Koppelomvormer',
            'summary' => 'Trillingen, slippen of oververhitting opgespoord en hersteld.',
            'body' => '<p>Een versleten overbruggingskoppeling in de koppelomvormer veroorzaakt trillingen bij constante snelheid, een hoger toerental en oververhitte olie. We bevestigen de oorzaak met live data voordat we beginnen.</p><h2>Herstelling</h2><ul><li>Demontage en controle van de koppelomvormer</li><li>Vervanging of revisie van de overbruggingskoppeling en afdichtingen</li><li>Olieverversing en adaptatie</li></ul>',
            'meta_title' => 'Herstelling van de koppelomvormer | BM-Matic Aalst',
            'meta_description' => 'Trillingen, slippen of oververhitting door de koppelomvormer opgespoord en hersteld.',
        ],
        'mechatronic' => [
            'slug' => 'mechatronica-klepblok', 'title' => 'Mechatronica en klepblok', 'menu_title' => 'Mechatronica en klepblok', 'menu_sub' => 'Solenoïdes en regeleenheden', 'short_title' => 'Mechatronica',
            'summary' => 'Solenoïdes, drukregeling en regeleenheden getest en hersteld.',
            'body' => '<p>Het klepblok en de mechatronica sturen elke schakeling: solenoïdes, drukregelaars en sensoren. Defecten veroorzaken harde schakelingen, noodloop en foutcodes.</p><h2>Herstelling</h2><ul><li>Test van solenoïdes en drukken</li><li>Reiniging en revisie van het klepblok</li><li>Herstelling of vervanging van de mechatronica, met programmering en adaptatie</li></ul>',
            'meta_title' => 'Herstelling mechatronica en klepblok | BM-Matic Aalst',
            'meta_description' => 'Solenoïdes, drukregeling en transmissiecomputers getest, hersteld en ingeleerd.',
        ],
        'dsg' => [
            'slug' => 'dsg-dct-cvt', 'title' => 'DSG, DCT en CVT', 'menu_title' => 'DSG, DCT en CVT', 'menu_sub' => 'Dubbele koppeling en CVT', 'short_title' => 'DSG en CVT',
            'summary' => 'Dubbelekoppelings- en continu variabele transmissies, inclusief koppelingspakketten.',
            'body' => '<p>Dubbelekoppelingsbakken (DSG, DCT, PowerShift) en CVT’s vragen eigen expertise. We herstellen droge en natte dubbele koppelingen en CVT-riemen, -poelies en -klepblokken.</p><h2>Typische werken</h2><ul><li>Vervanging van koppelingspakketten en basisinstellingen</li><li>Herstelling van de mechatronica voor DSG DQ200, DQ250 en DQ381</li><li>CVT-diagnose, olieonderhoud en herstelling</li></ul>',
            'meta_title' => 'Herstelling DSG, DCT en CVT | BM-Matic Aalst',
            'meta_description' => 'Dubbelekoppelingsbakken (DSG, DCT, PowerShift) en CVT’s gediagnosticeerd en hersteld, inclusief koppelingspakketten.',
        ],
    ],
    'transmission_types' => [
        ['label' => 'ZF 6HP / 8HP', 'description' => 'BMW, Audi, Jaguar, Land Rover en meer.'],
        ['label' => 'Aisin', 'description' => 'Volvo, Toyota, Peugeot, Citroën, Mini en andere.'],
        ['label' => 'Mercedes 7G / 9G-Tronic', 'description' => 'Personenwagens en bestelwagens van Mercedes-Benz.'],
        ['label' => 'VAG DSG', 'description' => 'Dubbelekoppelingsbakken van Volkswagen, Audi, Škoda en Seat.'],
        ['label' => 'Ford PowerShift', 'description' => 'Dubbelekoppelingsbakken van Ford.'],
        ['label' => 'Jatco CVT', 'description' => 'CVT’s van Nissan, Renault, Mitsubishi en Suzuki.'],
        ['label' => 'BMW Steptronic', 'description' => 'Automatische versnellingsbakken van BMW.'],
        ['label' => 'Volvo Geartronic', 'description' => 'Automatische versnellingsbakken van Volvo.'],
    ],
    'process_steps' => [
        ['title' => 'Online boeken', 'text' => 'Kies een tijdslot of bel ons. Vertel ons over de auto en de klachten.'],
        ['title' => 'Testen en uitlezen', 'text' => 'Testrit, foutcodes en oliecontrole — vóór elke herstelling.'],
        ['title' => 'Duidelijke offerte', 'text' => 'U krijgt de diagnose en een vaste offerte. Er begint niets zonder uw akkoord.'],
        ['title' => 'Herstelling en garantie', 'text' => 'We herstellen, rijden opnieuw een testrit en geven [X] maanden garantie.'],
    ],
    'stats' => [
        ['value' => '[XX]+', 'label' => 'Jaar ervaring'],
        ['value' => '[X.XXX]+', 'label' => 'Versnellingsbakken hersteld'],
        ['value' => '[X] maanden', 'label' => 'Garantie op herstellingen'],
        ['value' => 'Alle merken', 'label' => 'Automaat, DSG en CVT'],
    ],
];
