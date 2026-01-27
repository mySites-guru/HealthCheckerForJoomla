const securityHeaders = {
  'Content-Security-Policy': [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' https://esm.sh https://*.livechatinc.com https://static.cloudflareinsights.com https://www.googletagmanager.com https://www.google-analytics.com https://*.google.com https://*.doubleclick.net https://*.googleadservices.com",
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data: https:",
    "font-src 'self' data:",
    "connect-src 'self' https://esm.sh https://*.livechatinc.com https://*.lc.chat wss://*.livechatinc.com https://www.google-analytics.com https://www.googletagmanager.com https://*.google.com https://*.analytics.google.com https://*.doubleclick.net https://*.googleadservices.com",
    "frame-src 'self' https://*.livechatinc.com https://www.googletagmanager.com",
    "worker-src 'self' blob:",
  ].join('; '),
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'DENY',
  'X-XSS-Protection': '1; mode=block',
  'Referrer-Policy': 'strict-origin-when-cross-origin',
  'Permissions-Policy': 'camera=(), microphone=(), geolocation=()',
  'Strict-Transport-Security': 'max-age=31536000; includeSubDomains; preload',
};

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    // Skip redirects in development (check for env.ASSETS which indicates production deployment)
    // or if the request is not using HTTPS (local dev uses HTTP)
    const isProduction = url.protocol === 'https:' && env.ASSETS;

    if (isProduction) {
      // Redirect alternate domains to canonical domain
      const altDomains = [
        'joomlahealthcheck.com',
        'www.joomlahealthcheck.com',
        'myjoomlahealthcheck.com',
        'www.myjoomlahealthcheck.com',
      ];
      if (altDomains.includes(url.hostname)) {
        return Response.redirect('https://www.joomlahealthchecker.com' + url.pathname + url.search, 301);
      }

      // Redirect non-www to www (only for production domain)
      if (url.hostname === 'joomlahealthchecker.com') {
        return Response.redirect('https://www.joomlahealthchecker.com' + url.pathname + url.search, 301);
      }
    }

    const response = await env.ASSETS.fetch(request);

    // If the asset is not found (404), serve custom 404 page
    if (response.status === 404) {
      const notFoundUrl = new URL(request.url);
      notFoundUrl.pathname = '/404';  // Cloudflare Assets auto-resolves to 404.html
      const notFoundRequest = new Request(notFoundUrl.toString(), request);
      const notFoundResponse = await env.ASSETS.fetch(notFoundRequest);

      if (notFoundResponse.status === 200) {
        const newResponse = new Response(notFoundResponse.body, {
          ...notFoundResponse,
          status: 404,
          statusText: 'Not Found'
        });

        // Add charset to Content-Type
        newResponse.headers.set('Content-Type', 'text/html; charset=utf-8');

        // Add security headers
        for (const [key, value] of Object.entries(securityHeaders)) {
          newResponse.headers.set(key, value);
        }

        return newResponse;
      }
    }

    const newResponse = new Response(response.body, response);

    // Add charset to Content-Type for HTML files
    const contentType = newResponse.headers.get('Content-Type');
    if (contentType && contentType.includes('text/html') && !contentType.includes('charset')) {
      newResponse.headers.set('Content-Type', 'text/html; charset=utf-8');
    }

    // Add security headers
    for (const [key, value] of Object.entries(securityHeaders)) {
      // Allow /docs/ to be framed by same origin (for search widget)
      if (key === 'X-Frame-Options' && url.pathname.startsWith('/docs/')) {
        newResponse.headers.set(key, 'SAMEORIGIN');
      } else {
        newResponse.headers.set(key, value);
      }
    }

    return newResponse;
  },
};
