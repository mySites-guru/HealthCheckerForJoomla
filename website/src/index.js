import { EmailMessage } from 'cloudflare:email';

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

// Rate limiting: simple in-memory store (resets on worker restart)
const rateLimitStore = new Map();
const RATE_LIMIT_WINDOW = 60000; // 1 minute
const RATE_LIMIT_MAX = 5; // 5 submissions per minute per IP

function isRateLimited(ip) {
  const now = Date.now();
  const record = rateLimitStore.get(ip);

  if (!record || now - record.timestamp > RATE_LIMIT_WINDOW) {
    rateLimitStore.set(ip, { timestamp: now, count: 1 });
    return false;
  }

  if (record.count >= RATE_LIMIT_MAX) {
    return true;
  }

  record.count++;
  return false;
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email) && email.length <= 254;
}

function sanitize(str) {
  if (typeof str !== 'string') return '';
  return str.trim().slice(0, 255);
}

async function handleWaitlistSubmit(request, env) {
  // Check rate limit
  const ip = request.headers.get('CF-Connecting-IP') || 'unknown';
  if (isRateLimited(ip)) {
    return new Response(JSON.stringify({
      success: false,
      error: 'Too many requests. Please try again later.'
    }), {
      status: 429,
      headers: { 'Content-Type': 'application/json' }
    });
  }

  try {
    const body = await request.json();
    const email = sanitize(body.email?.toLowerCase());
    const name = sanitize(body.name);

    // Validate inputs
    if (!email || !isValidEmail(email)) {
      return new Response(JSON.stringify({
        success: false,
        error: 'Please enter a valid email address.'
      }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' }
      });
    }

    if (!name || name.length < 2) {
      return new Response(JSON.stringify({
        success: false,
        error: 'Please enter your name.'
      }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' }
      });
    }

    // Insert into D1
    const result = await env.DB.prepare(
      'INSERT INTO waitlist (email, name, source) VALUES (?, ?, ?)'
    ).bind(email, name, 'popover').run();

    if (!result.success) {
      throw new Error('Database insert failed');
    }

    // Send notification email
    try {
      const messageId = `<${crypto.randomUUID()}@joomlahealthchecker.com>`;
      const emailContent = [
        `From: Health Checker Waitlist <waitlist@joomlahealthchecker.com>`,
        `To: phil@phil-taylor.com`,
        `Subject: New Waitlist Signup: ${name}`,
        `Message-ID: ${messageId}`,
        `Date: ${new Date().toUTCString()}`,
        `MIME-Version: 1.0`,
        `Content-Type: text/plain; charset=utf-8`,
        ``,
        `New waitlist signup!`,
        ``,
        `Name: ${name}`,
        `Email: ${email}`,
        `Time: ${new Date().toISOString()}`,
        ``,
        `---`,
        `Health Checker for Joomla`,
        `https://www.joomlahealthchecker.com`
      ].join('\r\n');

      const emailMessage = new EmailMessage(
        'waitlist@joomlahealthchecker.com',
        'phil@phil-taylor.com',
        new Blob([emailContent], { type: 'message/rfc822' }).stream()
      );
      await env.SEND_EMAIL.send(emailMessage);
    } catch (emailError) {
      // Log but don't fail the request if email fails
      console.error('Email send failed:', emailError);
    }

    return new Response(JSON.stringify({
      success: true,
      message: 'Thank you for joining the waitlist!'
    }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    });

  } catch (error) {
    // Check for duplicate email
    if (error.message?.includes('UNIQUE constraint failed')) {
      return new Response(JSON.stringify({
        success: false,
        error: 'This email is already on the waitlist.'
      }), {
        status: 409,
        headers: { 'Content-Type': 'application/json' }
      });
    }

    console.error('Waitlist error:', error);
    return new Response(JSON.stringify({
      success: false,
      error: 'Something went wrong. Please try again.'
    }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

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

    // API: Waitlist submission
    if (url.pathname === '/api/waitlist' && request.method === 'POST') {
      return handleWaitlistSubmit(request, env);
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
