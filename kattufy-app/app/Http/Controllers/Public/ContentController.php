<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Content\Models\ContentPage;
use App\Support\Faq;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function legal(string $slug): View
    {
        return $this->render(ContentPage::published()->where('slug', $slug)->where('page_type', 'legal')->firstOrFail());
    }

    public function page(string $slug): View
    {
        return $this->render(ContentPage::published()->where('slug', $slug)->firstOrFail());
    }

    public function guides(): View
    {
        return view('content.guides', ['guides' => ContentPage::published()->where('page_type', 'guide')->orderBy('published_at', 'desc')->get()]);
    }

    public function guide(string $slug): View
    {
        return $this->render(ContentPage::published()->where('page_type', 'guide')->where('slug', $slug)->firstOrFail());
    }

    public function faq(): View
    {
        return view('content.faq', ['faqs' => Faq::items()]);
    }

    /** How listing works, for businesses (the self-registration starts at /register/). */
    public function join(): View
    {
        return view('content.join');
    }

    private function render(ContentPage $page): View
    {
        [$title, $titleLang] = $page->titleFor(app()->getLocale());
        [$body, $bodyLang] = $page->bodyFor(app()->getLocale());

        return view('content.page', [
            'page' => $page,
            'title' => $title,
            'titleLang' => $titleLang,
            'html' => ContentPage::renderMarkdown($body),
            'bodyLang' => $bodyLang,
        ]);
    }
}
