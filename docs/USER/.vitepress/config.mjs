import { defineConfig } from 'vitepress'
import llmstxt from 'vitepress-plugin-llms'
import { copyOrDownloadAsMarkdownButtons } from 'vitepress-plugin-llms'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  title: 'Health Checker for Joomla',
  description: 'Free Joomla 5+ extension with 130+ automated health checks for security, performance, SEO, and database. Instant reports from your admin panel.',
  base: '/docs/',
  outDir: '../../website/public/docs',
  cleanUrls: true,
  lang: 'en-US',

  head: [
    // SEO Meta Tags
    ['meta', { name: 'keywords', content: 'Joomla Health Checker, Joomla site health, Joomla security check, Joomla performance audit, Joomla 5 extension, Joomla monitoring' }],
    ['meta', { name: 'robots', content: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' }],
    ['meta', { name: 'author', content: 'Phil E. Taylor, mySites.guru' }],

    // PWA/Mobile Meta Tags
    ['meta', { name: 'theme-color', content: '#1e3a8a' }],
    ['meta', { name: 'msapplication-TileColor', content: '#1e3a8a' }],
    ['meta', { name: 'apple-mobile-web-app-capable', content: 'yes' }],
    ['meta', { name: 'mobile-web-app-capable', content: 'yes' }],
    ['meta', { name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent' }],

    // Open Graph / Facebook
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:site_name', content: 'Health Checker for Joomla' }],
    ['meta', { property: 'og:locale', content: 'en_US' }],
    ['meta', { property: 'og:image', content: 'https://www.joomlahealthchecker.com/social-preview.jpg' }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { property: 'og:image:alt', content: 'Health Checker for Joomla - Secure, Fast, and Reliable. Get Your Site\'s Diagnosis.' }],

    // Twitter Card
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:image', content: 'https://www.joomlahealthchecker.com/social-preview.jpg' }],
    ['meta', { name: 'twitter:image:alt', content: 'Health Checker for Joomla - Secure, Fast, and Reliable. Get Your Site\'s Diagnosis.' }],
    ['meta', { name: 'twitter:creator', content: '@PhilETaylor' }],

    // Favicons
    ['link', { rel: 'icon', type: 'image/png', href: 'https://www.joomlahealthchecker.com/favicon-96x96.png', sizes: '96x96' }],
    ['link', { rel: 'icon', type: 'image/svg+xml', href: 'https://www.joomlahealthchecker.com/favicon.svg' }],
    ['link', { rel: 'shortcut icon', href: 'https://www.joomlahealthchecker.com/favicon.ico' }],
    ['link', { rel: 'apple-touch-icon', sizes: '180x180', href: 'https://www.joomlahealthchecker.com/apple-touch-icon.png' }],
    ['link', { rel: 'manifest', href: 'https://www.joomlahealthchecker.com/site.webmanifest' }],

    // LLM Documentation
    ['link', { rel: 'alternate', type: 'text/plain', title: 'LLM Documentation', href: 'https://www.joomlahealthchecker.com/llms.txt' }],

    // Schema.org Structured Data - SoftwareApplication
    ['script', { type: 'application/ld+json' }, JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'SoftwareApplication',
      name: 'Health Checker for Joomla',
      applicationCategory: 'WebApplication',
      operatingSystem: 'Cross-platform',
      offers: {
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'USD'
      },
      description: 'Free GPL-licensed Joomla 5+ extension that runs over 130 automated health checks covering security, performance, SEO, database health, and more. Get instant comprehensive reports directly from your Joomla admin panel.',
      softwareVersion: '2.0.2',
      releaseNotes: 'https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/v2.0.2',
      downloadUrl: 'https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/v2.0.2/pkg_healthchecker-v2.0.2.zip',
      installUrl: 'https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/v2.0.2/pkg_healthchecker-v2.0.2.zip',
      screenshot: 'https://www.joomlahealthchecker.com/social-preview.jpg',
      url: 'https://www.joomlahealthchecker.com',
      applicationSubCategory: 'Site Health Monitoring',
      softwareRequirements: 'Joomla 5.0+, PHP 8.1+',
      permissions: 'Requires Super Admin access',
      softwareHelp: {
        '@type': 'CreativeWork',
        url: 'https://www.joomlahealthchecker.com/docs/'
      },
      author: {
        '@type': 'Organization',
        name: 'mySites.guru',
        url: 'https://mysites.guru',
        sameAs: [
          'https://github.com/mySites-guru/HealthCheckerForJoomla',
          'https://twitter.com/PhilETaylor'
        ]
      },
      maintainer: {
        '@type': 'Person',
        name: 'Phil E. Taylor',
        url: 'https://github.com/PhilETaylor'
      },
      datePublished: '2026-01-16',
      dateModified: '2026-01-16',
      license: 'https://www.gnu.org/licenses/gpl-2.0.html',
      inLanguage: 'en',
      keywords: 'Joomla, health check, security audit, performance monitoring, SEO analysis, database optimization, site maintenance, Joomla 5',
      aggregateRating: {
        '@type': 'AggregateRating',
        ratingValue: '5.0',
        ratingCount: '1',
        bestRating: '5',
        worstRating: '1'
      },
      featureList: [
        'Over 130 automated health checks',
        '8 check categories: System, Database, Security, Users, Extensions, Performance, SEO, Content',
        'Real-time health status monitoring',
        'No database tables required',
        'Session-based results',
        'Super Admin only access',
        'GPL v2+ licensed',
        'Event-driven architecture',
        'Plugin extensibility',
        'Free and open source'
      ],
      softwareAddOn: [
        {
          '@type': 'SoftwareApplication',
          name: 'Akeeba Backup Integration',
          description: 'Optional plugin for Akeeba Backup health checks',
          applicationCategory: 'WebApplication',
          operatingSystem: 'Cross-platform',
          offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'USD'
          },
          aggregateRating: {
            '@type': 'AggregateRating',
            ratingValue: '5.0',
            ratingCount: '1',
            bestRating: '5',
            worstRating: '1'
          }
        },
        {
          '@type': 'SoftwareApplication',
          name: 'Admin Tools Integration',
          description: 'Optional plugin for Admin Tools security checks',
          applicationCategory: 'WebApplication',
          operatingSystem: 'Cross-platform',
          offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'USD'
          },
          aggregateRating: {
            '@type': 'AggregateRating',
            ratingValue: '5.0',
            ratingCount: '1',
            bestRating: '5',
            worstRating: '1'
          }
        },
        {
          '@type': 'SoftwareApplication',
          name: 'mySites.guru Integration',
          description: 'Optional plugin for mySites.guru API integration',
          applicationCategory: 'WebApplication',
          operatingSystem: 'Cross-platform',
          offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'USD'
          },
          aggregateRating: {
            '@type': 'AggregateRating',
            ratingValue: '5.0',
            ratingCount: '1',
            bestRating: '5',
            worstRating: '1'
          }
        }
      ],
      isAccessibleForFree: true,
      copyrightYear: '2026',
      copyrightHolder: {
        '@type': 'Organization',
        name: 'mySites.guru'
      }
    })],

    // Schema.org Structured Data - FAQPage
    ['script', { type: 'application/ld+json' }, JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: [
        {
          '@type': 'Question',
          name: 'What is Health Checker for Joomla?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'Health Checker for Joomla is a free, GPL-licensed extension that performs over 124 automated health checks on your Joomla 5+ website. It analyzes security settings, performance configurations, SEO optimization, database health, user management, content quality, extensions, and system settings to provide instant diagnostic reports directly from your Joomla admin panel.'
          }
        },
        {
          '@type': 'Question',
          name: 'Is Health Checker for Joomla really free?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'Yes, Health Checker for Joomla is 100% free and open source, licensed under GPL v2 or later (the same license as Joomla itself). There are no premium versions, no hidden costs, and no feature limitations. You get all 130 health checks completely free forever.'
          }
        },
        {
          '@type': 'Question',
          name: 'What versions of Joomla does Health Checker support?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'Health Checker is designed for Joomla 5.0 and later versions. It requires PHP 8.1 or higher to run. The extension is regularly updated to support the latest Joomla releases and best practices.'
          }
        },
        {
          '@type': 'Question',
          name: 'Does Health Checker store any data or send information externally?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'No, Health Checker does not create any database tables and does not store persistent data. All health check results are session-based only. The extension does not send any data externally or communicate with external servers. Your site data stays completely private and secure on your own server.'
          }
        },
        {
          '@type': 'Question',
          name: 'How do I install Health Checker for Joomla?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'Download the pkg_healthchecker ZIP file from the official website or GitHub releases page. In your Joomla admin panel, go to System > Install > Extensions, upload the ZIP file, and install it. The unified package installer will automatically install the component, dashboard module, and all plugin checks. Once installed, you\'ll find Health Checker in the Components menu.'
          }
        }
      ]
    })],

    // Schema.org Structured Data - WebSite
    ['script', { type: 'application/ld+json' }, JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      name: 'Health Checker for Joomla',
      url: 'https://www.joomlahealthchecker.com',
      potentialAction: {
        '@type': 'SearchAction',
        target: 'https://www.joomlahealthchecker.com/docs/?search={search_term_string}',
        'query-input': 'required name=search_term_string'
      }
    })]
  ],

  // Generate per-page OG/Twitter meta tags from frontmatter
  transformPageData(pageData) {
    const title = pageData.title || 'Health Checker for Joomla'
    const description = pageData.frontmatter?.description || pageData.description || 'Free Joomla 5+ extension with 130+ automated health checks for security, performance, SEO, and database.'

    // Build canonical URL
    let relativePath = pageData.relativePath || ''
    relativePath = relativePath.replace(/\.md$/, '').replace(/index$/, '')
    const url = `https://www.joomlahealthchecker.com/docs/${relativePath}`

    pageData.frontmatter.head ??= []
    pageData.frontmatter.head.push(
      ['meta', { property: 'og:title', content: `${title} | Health Checker for Joomla` }],
      ['meta', { property: 'og:description', content: description }],
      ['meta', { property: 'og:url', content: url }],
      ['meta', { name: 'twitter:title', content: `${title} | Health Checker for Joomla` }],
      ['meta', { name: 'twitter:description', content: description }],
      ['meta', { name: 'twitter:url', content: url }]
    )
  },

  // Vite plugin for LLM file generation
  vite: {
    plugins: [llmstxt()]
  },

  // Markdown plugin for Copy/Download buttons
  markdown: {
    config(md) {
      md.use(copyOrDownloadAsMarkdownButtons)
    }
  },

  // Build hooks
  async buildEnd(siteConfig) {
    // Update search-widget.js with the current search index hash
    const chunksDir = path.resolve(__dirname, '../../../website/public/docs/assets/chunks')
    const searchWidgetPath = path.resolve(__dirname, '../../../website/public/search-widget.js')

    try {
      // Find the search index file
      const files = fs.readdirSync(chunksDir)
      const searchIndexFile = files.find(f => f.startsWith('@localSearchIndexroot.') && f.endsWith('.js'))

      if (!searchIndexFile) {
        console.warn('⚠️  Could not find search index file')
        return
      }

      // Extract hash from filename: @localSearchIndexroot.HASH.js
      const hash = searchIndexFile.replace('@localSearchIndexroot.', '').replace('.js', '')
      console.log(`✓ Found search index hash: ${hash}`)

      // Read the search-widget.js template
      const searchWidgetContent = fs.readFileSync(searchWidgetPath, 'utf-8')

      // Update the hash - works for both minified and non-minified versions
      let updatedContent = searchWidgetContent.replace(
        /const SEARCH_INDEX_HASH = '[^']+'/,
        `const SEARCH_INDEX_HASH = '${hash}'`
      )

      // Also handle minified format: const o="HASH"
      updatedContent = updatedContent.replace(
        /const o="[^"]+"/,
        `const o="${hash}"`
      )

      // Write back
      fs.writeFileSync(searchWidgetPath, updatedContent, 'utf-8')
      console.log(`✓ Updated search-widget.js with hash: ${hash}`)
    } catch (error) {
      console.error('✗ Failed to update search-widget.js:', error.message)
    }
  },

  sitemap: {
    hostname: 'https://www.joomlahealthchecker.com',
    transformItems: (items) => {
      // Filter out README
      items = items.filter(item => !item.url.includes('README'))
      // Add /docs prefix to all URLs (workaround for base path bug)
      items = items.map(item => ({
        ...item,
        url: item.url.startsWith('/') ? `/docs${item.url}` : `/docs/${item.url}`
      }))
      // Add root page
      items.unshift({
        url: '/',
        changefreq: 'weekly',
        priority: 1.0
      })
      return items
    }
  },

  themeConfig: {
    logo: { src: '/logo.svg', alt: 'Health Checker for Joomla' },
    siteTitle: 'Health Checker Docs',
    logoLink: 'https://www.joomlahealthchecker.com/',
    nav: [
      { text: 'Guide', link: '/getting-started' },
      { text: 'For Developers', link: '/developers/' },
      { text: 'License', link: '/license' },
      { text: 'GitHub', link: 'https://github.com/mySites-guru/HealthCheckerForJoomla' }
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Quick Start', link: '/getting-started' },
          { text: 'Introduction', link: '/introduction' },
          { text: 'Installation', link: '/installation' }
        ]
      },
      {
        text: 'User Guide',
        items: [
          { text: 'Understanding Health Checks', link: '/understanding-checks' },
          { text: 'Running Health Checks', link: '/running-checks' },
          { text: 'Reading Results', link: '/reading-results' },
          { text: 'Managing Checks', link: '/guide/managing-checks' },
          { text: 'Exporting Reports', link: '/exporting-reports' },
          { text: 'Dashboard Widget', link: '/dashboard-widget' }
        ]
      },
      {
        text: 'Health Checks Reference',
        items: [
          { text: 'All Checks Index', link: '/checks/' },
          { text: 'System & Hosting', link: '/checks/system' },
          { text: 'Database', link: '/checks/database' },
          { text: 'Security', link: '/checks/security' },
          { text: 'Users', link: '/checks/users' },
          { text: 'Extensions', link: '/checks/extensions' },
          { text: 'Performance', link: '/checks/performance' },
          { text: 'SEO', link: '/checks/seo' },
          { text: 'Content Quality', link: '/checks/content' }
        ]
      },
      {
        text: 'For Developers',
        items: [
          { text: 'Developer Overview', link: '/developers/' },
          { text: 'Quick Start', link: '/developers/quick-start' },
          { text: 'Creating Health Checks', link: '/developers/creating-checks' },
          { text: 'Custom Categories', link: '/developers/custom-categories' },
          { text: 'Provider Metadata', link: '/developers/provider-metadata' },
          { text: 'API Reference', link: '/developers/api-reference' },
          { text: 'Best Practices', link: '/developers/best-practices' },
          { text: 'Examples', link: '/developers/examples' }
        ]
      },
      {
        text: 'Optional Integrations',
        items: [
          { text: 'Akeeba Backup', link: '/integrations/akeeba-backup' },
          { text: 'Akeeba Admin Tools', link: '/integrations/akeeba-admin-tools' },
          { text: 'Community Plugins', link: '/integrations/community-plugins' }
        ]
      },
      {
        text: 'About',
        items: [
          { text: 'Code Quality Commitment', link: '/code-quality' },
          { text: 'The Sponsor', link: '/sponsor' },
          { text: 'License', link: '/license' }
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/mySites-guru/HealthCheckerForJoomla' }
    ],

    editLink: {
      pattern: 'https://github.com/mySites-guru/HealthCheckerForJoomla/edit/main/docs/USER/:path',
      text: 'Edit this page on GitHub'
    },

    footer: {
      message: 'Released under the GPL v2+ License',
      copyright: 'Copyright © 2026 Phil Taylor / mySites.guru'
    },

    search: {
      provider: 'local'
    }
  }
})
