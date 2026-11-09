<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\TranslationManager;
use App\Models\Translation;
use App\Models\User;
use App\Support\MachineTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** The translation catalogue lives in the database: one row per key with an English and an Arabic text. */
class TranslationsInDatabaseTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();

        $this->admin = new User(['name' => 'Root Admin', 'username' => 'root', 'email' => 'root@x.test', 'password' => 'Secret123!']);
        $this->admin->role = UserRole::SuperAdmin;
        $this->admin->save();
    }

    private function freshHttp(): void
    {
        app()->forgetInstance(\Illuminate\Http\Client\Factory::class);
        Http::clearResolvedInstance(\Illuminate\Http\Client\Factory::class);
    }

    private function fakeService(string $translated = 'نص مترجم'): void
    {
        $this->freshHttp();
        Http::fake(['*' => Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => $translated]])]);
    }

    private function page(): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::actingAs($this->admin)->test(TranslationManager::class);
    }

    private function keys($page): array
    {
        return $page->viewData('rows')->pluck('key')->all();
    }

    // ---- the catalogue itself ------------------------------------------------------------------------

    public function test_the_table_has_one_row_per_key_with_an_english_and_an_arabic_column(): void
    {
        $this->assertFileDoesNotExist(base_path('lang/ar.json'));
        $this->assertGreaterThan(400, Translation::count());                       // imported by the migrations

        $row = Translation::where('key', 'Approve task')->firstOrFail();
        $this->assertSame('Approve task', $row->en);
        $this->assertSame('الموافقة على المهمة', $row->ar);
        $this->assertSame('code', $row->type);
    }

    public function test_the_texts_shown_on_screen_come_from_the_database(): void
    {
        app()->setLocale('ar');
        $this->assertSame('الموافقة على المهمة', __('Approve task'));
        app()->setLocale('en');
        $this->assertSame('Approve task', __('Approve task'));
    }

    public function test_editing_the_arabic_text_changes_it_everywhere_at_once(): void
    {
        app()->setLocale('ar');
        $this->assertSame('الموافقة على المهمة', __('Approve task'));                // loaded (and cached)

        Translation::where('key', 'Approve task')->firstOrFail()->update(['ar' => 'اعتماد المهمة']);

        app('translator')->setLoaded([]);                                            // a new request
        $this->assertSame('اعتماد المهمة', __('Approve task'));
    }

    public function test_editing_the_english_text_changes_the_english_interface(): void
    {
        Translation::where('key', 'Approve task')->firstOrFail()->update(['en' => 'Sign off task']);

        app('translator')->setLoaded([]);
        app()->setLocale('en');

        $this->assertSame('Sign off task', __('Approve task'));
    }

    public function test_an_empty_translation_falls_back_to_the_key(): void
    {
        Translation::where('key', 'Approve task')->firstOrFail()->update(['ar' => null]);
        app('translator')->setLoaded([]);
        app()->setLocale('ar');

        $this->assertSame('Approve task', __('Approve task'));
    }

    // ---- the page --------------------------------------------------------------------------------------

    public function test_only_the_super_admin_opens_the_translations_page(): void
    {
        $this->get('/admin/translations')->assertRedirect('/login');
        foreach ([$this->employee, $this->manager, $this->hr] as $user) {
            $this->actingAs($user)->get('/'.$user->url_prefix.'/translations')->assertForbidden();
            $this->actingAs($user)->get('/admin/translations')->assertNotFound();
        }
        $this->actingAs($this->admin)->get('/admin/translations')->assertOk()
            ->assertSee('Translated To English')->assertSee('Translated To Arabic')->assertSee('Auto-translate empty texts');

        Livewire::actingAs($this->hr)->test(TranslationManager::class)->assertForbidden();
    }

    public function test_the_admin_saves_both_texts_of_a_key(): void
    {
        $row = Translation::where('key', 'Approve task')->firstOrFail();

        $this->page()
            ->set("edits.{$row->id}.en", 'Approve the task')->set("edits.{$row->id}.ar", 'اعتماد المهمة')
            ->call('save', $row->id)->assertHasNoErrors();

        $this->assertSame('Approve the task', $row->fresh()->en);
        $this->assertSame('اعتماد المهمة', $row->fresh()->ar);
        $this->assertFalse($row->fresh()->machine);
    }

    public function test_a_translation_must_keep_the_placeholders_of_the_key(): void
    {
        $row = Translation::where('key', 'like', '%:count attempts left%')->firstOrFail();
        $before = $row->ar;

        $this->page()->set("edits.{$row->id}.ar", 'بقيت محاولات')->call('save', $row->id)->assertHasErrors("edits.{$row->id}.ar");

        $this->assertSame($before, $row->fresh()->ar);
    }

    public function test_search_and_the_translated_to_filters(): void
    {
        Translation::create(['key' => 'A brand new sentence', 'type' => 'code', 'en' => 'A brand new sentence', 'ar' => null]);
        Translation::create(['key' => 'No english yet', 'type' => 'keyword', 'en' => null, 'ar' => 'عربي فقط']);

        $this->assertSame(['A brand new sentence'], $this->keys($this->page()->set('search', 'brand new')));

        $noArabic = $this->keys($this->page()->set('inArabic', ['no']));
        $this->assertContains('A brand new sentence', $noArabic);
        $this->assertNotContains('Approve task', $noArabic);

        $yesArabic = $this->keys($this->page()->set('search', 'task')->set('inArabic', ['yes']));
        $this->assertContains('Approve task', $yesArabic);
        $this->assertNotContains('A brand new sentence', $this->keys($this->page()->set('search', 'brand')->set('inArabic', ['yes'])));

        $this->assertSame(['No english yet'], $this->keys($this->page()->set('inEnglish', ['no'])));

        // choosing both Yes and No means "no filter"
        $this->assertContains('A brand new sentence', $this->keys($this->page()->set('inArabic', ['yes', 'no'])));
    }

    public function test_the_type_filter_separates_code_keys_from_keywords(): void
    {
        Translation::create(['key' => 'My own keyword', 'type' => 'keyword', 'en' => 'My own keyword', 'ar' => 'كلمتي']);

        $this->assertSame(['My own keyword'], $this->keys($this->page()->set('type', ['keyword'])));
        $this->assertNotContains('My own keyword', $this->keys($this->page()->set('type', ['code'])));
    }

    public function test_the_admin_adds_and_deletes_a_keyword_but_not_a_code_key(): void
    {
        $page = $this->page()->set('newKey', 'welcome.banner')->set('newEn', 'Welcome to TaskFlow')->set('newAr', 'مرحبا بك في تاسك فلو')->call('addKeyword')->assertHasNoErrors();

        $row = Translation::where('key', 'welcome.banner')->firstOrFail();
        $this->assertSame('keyword', $row->type);
        $this->assertSame('مرحبا بك في تاسك فلو', $row->ar);

        $page->set('newKey', 'welcome.banner')->call('addKeyword')->assertHasErrors('newKey');       // duplicate key
        $page->set('newKey', '')->call('addKeyword')->assertHasErrors(['newKey' => 'required']);

        $page->call('deleteKeyword', $row->id);
        $this->assertDatabaseMissing('translations', ['key' => 'welcome.banner']);

        $code = Translation::where('key', 'Approve task')->firstOrFail();
        $page->call('deleteKeyword', $code->id);                                                       // refused: it is a code key
        $this->assertDatabaseHas('translations', ['key' => 'Approve task']);
    }

    // ---- the auto-translate function ---------------------------------------------------------------------

    public function test_auto_translate_fills_the_empty_arabic_texts_and_marks_them_as_machine(): void
    {
        $this->fakeService('جملة جديدة');
        $row = Translation::create(['key' => 'A brand new sentence', 'type' => 'code', 'en' => 'A brand new sentence', 'ar' => null]);

        $this->page()->call('translateMissing');

        $this->assertSame('جملة جديدة', $row->fresh()->ar);
        $this->assertTrue($row->fresh()->machine);
        Http::assertSent(fn ($request) => str_contains($request['langpair'], 'en|ar') && $request['q'] === 'A brand new sentence');
    }

    public function test_suggest_only_fills_the_box_and_saves_nothing_until_save_is_pressed(): void
    {
        $this->fakeService('اقتراح');
        $row = Translation::create(['key' => 'Another new sentence', 'type' => 'code', 'en' => 'Another new sentence', 'ar' => null]);

        $page = $this->page()->call('suggest', $row->id);

        $page->assertSet("edits.{$row->id}.ar", 'اقتراح');
        $this->assertNull($row->fresh()->ar);

        $page->call('save', $row->id);
        $this->assertSame('اقتراح', $row->fresh()->ar);
    }

    public function test_placeholders_are_protected_during_machine_translation(): void
    {
        $this->freshHttp();
        Http::fake(fn () => Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => 'بقيت [[0]] محاولات']]));

        $this->assertSame('بقيت :count محاولات', app(MachineTranslator::class)->translate(':count attempts left'));
        Http::assertSent(fn ($request) => $request['q'] === '[[0]] attempts left');
    }

    public function test_a_damaged_placeholder_or_a_service_failure_never_saves_a_text(): void
    {
        $this->freshHttp();
        Http::fake(['*' => Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => 'بقيت محاولات']])]);   // [[0]] lost
        $this->assertNull(app(MachineTranslator::class)->translate(':count attempts left'));

        $this->freshHttp();
        Http::fake(['*' => Http::response('down', 500)]);
        $this->assertNull(app(MachineTranslator::class)->translate('Approve'));

        $this->freshHttp();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('no network'));
        $this->assertNull(app(MachineTranslator::class)->translate('Approve'));
    }

    public function test_the_scan_adds_keys_used_in_the_code_that_are_missing_from_the_table(): void
    {
        Translation::where('key', 'Approve task')->delete();

        $this->artisan('translations:scan')->assertSuccessful();

        $row = Translation::where('key', 'Approve task')->firstOrFail();
        $this->assertNull($row->ar);                                       // added empty, ready to be translated
        $this->assertSame('Approve task', $row->en);
        $this->assertSame('code', $row->type);
    }

    public function test_the_auto_translate_command_works_in_batches(): void
    {
        $this->fakeService('مترجم');
        Translation::create(['key' => 'Sentence one', 'type' => 'code', 'en' => 'Sentence one', 'ar' => null]);
        Translation::create(['key' => 'Sentence two', 'type' => 'code', 'en' => 'Sentence two', 'ar' => '']);

        $this->artisan('translations:auto', ['--limit' => 1])->assertSuccessful();

        $this->assertSame(1, Translation::where('machine', true)->count());
    }
}
