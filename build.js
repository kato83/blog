const fs = require('fs-extra');
const path = require('path');
const matter = require('gray-matter');
const MarkdownIt = require('markdown-it');
const markdownItAnchor = require('markdown-it-anchor');
const Handlebars = require('handlebars');
const slugify = require('slugify');

const ROOT = path.resolve(__dirname);
const CONTENT_DIR = path.join(ROOT, 'content', 'posts');
const OUT_DIR = path.join(ROOT, 'dist');
const TEMPLATES_DIR = path.join(ROOT, 'templates');

const site = fs.pathExistsSync(path.join(ROOT, 'site.config.json'))
  ? fs.readJsonSync(path.join(ROOT, 'site.config.json'))
  : { title: 'My Blog', description: '', baseUrl: '' };

const md = new MarkdownIt({ html: true, linkify: true }).use(markdownItAnchor);

function loadTemplate(name) {
  const p = path.join(TEMPLATES_DIR, name);
  return Handlebars.compile(fs.readFileSync(p, 'utf8'));
}

async function build() {
  fs.removeSync(OUT_DIR);
  fs.ensureDirSync(OUT_DIR);

  const postTpl = loadTemplate('post.hbs');
  const indexTpl = loadTemplate('index.hbs');

  if (!fs.existsSync(CONTENT_DIR)) {
    console.warn('No content found. Create markdown files in content/posts/');
    return;
  }

  const files = fs.readdirSync(CONTENT_DIR).filter(f => f.endsWith('.md'));
  const posts = files.map(file => {
    const src = fs.readFileSync(path.join(CONTENT_DIR, file), 'utf8');
    const { data, content } = matter(src);
    const html = md.render(content);
    const title = data.title || path.basename(file, '.md');
    const basename = path.basename(file, '.md');
    const slug = data.slug || slugify(title, { lower: true, strict: true });
    const date = data.date || fs.statSync(path.join(CONTENT_DIR, file)).mtime.toISOString();
    const url = site.baseUrl ? `${site.baseUrl.replace(/\/$/, '')}/${slug}/` : `/${slug}/`;
    return { ...data, title, contentHtml: html, slug, date, url, basename };
  });

  posts.sort((a, b) => new Date(b.date) - new Date(a.date));

  // copy non-markdown assets from content/posts to dist (preserve relative paths)
  function walkDir(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    let results = [];
    for (const e of entries) {
      const p = path.join(dir, e.name);
      if (e.isDirectory()) {
        results = results.concat(walkDir(p));
      } else {
        results.push(p);
      }
    }
    return results;
  }

  const allEntries = walkDir(CONTENT_DIR);
  const assetFiles = allEntries.filter(p => !p.endsWith('.md'));
  for (const asset of assetFiles) {
    const rel = path.relative(CONTENT_DIR, asset);
    const dest = path.join(OUT_DIR, rel);
    fs.ensureDirSync(path.dirname(dest));
    fs.copyFileSync(asset, dest);
  }


  // write each post
  for (const post of posts) {
    const outDir = path.join(OUT_DIR, post.slug);
    fs.ensureDirSync(outDir);
    const html = postTpl({ site, page: post });
    fs.writeFileSync(path.join(outDir, 'index.html'), html, 'utf8');
    // also write a flat html file using the original markdown basename (e.g. hello.md -> dist/hello.html)
    if (post.basename) {
      const flatPath = path.join(OUT_DIR, `${post.basename}.html`);
      fs.writeFileSync(flatPath, html, 'utf8');
    }
  }

  // write index
  const indexHtml = indexTpl({ site, posts });
  fs.writeFileSync(path.join(OUT_DIR, 'index.html'), indexHtml, 'utf8');

  // if repository README.md exists at root, build it to dist/index.html (overrides index)
  const readmePath = path.join(ROOT, 'README.md');
  if (fs.existsSync(readmePath)) {
    try {
      const src = fs.readFileSync(readmePath, 'utf8');
      const { data, content } = matter(src);
      const html = md.render(content);
      const page = { title: data.title || site.title, contentHtml: html };
      const readmeTpl = loadTemplate('post.hbs');
      const readmeHtml = readmeTpl({ site, page });
      fs.writeFileSync(path.join(OUT_DIR, 'index.html'), readmeHtml, 'utf8');
    } catch (e) {
      console.warn('Failed to build README.md into index.html:', e.message || e);
    }
  }

  console.log(`Built ${posts.length} posts to ${OUT_DIR}`);
}

build().catch(err => {
  console.error(err);
  process.exit(1);
});
