{{--
    Prints the x-data attribute for a listingPlanner (resources/js/app.js).
    Used on the plan page (mode "wizard") and on Services and projects / Media
    gallery (mode "section"), so all three read and save the same browser
    draft. Starting values come from the saved business profile.
--}}
@php
    $mode ??= 'wizard';
    $section ??= 'company';
    $planNames = collect(\App\Support\ListingPlans::PLANS)->mapWithKeys(fn ($p) => [$p => __('plans.'.$p.'.name')])->all();
    $limits = \App\Support\ListingPlans::DESCRIPTION_LIMITS;
    $national = \App\Support\Phone::national($provider->phone) ?? '';
    $address = (string) $provider->address;
    if ($provider->pincode && ! str_contains($address, $provider->pincode)) {
        $address = trim($address.' '.$provider->pincode);
    }
    $plannerConfig = [
        'mode' => $mode,
        'section' => $section,
        'planKey' => \App\Support\ListingPlans::storageKey($provider),
        'draftKey' => \App\Support\ListingPlans::draftKey($provider),
        'fields' => \App\Support\ListingPlans::FIELDS,
        'limits' => $limits,
        'planNames' => $planNames,
        'initial' => [
            'name' => (string) $provider->business_name_en,
            'description' => (string) $provider->description_en,
            'email' => (string) $provider->email,
            'address' => $address,
            'website' => (string) $provider->website_url,
            'phone' => $national,
            'whatsapp' => $national,
            'whatsappEnabled' => (bool) ($provider->allow_whatsapp ?? true),
            'services' => $provider->services->map(fn ($s) => ['name' => (string) tr_field($s, 'name'), 'description' => ''])->values(),
            'projects' => $provider->portfolioItems->map(fn ($p) => [
                'name' => (string) tr_field($p, 'title'),
                'place' => $p->locality ? (string) tr_field($p->locality, 'name') : '',
                'year' => (string) ($p->completed_on?->year ?? ''),
                'description' => (string) (tr_field($p, 'description') ?? ''),
            ])->values(),
            'videoLinks' => [],
        ],
        'existingPhotos' => $provider->portfolioItems->filter(fn ($p) => $p->image)
            ->map(fn ($p) => ['url' => $p->image->publicUrl(800), 'name' => (string) tr_field($p, 'title')])->values(),
        'media' => [
            'business' => ['types' => ['image/jpeg', 'image/png', 'image/webp'], 'maxMb' => 8, 'max' => 12],
            'project' => ['types' => ['image/jpeg', 'image/png', 'image/webp'], 'maxMb' => 8, 'max' => 24],
            'videos' => ['types' => ['video/mp4', 'video/webm'], 'maxMb' => 50, 'max' => 4],
        ],
        'messages' => [
            'titles' => [
                'choose' => __('plans.choose_title'),
                'details' => __('plans.details_title'),
                'review' => __('plans.review_title'),
                'saved' => __('plans.saved_title'),
            ],
            'sections' => [
                'company' => __('plans.section_company'),
                'contact' => __('plans.section_contact'),
                'portfolio' => __('plans.section_portfolio'),
                'media' => __('plans.section_media'),
            ],
            'nameRequired' => __('plans.v_name_required'),
            'nameShort' => __('plans.v_name_short'),
            'descriptionOver' => collect($limits)->map(fn ($limit, $p) => __('plans.description_over', ['limit' => number_format($limit), 'plan' => $planNames[$p]]))->all(),
            'descriptionHint' => collect($limits)->map(fn ($limit, $p) => __('plans.description_hint', ['limit' => number_format($limit), 'plan' => $planNames[$p]]))->all(),
            'email' => __('plans.v_email'),
            'address' => __('plans.v_address'),
            'website' => __('plans.v_website'),
            'phone' => __('plans.v_phone'),
            'whatsappRequired' => __('plans.v_whatsapp_required'),
            'whatsapp' => __('plans.v_whatsapp'),
            'serviceName' => __('plans.v_service_name'),
            'projectName' => __('plans.v_project_name'),
            'projectYear' => __('plans.v_project_year', ['year' => date('Y')]),
            'videoLink' => __('plans.v_video_link'),
            'mediaType' => __('plans.media_type'),
            'mediaSize' => __('plans.media_size'),
            'mediaLimit' => __('plans.media_limit'),
            'addedOne' => trans_choice('plans.media_added', 1),
            'addedMany' => trans_choice('plans.media_added', 2, ['count' => ':count']),
            'notProvided' => __('plans.not_provided'),
            'saved' => __('plans.saved_locally'),
            'discarded' => __('plans.discarded'),
        ],
    ];
@endphp
x-data="listingPlanner(@js($plannerConfig))"
