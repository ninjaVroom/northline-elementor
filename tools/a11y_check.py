"""Static accessibility smoke check for Northline routes. Usage: python tools/a11y_check.py [base_url]"""
import sys, urllib.request
from collections import Counter
from bs4 import BeautifulSoup

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://northline.local"
PAGES = ['/', '/services/', '/services/throughput-and-oee-recovery/', '/case-studies/', '/case-studies/tier-2-automotive-stamping-consolidation/',
         '/pricing/', '/contact/', '/book/', '/resources/', '/resources/category/smed/', '/resources/measuring-true-oee-baselines/',
         '/guides/operations-review/', '/privacy/', '/cookies/', '/about/', '/campaign/operations-sprint/', '/?s=changeover', '/nope/']

def check(html):
    d = BeautifulSoup(html, 'html.parser'); out = []
    if not d.html.get('lang'): out.append('no lang')
    if not d.find('main'): out.append('no main')
    if len(d.find_all('h1')) != 1: out.append(f"h1 count {len(d.find_all('h1'))}")
    lv = [int(h.name[1]) for h in d.find('main').find_all(['h1', 'h2', 'h3', 'h4'])] if d.find('main') else []
    out += [f'skip h{a}>h{b}' for a, b in zip(lv, lv[1:]) if b > a + 1]
    out += ['img no alt: ' + i.get('src', '')[-40:] for i in d.find_all('img') if i.get('alt') is None]
    for a in d.find_all(['a', 'button']):
        if a.name == 'button' and a.find_parent('select'):  # customizable select: <selectedcontent> mirrors the chosen option
            continue
        if not (a.get_text(strip=True) or a.get('aria-label')): out.append('no name: ' + str(a)[:70])
    for i in d.find_all(['input', 'select', 'textarea']):
        if i.get('type') == 'hidden' or i.find_parent(class_='nl-hp'): continue
        if not ((i.get('id') and d.find('label', attrs={'for': i['id']})) or i.find_parent('label') or i.get('aria-label')):
            out.append('no label: ' + str(i.get('name')))
    out += ['dup id: ' + k for k, v in Counter(e['id'] for e in d.find_all(id=True)).items() if v > 1]
    return out

bad = 0
for p in PAGES:
    try:
        r = urllib.request.urlopen(BASE + p); code, html = r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        code, html = e.code, e.read().decode()
    issues = check(html); bad += bool(issues)
    print(code, p, 'ok' if not issues else issues)
sys.exit(1 if bad else 0)
