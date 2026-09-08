#!/usr/bin/env python3
"""QPedia house converter: deliverable MD (full-space Persian) -> ready HTML body.
Allowed tags: h2 h3 p strong em blockquote ul ol li a details summary.
Usage: python3 tools/md2html.py <in.md> <out.html>
"""
import re, sys

NUM = '([۰-۹]+|\\d+)'


def inline(s):
    s = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', s)
    s = re.sub(r'\[([^\]]+)\]\((https?://[^)]+)\)', r'<a href="\2">\1</a>', s)
    return s


def convert(src, dst):
    t = open(src, encoding='utf-8').read()
    t = t.split('\n', 1)[1]
    t = t.split('## بستهٔ انتشار')[0].rstrip()
    lines = t.split('\n')
    out, i = [], 0

    def flush(buf):
        if not buf:
            return
        p = ' '.join(buf).strip()
        if p:
            out.append('<p>' + inline(p) + '</p>')

    buf = []
    while i < len(lines):
        ln = lines[i].rstrip()
        if ln.startswith('## '):
            flush(buf); buf = []
            out.append('<h2>' + ln[3:].strip() + '</h2>')
        elif ln.startswith('### '):
            flush(buf); buf = []
            out.append('<h3>' + ln[4:].strip() + '</h3>')
        elif ln.startswith('> '):
            flush(buf); buf = []
            q = []
            while i < len(lines) and lines[i].startswith('> '):
                q.append(lines[i][2:].strip()); i += 1
            out.append('<blockquote><p>' + inline(' '.join(q)) + '</p></blockquote>')
            continue
        elif re.match(r'^' + NUM + r'\. ', ln):
            flush(buf); buf = []
            items = []
            while i < len(lines) and re.match(r'^' + NUM + r'\. ', lines[i]):
                stripped = re.sub(r'^' + NUM + r'\. ', '', lines[i].rstrip())
                items.append('<li>' + inline(stripped) + '</li>'); i += 1
            out.append('<ol>' + ''.join(items) + '</ol>')
            continue
        elif ln.startswith('- '):
            flush(buf); buf = []
            items = []
            while i < len(lines) and lines[i].startswith('- '):
                items.append('<li>' + inline(lines[i][2:].rstrip()) + '</li>'); i += 1
            out.append('<ul>' + ''.join(items) + '</ul>')
            continue
        elif re.match(r'^\*\*' + NUM + r'\. ', ln):
            flush(buf); buf = []
            q = re.match(r'^\*\*(.+?)\*\*\s*$', ln).group(1)
            ans = []
            while i + 1 < len(lines) and lines[i + 1].strip() and \
                    not lines[i + 1].startswith(('**', '##', '- ', '> ')) and \
                    not re.match(r'^' + NUM + r'\. ', lines[i + 1]):
                i += 1; ans.append(lines[i].strip())
            out.append('<details><summary><strong>' + inline(q) + '</strong></summary><p>'
                       + inline(' '.join(ans)) + '</p></details>')
        elif ln.strip() == '':
            flush(buf); buf = []
        else:
            buf.append(ln.strip())
        i += 1
    flush(buf)
    res = '\n'.join(out) + '\n'
    open(dst, 'w', encoding='utf-8').write(res)
    words = len(re.findall(r'[\u0600-\u06FF]+|[A-Za-z0-9]+', re.sub(r'<[^>]+>', ' ', res)))
    print(f"{dst} | details: {res.count('<details>')} | h2: {res.count('<h2>')} | "
          f"links: {res.count('<a href')} | ZWNJ: {res.count(chr(0x200c))} | words: {words} | "
          f"منابع last: {res.rstrip().endswith('</ol>')}")


if __name__ == '__main__':
    convert(sys.argv[1], sys.argv[2])
