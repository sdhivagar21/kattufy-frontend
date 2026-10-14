// Login gate for the static preview. Every page except the sign-in flow sends
// visitors to the login page until they submit a one-time code, and each
// account type only opens its own dashboard pages. Loaded synchronously at the
// top of <head> so blocked pages never paint first.
(function () {
    var KEY = 'kattufy-role';
    var root = new URL('.', document.currentScript.src);

    // Paths are relative to the site root, with an optional "ta/" prefix and a
    // trailing "index.html", ".html" or "/" stripped first.
    var PUBLIC = /^(login|register|admin\/login|admin\/verify-mobile|privacy|terms)(\/|$)|^admin\/verify-email$/;
    // Pages that belong to one account type; everything else is shared.
    var RESTRICTED = /^(user|provider|account|admin)(\/|$)/;

    // Where each account type lands, and which restricted pages it may open.
    var ROLES = {
        user:     { home: 'user/dashboard/index.html',        own: /^user(\/|$)/ },
        provider: { home: 'provider/dashboard/index.html',    own: /^(provider|account)(\/|$)/ },
        pending:  { home: 'provider/verification/index.html', own: /^provider\/(register|verification)(\/|$)/ },
        admin:    { home: 'admin/dashboard/index.html',       own: /^admin(\/|$)/ },
        // Registered but no account type picked yet.
        'new':    { home: 'select-role/index.html',           own: /^select-role(\/|$)/ }
    };

    // Submitting the code on these pages signs in as that account type.
    var SIGN_IN = {
        'login/verify': 'user',
        'login/verify/user': 'user',
        'login/verify/provider': 'provider',
        'login/verify/pending': 'pending',
        'login/verify/register': 'new',
        'admin/verify-email': 'admin'
    };

    // Demo numbers typed into the login form open their own account type.
    var DEMO_NUMBERS = {
        '9000000002': 'verify/provider.html',
        '9000000003': 'verify/pending.html',
        '9000000004': '../admin/login/index.html'
    };

    function page(url) {
        var u = new URL(url, location.href);
        if (u.origin !== root.origin || u.pathname.indexOf(root.pathname) !== 0) return null;
        var rel = u.pathname.slice(root.pathname.length).replace(/(index)?\.html$/, '').replace(/\/$/, '');
        var m = /^(ta(?:\/|$))?(.*)$/.exec(rel);
        return { lang: m[1] ? 'ta/' : '', path: m[2] };
    }

    function role() {
        try { var r = localStorage.getItem(KEY); } catch (e) {}
        return ROLES.hasOwnProperty(r) ? r : null;
    }

    function setRole(r) {
        try { r ? localStorage.setItem(KEY, r) : localStorage.removeItem(KEY); } catch (e) {}
    }

    function redirect(here, target) {
        document.documentElement.style.display = 'none';
        location.replace(new URL(here.lang + target, root).href);
    }

    function gate() {
        var here = page(location.href);
        if (!here || PUBLIC.test(here.path)) return;
        var r = role();
        if (!r) return redirect(here, 'login/index.html');
        var allowed = r === 'new' ? ROLES[r].own.test(here.path)
            : !RESTRICTED.test(here.path) || ROLES[r].own.test(here.path);
        if (!allowed) redirect(here, ROLES[r].home);
    }

    gate();
    // Back/forward cache can restore a page after logging out.
    window.addEventListener('pageshow', function (e) { if (e.persisted) gate(); });

    // Runs before the static form handler at the end of <body>, so it can
    // record the account type and change where the form goes.
    window.addEventListener('submit', function (e) {
        var f = e.target;
        if ((f.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        var next = f.getAttribute('data-static-next');
        var here = page(location.href);
        if (!here) return;

        if (here.path === 'login' && f.querySelector('[name="phone"]')) {
            var number = f.querySelector('[name="phone"]').value.replace(/\D/g, '').slice(-10);
            f.setAttribute('data-static-next', DEMO_NUMBERS[number] || 'verify/index.html');
            return;
        }
        if (SIGN_IN.hasOwnProperty(here.path) && next) {
            setRole(SIGN_IN[here.path]);
            return;
        }
        if (here.path === 'select-role' && f.hasAttribute('data-static-field')) {
            var picked = f.querySelector('[name="role"]:checked');
            if (picked) setRole(picked.value === 'provider' ? 'pending' : 'user');
            return;
        }
        // Log out buttons are the only POST forms that lead back to the home page.
        var dest = next && page(next);
        if (dest && dest.path === '') setRole(null);
    }, true);
})();
