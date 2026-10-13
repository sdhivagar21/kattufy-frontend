// Login gate for the static preview. Every page except the sign-in flow sends
// visitors to the login page until they submit a one-time code. Loaded
// synchronously at the top of <head> so protected pages never paint first.
(function () {
    var KEY = 'kattufy-signed-in';
    var root = new URL('.', document.currentScript.src);

    // Pages reachable while signed out, relative to the site root (an optional
    // "ta/" prefix and a trailing "index.html" are stripped first).
    var PUBLIC = /^(login|register|admin\/login|admin\/verify-mobile|privacy|terms)(\/|$)|^admin\/verify-email\/?$/;
    // Code-entry pages: submitting their form completes sign-in.
    var VERIFY = /^login\/verify(\/|$)|^admin\/verify-email\/?$/;

    function page(url) {
        var u = new URL(url, location.href);
        if (u.origin !== root.origin || u.pathname.indexOf(root.pathname) !== 0) return null;
        var rel = u.pathname.slice(root.pathname.length).replace(/index\.html$/, '');
        var m = /^(ta\/)?(.*)$/.exec(rel);
        return { lang: m[1] || '', path: m[2] };
    }

    function signedIn() {
        try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; }
    }

    function setSignedIn(on) {
        try { on ? localStorage.setItem(KEY, '1') : localStorage.removeItem(KEY); } catch (e) {}
    }

    function gate() {
        var here = page(location.href);
        if (!here || PUBLIC.test(here.path) || signedIn()) return;
        document.documentElement.style.display = 'none';
        location.replace(new URL(here.lang + 'login/index.html', root).href);
    }

    gate();
    // Back/forward cache can restore a protected page after logging out.
    window.addEventListener('pageshow', function (e) { if (e.persisted) gate(); });

    // Runs before the static form handler at the end of <body>.
    window.addEventListener('submit', function (e) {
        var f = e.target;
        if ((f.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        var next = f.getAttribute('data-static-next');
        var here = page(location.href);
        if (here && VERIFY.test(here.path) && (next || f.hasAttribute('data-static-field'))) {
            setSignedIn(true);
            return;
        }
        // Log out buttons are the only POST forms that lead back to the home page.
        var dest = next && page(next);
        if (dest && dest.path === '') setSignedIn(false);
    }, true);
})();
