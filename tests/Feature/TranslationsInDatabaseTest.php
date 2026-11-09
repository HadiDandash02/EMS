<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\DeleteTranslation;
use App\Livewire\Admin\TranslationForm;
use App\Livewire\Admin\TranslationManager;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Translated To English')->assertSee('Translated To Arabic')->assertDontSee('Auto-translate');

        Livewire::actingAs($this->hr)->test(TranslationManager::class)->assertForbidden();
    }



    public function test_search_and_the_translated_to_filters(): void
    {
        Translation::create(['key' => 'A brand new sentence', 'type' => 'code', 'en' => 'A brand new sentence', 'ar' => null]);
        Translation::create(['key' => 'No english yet', 'type' => 'keyword', 'en' => null, 'ar' => 'عربي فقط']);

        $this->assertSame(['A brand new sentence'], $this->keys($this->page()->set('search', 'brand new')));

        $noArabic = $this->keys($this->page()->set('search', 'brand')->set('inArabic', ['no']));
        $this->assertContains('A brand new sentence', $noArabic);
        $this->assertNotContains('Approve task', $this->keys($this->page()->set('search', 'Approve task')->set('inArabic', ['no'])));

        $yesArabic = $this->keys($this->page()->set('search', 'task')->set('inArabic', ['yes']));
        $this->assertContains('Approve task', $yesArabic);
        $this->assertNotContains('A brand new sentence', $this->keys($this->page()->set('search', 'brand')->set('inArabic', ['yes'])));

        $this->assertSame(['No english yet'], $this->keys($this->page()->set('inEnglish', ['no'])));

        // choosing both Yes and No means "no filter"
        $this->assertContains('A brand new sentence', $this->keys($this->page()->set('search', 'brand')->set('inArabic', ['yes', 'no'])));
    }

    public function test_the_type_filter_separates_code_keys_from_keywords(): void
    {
        Translation::create(['key' => 'My own keyword', 'type' => 'keyword', 'en' => 'My own keyword', 'ar' => 'كلمتي']);

        $this->assertSame(['My own keyword'], $this->keys($this->page()->set('type', ['keyword'])));
        $this->assertNotContains('My own keyword', $this->keys($this->page()->set('type', ['code'])));
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


    public function test_the_privilege_belongs_to_the_super_admin_only(): void
    {
        $this->assertTrue($this->admin->can('manage-translations'));
        foreach ([$this->employee, $this->manager, $this->hr] as $user) {
            $this->assertFalse($user->can('manage-translations'));
        }
    }

    public function test_the_catalogue_search_runs_on_mysql_too_because_reserved_words_are_qualified(): void
    {
        // `key` is a reserved word in MySQL (SQLite accepts it, so the other tests could not see the problem):
        // the generated SQL must always write translations.key, never a bare key
        $source = file_get_contents(app_path('Livewire/Admin/TranslationManager.php'));

        $this->assertStringContainsString("'translations.key'", $source);
        $this->assertDoesNotMatchRegularExpression("/whereContains\(\\\$q, '(key|en|ar)'/", $source);

        $page = $this->page()->set('search', 'approve');
        $this->assertContains('Approve task', $this->keys($page));
    }

    public function test_there_is_no_machine_translation_any_more(): void
    {
        $this->assertFileDoesNotExist(app_path('Support/MachineTranslator.php'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('translations', 'machine'));
        $this->actingAs($this->admin)->get('/admin/translations')->assertOk()->assertDontSee('Suggest')->assertDontSee('Auto-translate');
    }
    // ---- Actions: Edit / Delete, each on its own page --------------------------------------------------

    public function test_the_table_is_read_only_and_the_actions_are_edit_and_delete(): void
    {
        $row = Translation::where('key', 'Approve task')->firstOrFail();

        $html = $this->actingAs($this->admin)->get('/admin/translations?q=Approve+task')->assertOk()->getContent();

        $this->assertStringNotContainsString('<textarea', $html);                       // no inline editing any more
        $this->assertStringContainsString(route('admin.translations.edit', $row), $html);
        $this->assertStringContainsString(route('admin.translations.delete', $row), $html);
        $this->assertStringContainsString('الموافقة على المهمة', $html);                  // the Arabic text is shown as plain text
    }

    public function test_the_admin_edits_both_texts_of_a_key_on_its_own_page(): void
    {
        $row = Translation::where('key', 'Approve task')->firstOrFail();
        $this->actingAs($this->admin)->get(route('admin.translations.edit', $row))->assertOk()->assertSee('Approve task');

        Livewire::actingAs($this->admin)->test(TranslationForm::class, ['translation' => $row])
            ->assertSet('en', 'Approve task')->assertSet('ar', 'الموافقة على المهمة')
            ->set('en', 'Approve the task')->set('ar', 'اعتماد المهمة')
            ->call('save')->assertHasNoErrors()->assertRedirect(route('admin.translations'));

        $this->assertSame('Approve the task', $row->fresh()->en);
        $this->assertSame('اعتماد المهمة', $row->fresh()->ar);
        $this->assertSame('Approve task', $row->fresh()->key);                           // the key never changes
    }

    public function test_the_key_of_an_existing_translation_cannot_be_changed_from_the_browser(): void
    {
        $row = Translation::where('key', 'Approve task')->firstOrFail();

        Livewire::actingAs($this->admin)->test(TranslationForm::class, ['translation' => $row])
            ->set('key', 'Something else')->call('save')->assertHasNoErrors();

        $this->assertSame('Approve task', $row->fresh()->key);
        $this->assertDatabaseMissing('translations', ['key' => 'Something else']);
    }

    public function test_a_translation_must_keep_the_placeholders_of_the_key(): void
    {
        $row = Translation::where('key', 'like', '%:count attempts left%')->firstOrFail();
        $before = $row->ar;

        Livewire::actingAs($this->admin)->test(TranslationForm::class, ['translation' => $row])
            ->set('ar', 'بقيت محاولات')->call('save')->assertHasErrors('ar');

        $this->assertSame($before, $row->fresh()->ar);
    }

    public function test_the_admin_adds_a_keyword_with_validation_and_no_duplicates(): void
    {
        $page = Livewire::actingAs($this->admin)->test(TranslationForm::class);

        $page->set('key', '')->call('save')->assertHasErrors(['key' => 'required']);

        $page->set('key', 'welcome.banner')->set('en', 'Welcome to TaskFlow')->set('ar', 'مرحبا بك في تاسك فلو')
            ->call('save')->assertHasNoErrors()->assertRedirect(route('admin.translations'));

        $row = Translation::where('key', 'welcome.banner')->firstOrFail();
        $this->assertSame('keyword', $row->type);
        $this->assertSame('مرحبا بك في تاسك فلو', $row->ar);

        Livewire::actingAs($this->admin)->test(TranslationForm::class)->set('key', 'welcome.banner')->call('save')->assertHasErrors('key');   // duplicate
    }

    public function test_the_admin_deletes_a_translation_on_a_confirmation_page(): void
    {
        $keyword = Translation::create(['key' => 'temp.keyword', 'type' => 'keyword', 'en' => 'Temp', 'ar' => 'مؤقت']);

        $this->actingAs($this->admin)->get(route('admin.translations.delete', $keyword))->assertOk()->assertSee('temp.keyword')->assertSee('Please confirm');

        Livewire::actingAs($this->admin)->test(DeleteTranslation::class, ['translation' => $keyword])
            ->call('delete')->assertRedirect(route('admin.translations'));

        $this->assertDatabaseMissing('translations', ['key' => 'temp.keyword']);
    }

    public function test_deleting_a_code_key_removes_its_arabic_text_and_the_english_key_is_shown_again(): void
    {
        $row = Translation::where('key', 'Approve task')->firstOrFail();

        Livewire::actingAs($this->admin)->test(DeleteTranslation::class, ['translation' => $row])->call('delete');

        app('translator')->setLoaded([]);
        app()->setLocale('ar');
        $this->assertSame('Approve task', __('Approve task'));
    }

    public function test_other_roles_cannot_open_edit_or_delete(): void
    {
        $row = Translation::first();

        foreach ([$this->employee, $this->manager, $this->hr] as $user) {
            $this->actingAs($user)->get("/admin/translations/{$row->id}/edit")->assertNotFound();
            $this->actingAs($user)->get("/admin/translations/{$row->id}/delete")->assertNotFound();
            Livewire::actingAs($user)->test(TranslationForm::class, ['translation' => $row])->assertForbidden();
            Livewire::actingAs($user)->test(DeleteTranslation::class, ['translation' => $row])->assertForbidden();
        }
    }
}
