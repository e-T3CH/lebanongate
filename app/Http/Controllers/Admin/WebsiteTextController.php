<?php

declare(strict_types=1);

namespace Gate\Http\Controllers\Admin;

use Gate\Http\Request;
use Gate\Http\Response;
use Gate\Repositories\WebsiteTextRepository;
use Gate\Services\AuditLog;

/**
 * Content → Website texts: the fixed wording of the public site per language (buttons, labels, the footer blurb, the
 * newsletter box, form messages, visitor emails). Filter by part of the site, search, save; a changed text shows
 * "Changed" and a Reset button that puts the original wording back. Changed texts survive updates.
 */
final class WebsiteTextController extends ContentController
{
    public function index(Request $request): Response
    {
        $lang = $this->editLang($request->query('lang'));
        $reference = $this->app->languages()->defaultCode();
        $category = $request->query('part');
        $category = array_key_exists($category, WebsiteTextRepository::CATEGORIES) ? $category : '';
        $search = mb_substr(trim($request->query('q')), 0, 80);
        $repo = $this->repo();
        $rows = $repo->rows($lang, $reference, $category, $search);
        $old = $this->app->session()->pull('texts_old');
        $errors = $this->app->session()->pull('texts_errors');
        $query = array_filter(['part' => $category, 'q' => $search]);
        $base = $this->app->adminPath('website-texts') . ($query !== [] ? '?' . http_build_query($query) : '');
        $parts = [['value' => '', 'label' => $this->t('admin.texts.part_all')]];
        foreach (array_keys(WebsiteTextRepository::CATEGORIES) as $key) {
            $parts[] = ['value' => $key, 'label' => $this->t('admin.texts.part_' . $key)];
        }
        return $this->adminView('admin/website-texts', 'texts', $this->t('admin.texts.title'), $this->t('admin.texts.subtitle'), [
            'rows' => $rows,
            'lang' => $lang,
            'reference' => $reference,
            'rtl' => $lang === 'ar',
            'category' => $category,
            'search' => $search,
            'parts' => $parts,
            'changed' => array_sum($repo->customCounts($lang)),
            'tabs' => $this->languageTabs($base, $lang, [], false),
            'old' => is_array($old) ? $old : [],
            'errors' => is_array($errors) ? $errors : [],
            'canEdit' => $this->can('content.edit'),
        ]);
    }

    public function save(Request $request): Response
    {
        $lang = $this->editLang($request->input('lang'));
        $reference = $this->app->languages()->defaultCode();
        $back = $this->app->adminPath('website-texts?' . http_build_query(array_filter(['lang' => $lang, 'part' => $request->input('part'), 'q' => $request->input('q')])));
        $repo = $this->repo();
        $userId = $this->app->auth()->user()['id'] ?? null;

        // Reset is a button of the same form: the other texts on the page are saved too, so no edit is lost.
        $reset = $request->input('reset');
        $values = $request->inputMap('text');
        unset($values[$reset]);
        $result = $repo->save($lang, $reference, $values);
        if ($result['saved'] > 0) {
            $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $userId, ['type' => 'website_texts', 'lang' => $lang, 'count' => $result['saved']]);
        }
        $resetDone = $reset !== '' && $repo->reset($lang, $reference, $reset);
        if ($resetDone) {
            $this->app->audit()->record(AuditLog::CONTENT_CHANGED, $userId, ['type' => 'website_text.reset', 'lang' => $lang, 'key' => $reset]);
        }
        if ($result['errors'] !== []) {
            $messages = [];
            foreach ($result['errors'] as $key => $reason) {
                $messages[$key] = $this->t('admin.texts.error_' . $reason);
            }
            $this->app->session()->flash('texts_errors', $messages);
            $this->app->session()->flash('texts_old', array_intersect_key($values, $messages));
            $this->flashToast('admin.texts.check_fields', 'error');
            return $this->back($back);
        }
        $this->flashToast($resetDone ? 'admin.texts.reset_done' : ($result['saved'] > 1 ? 'admin.texts.saved' : ($result['saved'] === 1 ? 'admin.texts.saved_one' : 'admin.texts.nothing')), 'success', ['count' => $result['saved']]);
        return $this->back($back);
    }

    private function repo(): WebsiteTextRepository
    {
        return new WebsiteTextRepository($this->app->db(), $this->app->clock);
    }
}
