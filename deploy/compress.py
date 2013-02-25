
import os
import tempfile
try:
    import simplejson as json
except:
    import json


PROJECT_DIR  = os.path.realpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))
SRC_DIR      = os.path.join(PROJECT_DIR, 'www', '_src')

JS_SRC_DIR   = os.path.join(SRC_DIR, 'js')
CSS_SRC_DIR  = os.path.join(SRC_DIR, 'css')

JS_DEST_DIR  = os.path.join(PROJECT_DIR, 'www', 'js')
CSS_DEST_DIR = os.path.join(PROJECT_DIR, 'www', 'css')

COMPRESSOR   = os.path.join(PROJECT_DIR, 'deploy', 'yuicompressor-2.4.2.jar')

if not os.path.exists(JS_DEST_DIR):
    os.makedirs(JS_DEST_DIR)

if not os.path.exists(CSS_DEST_DIR):
    os.makedirs(CSS_DEST_DIR)

def compress_js(src, dest):
    """docstring for co"""
    print 'Compressing %s => %s' % (src, dest)
    os.system('java -jar %s --type js %s -o %s' % (COMPRESSOR, src, dest))

def compress_css(src, dest):
    """docstring for compress_css"""
    print 'Compressing %s => %s' % (src, dest)
    os.system('java -jar %s --type css %s -o %s' % (COMPRESSOR, src, dest))

def combine_files(files, dest):
    print 'Combining %s => %s' % (files, dest)
    f = open(dest, 'wb')
    try:
        for fn in files:
            x = open(fn, 'rb')
            f.write(x.read())
            f.write('\n')
            x.close()
    finally:
        f.close()

def main():
    f = open(os.path.join(JS_SRC_DIR, 'scripts.json'), 'rb')
    libs = json.loads(f.read())
    f.close()

    # compress js
    print 'Compressing javascripts:'
    for k, v in libs.items():
        sources = [os.path.join(JS_SRC_DIR, item + '.js') for item in v] 
        dest = os.path.join(JS_DEST_DIR, k + '.js')

        if os.path.exists(dest):
            ctime = os.path.getmtime(dest)
            new = [s for s in sources if os.path.getmtime(s) > ctime]
            if not new: continue

        if len(v) > 1:
            fd, src = tempfile.mkstemp()
            os.close(fd)
            combine_files([os.path.join(JS_SRC_DIR, item + '.js') for item in v], src)
            compress_js(src, dest)
            os.unlink(src)
        else:
            src = os.path.join(JS_SRC_DIR, v[0] + '.js')
            compress_js(src, dest)

    # compress css
    print 'Compressing stylesheets:'
    css_files = os.listdir(CSS_SRC_DIR)

    for cf in css_files:
        source = os.path.join(CSS_SRC_DIR, cf)
        dest = os.path.join(CSS_DEST_DIR, cf)
        if os.path.exists(dest) and os.path.getmtime(source) < os.path.getmtime(dest):
            continue
        compress_css(source, dest)


if __name__ == '__main__':
    main()

