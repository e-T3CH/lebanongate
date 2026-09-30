<?php

declare(strict_types=1);

namespace BMMatic\Database\Seeders;

use BMMatic\Core\Database;

/**
 * Ready-to-use texts for the optional customer email per status (Settings → Email → Emails to customers), in the
 * three languages. Every status stays switched off until the workshop turns it on. Existing texts are never
 * overwritten. Placeholders: :name, :car, :status, :site, :phone.
 */
final class StatusEmailSeeder
{
    private const TEXTS = [
        'en' => [
            'new' => ['We received your request — :site', "Hello :name,\n\nThank you for your request about your :car. We will contact you within one working day to plan the diagnosis.\n\nQuestions in the meantime? Call us on :phone.\n\nKind regards,\n:site"],
            'confirmed' => ['Your appointment is confirmed — :site', "Hello :name,\n\nYour appointment for your :car is confirmed. If the date or time no longer suits you, please call us on :phone.\n\nKind regards,\n:site"],
            'diagnosis' => ['We are examining your :car — :site', "Hello :name,\n\nYour :car is with us and the diagnosis has started. We will call you as soon as we know what is needed and what it costs.\n\nKind regards,\n:site"],
            'quoted' => ['Your quote is ready — :site', "Hello :name,\n\nThe diagnosis of your :car is complete and your quote is ready. We will go through it with you by phone; you can also reach us on :phone.\n\nNothing is repaired without your approval.\n\nKind regards,\n:site"],
            'done' => ['Your :car is ready — :site', "Hello :name,\n\nThe work on your :car is finished and it is ready to be collected. Please call us on :phone if you would like to agree on a time.\n\nThank you for your trust.\n:site"],
            'cancelled' => ['Your request has been closed — :site', "Hello :name,\n\nYour request about your :car has been closed. If this is not what you expected, please call us on :phone.\n\nKind regards,\n:site"],
        ],
        'fr' => [
            'new' => ['Nous avons reçu votre demande — :site', "Bonjour :name,\n\nMerci pour votre demande concernant votre :car. Nous vous contactons dans un délai d’un jour ouvrable pour planifier le diagnostic.\n\nUne question entre-temps ? Appelez-nous au :phone.\n\nCordialement,\n:site"],
            'confirmed' => ['Votre rendez-vous est confirmé — :site', "Bonjour :name,\n\nVotre rendez-vous pour votre :car est confirmé. Si la date ou l’heure ne vous convient plus, appelez-nous au :phone.\n\nCordialement,\n:site"],
            'diagnosis' => ['Nous examinons votre :car — :site', "Bonjour :name,\n\nVotre :car est chez nous et le diagnostic a commencé. Nous vous appelons dès que nous savons ce qui est nécessaire et ce que cela coûte.\n\nCordialement,\n:site"],
            'quoted' => ['Votre devis est prêt — :site', "Bonjour :name,\n\nLe diagnostic de votre :car est terminé et votre devis est prêt. Nous le parcourons avec vous par téléphone ; vous pouvez aussi nous joindre au :phone.\n\nAucune réparation n’est effectuée sans votre accord.\n\nCordialement,\n:site"],
            'done' => ['Votre :car est prête — :site', "Bonjour :name,\n\nLes travaux sur votre :car sont terminés et vous pouvez venir la chercher. Appelez-nous au :phone pour convenir d’une heure.\n\nMerci pour votre confiance.\n:site"],
            'cancelled' => ['Votre demande a été clôturée — :site', "Bonjour :name,\n\nVotre demande concernant votre :car a été clôturée. Si ce n’est pas ce que vous attendiez, appelez-nous au :phone.\n\nCordialement,\n:site"],
        ],
        'nl' => [
            'new' => ['We ontvingen uw aanvraag — :site', "Beste :name,\n\nBedankt voor uw aanvraag over uw :car. We nemen binnen één werkdag contact op om de diagnose in te plannen.\n\nVragen in de tussentijd? Bel ons op :phone.\n\nMet vriendelijke groeten,\n:site"],
            'confirmed' => ['Uw afspraak is bevestigd — :site', "Beste :name,\n\nUw afspraak voor uw :car is bevestigd. Past de datum of het uur toch niet, bel ons dan op :phone.\n\nMet vriendelijke groeten,\n:site"],
            'diagnosis' => ['We onderzoeken uw :car — :site', "Beste :name,\n\nUw :car staat bij ons en de diagnose is begonnen. We bellen u zodra we weten wat er nodig is en wat het kost.\n\nMet vriendelijke groeten,\n:site"],
            'quoted' => ['Uw offerte is klaar — :site', "Beste :name,\n\nDe diagnose van uw :car is klaar en uw offerte ligt klaar. We overlopen ze telefonisch met u; u kunt ons ook bereiken op :phone.\n\nEr wordt niets hersteld zonder uw akkoord.\n\nMet vriendelijke groeten,\n:site"],
            'done' => ['Uw :car is klaar — :site', "Beste :name,\n\nDe werken aan uw :car zijn klaar en u kunt hem komen ophalen. Bel ons op :phone om een uur af te spreken.\n\nBedankt voor uw vertrouwen.\n:site"],
            'cancelled' => ['Uw aanvraag is afgesloten — :site', "Beste :name,\n\nUw aanvraag over uw :car is afgesloten. Is dat niet wat u verwachtte, bel ons dan op :phone.\n\nMet vriendelijke groeten,\n:site"],
        ],
    ];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return int texts added */
    public function run(): int
    {
        $added = 0;
        foreach (self::TEXTS as $lang => $statuses) {
            if ($this->db->first('languages', ['code' => $lang]) === null) {
                continue;
            }
            foreach ($statuses as $status => [$subject, $body]) {
                if ($this->db->first('appointment_status_emails', ['status' => $status, 'lang_code' => $lang]) === null) {
                    $this->db->insert('appointment_status_emails', ['status' => $status, 'lang_code' => $lang, 'subject' => $subject, 'body' => $body]);
                    $added++;
                }
            }
        }
        return $added;
    }
}
