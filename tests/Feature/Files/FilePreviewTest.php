<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\Support\CompoundFileWriter;
use Tests\TestCase;

/**
 * Aperçu des tableurs et des e-mails de la GED (route files.preview).
 */
class FilePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();

        Permission::findOrCreate('products-menu');
        Permission::findOrCreate('products-edit');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['products-menu', 'products-edit']);
        $this->actingAs($this->user);
    }

    private function attach(string $contents, string $name, string $mime = 'application/octet-stream'): File
    {
        $storage = app(FileStorageService::class);
        $file = $storage->storeContents($contents, $name, $mime);

        $product = Products::factory()->create(['picture' => null, 'drawing_file' => null, 'stl_file' => null, 'svg_file' => null, 'purchased' => 2]);
        $storage->attach($file, $product);

        return $file;
    }

    private function xlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chiffrage');
        $sheet->fromArray([
            ['Désignation', 'Qté', 'PU', 'Total'],
            ['Tôle 2 mm', 3, 12.5, '=B2*C2'],
        ]);
        $sheet->getStyle('C2:D2')->getNumberFormat()->setFormatCode('#,##0.00');

        $hidden = new Worksheet($spreadsheet, 'Paramètres');
        $spreadsheet->addSheet($hidden);
        $hidden->setCellValue('A1', 'marge');
        $hidden->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }

    private function eml(): string
    {
        $png = base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        $pdf = base64_encode('%PDF-1.4 devis');
        $html = quoted_printable_encode('<p>Bonjour, voici le <b>devis</b> sign&eacute;.</p><img src="cid:logo@client"><img src="https://tracker.example/pixel.gif"><script>alert(1)</script><a href="https://client.example" onclick="steal()">site</a>');

        return implode("\r\n", [
            'From: =?UTF-8?B?' . base64_encode('Jérôme Martin') . '?= <jerome@client.fr>',
            'To: "Achats, Usine" <achats@usine.fr>, bureau@usine.fr',
            'Cc: compta@client.fr',
            'Subject: =?ISO-8859-1?Q?Commande_sign=E9e?=',
            'Date: Mon, 3 Nov 2026 14:05:00 +0100 (CET)',
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="mixed"',
            '',
            'Préambule ignoré',
            '--mixed',
            'Content-Type: multipart/related; boundary="related"',
            '',
            '--related',
            'Content-Type: multipart/alternative; boundary="alt"',
            '',
            '--alt',
            'Content-Type: text/plain; charset=utf-8',
            '',
            'Bonjour, voici le devis signé.',
            '--alt',
            'Content-Type: text/html; charset=utf-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            $html,
            '--alt--',
            '--related',
            'Content-Type: image/png',
            'Content-Transfer-Encoding: base64',
            'Content-ID: <logo@client>',
            'Content-Disposition: inline',
            '',
            $png,
            '--related--',
            '--mixed',
            'Content-Type: application/pdf',
            'Content-Transfer-Encoding: base64',
            "Content-Disposition: attachment; filename*=UTF-8''devis%20n%C2%B012.pdf",
            '',
            $pdf,
            '--mixed--',
            '',
        ]);
    }

    private function msg(string $attachment): string
    {
        $utf16 = fn (string $text) => mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
        // 2026-11-03 13:05:00 UTC en FILETIME.
        $filetime = (1793711100 + 11644473600) * 10000000;
        $property = fn (int $type, int $id, string $value) => pack('vvV', $type, $id, 6) . str_pad($value, 8, "\0");

        $recipient = fn (string $name, string $smtp, int $type) => [
            '__substg1.0_3001001F' => $utf16($name),
            '__substg1.0_39FE001F' => $utf16($smtp),
            '__properties_version1.0' => str_repeat("\0", 8) . $property(0x0003, 0x0C15, pack('V', $type)),
        ];

        return CompoundFileWriter::build([
            '__substg1.0_0037001F' => $utf16('Plan modifié – indice B'),
            '__substg1.0_0C1A001F' => $utf16('Jean Dupont'),
            '__substg1.0_5D01001F' => $utf16('jean@client.fr'),
            '__substg1.0_1000001F' => $utf16('Texte brut'),
            '__substg1.0_10130102' => '<html><body><p>Voir le plan joint</p><script>alert(1)</script></body></html>',
            '__properties_version1.0' => str_repeat("\0", 32)
                . $property(0x0040, 0x0039, pack('P', $filetime))
                . $property(0x0003, 0x3FDE, pack('V', 65001)),
            '__recip_version1.0_#00000000' => $recipient('Bureau d\'études', 'be@usine.fr', 1),
            '__recip_version1.0_#00000001' => $recipient('Qualité', 'qualite@usine.fr', 2),
            '__attach_version1.0_#00000000' => [
                '__substg1.0_3707001F' => $utf16('plan-B.pdf'),
                '__substg1.0_370E001F' => $utf16('application/pdf'),
                '__substg1.0_37010102' => $attachment,
                '__properties_version1.0' => str_repeat("\0", 8),
            ],
        ]);
    }

    public function test_mails_are_accepted_by_the_ged(): void
    {
        $product = Products::factory()->create(['picture' => null, 'drawing_file' => null, 'stl_file' => null, 'svg_file' => null, 'purchased' => 2]);

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [
                UploadedFile::fake()->createWithContent('commande.eml', $this->eml()),
                UploadedFile::fake()->createWithContent('plan.msg', $this->msg('pdf')),
            ],
        ])->assertStatus(201)
            ->assertJsonPath('files.0.kind', FileKindResolver::KIND_MAIL)
            ->assertJsonPath('files.0.is_viewable', true);

        $file = File::where('original_file_name', 'commande.eml')->firstOrFail();
        $this->assertSame(route('files.preview', ['file' => $file->id]), $file->preview_url);
    }

    public function test_a_workbook_is_previewed_with_every_sheet_as_excel_displays_it(): void
    {
        $file = $this->attach($this->xlsx(), 'chiffrage.xlsx');

        $response = $this->getJson(route('files.preview', ['file' => $file->id]))->assertOk();

        $response->assertJsonPath('type', 'sheet');
        $response->assertJsonCount(2, 'sheets');
        $response->assertJsonPath('sheets.0.name', 'Chiffrage');
        $response->assertJsonPath('sheets.0.rows.0', ['Désignation', 'Qté', 'PU', 'Total']);
        $response->assertJsonPath('sheets.0.rows.1', ['Tôle 2 mm', '3', '12.50', '37.50']);
        $response->assertJsonPath('sheets.0.truncated', false);
        $response->assertJsonPath('sheets.1.hidden', true);
    }

    public function test_a_large_sheet_is_truncated_and_says_so(): void
    {
        config(['files.preview.max_rows' => 2, 'files.preview.max_columns' => 2]);
        $file = $this->attach($this->xlsx(), 'chiffrage.xlsx');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertOk()
            ->assertJsonPath('sheets.0.rows', [['Désignation', 'Qté'], ['Tôle 2 mm', '3']])
            ->assertJsonPath('sheets.0.total_columns', 4)
            ->assertJsonPath('sheets.0.truncated', true);
    }

    public function test_a_windows_csv_is_previewed_in_utf8(): void
    {
        $file = $this->attach(mb_convert_encoding("Code;Désignation\nA1;Tôle\n", 'Windows-1252', 'UTF-8'), 'export.csv', 'text/csv');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertOk()
            ->assertJsonPath('sheets.0.name', 'export')
            ->assertJsonPath('sheets.0.rows', [['Code', 'Désignation'], ['A1', 'Tôle']]);
    }

    public function test_an_eml_is_previewed_with_a_cleaned_body(): void
    {
        $file = $this->attach($this->eml(), 'commande.eml');

        $response = $this->getJson(route('files.preview', ['file' => $file->id]))->assertOk();

        $response->assertJsonPath('type', 'mail');
        $response->assertJsonPath('subject', 'Commande signée');
        $response->assertJsonPath('from', 'Jérôme Martin <jerome@client.fr>');
        $response->assertJsonPath('to', ['"Achats, Usine" <achats@usine.fr>', 'bureau@usine.fr']);
        $response->assertJsonPath('cc', ['compta@client.fr']);
        $response->assertJsonPath('date', '2026-11-03T14:05:00+01:00');
        $response->assertJsonPath('text', 'Bonjour, voici le devis signé.');

        $html = $response->json('html');
        $this->assertStringContainsString('<b>devis</b>', $html);
        $this->assertStringContainsString('src="data:image/png;base64,', $html, 'The inline picture is embedded.');
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('tracker.example', $html, 'A remote image must not be loaded.');
        $this->assertStringContainsString('href="https://client.example"', $html);

        // The embedded picture is not repeated among the attachments.
        $response->assertJsonCount(1, 'attachments');
        $response->assertJsonPath('attachments.0.name', 'devis n°12.pdf');
        $response->assertJsonPath('attachments.0.mime', 'application/pdf');
    }

    public function test_an_attachment_of_an_eml_is_downloaded_as_an_opaque_file(): void
    {
        $file = $this->attach($this->eml(), 'commande.eml');
        $url = $this->getJson(route('files.preview', ['file' => $file->id]))->json('attachments.0.download_url');

        $response = $this->get($url)->assertOk();

        $this->assertSame('%PDF-1.4 devis', $response->getContent());
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString("filename*=utf-8''devis%20n%C2%B012.pdf", $response->headers->get('Content-Disposition'));

        $this->get(route('files.preview.attachment', ['file' => $file->id, 'index' => 9]))->assertNotFound();
    }

    public function test_an_outlook_msg_is_previewed(): void
    {
        // Above the 4096 bytes cut-off: read from regular sectors, not the mini stream.
        $pdf = '%PDF-1.4 ' . str_repeat('x', 6000);
        $file = $this->attach($this->msg($pdf), 'plan.msg');

        $response = $this->getJson(route('files.preview', ['file' => $file->id]))->assertOk();

        $response->assertJsonPath('subject', 'Plan modifié – indice B');
        $response->assertJsonPath('from', 'Jean Dupont <jean@client.fr>');
        $response->assertJsonPath('to', ['Bureau d\'études <be@usine.fr>']);
        $response->assertJsonPath('cc', ['Qualité <qualite@usine.fr>']);
        $response->assertJsonPath('date', '2026-11-03T13:05:00+00:00');
        $response->assertJsonPath('text', 'Texte brut');
        $this->assertStringContainsString('Voir le plan joint', $response->json('html'));
        $this->assertStringNotContainsString('<script', $response->json('html'));
        $response->assertJsonPath('attachments.0.name', 'plan-B.pdf');
        $response->assertJsonPath('attachments.0.size', strlen($pdf));

        $this->assertSame($pdf, $this->get($response->json('attachments.0.download_url'))->assertOk()->getContent());
    }

    public function test_a_small_msg_attachment_is_read_from_the_mini_stream(): void
    {
        $file = $this->attach($this->msg('%PDF-1.4 court'), 'plan.msg');
        $url = $this->getJson(route('files.preview', ['file' => $file->id]))->json('attachments.0.download_url');

        $this->assertSame('%PDF-1.4 court', $this->get($url)->assertOk()->getContent());
    }

    public function test_an_outlook_message_saved_as_eml_is_recognised_by_its_content(): void
    {
        $file = $this->attach($this->msg('%PDF'), 'Demande de renseignements.eml');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertOk()
            ->assertJsonPath('subject', 'Plan modifié – indice B');
    }

    public function test_a_utf16_eml_is_read(): void
    {
        $file = $this->attach("\xFF\xFE" . mb_convert_encoding($this->eml(), 'UTF-16LE', 'UTF-8'), 'unicode.eml');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertOk()
            ->assertJsonPath('subject', 'Commande signée')
            ->assertJsonPath('from', 'Jérôme Martin <jerome@client.fr>');
    }

    public function test_a_thunderbird_eml_with_bom_and_mbox_line_is_read(): void
    {
        $raw = "\xEF\xBB\xBF\r\nFrom - Tue Nov 03 14:05:00 2026\r\nX-Mozilla-Status: 0001\r\n"
            . "Subject: Demande de renseignements\r\nFrom: prospect@exemple.fr\r\n \r\nBonjour,\r\nquel délai ?\r\n";
        $file = $this->attach($raw, 'thunderbird.eml');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertOk()
            ->assertJsonPath('subject', 'Demande de renseignements')
            ->assertJsonPath('from', 'prospect@exemple.fr')
            ->assertJsonPath('text', "Bonjour,\nquel délai ?\n");
    }

    public function test_a_web_page_saved_as_eml_is_reported_as_such(): void
    {
        // Ce que donne « Enregistrer sous » sur Outlook en ligne quand le navigateur sauve la page.
        $file = $this->attach('<!doctype html><html dir=ltr><head><title>Outlook</title></head><body>JavaScript must be enabled.</body></html>', 'Demande de renseignements.eml');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertStatus(422)
            ->assertJsonPath('reason', 'not_a_mail');
    }

    public function test_an_eml_without_anything_recognisable_is_unreadable_not_empty(): void
    {
        $file = $this->attach(str_repeat("\x01\x02\x8F\x00binaire", 40), 'bruit.eml');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertStatus(422)
            ->assertJsonPath('reason', 'unreadable');
    }

    public function test_an_unreadable_file_answers_a_reason_instead_of_failing(): void
    {
        $file = $this->attach('pas un fichier Outlook', 'abime.msg');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertStatus(422)
            ->assertJsonPath('reason', 'unreadable');
    }

    public function test_a_file_above_the_limit_is_not_opened(): void
    {
        config(['files.preview.max_bytes' => 10]);
        $file = $this->attach($this->eml(), 'commande.eml');

        $this->getJson(route('files.preview', ['file' => $file->id]))
            ->assertStatus(422)
            ->assertJsonPath('reason', 'too_large');
    }

    public function test_kinds_rendered_by_the_browser_have_no_server_preview(): void
    {
        $file = $this->attach('%PDF-1.4', 'plan.pdf', 'application/pdf');

        $this->assertNull($file->preview_url);
        $this->getJson(route('files.preview', ['file' => $file->id]))->assertNotFound();
    }

    public function test_an_employee_folder_mail_stays_confidential(): void
    {
        $storage = app(FileStorageService::class);
        $file = $storage->storeContents($this->eml(), 'arret.eml', 'message/rfc822');
        $employee = User::factory()->create();
        $storage->attach($file, $employee);

        $this->getJson(route('files.preview', ['file' => $file->id]))->assertForbidden();
        $this->get(route('files.preview.attachment', ['file' => $file->id, 'index' => 0]))->assertForbidden();

        $this->actingAs($employee)->getJson(route('files.preview', ['file' => $file->id]))->assertOk();
    }
}
