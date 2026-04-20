// ============================================================
//  utils.js — Global utility functions for Chronos
// ============================================================

// ----------------------------------------------------------
//  XSS-safe HTML escape (single canonical definition)
// ----------------------------------------------------------
window.escapeHTML = function(str) {
    if (str == null) return '';
    return String(str).replace(/[&<>"']/g, function(ch) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return map[ch];
    });
};

// ----------------------------------------------------------
//  Toast Notification System (queued, stacked)
// ----------------------------------------------------------
(function() {
    let container = null;

    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    window.showToast = function(message, type = 'info') {
        const c = getContainer();
        const toast = document.createElement('div');
        toast.className = `toast glass toast-${type}`;
        toast.innerHTML = `
            <span style="flex:1">${escapeHTML(String(message))}</span>
            <button class="toast-close" aria-label="Dismiss">×</button>
        `;

        c.appendChild(toast);

        const dismiss = () => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 320);
        };

        toast.querySelector('.toast-close').addEventListener('click', dismiss);
        const timer = setTimeout(dismiss, 4500);

        // Allow click to dismiss early
        toast.addEventListener('click', () => { clearTimeout(timer); dismiss(); });
    };
})();

// ----------------------------------------------------------
//  Promise-based Confirm Dialog (replaces native confirm())
// ----------------------------------------------------------
window.showConfirmDialog = function({ title = 'Confirm', message = 'Are you sure?', confirmText = 'Confirm', isDanger = false } = {}) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'confirm-dialog-overlay';
        overlay.innerHTML = `
            <div class="confirm-dialog-box glass">
                <h3>${escapeHTML(title)}</h3>
                <p>${escapeHTML(message)}</p>
                <div class="confirm-dialog-actions">
                    <button class="btn btn-secondary" id="confirmCancel">Cancel</button>
                    <button class="btn ${isDanger ? 'btn-danger' : 'btn-primary'}" id="confirmOk">${escapeHTML(confirmText)}</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        const cleanup = (result) => {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 200);
            resolve(result);
        };

        overlay.querySelector('#confirmOk').addEventListener('click', () => cleanup(true));
        overlay.querySelector('#confirmCancel').addEventListener('click', () => cleanup(false));
        overlay.addEventListener('click', (e) => { if (e.target === overlay) cleanup(false); });

        // Keyboard: Enter = confirm, Escape = cancel
        const keyHandler = (e) => {
            if (e.key === 'Enter')  { document.removeEventListener('keydown', keyHandler); cleanup(true); }
            if (e.key === 'Escape') { document.removeEventListener('keydown', keyHandler); cleanup(false); }
        };
        document.addEventListener('keydown', keyHandler);
    });
};

// ----------------------------------------------------------
//  Ripple Effect (event delegation)
// ----------------------------------------------------------
document.addEventListener('click', function(e) {
    const target = e.target.closest('.btn, .ripple-target');
    if (!target) return;

    const rect = target.getBoundingClientRect();
    const diameter = Math.max(target.clientWidth, target.clientHeight);
    const radius = diameter / 2;

    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.width = ripple.style.height = `${diameter}px`;
    ripple.style.left = `${e.clientX - rect.left - radius}px`;
    ripple.style.top  = `${e.clientY - rect.top  - radius}px`;

    target.style.position = target.style.position || 'relative';
    target.style.overflow = 'hidden';

    const existing = target.querySelector('.ripple');
    if (existing) existing.remove();
    target.appendChild(ripple);
});

// ----------------------------------------------------------
//  Debounce + Throttle
// ----------------------------------------------------------
window.debounce = function(fn, wait) {
    let t;
    return function(...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
};

window.throttle = function(fn, limit) {
    let active = false;
    return function(...args) {
        if (active) return;
        active = true;
        fn.apply(this, args);
        setTimeout(() => active = false, limit);
    };
};

// ----------------------------------------------------------
//  Modal helpers
// ----------------------------------------------------------
window.openModal = function(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); el.style.display = 'flex'; }
};
window.closeModal = function(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('open');
    setTimeout(() => el.style.display = 'none', 200);
};

// Close modals on backdrop click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        setTimeout(() => e.target.style.display = 'none', 200);
    }
    if (e.target.classList.contains('modal-close') ||
        e.target.classList.contains('modal-cancel-btn')) {
        const overlay = e.target.closest('.modal-overlay');
        if (overlay) closeModal(overlay.id);
    }
});

// Close modals on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            m.classList.remove('open');
            setTimeout(() => m.style.display = 'none', 200);
        });
    }
});

// ----------------------------------------------------------
//  Floating panel helper (body-fixed, avoids all overflow clips)
// ----------------------------------------------------------
window.createFloatingPanel = function(anchorEl, className) {
    const el = document.createElement('div');
    el.className = className;
    el.style.cssText = 'position:fixed;z-index:99999;';
    document.body.appendChild(el);
    const rect = anchorEl.getBoundingClientRect();
    el.style.top  = `${rect.bottom + 6}px`;
    el.style.left = `${rect.left}px`;
    requestAnimationFrame(() => {
        if (!el.isConnected) return;
        const pr = el.getBoundingClientRect();
        if (pr.right > window.innerWidth - 8)
            el.style.left = `${Math.max(8, rect.right - pr.width)}px`;
        if (pr.bottom > window.innerHeight - 8)
            el.style.top  = `${rect.top - pr.height - 6}px`;
    });
    return el;
};

// ----------------------------------------------------------
//  CHRONOS DATE PICKER
// ----------------------------------------------------------
window.ChronosDatePicker = class ChronosDatePicker {
    constructor({ trigger, hidden, display, onSelect }) {
        Object.assign(this, { trigger, hidden, display, onSelect });
        this.pointer = new Date();
        this.overlay = null;
        this._click  = e => { if (this.overlay && !this.overlay.contains(e.target) && e.target !== trigger) this.close(); };
        trigger.addEventListener('click', e => { e.stopPropagation(); this.toggle(); });
        document.addEventListener('click', this._click);
    }
    toggle() { this.overlay ? this.close() : this.open(); }
    open() {
        this.close();
        if (this.hidden.value) this.pointer = new Date(this.hidden.value + 'T00:00:00');
        this.overlay = window.createFloatingPanel(this.trigger, 'chronos-picker');
        this._render();
    }
    close() { this.overlay?.remove(); this.overlay = null; }
    _render() {
        const p = new Date(this.pointer); p.setDate(1);
        const s = new Date(p); s.setDate(1 - p.getDay());
        const today = new Date().toISOString().slice(0, 10);
        const sel   = this.hidden.value;
        const cells = Array.from({ length: 42 }, (_, i) => {
            const d = new Date(s); d.setDate(s.getDate() + i);
            const k = d.toISOString().slice(0, 10), out = d.getMonth() !== p.getMonth();
            return `<button class="cal-day ${out?'is-outside':''} ${k===today?'today':''} ${k===sel?'is-selected':''}" type="button" data-k="${k}">
                <span class="cal-day__number">${d.getDate()}</span></button>`;
        }).join('');
        this.overlay.innerHTML = `
            <div class="cp-header">
                <button class="cp-nav" data-prev type="button">‹</button>
                <span class="cp-header-title">${p.toLocaleDateString([],{month:'long',year:'numeric'})}</span>
                <button class="cp-nav" data-next type="button">›</button>
            </div>
            <div class="calendar-board" style="margin-bottom:8px;">
                ${['S','M','T','W','T','F','S'].map(d=>`<div class="calendar-label" style="text-align:center;font-size:9px;font-weight:700;opacity:.5;padding:3px 0;">${d}</div>`).join('')}
                ${cells}
            </div>
            <div class="cp-footer">
                <button class="btn btn-ghost" data-clear type="button">Clear</button>
                <button class="btn btn-ghost" data-today type="button">Today</button>
            </div>`;
        this.overlay.querySelector('[data-prev]').onclick = e => { e.stopPropagation(); this.pointer.setMonth(this.pointer.getMonth()-1); this._render(); };
        this.overlay.querySelector('[data-next]').onclick = e => { e.stopPropagation(); this.pointer.setMonth(this.pointer.getMonth()+1); this._render(); };
        this.overlay.querySelector('[data-clear]').onclick = e => { e.stopPropagation(); this._pick(''); };
        this.overlay.querySelector('[data-today]').onclick = e => { e.stopPropagation(); this._pick(today); };
        this.overlay.querySelectorAll('[data-k]').forEach(b => {
            b.onclick = e => { e.stopPropagation(); if (!b.classList.contains('is-outside')) this._pick(b.dataset.k); };
        });
    }
    _pick(key) {
        this.hidden.value = key;
        if (this.display) this.display.textContent = key
            ? new Date(key+'T00:00:00').toLocaleDateString([],{month:'short',day:'numeric',year:'numeric'})
            : 'Pick date';
        this.trigger.classList.toggle('has-value', !!key);
        this.onSelect?.(key);
        this.close();
    }
};

// ----------------------------------------------------------
//  CHRONOS DATE+TIME PICKER
// ----------------------------------------------------------
window.ChronosDateTimePicker = class ChronosDateTimePicker {
    constructor({ trigger, hidden, display, onSelect }) {
        Object.assign(this, { trigger, hidden, display, onSelect });
        this.datePtr = new Date(); this.selDate = ''; this.hour = 9; this.minute = 0; this.phase = 'date'; this.overlay = null;
        this._click = e => { if (this.overlay && !this.overlay.contains(e.target) && e.target !== trigger) this.close(); };
        trigger.addEventListener('click', e => { e.stopPropagation(); this.toggle(); });
        document.addEventListener('click', this._click);
    }
    f(n)    { return String(n).padStart(2,'0'); }
    toggle(){ this.overlay ? this.close() : this.open(); }
    open() {
        this.close();
        if (this.hidden.value) {
            const [d,t] = this.hidden.value.split('T');
            this.selDate = d||'';
            if (this.selDate) this.datePtr = new Date(this.selDate+'T00:00:00');
            if (t) { const [h,m]=t.split(':'); this.hour=+h||9; this.minute=+m||0; }
        }
        this.phase='date'; this.overlay=window.createFloatingPanel(this.trigger,'chronos-picker'); this._render();
    }
    close() { this.overlay?.remove(); this.overlay=null; }
    _render(){ this.phase==='date'?this._renderDate():this._renderTime(); }
    _renderDate() {
        const p=new Date(this.datePtr); p.setDate(1);
        const s=new Date(p); s.setDate(1-p.getDay());
        const today=new Date().toISOString().slice(0,10);
        const cells=Array.from({length:42},(_,i)=>{
            const d=new Date(s); d.setDate(s.getDate()+i);
            const k=d.toISOString().slice(0,10), out=d.getMonth()!==p.getMonth();
            return `<button class="cal-day ${out?'is-outside':''} ${k===today?'today':''} ${k===this.selDate?'is-selected':''}" type="button" data-k="${k}">
                <span class="cal-day__number">${d.getDate()}</span></button>`;
        }).join('');
        this.overlay.innerHTML = `
            <p class="ctp-phase-label">① Pick a date</p>
            <div class="cp-header">
                <button class="cp-nav" data-prev type="button">‹</button>
                <span class="cp-header-title">${p.toLocaleDateString([],{month:'long',year:'numeric'})}</span>
                <button class="cp-nav" data-next type="button">›</button>
            </div>
            <div class="calendar-board" style="margin-bottom:8px;">
                ${['S','M','T','W','T','F','S'].map(d=>`<div class="calendar-label" style="text-align:center;font-size:9px;font-weight:700;opacity:.5;padding:3px 0;">${d}</div>`).join('')}
                ${cells}
            </div>
            <div class="cp-footer">
                <button class="btn btn-ghost" data-clear type="button">Clear</button>
                <button class="btn btn-ghost" data-today type="button">Today →</button>
            </div>`;
        this.overlay.querySelector('[data-prev]').onclick=e=>{e.stopPropagation();this.datePtr.setMonth(this.datePtr.getMonth()-1);this._renderDate();};
        this.overlay.querySelector('[data-next]').onclick=e=>{e.stopPropagation();this.datePtr.setMonth(this.datePtr.getMonth()+1);this._renderDate();};
        this.overlay.querySelector('[data-clear]').onclick=e=>{e.stopPropagation();this._clearAll();};
        this.overlay.querySelector('[data-today]').onclick=e=>{e.stopPropagation();this.selDate=new Date().toISOString().slice(0,10);this.phase='time';this._renderTime();};
        this.overlay.querySelectorAll('[data-k]').forEach(b=>{
            b.onclick=e=>{e.stopPropagation();if(!b.classList.contains('is-outside')){this.selDate=b.dataset.k;this.phase='time';this._renderTime();}};
        });
    }
    _renderTime() {
        const h12=this.hour%12||12, ampm=this.hour>=12?'PM':'AM';
        const dl=this.selDate?new Date(this.selDate+'T00:00:00').toLocaleDateString([],{month:'short',day:'numeric'}):'Today';
        this.overlay.innerHTML=`
            <p class="ctp-phase-label">② Set the time</p>
            <div class="cp-header">
                <button class="cp-nav" data-back type="button">‹</button>
                <span class="cp-header-title">${dl}</span>
                <span style="width:26px;"></span>
            </div>
            <div class="ctp-wheels">
                <div class="ctp-wheel">
                    <button class="ctp-arrow" data-hu type="button">▲</button>
                    <span class="ctp-digit">${this.f(h12)}</span>
                    <button class="ctp-arrow" data-hd type="button">▼</button>
                </div>
                <span class="ctp-colon">:</span>
                <div class="ctp-wheel">
                    <button class="ctp-arrow" data-mu type="button">▲</button>
                    <span class="ctp-digit">${this.f(this.minute)}</span>
                    <button class="ctp-arrow" data-md type="button">▼</button>
                </div>
                <div class="ctp-ampm">
                    <button class="ctp-ampm-btn ${ampm==='AM'?'active':''}" data-am type="button">AM</button>
                    <button class="ctp-ampm-btn ${ampm==='PM'?'active':''}" data-pm type="button">PM</button>
                </div>
            </div>
            <div class="cp-footer">
                <button class="btn btn-ghost" data-clear type="button">Clear</button>
                <button class="btn btn-primary" data-ok type="button">Set Reminder</button>
            </div>`;
        const adjH=d=>{
            const isAM=this.hour<12; let h=this.hour%12||12;
            h=((h-1+d+12)%12)+1;
            this.hour=isAM?(h===12?0:h):(h===12?12:h+12); this._renderTime();
        };
        this.overlay.querySelector('[data-back]').onclick=e=>{e.stopPropagation();this.phase='date';this._renderDate();};
        this.overlay.querySelector('[data-hu]').onclick=e=>{e.stopPropagation();adjH(1);};
        this.overlay.querySelector('[data-hd]').onclick=e=>{e.stopPropagation();adjH(-1);};
        this.overlay.querySelector('[data-mu]').onclick=e=>{e.stopPropagation();this.minute=(this.minute+5)%60;this._renderTime();};
        this.overlay.querySelector('[data-md]').onclick=e=>{e.stopPropagation();this.minute=(this.minute-5+60)%60;this._renderTime();};
        this.overlay.querySelector('[data-am]').onclick=e=>{e.stopPropagation();if(this.hour>=12)this.hour-=12;this._renderTime();};
        this.overlay.querySelector('[data-pm]').onclick=e=>{e.stopPropagation();if(this.hour<12)this.hour+=12;this._renderTime();};
        this.overlay.querySelector('[data-clear]').onclick=e=>{e.stopPropagation();this._clearAll();};
        this.overlay.querySelector('[data-ok]').onclick=e=>{
            e.stopPropagation();
            const d=this.selDate||new Date().toISOString().slice(0,10);
            const val=`${d}T${this.f(this.hour)}:${this.f(this.minute)}`;
            this.hidden.value=val;
            if(this.display) this.display.textContent=new Date(val).toLocaleString([],{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
            this.trigger.classList.add('has-value');
            this.onSelect?.(val); this.close();
        };
    }
    _clearAll() {
        this.hidden.value='';
        if(this.display) this.display.textContent='Set reminder';
        this.trigger.classList.remove('has-value');
        this.onSelect?.(''); this.close();
    }
};

// ----------------------------------------------------------
//  GLOBAL TIMER SYNC
// ----------------------------------------------------------
window.TimerSync = {
    getKey: () => 'crimson_global_timer',
    getState: () => {
        try { return JSON.parse(localStorage.getItem('crimson_global_timer')) || null; }
        catch(e) { return null; }
    },
    setState: (state) => {
        localStorage.setItem('crimson_global_timer', JSON.stringify(state));
    }
};

// ----------------------------------------------------------
//  SANCTUARY BRUTALIST CUSTOM CURSOR
// ----------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const supportsFinePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const cursor = document.querySelector('.cursor');
    const follower = document.querySelector('.cursor-follower');

    if (supportsFinePointer && cursor && follower) {
        document.body.classList.add('has-custom-cursor');

        let mouseX = window.innerWidth / 2;
        let mouseY = window.innerHeight / 2;
        let posX = mouseX;
        let posY = mouseY;

        cursor.style.opacity = '1';
        follower.style.opacity = '1';

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            cursor.style.transform = `translate3d(${mouseX - 5}px, ${mouseY - 5}px, 0)`;
        });

        const updateCursor = () => {
            posX += (mouseX - posX) / 8;
            posY += (mouseY - posY) / 8;
            follower.style.transform = `translate3d(${posX - 20}px, ${posY - 20}px, 0)`;
            requestAnimationFrame(updateCursor);
        };
        updateCursor();
    }
});
