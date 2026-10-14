import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

/* ---------------------------------------------------------------------------
 * Theme. Stored in localStorage under "theme" ("light" | "dark" | "system"),
 * the same key the Filament admin panel uses, so both stay in step.
 * The first paint is handled by the inline script in layouts/base.
 * ------------------------------------------------------------------------- */
const theme = {
    get() {
        try {
            return localStorage.getItem('theme') || 'system';
        } catch (e) {
            return 'system';
        }
    },
    isDark(mode) {
        return mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    },
    apply(mode, animate = false) {
        const root = document.documentElement;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (animate && !reduce) {
            root.classList.add('theme-transition');
            window.setTimeout(() => root.classList.remove('theme-transition'), 350);
        }
        root.classList.toggle('dark', this.isDark(mode));
        try {
            localStorage.setItem('theme', mode);
        } catch (e) {}
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: mode }));
    },
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (theme.get() === 'system') theme.apply('system');
});

Alpine.data('themeToggle', () => ({
    dark: document.documentElement.classList.contains('dark'),
    init() {
        window.addEventListener('theme-changed', () => (this.dark = document.documentElement.classList.contains('dark')));
    },
    toggle() {
        theme.apply(this.dark ? 'light' : 'dark', true);
    },
}));

/* ---------------------------------------------------------------------------
 * Splash: shows the brand briefly, then goes to the login page. Tap or any key
 * skips it; it is shown once per browser session.
 * ------------------------------------------------------------------------- */
Alpine.data('splash', ({ next, duration }) => ({
    leaving: false,
    init() {
        let seen = false;
        try {
            seen = sessionStorage.getItem('kattufy-splash-seen') === '1';
            sessionStorage.setItem('kattufy-splash-seen', '1');
        } catch (e) {}
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const wait = seen ? 0 : reduce ? Math.min(duration, 900) : duration;
        this.timer = window.setTimeout(() => this.go(), wait);
    },
    go() {
        if (this.leaving) return;
        this.leaving = true;
        window.clearTimeout(this.timer);
        window.setTimeout(() => window.location.replace(next), 280);
    },
}));

/* ---------------------------------------------------------------------------
 * One-time code entry: countdown to expiry and to the next resend.
 * ------------------------------------------------------------------------- */
Alpine.data('otpForm', ({ expiresIn = 0, resendIn = 0, demoCode = null }) => ({
    code: '',
    expiresIn,
    resendIn,
    demoCode,
    submitting: false,
    init() {
        this.tick = window.setInterval(() => {
            if (this.expiresIn > 0) this.expiresIn--;
            if (this.resendIn > 0) this.resendIn--;
        }, 1000);
        this.$nextTick(() => this.$refs.otp?.focus());
    },
    destroy() {
        window.clearInterval(this.tick);
    },
    clock(seconds) {
        const m = Math.floor(seconds / 60);
        const s = String(seconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    },
    clean() {
        this.code = this.code.replace(/\D/g, '').slice(0, 6);
    },
    fillDemo() {
        this.code = this.demoCode;
        this.$refs.otp?.focus();
    },
}));

/* ---------------------------------------------------------------------------
 * File field: shows the chosen file name and size, and checks type and size
 * before upload. The server checks again; this is only quick feedback.
 * ------------------------------------------------------------------------- */
const humanSize = (bytes) => {
    const kb = bytes / 1024;
    return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(kb))} KB`;
};

Alpine.data('fileField', ({ maxKb, types, messages }) => ({
    name: '',
    size: '',
    error: '',
    dragging: false,
    previewUrl: '',
    isImage: false,
    pick(event) {
        const input = event.target;
        const file = input.files?.[0];
        this.error = '';
        this.clearPreview();
        if (!file) {
            this.name = '';
            this.size = '';
            return;
        }
        const ext = file.name.split('.').pop().toLowerCase();
        if (!types.includes(ext)) {
            this.error = messages.type;
        } else if (file.size > maxKb * 1024) {
            this.error = messages.size;
        }
        if (this.error) {
            input.value = '';
            this.name = '';
            this.size = '';
            return;
        }
        this.name = file.name;
        this.size = humanSize(file.size);
        this.isImage = file.type.startsWith('image/');
        this.previewUrl = URL.createObjectURL(file);
    },
    /* Remove the chosen file before it is sent (the saved document, if any, is kept). */
    clear() {
        if (this.$refs.input) this.$refs.input.value = '';
        this.name = '';
        this.size = '';
        this.error = '';
        this.clearPreview();
        this.$nextTick(() => this.$refs.input?.focus());
    },
    clearPreview() {
        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
        this.previewUrl = '';
        this.isImage = false;
    },
    drop(event) {
        this.dragging = false;
        const input = this.$refs.input;
        if (event.dataTransfer?.files?.length) {
            const transfer = new DataTransfer();
            transfer.items.add(event.dataTransfer.files[0]);
            input.files = transfer.files;
            this.pick({ target: input });
        }
    },
}));

/* Disables the submit buttons after the first click, so a form is sent once. */
Alpine.data('submitOnce', () => ({
    busy: false,
    action: null,
    submit(event) {
        if (this.busy) {
            event.preventDefault();
            return;
        }
        this.action = event.submitter?.value ?? null;
        this.busy = true;
    },
}));

/* ---------------------------------------------------------------------------
 * Contact reveal (Architecture §6C). The number is fetched only when tapped.
 * ------------------------------------------------------------------------- */
Alpine.data('contactReveal', ({ url, locale }) => ({
    loading: null,
    result: null,
    error: '',
    async reveal(action) {
        if (this.loading) return;
        this.loading = action;
        this.error = '';
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ action, locale }),
                credentials: 'same-origin',
            });
            const data = await response.json();
            if (!response.ok) {
                this.error = data.message || 'Something went wrong. Please try again.';
                return;
            }
            this.result = data;
            if (action === 'call' && data.tel && window.matchMedia('(pointer: coarse)').matches) {
                window.location.href = data.tel;
            }
            if (action === 'whatsapp' && data.whatsapp) {
                window.open(data.whatsapp, '_blank', 'noopener');
            }
        } catch (e) {
            this.error = 'Network error. Check your connection and try again.';
        } finally {
            this.loading = null;
        }
    },
}));

/* Character counter for text areas. */
Alpine.data('counter', ({ max }) => ({
    max,
    left: max,
    init() {
        this.update();
    },
    update() {
        this.left = this.max - (this.$refs.field?.value.length ?? 0);
    },
}));

/* ---------------------------------------------------------------------------
 * Listing plans (front end only). The chosen plan and the plan details are
 * kept in this browser until plans are stored on the server; photos and
 * videos are previewed in the open tab only. Nothing here is sent to
 * Kattufy, activates a plan, takes a payment or changes what customers see.
 * ------------------------------------------------------------------------- */
const PLANS = ['free', 'pro', 'premium'];

/* Which form sections each plan has (fields per plan come from App\Support\ListingPlans). */
const PLAN_SECTIONS = {
    free: ['company'],
    pro: ['company', 'contact'],
    premium: ['company', 'contact', 'portfolio', 'media'],
};

const SECTION_OF_FIELD = {
    name: 'company',
    description: 'company',
    email: 'contact',
    address: 'contact',
    website: 'contact',
    phone: 'contact',
    whatsapp: 'contact',
    services: 'portfolio',
    projects: 'portfolio',
    videoLinks: 'media',
};

const browserStore = {
    read(key, fallback = null) {
        try {
            const raw = localStorage.getItem(key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    },
    write(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            return false;
        }
    },
};

const planStore = {
    get(key) {
        const plan = browserStore.read(key);
        return PLANS.includes(plan) ? plan : null;
    },
    set(key, plan) {
        browserStore.write(key, plan);
        window.dispatchEvent(new CustomEvent('listing-plan-changed', { detail: { key, plan } }));
    },
};

const isUrl = (value) => {
    if (!value) return true;
    try {
        const url = new URL(value);
        return (url.protocol === 'https:' || url.protocol === 'http:') && url.hostname.includes('.');
    } catch (e) {
        return false;
    }
};
const nationalNumber = (value) => {
    let digits = String(value || '').replace(/\D/g, '');
    if (digits.length === 12 && digits.startsWith('91')) digits = digits.slice(2);
    return digits;
};
const isMobile = (value) => /^[6-9]\d{9}$/.test(nationalNumber(value));
const isPhone = (value) => isMobile(value) || /^0\d{10}$/.test(nationalNumber(value));
const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(value || '').trim());
let uidCounter = 0;
const uid = (prefix) => `${prefix}${Date.now().toString(36)}${(uidCounter++).toString(36)}`;
const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const blankListing = () => ({
    name: '',
    description: '',
    email: '',
    address: '',
    website: '',
    phone: '',
    whatsapp: '',
    whatsappEnabled: true,
    services: [],
    projects: [],
    videoLinks: [],
});

/* Reads the selected plan, e.g. to show Premium-only navigation. */
Alpine.data('planState', ({ key }) => ({
    plan: null,
    init() {
        this.plan = planStore.get(key);
        window.addEventListener('listing-plan-changed', (e) => {
            if (e.detail.key === key) this.plan = e.detail.plan;
        });
    },
}));

/* Plan cards on the overview: choosing one is remembered in this browser. */
Alpine.data('planPicker', ({ key }) => ({
    plan: null,
    justChose: false,
    init() {
        this.plan = planStore.get(key);
        window.addEventListener('listing-plan-changed', (e) => {
            if (e.detail.key === key) this.plan = e.detail.plan;
        });
    },
    choose(plan) {
        this.plan = plan;
        planStore.set(key, plan);
        this.justChose = true;
        window.setTimeout(() => (this.justChose = false), 700);
    },
}));

/*
 * The listing plan flow: choose a plan → fill in only that plan's fields →
 * review → save (in this browser). mode "section" shows one section on its
 * own page (Services and projects, Media gallery) with the same data.
 */
Alpine.data('listingPlanner', (config) => ({
    mode: config.mode || 'wizard',
    plan: null,
    step: 'choose',
    section: 'company',
    form: blankListing(),
    saved: '',
    savedAt: null,
    touched: {},
    showErrors: false,
    confirmDiscard: false,
    notice: '',
    storageFailed: false,
    showPreview: false,
    justChose: false,
    mediaChanged: false,
    media: { business: [], project: [], videos: [] },
    mediaErrors: { business: [], project: [], videos: [] },
    mediaNotice: { business: '', project: '', videos: '' },
    dragging: '',
    viewer: null,
    currentYear: new Date().getFullYear(),

    init() {
        this.plan = planStore.get(config.planKey);

        const stored = browserStore.read(config.draftKey, {}) || {};
        const form = blankListing();
        for (const key of Object.keys(form)) {
            if (stored[key] !== undefined && stored[key] !== null) form[key] = stored[key];
            else if (config.initial[key] !== undefined && config.initial[key] !== null) form[key] = config.initial[key];
        }
        form.services = (form.services || []).map((s) => ({ id: s.id || uid('s'), name: s.name || '', description: s.description || '' }));
        form.projects = (form.projects || []).map((p) => ({ id: p.id || uid('p'), name: p.name || '', place: p.place || '', year: p.year || '', description: p.description || '' }));
        form.videoLinks = (form.videoLinks || []).map((l) => ({ id: l.id || uid('v'), url: l.url || '' }));
        this.form = form;
        this.saved = JSON.stringify(this.form);
        this.savedAt = stored.savedAt || null;
        this.media.project = (config.existingPhotos || []).map((p) => ({ ...p, id: uid('m'), kind: 'image', local: false, project: '' }));

        if (this.mode === 'section') {
            this.step = 'details';
            this.section = config.section;
        } else {
            const params = new URLSearchParams(window.location.search);
            const step = params.get('step');
            if (this.plan && (step === 'details' || step === 'review')) {
                this.step = 'details';
                const section = params.get('section');
                this.section = this.sections.includes(section) ? section : this.sections[0];
                if (step === 'review') this.$nextTick(() => this.toReview());
            }
        }

        window.addEventListener('listing-plan-changed', (e) => {
            if (e.detail.key === config.planKey) this.plan = e.detail.plan;
        });
        window.addEventListener('beforeunload', (e) => {
            if (this.dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    },

    /* ---- Plan ---------------------------------------------------------- */
    get sections() {
        return PLAN_SECTIONS[this.plan] || PLAN_SECTIONS.free;
    },
    get limit() {
        return config.limits[this.plan] || config.limits.free;
    },
    has(field, plan = this.plan) {
        return (config.fields[plan] || []).includes(field);
    },
    planName(plan = this.plan) {
        return config.planNames[plan] || '';
    },
    choose(plan) {
        this.plan = plan;
        planStore.set(config.planKey, plan);
        this.justChose = true;
        window.setTimeout(() => (this.justChose = false), 700);
        if (!this.sections.includes(this.section)) this.section = this.sections[0];
    },

    /* ---- Steps --------------------------------------------------------- */
    get stepIndex() {
        return ['choose', 'details', 'review', 'saved'].indexOf(this.step);
    },
    get title() {
        return (config.messages.titles[this.step] || '').replace(':plan', this.planName());
    },
    sectionLabel(section) {
        return config.messages.sections[section] || '';
    },
    get nextSectionName() {
        const index = this.sections.indexOf(this.section);
        return index < this.sections.length - 1 ? this.sections[index + 1] : null;
    },
    startDetails() {
        if (!this.plan) return;
        this.step = 'details';
        this.section = this.sections[0];
        this.showErrors = false;
        this.notice = '';
        this.syncUrl();
        this.scrollTop();
    },
    goSection(section) {
        if (!this.sections.includes(section)) return;
        this.section = section;
        this.step = 'details';
        this.syncUrl();
    },
    nextSection() {
        if (this.sectionErrors(this.section).length) {
            this.showErrors = true;
            this.focusFirstError();
            return;
        }
        const index = this.sections.indexOf(this.section);
        if (index < this.sections.length - 1) {
            this.goSection(this.sections[index + 1]);
            this.scrollTop();
        } else {
            this.toReview();
        }
    },
    prevSection() {
        const index = this.sections.indexOf(this.section);
        if (index > 0) {
            this.goSection(this.sections[index - 1]);
        } else {
            this.step = 'choose';
            this.syncUrl();
        }
        this.scrollTop();
    },
    toReview() {
        const keys = Object.keys(this.errors);
        if (keys.length) {
            this.showErrors = true;
            this.step = 'details';
            this.section = this.sectionOf(keys[0]);
            this.syncUrl();
            this.focusFirstError();
            return;
        }
        this.step = 'review';
        this.syncUrl();
        this.scrollTop();
    },
    editSection(section) {
        this.goSection(section);
        this.scrollTop();
    },
    save() {
        // A section page checks its own section; the plan form checks everything.
        const blocking = this.mode === 'section' ? this.sectionErrors(this.section) : Object.keys(this.errors);
        if (blocking.length) {
            if (this.mode === 'section') {
                this.showErrors = true;
                this.focusFirstError();
            } else {
                this.toReview();
            }
            return;
        }
        this.form.name = this.form.name.trim();
        this.form.website = this.form.website.trim();
        this.form.email = this.form.email.trim();
        this.savedAt = new Date().toISOString();
        this.storageFailed = !browserStore.write(config.draftKey, {
            ...this.form,
            plan: this.plan,
            savedAt: this.savedAt,
            media: { business: this.media.business.length, project: this.media.project.length, videos: this.media.videos.length },
        });
        this.saved = JSON.stringify(this.form);
        this.mediaChanged = false;
        this.touched = {};
        this.showErrors = false;
        this.confirmDiscard = false;
        if (this.mode === 'section') {
            this.notice = config.messages.saved;
        } else {
            this.step = 'saved';
            this.syncUrl();
            this.scrollTop();
        }
    },
    get dirty() {
        return this.mediaChanged || JSON.stringify(this.form) !== this.saved;
    },
    cancel() {
        if (this.dirty) {
            this.confirmDiscard = true;
            this.$nextTick(() => this.$refs.keepEditing?.focus());
            return;
        }
        this.discard(false);
    },
    discard(announce = true) {
        this.form = JSON.parse(this.saved);
        for (const kind of ['business', 'project', 'videos']) {
            this.media[kind].filter((m) => m.local).forEach((m) => URL.revokeObjectURL(m.url));
            this.media[kind] = this.media[kind].filter((m) => !m.local);
            this.mediaErrors[kind] = [];
            this.mediaNotice[kind] = '';
        }
        this.mediaChanged = false;
        this.confirmDiscard = false;
        this.touched = {};
        this.showErrors = false;
        this.notice = announce ? config.messages.discarded : '';
        if (this.mode === 'wizard') {
            this.step = 'choose';
            this.syncUrl();
            this.scrollTop();
        }
    },
    syncUrl() {
        if (this.mode !== 'wizard') return;
        const url = new URL(window.location.href);
        if (this.step === 'details' || this.step === 'review') {
            url.searchParams.set('step', this.step);
            if (this.step === 'details') url.searchParams.set('section', this.section);
            else url.searchParams.delete('section');
        } else {
            url.searchParams.delete('step');
            url.searchParams.delete('section');
        }
        window.history.replaceState(null, '', url);
    },
    scrollTop() {
        this.$nextTick(() => {
            const top = this.$root.getBoundingClientRect().top + window.scrollY - 110;
            if (window.scrollY > top) window.scrollTo({ top, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
        });
    },
    focusFirstError() {
        this.$nextTick(() => this.$root.querySelector('[aria-invalid="true"]')?.focus());
    },

    /* ---- Validation (quick feedback; the server would check again) ----- */
    get errors() {
        const f = this.form;
        const m = config.messages;
        const e = {};
        const name = (f.name || '').trim();
        if (!name) e.name = m.nameRequired;
        else if (name.length < 2) e.name = m.nameShort;
        if (this.descriptionLength > this.limit) e.description = m.descriptionOver[this.plan || 'free'];
        if (this.has('email') && f.email.trim() && !isEmail(f.email)) e.email = m.email;
        if (this.has('address') && f.address.trim() && f.address.trim().length < 10) e.address = m.address;
        if (this.has('website') && f.website.trim() && !isUrl(f.website.trim())) e.website = m.website;
        if (this.has('phone') && f.phone.trim() && !isPhone(f.phone)) e.phone = m.phone;
        if (this.has('whatsapp') && f.whatsappEnabled) {
            if (!f.whatsapp.trim()) e.whatsapp = m.whatsappRequired;
            else if (!isMobile(f.whatsapp)) e.whatsapp = m.whatsapp;
        }
        if (this.has('services')) {
            f.services.forEach((s, i) => {
                if (!s.name.trim()) e[`services.${i}`] = m.serviceName;
            });
        }
        if (this.has('projects')) {
            f.projects.forEach((p, i) => {
                if (!p.name.trim()) e[`projects.${i}.name`] = m.projectName;
                const year = String(p.year || '').trim();
                if (year && (!/^\d{4}$/.test(year) || +year < 1950 || +year > this.currentYear)) e[`projects.${i}.year`] = m.projectYear;
            });
            f.videoLinks.forEach((l, i) => {
                const url = l.url.trim();
                if (!url || !/^https:\/\//i.test(url) || !isUrl(url)) e[`videoLinks.${i}`] = m.videoLink;
            });
        }
        return e;
    },
    get errorCount() {
        return Object.keys(this.errors).length;
    },
    sectionOf(key) {
        return SECTION_OF_FIELD[key.split('.')[0]] || 'company';
    },
    sectionErrors(section) {
        return Object.keys(this.errors).filter((key) => this.sectionOf(key) === section);
    },
    showError(key) {
        return (this.showErrors || this.touched[key]) && this.errors[key] ? this.errors[key] : '';
    },
    invalid(key) {
        return this.showError(key) ? 'true' : 'false';
    },
    touch(key) {
        this.touched[key] = true;
    },
    get descriptionLength() {
        return (this.form.description || '').length;
    },
    get descriptionHint() {
        return config.messages.descriptionHint[this.plan || 'free'];
    },
    get descriptionState() {
        const ratio = this.descriptionLength / this.limit;
        return ratio > 1 ? 'over' : ratio >= 0.9 ? 'near' : 'ok';
    },
    get counterText() {
        return `${this.descriptionLength.toLocaleString('en-IN')} / ${this.limit.toLocaleString('en-IN')}`;
    },
    useBusinessPhone() {
        this.form.whatsapp = this.form.phone;
        this.touch('whatsapp');
    },
    normaliseWebsite() {
        const value = this.form.website.trim();
        if (value && !/^[a-z]+:\/\//i.test(value) && value.includes('.')) this.form.website = `https://${value}`;
        this.touch('website');
    },

    /* ---- Services, projects and video links ---------------------------- */
    addItem(list) {
        const item = list === 'services' ? { id: uid('s'), name: '', description: '' }
            : list === 'projects' ? { id: uid('p'), name: '', place: '', year: '', description: '' }
            : { id: uid('v'), url: '' };
        this.form[list].push(item);
        this.$nextTick(() => this.$root.querySelector(`[data-list="${list}"] > li:last-child input`)?.focus());
    },
    moveItem(list, index, step) {
        const target = index + step;
        if (target < 0 || target >= this.form[list].length) return;
        const items = [...this.form[list]];
        [items[index], items[target]] = [items[target], items[index]];
        this.form[list] = items;
    },
    removeItem(list, index) {
        const [item] = this.form[list].splice(index, 1);
        if (list === 'projects' && item) {
            this.media.project.forEach((photo) => {
                if (photo.project === item.id) photo.project = '';
            });
        }
        this.touched = Object.fromEntries(Object.entries(this.touched).filter(([key]) => !key.startsWith(`${list}.`)));
    },
    projectName(id) {
        return this.form.projects.find((p) => p.id === id)?.name || '';
    },

    /* ---- Photos and videos: checked and previewed in this tab only ----- */
    addMedia(kind, fileList) {
        const rules = config.media[kind];
        const m = config.messages;
        const errors = [];
        let added = 0;
        for (const file of Array.from(fileList || [])) {
            if (this.media[kind].length >= rules.max) {
                errors.push(m.mediaLimit.replace(':count', rules.max));
                break;
            }
            if (!rules.types.includes(file.type)) {
                errors.push(m.mediaType.replace(':name', file.name));
                continue;
            }
            if (file.size > rules.maxMb * 1024 * 1024) {
                errors.push(m.mediaSize.replace(':name', file.name).replace(':size', rules.maxMb));
                continue;
            }
            this.media[kind].push({ id: uid('m'), url: URL.createObjectURL(file), name: file.name, size: humanSize(file.size), kind: kind === 'videos' ? 'video' : 'image', local: true, project: '' });
            added++;
        }
        this.mediaErrors[kind] = errors;
        this.mediaNotice[kind] = added ? (added === 1 ? m.addedOne : m.addedMany.replace(':count', added)) : '';
        if (added) this.mediaChanged = true;
    },
    pickMedia(kind, event) {
        this.addMedia(kind, event.target.files);
        event.target.value = '';
    },
    dropMedia(kind, event) {
        this.dragging = '';
        this.addMedia(kind, event.dataTransfer?.files);
    },
    removeMedia(kind, index) {
        const [item] = this.media[kind].splice(index, 1);
        if (item?.local) URL.revokeObjectURL(item.url);
        this.mediaChanged = true;
        if (this.viewer) this.closeViewer();
    },
    moveMedia(kind, index, step) {
        const target = index + step;
        if (target < 0 || target >= this.media[kind].length) return;
        const items = [...this.media[kind]];
        [items[index], items[target]] = [items[target], items[index]];
        this.media[kind] = items;
    },
    get viewerItem() {
        return this.viewer ? this.media[this.viewer.kind][this.viewer.index] || null : null;
    },
    openViewer(kind, index) {
        this.viewer = { kind, index };
        this.$nextTick(() => this.$refs.viewer?.showModal());
    },
    closeViewer() {
        this.$refs.viewer?.querySelector('video')?.pause();
        if (this.$refs.viewer?.open) this.$refs.viewer.close();
        this.viewer = null;
    },
    stepViewer(direction) {
        if (!this.viewer) return;
        const items = this.media[this.viewer.kind];
        if (!items.length) return;
        this.$refs.viewer?.querySelector('video')?.pause();
        this.viewer = { kind: this.viewer.kind, index: (this.viewer.index + direction + items.length) % items.length };
    },

    /* ---- Live preview and review --------------------------------------- */
    get previewDescription() {
        return (this.form.description || '').slice(0, this.limit);
    },
    get websiteHost() {
        try {
            return new URL(this.form.website.trim()).hostname.replace(/^www\./, '');
        } catch (e) {
            return '';
        }
    },
    get previewPhotos() {
        return [...this.media.business, ...this.media.project].slice(0, 6);
    },
    formatPhone(value) {
        const digits = nationalNumber(value);
        if (/^[6-9]\d{9}$/.test(digits)) return `+91 ${digits.slice(0, 5)} ${digits.slice(5)}`;
        if (/^0\d{10}$/.test(digits)) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
        return value;
    },
    orDash(value) {
        const text = String(value ?? '').trim();
        return text || config.messages.notProvided;
    },
    count(choices, n) {
        return (choices[n === 0 ? 0 : n === 1 ? 1 : 2] || '').replace(':count', n);
    },
    get savedAtText() {
        if (!this.savedAt) return '';
        try {
            return new Date(this.savedAt).toLocaleString(document.documentElement.lang === 'ta' ? 'ta-IN' : 'en-IN', { dateStyle: 'medium', timeStyle: 'short' });
        } catch (e) {
            return '';
        }
    },
}));

window.Alpine = Alpine;
window.kattufyTheme = theme;
Alpine.start();
