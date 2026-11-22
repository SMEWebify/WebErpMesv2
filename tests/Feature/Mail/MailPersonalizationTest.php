<?php

namespace Tests\Feature\Mail;

use App\Mail\DocumentMail;
use App\Models\Admin\EmailTemplate;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\Quotes;
use App\Services\Mail\MailPersonalization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Signature par utilisateur et variables des modèles d'e-mail : remplacées à
 * l'ouverture de l'écran d'envoi, l'utilisateur relit ce qui partira.
 */
class MailPersonalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->user = User::factory()->create(['name' => 'Jeanne Martin']);
        $this->actingAs($this->user);

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create(['name' => 'Métallerie', 'curency' => 'EUR', 'pdf_header_font_color' => '#ddd']));
    }

    private function quote(): Quotes
    {
        $company = Companies::factory()->create(['label' => 'Durand & Fils', 'client_type' => 1]);
        $contact = CompaniesContacts::factory()->create([
            'companies_id' => $company->id, 'civility' => 'M.', 'first_name' => 'Paul', 'name' => 'Durand',
        ]);

        return Quotes::factory()->create([
            'code' => 'QT-0042', 'label' => 'Garde-corps', 'statu' => 1,
            'companies_id' => $company->id, 'companies_contacts_id' => $contact->id,
        ]);
    }

    public function test_the_variables_of_a_template_are_replaced_and_escaped(): void
    {
        $quote = $this->quote();
        EmailTemplate::create([
            'document_type' => 'quote',
            'subject' => 'Offre {code} - {entreprise}',
            'content' => '<p>Bonjour {contact},</p><p>Voici le devis {code} pour {client}.</p><p>{utilisateur}</p>',
        ]);

        $response = $this->get(route('email.create', ['type' => 'quote', 'id' => $quote->id]))->assertOk();

        // {code} dans l'objet : le numéro n'est pas ajouté une seconde fois.
        $this->assertSame('Offre QT-0042 - Métallerie', $response->viewData('object'));
        $message = $response->viewData('message');
        $this->assertStringContainsString('Bonjour M. Paul Durand,', $message);
        $this->assertStringContainsString('pour Durand &amp; Fils.', $message);
        $this->assertStringContainsString('<p>Jeanne Martin</p>', $message);
    }

    public function test_a_subject_without_code_still_gets_the_number_appended(): void
    {
        $quote = $this->quote();
        EmailTemplate::create(['document_type' => 'quote', 'subject' => 'Votre devis', 'content' => '<p>Bonjour</p>']);

        $response = $this->get(route('email.create', ['type' => 'quote', 'id' => $quote->id]))->assertOk();

        $this->assertSame('Votre devis QT-0042', $response->viewData('object'));
        $this->assertSame('<p>Bonjour</p>', $response->viewData('message'));
    }

    public function test_the_signature_is_appended_or_placed_where_the_template_asks(): void
    {
        $this->user->email_signature = '<p><strong>Jeanne Martin</strong></p><p>06 00 00 00 00</p>';

        $appended = MailPersonalization::withSignature('<p>Bonjour</p>', $this->user);
        $this->assertSame('<p>Bonjour</p><p><br></p><p><strong>Jeanne Martin</strong></p><p>06 00 00 00 00</p>', $appended);

        $placed = MailPersonalization::withSignature('<p>Bonjour</p><p>{signature}</p><p>PS</p>', $this->user);
        $this->assertSame('<p>Bonjour</p><p><strong>Jeanne Martin</strong></p><p>06 00 00 00 00</p><p>PS</p>', $placed);
    }

    public function test_without_signature_the_message_is_unchanged_and_the_variable_removed(): void
    {
        $this->assertSame('<p>Bonjour</p>', MailPersonalization::withSignature('<p>Bonjour</p>', $this->user));
        $this->assertSame('<p>Bonjour</p>', MailPersonalization::withSignature('<p>Bonjour</p><p>{signature}</p>', $this->user));
    }

    public function test_the_signature_is_cleaned_of_executable_markup(): void
    {
        $this->put(route('profile.email-signature.update'), [
            'email_signature' => '<p>Jeanne<img src="x" onerror="alert(1)"></p><script>alert(2)</script>',
        ])->assertRedirect();

        $signature = $this->user->fresh()->email_signature;
        $this->assertStringContainsString('Jeanne', $signature);
        $this->assertStringNotContainsString('onerror', $signature);
        $this->assertStringNotContainsString('<script', $signature);
    }

    public function test_an_emptied_editor_removes_the_signature(): void
    {
        $this->user->forceFill(['email_signature' => '<p>Jeanne</p>'])->save();

        $this->put(route('profile.email-signature.update'), ['email_signature' => '<p><br></p>'])->assertRedirect();

        $this->assertNull($this->user->fresh()->email_signature);
    }

    public function test_the_signature_shows_in_the_sending_screen(): void
    {
        $this->user->forceFill(['email_signature' => '<p>Jeanne Martin</p>'])->save();
        $quote = $this->quote();

        $response = $this->get(route('email.create', ['type' => 'quote', 'id' => $quote->id]))->assertOk();

        $this->assertSame('<p><br></p><p>Jeanne Martin</p>', $response->viewData('message'));
    }

    public function test_the_sent_mail_no_longer_shows_the_class_name_and_internal_id(): void
    {
        $quote = $this->quote();

        $html = (new DocumentMail(
            document: $quote,
            subjectText: 'Devis',
            messageContent: '<p>Bonjour</p>',
            fromAddress: 'erp@example.test',
            fromName: 'ERP',
        ))->render();

        $this->assertStringContainsString('<p>Bonjour</p>', $html);
        $this->assertStringNotContainsString('Quotes #', $html);
    }
}
