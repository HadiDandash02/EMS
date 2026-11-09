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

/** The interface translations live in the database (no lang/ar.json) and are edited on /admin/translations. */
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

    private function fakeService(string $translated = 'نص مترجم')
    {
        Http::fake(['*' => Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => $translated]])]);
    }

    public function test_there_is_no_translation_file_any_more_the_texts_come_from_the_database(): void
    {
        $this->assertFileDoesNotExist(base_path('lang/ar.json'));
        $this->assertGreaterThan(400, Translation::where('locale', 'ar')->count());          // imported by the migration

        app()->setLocale('ar');
        $this->assertSame('الموافقة على المهمة', __('Approve task'));
        app()->setLocale('en');
        $this->assertSame('Approve task', __('Approve task'));
    }

    public function test_editing_a_translation_changes_the_text_everywhere_at_once(): void
    {
        app()->setLocale('ar');
        $this->assertSame('الموافقة على المهمة', __('Approve task'));     // loaded (and cached)

        Translation::where('locale', 'ar')->where('key', 'Approve task')->firstOrFail()->update(['value' => 'اعتماد المهمة']);

        // a new request (the translator forgets what it loaded) sees the new text
        app('translator')->setLoaded([]);
        $this->assertSame('اعتماد المهمة', __('Approve task'));
    }

    public function test_an_empty_translation_falls_back_to_the_english_text(): void
    {
        Translation::where('locale', 'ar')->where('key', 'Approve task')->firstOrFail()->update(['value' => null]);
        app('translator')->setLoaded([]);
        app()->setLocale('ar');

        $this->assertSame('Approve task', __('Approve task'));
    }

    public function test_only_the_super_admin_opens_the_translations_page(): void
    {
        $this->get('/admin/translations')->assertRedirect('/login');
        foreach ([$this->employee, $this->manager, $this->hr] as $user) {
            $this->actingAs($user)->get('/'.$user->url_prefix.'/translations')->assertForbidden();   // their own area: role check
            $this->actingAs($user)->get('/admin/translations')->assertNotFound();                          // the admin area does not even exist for them
        }
        $this->actingAs($this->admin)->get('/admin/translations')->assertOk()->assertSee('Auto-translate empty texts')->assertSee('Scan the code for new texts');

        Livewire::actingAs($this->hr)->test(TranslationManager::class)->assertForbidden();
    }

    public function test_the_admin_edits_and_saves_a_translation(): void
    {
        $row = Translation::where('locale', 'ar')->where('key', 'Approve task')->firstOrFail();

        Livewire::actingAs($this->admin)->test(TranslationManager::class)
            ->set("edits.{$row->id}", 'اعتماد المهمة')->call('save', $row->id)->assertHasNoErrors();

        $this->assertSame('اعتماد المهمة', $row->fresh()->value);
        $this->assertFalse($row->fresh()->machine);
    }

    public function test_a_translation_must_keep_the_placeholders_of_the_english_text(): void
    {
        $row = Translation::where('locale', 'ar')->where('key', 'like', '%:count attempts left%')->firstOrFail();
        $before = $row->value;

        Livewire::actingAs($this->admin)->test(TranslationManager::class)
            ->set("edits.{$row->id}", 'بقيت محاولات')->call('save', $row->id)->assertHasErrors("edits.{$row->id}");

        $this->assertSame($before, $row->fresh()->value);
    }

    public function test_the_list_can_be_searched_and_filtered_to_untranslated_texts(): void
    {
        Translation::create(['locale' => 'ar', 'group' => '*', 'key' => 'A brand new sentence', 'value' => null]);

        $list = Livewire::actingAs($this->admin)->test(TranslationManager::class)->set('search', 'brand new');
        $this->assertSame(['A brand new sentence'], $list->viewData('rows')->pluck('key')->all());

        $missing = Livewire::actingAs($this->admin)->test(TranslationManager::class)->set('show', ['untranslated']);
        $this->assertContains('A brand new sentence', $missing->viewData('rows')->pluck('key')->all());
        $this->assertNotContains('Approve task', $missing->viewData('rows')->pluck('key')->all());
    }

    // ---- the auto-translate function --------------------------------------------------------------

    public function test_auto_translate_fills_the_empty_texts_and_marks_them_as_machine(): void
    {
        $this->fakeService('جملة جديدة');
        $row = Translation::create(['locale' => 'ar', 'group' => '*', 'key' => 'A brand new sentence', 'value' => null]);

        Livewire::actingAs($this->admin)->test(TranslationManager::class)->call('translateMissing');

        $this->assertSame('جملة جديدة', $row->fresh()->value);
        $this->assertTrue($row->fresh()->machine);
        Http::assertSent(fn ($request) => str_contains($request['langpair'], 'en|ar') && $request['q'] === 'A brand new sentence');
    }

    public function test_suggest_only_fills_the_box_and_saves_nothing_until_save_is_pressed(): void
    {
        $this->fakeService('اقتراح');
        $row = Translation::create(['locale' => 'ar', 'group' => '*', 'key' => 'Another new sentence', 'value' => null]);

        $page = Livewire::actingAs($this->admin)->test(TranslationManager::class)->call('suggest', $row->id);

        $page->assertSet("edits.{$row->id}", 'اقتراح');
        $this->assertNull($row->fresh()->value);

        $page->call('save', $row->id);
        $this->assertSame('اقتراح', $row->fresh()->value);
    }

    public function test_placeholders_are_protected_during_machine_translation(): void
    {
        Http::fake(function ($request) {
            // the service must receive tokens instead of :count and must give them back
            return Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => 'بقيت [[0]] محاولات']]);
        });

        $this->assertSame('بقيت :count محاولات', app(MachineTranslator::class)->translate(':count attempts left'));
        Http::assertSent(fn ($request) => $request['q'] === '[[0]] attempts left');
    }

    public function test_a_damaged_placeholder_or_a_service_failure_never_saves_a_text(): void
    {
        Http::fake(['*' => Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => 'بقيت محاولات']])]);   // [[0]] lost
        $this->assertNull(app(MachineTranslator::class)->translate(':count attempts left'));

        $this->freshHttp();                                              // stubs of an earlier Http::fake() would stay active otherwise
        Http::fake(['*' => Http::response('down', 500)]);
        $this->assertNull(app(MachineTranslator::class)->translate('Approve'));

        $this->freshHttp();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('no network'));
        $this->assertNull(app(MachineTranslator::class)->translate('Approve'));
    }

    public function test_the_scan_adds_texts_used_in_the_code_that_are_missing_from_the_table(): void
    {
        Translation::where('locale', 'ar')->where('key', 'Approve task')->delete();

        $this->artisan('translations:scan')->assertSuccessful();

        $row = Translation::where('locale', 'ar')->where('key', 'Approve task')->firstOrFail();
        $this->assertNull($row->value);                                   // added empty, ready to be translated
    }

    public function test_the_auto_translate_command_works_in_batches(): void
    {
        $this->fakeService('مترجم');
        Translation::create(['locale' => 'ar', 'group' => '*', 'key' => 'Sentence one', 'value' => null]);
        Translation::create(['locale' => 'ar', 'group' => '*', 'key' => 'Sentence two', 'value' => '']);

        $this->artisan('translations:auto', ['--limit' => 1])->assertSuccessful();

        $this->assertSame(1, Translation::where('machine', true)->count());
    }
}
