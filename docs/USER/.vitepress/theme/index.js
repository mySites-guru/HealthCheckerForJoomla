import DefaultTheme from 'vitepress/theme'
import { h, onMounted, nextTick } from 'vue'
import CopyOrDownloadAsMarkdownButtons from 'vitepress-plugin-llms/vitepress-components/CopyOrDownloadAsMarkdownButtons.vue'
import './custom.css'

export default {
  extends: DefaultTheme,
  Layout() {
    return h(DefaultTheme.Layout, null, {})
  },
  enhanceApp({ app, router }) {
    // Register the markdown button component
    app.component('CopyOrDownloadAsMarkdownButtons', CopyOrDownloadAsMarkdownButtons)

    // Redirect index to getting-started
    if (typeof window !== 'undefined') {
      router.onAfterRouteChanged = (to) => {
        // VitePress paths are relative to base, so '/' is actually '/docs/'
        if (to === '/' || to === '/index') {
          window.location.href = '/docs/getting-started'
        }
      }

      // Also check on initial load
      if (window.location.pathname === '/docs/' || window.location.pathname === '/docs/index' || window.location.pathname === '/docs') {
        window.location.href = '/docs/getting-started'
      }
    }

    // Override logo click behavior
    if (typeof window !== 'undefined') {
      const updateLogoLink = () => {
        const logoLink = document.querySelector('.VPNavBarTitle a')
        if (logoLink && !logoLink.dataset.brandFixed) {
          logoLink.href = 'https://www.joomlahealthchecker.com/'
          logoLink.dataset.brandFixed = 'true'

          // Also override click behavior
          logoLink.addEventListener('click', (e) => {
            e.preventDefault()
            window.location.href = 'https://www.joomlahealthchecker.com/'
          })
        }
      }

      // Update on route changes
      router.onAfterRouteChanged = updateLogoLink

      // Update on initial load with multiple attempts
      const tryUpdate = () => {
        updateLogoLink()
        // Keep trying for the first 2 seconds in case DOM hasn't loaded
        setTimeout(updateLogoLink, 100)
        setTimeout(updateLogoLink, 300)
        setTimeout(updateLogoLink, 500)
        setTimeout(updateLogoLink, 1000)
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryUpdate)
      } else {
        tryUpdate()
      }

      // Also use MutationObserver to catch any DOM changes
      const observer = new MutationObserver(updateLogoLink)
      const observerTarget = document.querySelector('body') || document.documentElement
      observer.observe(observerTarget, {
        childList: true,
        subtree: true
      })

      // Initialize LiveChat
      window.__lc = window.__lc || {};
      window.__lc.license = 5560341;
      window.__lc.integration_name = "manual_channels";
      window.__lc.product_name = "livechat";
      (function (n, t, c) {
        function i(n) {
          return e._h ? e._h.apply(null, n) : e._q.push(n);
        }
        var e = {
          _q: [],
          _h: null,
          _v: "2.0",
          on: function () {
            i(["on", c.call(arguments)]);
          },
          once: function () {
            i(["once", c.call(arguments)]);
          },
          off: function () {
            i(["off", c.call(arguments)]);
          },
          get: function () {
            if (!e._h)
              throw new Error(
                "[LiveChatWidget] You can't use getters before load.",
              );
            return i(["get", c.call(arguments)]);
          },
          call: function () {
            i(["call", c.call(arguments)]);
          },
          init: function () {
            var n = t.createElement("script");
            ((n.async = !0),
              (n.type = "text/javascript"),
              (n.src = "https://cdn.livechatinc.com/tracking.js"),
              t.head.appendChild(n));
          },
        };
        (!n.__lc.asyncInit && e.init(),
          (n.LiveChatWidget = n.LiveChatWidget || e));
      })(window, document, [].slice);
    }
  }
}
