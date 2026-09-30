<?php

declare(strict_types=1);

namespace Gate\Core;

/**
 * Parameter specifications of every component in app/Views/components (see Props for the type syntax).
 * Rendered with $view->component('name', [...props]), which returns Html. Text parameters are always escaped;
 * "html" slots accept Html from other components or plain strings (escaped). Components contain no hardcoded
 * text: their own labels come from lang/{code}/ui.php, everything else is passed in by the caller.
 */
final class Components
{
    public const SPECS = [
        // ---------------------------------------------------------------------------------------------- basics
        'icon' => [
            'icon' => 'icon',                                   // e.g. "fa-solid fa-check"
            'size' => ['enum:13|14|15|16|17|18|20|22|24|34', '16'],
            'class' => ['classes', ''],                         // e.g. "ic-accent", "ic-ne", "ic-chev"
        ],
        'button' => [
            'label' => 'string',
            'variant' => ['enum:primary|ghost|admin-primary|admin-secondary|admin-danger', 'primary'],
            'href' => ['?url', null],                           // link when set, <button> otherwise
            'type' => ['enum:button|submit|reset', 'button'],
            'icon' => ['?icon', null],
            'iconPosition' => ['enum:start|end', 'end'],
            'iconClass' => ['classes', ''],
            'iconSize' => ['enum:13|14|16|18|20', '16'],
            'class' => ['classes', ''],
            'id' => ['?id', null],
            'name' => ['?id', null],
            'value' => ['?string', null],
            'ariaLabel' => ['?string', null],
            'disabled' => ['bool', false],
            'loading' => ['bool', false],                       // spinner, aria-busy, not clickable
            'iconOnly' => ['bool', false],                      // draw only the icon; the label stays for screen readers
            'attrs' => ['attrs', []],
        ],
        'status-pill' => [
            'label' => 'string',
            'tone' => ['enum:neutral|new|confirmed|diagnosis|quoted|danger', 'neutral'],
            'dot' => ['bool', false],
            'icon' => ['?icon', null],
            'class' => ['classes', ''],
            'attrs' => ['attrs', []],
        ],
        'kpi-card' => [
            'label' => 'string',
            'value' => 'string',
            'icon' => 'icon',
            'delta' => ['?string', null],
            'tone' => ['enum:neutral|up|late', 'neutral'],
            'class' => ['classes', ''],
        ],
        'notice' => [
            'title' => 'string',
            'text' => ['?string', null],
            'icon' => ['icon', 'fa-solid fa-shield-halved'],
            'linkHref' => ['?url', null],
            'linkLabel' => ['?string', null],
            'role' => ['enum:note|alert|status', 'note'],
            'class' => ['classes', ''],
        ],
        'progress' => [
            'value' => 'int',                                   // 0–100, width through the .pct-N class (no inline style)
            'label' => ['?string', null],                       // accessible name
            'class' => ['classes', ''],
        ],
        'setting-row' => [
            'name' => 'string',
            'control' => 'html',
            'description' => ['?string', null],
            'wide' => ['bool', false],
            'class' => ['classes', ''],
        ],

        // ----------------------------------------------------------------------------------------------- forms
        'toggle' => [
            'label' => ['?string', null],                       // aria-label; null when the toggle sits inside a <label>
            'name' => ['?id', null],
            'id' => ['?id', null],
            'checked' => ['bool', false],
            'disabled' => ['bool', false],
            'required' => ['bool', false],
            'value' => ['string', '1'],
            'uncheckedValue' => ['?string', '0'],               // hidden input so an unchecked toggle still submits
            'autosave' => ['bool', false],
            'toast' => ['?string', null],
            'class' => ['classes', ''],
            'inputClass' => ['classes', ''],
            'attrs' => ['attrs', []],
        ],
        'input' => [
            'label' => ['?string', null],
            'name' => ['?id', null],
            'id' => ['?id', null],
            'type' => ['enum:text|email|tel|password|number|search|url|date', 'text'],
            'value' => ['?string', null],
            'placeholder' => ['?string', null],
            'autocomplete' => ['?string', null],
            'inputmode' => ['?string', null],
            'ariaLabel' => ['?string', null],
            'error' => ['?string', null],
            'hint' => ['?string', null],
            'suffix' => ['?string', null],                      // unit after the value ("minutes"); '' keeps the frame
            'maxlength' => ['?int', null],
            'required' => ['bool', false],
            'disabled' => ['bool', false],
            'readonly' => ['bool', false],
            'autofocus' => ['bool', false],
            'reveal' => ['bool', true],                        // password fields: a show/hide button
            'color' => ['bool', false],                        // a colour picker before the colour code
            'class' => ['classes', ''],
            'inputClass' => ['classes', ''],
            'attrs' => ['attrs', []],
        ],
        'textarea' => [
            'label' => 'string',
            'name' => ['?id', null],
            'id' => ['?id', null],
            'value' => ['?string', null],
            'rows' => ['int', 3],
            'placeholder' => ['?string', null],
            'error' => ['?string', null],
            'hint' => ['?string', null],
            'maxlength' => ['?int', null],
            'required' => ['bool', false],
            'disabled' => ['bool', false],
            'class' => ['classes', ''],
            'attrs' => ['attrs', []],
        ],
        'select' => [
            'name' => 'id',
            'options' => 'list',                                // list<array{value: string, label: string, code?: string, stars?: int, suffix?: string}>
            'label' => ['?string', null],
            'ariaLabel' => ['?string', null],                   // when there is no visible label
            'panelLabel' => ['?string', null],
            'id' => ['?id', null],
            'value' => ['?string', null],
            'error' => ['?string', null],
            'hint' => ['?string', null],
            'size' => ['enum:md|sm', 'md'],
            'panel' => ['enum:below|full|rating', 'below'],
            'leadIcon' => ['?icon', null],
            'check' => ['enum:end|inline', 'end'],
            'autosave' => ['bool', false],
            'toast' => ['?string', null],
            'open' => ['bool', false],
            'required' => ['bool', false],
            'disabled' => ['bool', false],
            'class' => ['classes', ''],
            'attrs' => ['attrs', []],
        ],
        'form-error' => [
            'message' => 'string',
            'id' => ['?id', null],
            'class' => ['classes', ''],
        ],
        'form-errors' => [
            'messages' => 'list',                               // list<string>
            'title' => ['?string', null],
            'variant' => ['enum:admin|public', 'admin'],
            'icon' => ['icon', 'fa-solid fa-circle-exclamation'],
            'class' => ['classes', ''],
        ],

        // ----------------------------------------------------------------------------------------------- admin
        'admin-sidebar' => [
            'menu' => 'list',                                   // AdminMenu::build() output
            'active' => ['string', ''],
            'brandMark' => ['string', 'BM'],
            'brandName' => ['string', 'GATE Lebanon'],
            'logoutAction' => ['?url', null],                   // POST form with CSRF token
            'open' => ['bool', false],
            'id' => ['id', 'sb'],
        ],
        'admin-topbar' => [
            'title' => 'string',
            'subtitle' => ['string', ''],
            'userName' => ['?string', null],
            'userRole' => ['?string', null],
            'viewSiteHref' => ['?url', '/'],
            'search' => ['bool', false],
            'searchAction' => ['?url', null],
            'notifications' => ['?int', null],                  // null: no bell; > 0 shows the dot
            'userMenu' => ['bool', false],
            'sidebarId' => ['id', 'sb'],
        ],
        'tabs' => [
            'items' => 'list',                                  // list<array{label: string, href: string, active?: bool, count?: int}>
            'label' => 'string',
            'variant' => ['enum:settings|reviews', 'settings'],
            'class' => ['classes', ''],
        ],
        'row-actions' => [
            // list<array{action: string, label: string, icon?: string, tone?: 'danger', confirm?: string, confirmTitle?: string, fields?: array<string, scalar>}>
            'actions' => 'list',
            'class' => ['classes', ''],
        ],
        'data-table' => [
            'columns' => 'list',                                // list<array{key: string, label: string, hideLabel?: bool, class?: string}>
            'rows' => 'list',                                   // list<array<string, Html|string>>
            'caption' => ['?string', null],
            'empty' => ['?html', null],
            'class' => ['classes', ''],
        ],
        'pagination' => [
            'page' => 'int',
            'pages' => 'int',
            'url' => 'url',                                     // contains {page}
            'class' => ['classes', ''],
        ],
        'empty-state' => [
            'title' => 'string',
            'text' => ['?string', null],
            'icon' => ['icon', 'fa-regular fa-folder-open'],
            'action' => ['?html', null],
            'variant' => ['enum:admin|public', 'admin'],
            'class' => ['classes', ''],
        ],

        // -------------------------------------------------------------------------------------------- feedback
        'toast' => [
            'message' => 'string',
            'type' => ['enum:success|error|info|warning', 'success'],
            'class' => ['classes', ''],
        ],
        'confirm-modal' => [
            'id' => ['id', 'bm-confirm'],
            'title' => ['?string', null],
            'message' => ['?string', null],
            'confirmLabel' => ['?string', null],
            'cancelLabel' => ['?string', null],
            'tone' => ['enum:default|danger', 'default'],
            'open' => ['bool', false],
        ],
    ];

    /** Font Awesome icon per social network (brands where Font Awesome Free has the logo). */
    public const SOCIAL_ICONS = [
        'facebook' => 'fa-brands fa-facebook-f',
        'instagram' => 'fa-brands fa-instagram',
        'linkedin' => 'fa-brands fa-linkedin-in',
        'x' => 'fa-brands fa-x-twitter',
        'youtube' => 'fa-brands fa-youtube',
        'whatsapp' => 'fa-brands fa-whatsapp',
    ];

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::SPECS);
    }
}
