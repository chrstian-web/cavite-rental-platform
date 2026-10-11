{{--
    Step guide for the getting-started checklist.
    - Popup: opens when a checklist item is clicked (dashboard), explains what to do in numbered steps.
    - Panel: after "Take me there", a small panel stays on screen on the other pages (search, home page,
      forms) so the user can follow the steps while doing them. Kept in sessionStorage; no server calls.
--}}
<div id="ob-guide" class="hidden" style="position:fixed;inset:0;z-index:9998;background:rgba(2,6,23,.55);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:16px" role="dialog" aria-modal="true" aria-labelledby="ob-guide-title">
    <div class="surface-card ob-card p-6 sm:p-7 w-full" style="max-width:480px;max-height:calc(100vh - 32px);overflow-y:auto">
        <p class="ob-faint" style="font-size:.75rem;font-weight:700">Step guide</p>
        <h2 id="ob-guide-title" class="ob-title" style="font-size:1.25rem;font-weight:800;line-height:1.3;margin-top:2px"></h2>
        <p id="ob-guide-intro" class="ob-lead" style="font-size:.92rem;line-height:1.5;margin-top:8px"></p>

        <p class="ob-title" style="font-size:.85rem;font-weight:800;margin-top:18px">What to do</p>
        <ol id="ob-guide-steps" class="ob-steps ob-text" style="margin-top:6px"></ol>

        <div class="mt-6 flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
            <button type="button" id="ob-guide-close" class="ob-btn ob-btn-ghost">Close</button>
            <a id="ob-guide-go" href="#" class="ob-btn ob-btn-primary"></a>
        </div>
        <p id="ob-guide-note" class="ob-faint text-center mt-4" style="font-size:.78rem">A small guide stays on your screen while you do this.</p>
    </div>
</div>

<div id="ob-panel" class="hidden ob-card" role="complementary" aria-label="Step guide" style="position:fixed;right:16px;bottom:16px;z-index:9000;width:min(350px,calc(100vw - 32px));border-radius:16px;padding:14px 16px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
        <div style="min-width:0">
            <p class="ob-faint" style="font-size:.7rem;font-weight:700">Step guide</p>
            <p id="ob-panel-title" class="ob-title" style="font-size:.95rem;font-weight:800;line-height:1.3"></p>
        </div>
        <div class="ob-text" style="display:flex;gap:2px;flex-shrink:0">
            <button type="button" id="ob-panel-toggle" class="ob-icon-btn" aria-label="Minimize the guide" aria-expanded="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path id="ob-panel-chev" d="M6 9l6 6 6-6"/></svg>
            </button>
            <button type="button" id="ob-panel-close" class="ob-icon-btn" aria-label="Close the guide">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>
    <div id="ob-panel-body" style="margin-top:8px">
        <ol id="ob-panel-steps" class="ob-steps ob-text"></ol>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px">
            <a id="ob-panel-back" href="{{ route('dashboard') }}" class="ob-btn ob-btn-ghost ob-btn-sm">Back to checklist</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var KEY = 'ob-active-guide';
    var UID = @json(auth()->id());
    var ON_DASHBOARD = @json(request()->routeIs('dashboard'));
    var $ = function (id) { return document.getElementById(id); };

    var modal = $('ob-guide'), panel = $('ob-panel'), opener = null;

    function read() {
        try { var g = JSON.parse(sessionStorage.getItem(KEY)); return g && g.uid === UID ? g : null; } catch (e) { return null; }
    }
    function write(g) { try { sessionStorage.setItem(KEY, JSON.stringify(g)); } catch (e) {} }
    function clear() { try { sessionStorage.removeItem(KEY); } catch (e) {} }

    function fillSteps(ol, steps) {
        ol.innerHTML = '';
        (steps || []).forEach(function (text) {
            var li = document.createElement('li');
            var span = document.createElement('span');
            span.textContent = text;
            li.appendChild(span);
            ol.appendChild(li);
        });
    }

    /* ---------- Popup (dashboard) ---------- */
    window.obOpenGuide = function (g, fromEl) {
        opener = fromEl || document.activeElement;
        $('ob-guide-title').textContent = g.title;
        $('ob-guide-intro').textContent = g.intro || '';
        fillSteps($('ob-guide-steps'), g.steps);

        var go = $('ob-guide-go');
        go.textContent = g.cta || 'Take me there';
        go.setAttribute('href', g.url);
        go.onclick = function () {
            write({ uid: UID, title: g.title, steps: g.steps, collapsed: window.innerWidth < 640 });
        };

        modal.classList.remove('hidden');
        modal.classList.add('ob-open');
        go.focus();
    };

    function closeModal() {
        modal.classList.remove('ob-open');
        modal.classList.add('hidden');
        if (opener && opener.focus) opener.focus();
    }
    $('ob-guide-close').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    modal.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    /* ---------- Panel (every other page) ---------- */
    function setCollapsed(collapsed) {
        $('ob-panel-body').style.display = collapsed ? 'none' : 'block';
        $('ob-panel-toggle').setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        $('ob-panel-toggle').setAttribute('aria-label', collapsed ? 'Expand the guide' : 'Minimize the guide');
        $('ob-panel-chev').setAttribute('d', collapsed ? 'M6 15l6-6 6 6' : 'M6 9l6 6 6-6');
        var g = read();
        if (g) { g.collapsed = collapsed; write(g); }
    }

    function showPanel(g) {
        $('ob-panel-title').textContent = g.title;
        fillSteps($('ob-panel-steps'), g.steps);
        panel.classList.remove('hidden');
        setCollapsed(!!g.collapsed);
    }

    $('ob-panel-toggle').addEventListener('click', function () {
        setCollapsed($('ob-panel-body').style.display !== 'none');
    });
    $('ob-panel-close').addEventListener('click', function () {
        clear();
        panel.classList.add('hidden');
    });
    // Going back to the checklist ends the guide.
    $('ob-panel-back').addEventListener('click', clear);

    var active = read();
    if (active) {
        if (ON_DASHBOARD) clear();       // back on the dashboard: the guide has done its job
        else showPanel(active);
    }
})();
</script>
@endpush
