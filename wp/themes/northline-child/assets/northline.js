/* Northline front end. Progressive enhancement only: every feature works without this file. */
(() => {
	const d = document;
	const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Light / dark theme. The head script already set data-theme before paint. */
	const themeBtn = d.querySelector('[data-theme-toggle]');
	if (themeBtn) {
		const html = d.documentElement;
		const meta = d.querySelector('meta[name="theme-color"]');
		const sync = () => {
			const dark = html.dataset.theme === 'dark';
			themeBtn.setAttribute('aria-pressed', String(dark));
			themeBtn.setAttribute('aria-label', 'Dark theme');
			themeBtn.title = dark ? 'Switch to light theme' : 'Switch to dark theme';
			if (meta) meta.content = dark ? '#10171e' : '#f6f7f5';
		};
		const apply = (t, save) => {
			html.dataset.theme = t;
			sync();
			if (save) { try { localStorage.setItem('nl-theme', t); } catch (e) { /* private mode: theme still applies for this page */ } }
		};
		sync();
		themeBtn.addEventListener('click', () => {
			const next = html.dataset.theme === 'dark' ? 'light' : 'dark';
			if (reduce || !d.startViewTransition) { apply(next, true); return; }
			// Circular reveal of the new theme, expanding from the toggle.
			const r = themeBtn.getBoundingClientRect();
			const x = r.left + r.width / 2;
			const y = r.top + r.height / 2;
			const radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
			html.classList.add('nl-theme-vt');
			const vt = d.startViewTransition(() => apply(next, true));
			vt.ready.then(() => html.animate(
				{ clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
				{ duration: 700, easing: 'cubic-bezier(0.65, 0, 0.35, 1)', pseudoElement: '::view-transition-new(root)' }
			)).catch(() => {});
			vt.finished.finally(() => html.classList.remove('nl-theme-vt'));
		});
		// Follow the system setting until the visitor makes a choice.
		matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
			let saved = null;
			try { saved = localStorage.getItem('nl-theme'); } catch (err) { /* ignore */ }
			if (!saved) apply(e.matches ? 'dark' : 'light', false);
		});
	}

	/* Motion system: reveals, split headings, image unmasking, hero parallax. */
	if (!reduce && 'IntersectionObserver' in window) {
		const root = d.documentElement;
		// Split headings into words (text-only headings; content and order are unchanged).
		d.querySelectorAll('.nl-display, h2.nl-h2, .nl-hero__kicker').forEach((h) => {
			if (h.children.length || h.closest('.nl-spot, .nl-form-done')) return;
			const words = h.textContent.trim().split(/\s+/);
			h.textContent = '';
			words.forEach((w, i) => {
				const outer = d.createElement('span');
				const inner = d.createElement('span');
				outer.className = 'nl-w';
				inner.textContent = w;
				inner.style.setProperty('--w', i);
				outer.append(inner);
				h.append(outer, i < words.length - 1 ? ' ' : '');
			});
			h.classList.add('nl-split');
		});
		// Extra blocks that rise in, and image frames that unmask.
		d.querySelectorAll('.nl-faq__item, .nl-glance, .nl-doors__main > .nl-btn, .nl-doors__aside, .nl-form, .nl-form-done, .nl-contact-aside, .nl-booking, .nl-chips, .nl-filters, .nl-count, .nl-rich .nl-prose, .nl-engage__head > .nl-read, .nl-sec__head > a, .nl-engage__foot, .nl-packages__note, .nl-article__body > *, .nl-doc .nl-prose > *, .nl-guide, .nl-lead__grid > div, .nl-results > li')
			.forEach((el) => el.setAttribute('data-reveal', ''));
		d.querySelectorAll('.nl-svc__img, .nl-case__img, .nl-person__img, .nl-post__img, .nl-lead__img, .nl-guide-sec__cover, .nl-spot__img, .nl-article__img')
			.forEach((el) => el.setAttribute('data-reveal-img', ''));
		// Stagger by position among revealing siblings (capped so long lists don't lag).
		d.querySelectorAll('[data-reveal]').forEach((el) => {
			const sibs = [...el.parentElement.children].filter((c) => c.hasAttribute('data-reveal'));
			el.style.setProperty('--i', Math.min(sibs.indexOf(el), 5));
		});
		[...d.querySelectorAll('.nl-hero__copy > :not(h1)')].forEach((el, i) => el.style.setProperty('--i', i));
		d.querySelectorAll('.nl-hero .nl-split').forEach((h) => h.style.setProperty('--d', '120ms'));

		const io = new IntersectionObserver((entries) => {
			entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
		}, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
		const pending = [...d.querySelectorAll('[data-reveal], [data-reveal-img], .nl-split, .nl-proof, .nl-map')];
		pending.forEach((el) => io.observe(el));
		root.classList.add('nl-motion', 'nl-ready');
		// Safety net: anything already scrolled past (anchor jumps, fast flings) is shown at once.
		const sweep = () => pending.forEach((el) => { if (!el.classList.contains('is-in') && el.getBoundingClientRect().top < innerHeight * 0.9) { el.classList.add('is-in'); io.unobserve(el); } });
		addEventListener('scrollend', sweep, { passive: true });
		addEventListener('load', () => setTimeout(sweep, 400));

		// Hero photograph drifts against the scroll (composes with the entrance zoom via `translate`).
		const heroImg = d.querySelector('.nl-hero__frame img');
		if (heroImg) {
			heroImg.style.scale = '1.12';
			let ticking = false;
			addEventListener('scroll', () => {
				if (ticking) return;
				ticking = true;
				requestAnimationFrame(() => { heroImg.style.translate = `0 ${Math.min(scrollY * 0.09, 48).toFixed(1)}px`; ticking = false; });
			}, { passive: true });
		}
	}

	/* Header: mobile menu + hide on scroll down, show on scroll up. */
	const header = d.querySelector('[data-header]');
	const btn = d.querySelector('[data-menu-btn]');
	if (header && btn) {
		const close = (focus) => { header.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); if (focus) btn.focus(); };
		btn.addEventListener('click', () => {
			const open = btn.getAttribute('aria-expanded') !== 'true';
			btn.setAttribute('aria-expanded', String(open));
			header.classList.toggle('is-open', open);
		});
		d.addEventListener('keydown', (e) => { if (e.key === 'Escape' && header.classList.contains('is-open')) close(true); });
		let last = scrollY;
		addEventListener('scroll', () => {
			const y = scrollY;
			const hide = y > last && y > 240 && !header.classList.contains('is-open') && !header.contains(d.activeElement);
			header.classList.toggle('is-hidden', hide);
			last = y;
		}, { passive: true });
	}

	/* Services mega panel: hover opens it (CSS); the chevron button opens it for keyboard and touch. */
	d.querySelectorAll('.nl-has-mega').forEach((li) => {
		const toggle = li.querySelector('.nl-mega-toggle');
		if (!toggle) return;
		const set = (open) => { li.classList.toggle('is-open', open); toggle.setAttribute('aria-expanded', String(open)); };
		toggle.addEventListener('click', () => set(!li.classList.contains('is-open')));
		li.addEventListener('focusout', (e) => { if (!li.contains(e.relatedTarget)) set(false); });
		li.addEventListener('keydown', (e) => {
			if (e.key !== 'Escape') return;
			set(false);
			li.classList.add('is-suppressed'); // keep it shut even while the pointer rests on it
			toggle.focus();
		});
		li.addEventListener('mouseleave', () => li.classList.remove('is-suppressed'));
		d.addEventListener('click', (e) => { if (!li.contains(e.target)) set(false); });
	});

	/* Metric count-up: animates the number inside the final string; the HTML already holds the final value. */
	const counters = d.querySelectorAll('[data-count]');
	if (counters.length && !reduce && 'IntersectionObserver' in window) {
		const io = new IntersectionObserver((entries) => {
			entries.forEach(({ isIntersecting, target }) => {
				if (!isIntersecting) return;
				io.unobserve(target);
				const final = target.textContent;
				const m = final.match(/^(\D*?)(\d[\d,]*\.?\d*)(.*)$/);
				if (!m) return;
				const [, pre, num, post] = m;
				const to = parseFloat(num.replace(/,/g, ''));
				const dec = (num.split('.')[1] || '').length;
				const t0 = performance.now();
				const step = (t) => {
					const p = Math.min(1, (t - t0) / 1100);
					const v = to * (1 - Math.pow(1 - p, 3));
					target.textContent = pre + v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + post;
					if (p < 1) requestAnimationFrame(step); else target.textContent = final;
				};
				requestAnimationFrame(step);
			});
		}, { threshold: 0.6 });
		counters.forEach((c) => io.observe(c));
	}

	/* Case filters: apply on change (the submit button stays for no-JS). */
	d.querySelectorAll('form[data-autosubmit] select').forEach((s) => s.addEventListener('change', () => s.form.submit()));

	/* Multi-step inquiry form. Server validation remains authoritative. */
	d.querySelectorAll('form[data-steps]').forEach((form) => {
		const steps = [...form.querySelectorAll('.nl-form__step')];
		const progress = form.querySelector('[data-progress]');
		const prev = form.querySelector('[data-prev]');
		const next = form.querySelector('[data-next]');
		const submit = form.querySelector('[data-submit]');
		const review = form.querySelector('[data-review]');
		const utm = form.querySelector('[data-utm]');
		if (utm) utm.value = [...new URLSearchParams(location.search)].filter(([k]) => k.startsWith('utm_')).map(([k, v]) => `${k}=${v}`).join('&').slice(0, 300);
		let i = 0;
		const errorFor = (el) => {
			if (el.type === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(el.value.trim()) ? '' : 'Please enter a valid business email address.';
			if (el.type === 'checkbox') return el.checked ? '' : 'Please confirm we may contact you about this inquiry.';
			if (el.type === 'number') { const n = Number(el.value); return Number.isInteger(n) && n >= 1 && n <= 99 ? '' : 'Please enter a plant count between 1 and 99.'; }
			return el.value.trim() ? '' : `Please enter your ${el.labels[0].textContent.replace('*', '').trim().toLowerCase()}.`;
		};
		const check = (step) => {
			let first = null;
			step.querySelectorAll('[required]').forEach((el) => {
				const field = el.closest('.nl-field');
				field.querySelector('.nl-field__err')?.remove();
				const msg = errorFor(el);
				field.classList.toggle('is-invalid', !!msg);
				if (msg) {
					const p = d.createElement('p');
					p.className = 'nl-field__err';
					p.id = `nl-e-${el.name}`;
					p.textContent = msg;
					field.append(p);
					el.setAttribute('aria-invalid', 'true');
					el.setAttribute('aria-describedby', p.id);
					first ??= el;
				} else {
					el.removeAttribute('aria-invalid');
					el.removeAttribute('aria-describedby');
				}
			});
			first?.focus();
			return !first;
		};
		const show = (n, focus) => {
			i = n;
			steps.forEach((s, k) => s.classList.toggle('is-current', k === i));
			prev.hidden = i === 0;
			next.hidden = i === steps.length - 1;
			submit.hidden = i !== steps.length - 1;
			progress.textContent = `Step ${i + 1} of ${steps.length}: ${steps[i].dataset.title}`;
			if (i === steps.length - 1 && review) {
				review.hidden = false;
				review.replaceChildren(...['interest', 'email', 'company', 'role', 'plants'].flatMap((name) => {
					const el = form.elements[name];
					const dt = d.createElement('dt'); dt.textContent = el.labels[0].textContent.replace('*', '').trim();
					const dd = d.createElement('dd'); dd.textContent = el.value || '—';
					return [dt, dd];
				}));
			}
			if (focus) steps[i].querySelector('input, select, textarea')?.focus();
		};
		next.addEventListener('click', () => { if (check(steps[i])) show(i + 1, true); });
		prev.addEventListener('click', () => show(i - 1, true));
		form.addEventListener('submit', (e) => {
			const bad = steps.findIndex((s) => { s.classList.add('is-current'); const ok = check(s); s.classList.toggle('is-current', s === steps[i]); return !ok; });
			if (bad !== -1) { e.preventDefault(); show(bad, false); check(steps[bad]); return; }
			submit.disabled = true;
			submit.textContent = 'Sending…';
		});
		// Server error: open the step that holds the invalid field.
		const invalid = form.querySelector('[aria-invalid="true"]');
		show(invalid ? steps.indexOf(invalid.closest('.nl-form__step')) : 0, false);
		form.querySelector('.nl-form__summary')?.focus();
	});
	d.querySelector('.nl-form-done')?.focus();

	/* Office map: cards and pins highlight each other; the route runner stops for reduced motion. */
	d.querySelectorAll('.nl-map-sec').forEach((sec) => {
		if (reduce) sec.querySelector('.nl-map__route')?.pauseAnimations?.();
		const hot = (slug, on) => sec.querySelectorAll(`[data-office="${slug}"]`).forEach((el) => el.classList.toggle('is-hot', on));
		sec.querySelectorAll('.nl-office').forEach((card) => {
			const slug = card.dataset.office;
			['mouseenter', 'focusin'].forEach((ev) => card.addEventListener(ev, () => hot(slug, true)));
			['mouseleave', 'focusout'].forEach((ev) => card.addEventListener(ev, () => hot(slug, false)));
		});
	});

	/* Scheduler: one day's times at a time, a live summary, and no submit without a time. */
	d.querySelectorAll('form[data-sched]').forEach((form) => {
		const summary = form.querySelector('[data-sched-summary]');
		const groups = [...form.querySelectorAll('.nl-slots')];
		const tz = form.querySelector('.nl-sched__tz')?.textContent.replace(/[()]/g, '') || '';
		const say = () => {
			const slot = form.querySelector('input[name="slot"]:checked');
			if (!slot) { summary.textContent = ''; return; }
			const day = slot.closest('.nl-slots').querySelector('.nl-slots__day').textContent;
			summary.textContent = `${day} at ${slot.nextElementSibling.textContent} (${tz})`;
		};
		const showDay = (day) => {
			groups.forEach((g) => {
				const on = g.dataset.day === day;
				g.classList.toggle('is-current', on);
				if (!on) g.querySelectorAll('input:checked').forEach((i) => { i.checked = false; });
			});
			say();
		};
		form.querySelectorAll('input[name="day"]').forEach((r) => r.addEventListener('change', () => showDay(r.value)));
		form.querySelectorAll('input[name="slot"]').forEach((r) => r.addEventListener('change', say));
		showDay(form.querySelector('input[name="day"]:checked')?.value || groups[0]?.dataset.day);
		form.addEventListener('submit', (e) => {
			if (!form.querySelector('input[name="slot"]:checked')) {
				e.preventDefault();
				summary.textContent = 'Please pick a time first.';
				form.querySelector('.nl-slots.is-current input:not(:disabled)')?.focus();
			}
		});
		form.querySelector('.nl-form__summary')?.focus();
	});
	d.querySelector('#nl-book.nl-form-done')?.focus();

	/* Consent preferences: first-party cookie, one year. No optional scripts load until granted. */
	d.querySelectorAll('form[data-consent]').forEach((form) => {
		const status = form.querySelector('[data-consent-status]');
		const saved = Object.fromEntries((d.cookie.match(/(?:^|; )nl_consent=([^;]*)/)?.[1] || '').split('|').filter(Boolean).map((k) => [k, true]));
		['analytics', 'scheduling'].forEach((k) => { form.elements[k].checked = !!saved[k]; });
		const save = (vals) => {
			d.cookie = `nl_consent=${vals.join('|')}; max-age=31536000; path=/; SameSite=Lax`;
			status.textContent = 'Preferences saved.';
		};
		form.addEventListener('submit', (e) => { e.preventDefault(); save(['essential', ...['analytics', 'scheduling'].filter((k) => form.elements[k].checked)]); });
		form.querySelector('[data-consent-reject]').addEventListener('click', () => { ['analytics', 'scheduling'].forEach((k) => { form.elements[k].checked = false; }); save(['essential']); });
	});

	/* 3D assembly: rendered frame sequence scrubbed by scroll through the steps. */
	const asm = d.querySelector('.nl-assembly');
	if (asm && Number(asm.dataset.count) > 1 && !reduce && 'IntersectionObserver' in window) {
		const section = asm.closest('.nl-engage');
		const canvas = asm.querySelector('canvas');
		const ctx = canvas.getContext('2d');
		const base = asm.dataset.frames;
		const count = Number(asm.dataset.count);
		const frames = [];
		let loaded = 0;
		let current = -1;
		const draw = (n) => {
			const img = frames[n];
			if (!img || !img.complete || n === current) return;
			current = n;
			ctx.clearRect(0, 0, canvas.width, canvas.height);
			ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
		};
		const head = section.querySelector('.nl-engage__head');
		const progress = () => {
			// Desktop: the model is pinned, so progress runs across the whole section.
			if (getComputedStyle(head).position === 'sticky') {
				const r = section.getBoundingClientRect();
				const total = r.height - innerHeight;
				return Math.min(1, Math.max(0, -r.top / (total > 0 ? total : 1)));
			}
			// Phone/tablet: it assembles while crossing the screen (bottom 90% to top 20%).
			const r = asm.getBoundingClientRect();
			const p = (innerHeight * 0.9 - r.top) / (innerHeight * 0.7);
			return Math.min(1, Math.max(0, p));
		};
		const update = () => draw(Math.round(progress() * (count - 1)));
		const load = () => {
			for (let n = 0; n < count; n++) {
				const img = new Image();
				img.decoding = 'async';
				img.src = `${base}${String(n).padStart(3, '0')}.webp`;
				img.onload = () => { if (++loaded === count) { asm.classList.add('is-live'); current = -1; update(); } };
				frames[n] = img;
			}
		};
		new IntersectionObserver((entries, o) => { if (entries[0].isIntersecting) { o.disconnect(); load(); } }, { rootMargin: '600px' }).observe(asm);
		addEventListener('scroll', () => { if (loaded === count) requestAnimationFrame(update); }, { passive: true });
	}
	/* Active step highlight beside the assembly (not motion; runs with reduced motion too). */
	if (asm && 'IntersectionObserver' in window) {
		const stepIO = new IntersectionObserver((entries) => {
			entries.forEach(({ isIntersecting, target }) => target.classList.toggle('is-active', isIntersecting));
		}, { rootMargin: '-40% 0px -40% 0px' });
		asm.closest('.nl-engage').querySelectorAll('.nl-step').forEach((s) => stepIO.observe(s));
	}
})();
