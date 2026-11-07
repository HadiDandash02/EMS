# TaskFlow design system – where to change the look

Every page is built from the same few pieces, so a visual change is made **once** and appears everywhere.

| I want to change… | Go to | What to edit |
|---|---|---|
| **Any button** (colour, radius, size, hover) | `resources/css/app.css` → section *Buttons* | `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-success`, `.btn-danger`, `.btn-warning`, `.btn-soft`, `.btn-soft-danger` |
| Which icon / variants a button supports | `resources/views/components/button.blade.php` | the `<x-button variant="…" icon="…">` component |
| **Page width, spacing, title size** | `resources/css/app.css` → section *Page + card standard* | `.page`, `.page-title`, `.page-subtitle` |
| **Every white box (card)**: corners, shadow, header, footer | same section | `.card`, `.card-head`, `.card-body`, `.card-foot`, `.card-title`, `.card-hint` |
| Caption/value pairs in side summaries | same section | `.info-label`, `.info-value` |
| **Brand colours** (indigo/violet…) | Tailwind palette variables used by the classes above (e.g. `from-indigo-600`) – change them inside `.btn-primary` and the table header gradient in `components/data-table` / list views |
| **Dark mode colours** | `resources/css/app.css` → `[data-theme='dark']` block | the `--color-slate-*` variables and the explicit overrides under it |
| Fonts | `resources/css/app.css` (top: `@fontsource` imports) and `@theme { --font-sans … }` |
| Sidebar / header | `resources/views/components/layouts/app.blade.php` |
| Status / priority / role badge colours | `components/status-badge`, `priority-badge`, `role-badge` |
| Filter panel, results table, row menu | `components/filter-bar`, `data-table`, `table-footer`, `row-menu` |

After editing CSS run `npm run build` (or keep `npm run dev` running).

## The page template

Every page uses the same skeleton – breadcrumbs → title → content in cards. There is **no Back button**: the breadcrumb is the way back.

```blade
<x-page :title="__('Task')" :crumbs="[[__('Tasks'), $listUrl], [__('Task'), null]]">
    <x-slot:meta>   <x-status-badge … /> </x-slot:meta>      {{-- optional: badges under the title --}}
    <x-slot:actions> <x-button …>Edit</x-button> </x-slot:actions>   {{-- optional: buttons on the right --}}

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" :title="__('Details')" :hint="__('…')">
            …content…
            <x-slot:foot> <x-button>Save</x-button> </x-slot:foot>   {{-- optional: buttons row --}}
        </x-card>
        <x-card :title="__('Summary')"><x-info-list :items="[__('Status') => 'Pending']" /></x-card>
    </div>
</x-page>
```

* `<x-page>` – `resources/views/components/page.blade.php` (full width, `.page`)
* `<x-card>` – `resources/views/components/card.blade.php` (`tone="danger"` for destructive pages)
* `<x-info-list>` – `resources/views/components/info-list.blade.php`
* Task side summary shared by the detail, confirm and reject pages: `resources/views/livewire/tasks/partials/summary.blade.php`

## Lists: filters first, table after Search

All five lists (tasks, employees, approvals, users, departments) share two components, so they behave identically:

1. `<x-filter-bar>` – the collapsible filter panel (Search / Clear).
2. `<x-data-table>` – the **Results** card. It stays **hidden until Search is pressed** (or until filters arrive in the URL); then it has its own Collapse / Expand button, search-in-table, rows per page, column chooser and CSV export.

The behaviour lives in `app/Livewire/Concerns/HasFilterBar.php` (`$searched`, `applyFilters()`, `clearFilters()`).
