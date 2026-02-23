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

// allow specifying config file via --config or -c, default to site.config.json
function parseConfigArg() {
  const argv = process.argv.slice(2);
  for (let i = 0; i < argv.length; i++) {
    const a = argv[i];
    if (a.startsWith('--config=')) return a.split('=')[1];
    if (a === '--config' || a === '-c') return argv[i + 1];
  }
  // also check env var
  if (process.env.SITE_CONFIG) return process.env.SITE_CONFIG;
  return 'site.config.json';
}

const configPath = path.resolve(ROOT, parseConfigArg());
const site = fs.pathExistsSync(configPath)
  ? fs.readJsonSync(configPath)
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

  // find all markdown files recursively under CONTENT_DIR
  function findMarkdownFiles(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    let results = [];
    for (const e of entries) {
      const p = path.join(dir, e.name);
      if (e.isDirectory()) {
        results = results.concat(findMarkdownFiles(p));
      } else if (e.isFile() && e.name.endsWith('.md')) {
        results.push(p);
      }
    }
    return results;
  }

  const mdFiles = findMarkdownFiles(CONTENT_DIR);
  const posts = mdFiles.map(filePath => {
    const relPath = path.relative(CONTENT_DIR, filePath).replace(/\\/g, '/');
    const src = fs.readFileSync(filePath, 'utf8');
    const { data, content } = matter(src);
    const html = md.render(content);
    const title = data.title || path.basename(filePath, '.md');
    const filename = path.basename(filePath);
    const isReadme = filename.toLowerCase() === 'readme.md';
    // slug: for README.md use parent relative dir, otherwise use filename (or data.slug)
    let slug;
    if (isReadme) {
      const parent = path.dirname(relPath);
      slug = parent === '.' ? (data.slug || slugify(title, { lower: true, strict: true })) : parent;
    } else {
      const basename = path.basename(filePath, '.md');
      slug = data.slug || slugify(basename, { lower: true, strict: true });
    }
    const date = data.date || fs.statSync(filePath).mtime.toISOString();
    const url = site.baseUrl ? `${site.baseUrl.replace(/\/$/, '')}/${slug.replace(/^\//, '')}/` : `/${slug.replace(/^\//, '')}/`;
    const basename = isReadme ? path.basename(slug) : path.basename(filePath, '.md');
    return { ...data, title, contentHtml: html, slug, date, url, basename, relPath, isReadme };
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
    // determine output path based on whether this is a README inside a directory
    const parentRel = path.dirname(post.relPath);
    const parentPath = parentRel === '.' ? '' : parentRel;
    const outDir = path.join(OUT_DIR, parentPath);
    fs.ensureDirSync(outDir);
    const html = postTpl({ site, page: post });
    if (post.isReadme) {
      // content/posts/<dir>/README.md -> dist/<dir>/index.html
      fs.writeFileSync(path.join(outDir, 'index.html'), html, 'utf8');
      // set URL to directory URL
      post.url = site.baseUrl ? `${site.baseUrl.replace(/\/$/, '')}/${parentPath.replace(/^\//, '')}/` : `/${parentPath.replace(/^\//, '')}/`;
    } else {
      // content/posts/<dir>/name.md -> dist/<dir>/name.html
      const fileName = `${post.basename}.html`;
      fs.writeFileSync(path.join(outDir, fileName), html, 'utf8');
      post.url = site.baseUrl ? `${site.baseUrl.replace(/\/$/, '')}/${(parentPath ? parentPath + '/' : '')}${fileName}` : `/${(parentPath ? parentPath + '/' : '')}${fileName}`;
    }
  }

  // write index
  const indexHtml = indexTpl({ site, posts });
  fs.writeFileSync(path.join(OUT_DIR, 'index.html'), indexHtml, 'utf8');

  // Note: Do not process repository root README.md; build only processes files under content/posts

  console.log(`Built ${posts.length} posts to ${OUT_DIR}`);
}

build().catch(err => {
  console.error(err);
  process.exit(1);
});
