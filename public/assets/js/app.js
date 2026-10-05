/*
 * Site behaviour, ported from the design's React client components:
 * Reveal, Header (scroll state, dropdowns, mobile menu), NumberBand CountUp,
 * ArchiveGrid, YearAccordion, NewsletterForm and ContactForm.
 *
 * Stateful elements list their classes for each state in data attributes,
 * e.g. data-open-on / data-open-off, and swap() moves between them.
 */
(function () {
  "use strict";

  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function classes(value) {
    return (value || "").split(/\s+/).filter(Boolean);
  }

  /** Switch an element between the class lists in data-{name}-on / -off. */
  function swap(el, name, on) {
    if (!el) return;
    var add = classes(el.getAttribute("data-" + name + "-" + (on ? "on" : "off")));
    var remove = classes(el.getAttribute("data-" + name + "-" + (on ? "off" : "on")));
    remove.forEach(function (c) { el.classList.remove(c); });
    add.forEach(function (c) { el.classList.add(c); });
  }

  function rotate(el, on) {
    if (el) el.classList.toggle("rotate-180", on);
  }

  /* ------------------------------------------------------------- Reveal */

  var revealObserver = "IntersectionObserver" in window
    ? new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      }, { rootMargin: "0px 0px -12% 0px", threshold: 0.05 })
    : null;

  function observeReveals(root) {
    (root || document).querySelectorAll(".reveal:not(.is-visible)").forEach(function (el) {
      if (revealObserver) revealObserver.observe(el);
      else el.classList.add("is-visible");
    });
  }

  observeReveals();

  /* ------------------------------------------------------------- Header */

  var header = document.querySelector("[data-header]");
  if (header) {
    var scrollTargets = header.querySelectorAll("[data-scrolled-on]");
    var wasScrolled = null;
    var onScroll = function () {
      var scrolled = window.scrollY > 24;
      if (scrolled === wasScrolled) return;
      wasScrolled = scrolled;
      scrollTargets.forEach(function (el) { swap(el, "scrolled", scrolled); });
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });

    // Desktop dropdowns: open on hover, close after a short grace period so
    // the pointer can travel from the link to the panel.
    var openDropdown = null;
    var closeTimer = null;

    var setDropdown = function (item, open) {
      if (!item) return;
      swap(item.querySelector("[data-panel]"), "open", open);
      var link = item.querySelector(":scope > a");
      if (link) link.setAttribute("aria-expanded", open ? "true" : "false");
      rotate(link && link.querySelector("svg"), open);
    };

    var closeAll = function () {
      if (openDropdown) setDropdown(openDropdown, false);
      openDropdown = null;
    };

    header.querySelectorAll("[data-dropdown]").forEach(function (item) {
      item.addEventListener("mouseenter", function () {
        clearTimeout(closeTimer);
        if (openDropdown && openDropdown !== item) setDropdown(openDropdown, false);
        openDropdown = item;
        setDropdown(item, true);
      });
      item.addEventListener("mouseleave", function () {
        clearTimeout(closeTimer);
        closeTimer = setTimeout(closeAll, 140);
      });
      item.addEventListener("focusin", function () {
        clearTimeout(closeTimer);
        if (openDropdown && openDropdown !== item) setDropdown(openDropdown, false);
        openDropdown = item;
        setDropdown(item, true);
      });
      item.addEventListener("focusout", function (event) {
        if (!item.contains(event.relatedTarget)) {
          setDropdown(item, false);
          if (openDropdown === item) openDropdown = null;
        }
      });
    });

    header.querySelectorAll("[data-subdropdown]").forEach(function (item) {
      var set = function (open) {
        swap(item.querySelector("[data-subpanel]"), "open", open);
        var link = item.querySelector(":scope > a");
        if (link) link.setAttribute("aria-expanded", open ? "true" : "false");
      };
      item.addEventListener("mouseenter", function () { set(true); });
      item.addEventListener("mouseleave", function () { set(false); });
      item.addEventListener("focusin", function () { set(true); });
      item.addEventListener("focusout", function (event) {
        if (!item.contains(event.relatedTarget)) set(false);
      });
    });

    // Mobile menu
    var menu = header.querySelector("[data-menu]");
    var setMenu = function (open) {
      swap(menu, "open", open);
      swap(menu.querySelector("[data-menu-backdrop]"), "open", open);
      swap(menu.querySelector("[data-menu-panel]"), "open", open);
      menu.setAttribute("aria-hidden", open ? "false" : "true");
      document.body.style.overflow = open ? "hidden" : "";
    };
    header.querySelector("[data-menu-open]").addEventListener("click", function () { setMenu(true); });
    header.querySelector("[data-menu-close]").addEventListener("click", function () { setMenu(false); });
    menu.querySelector("[data-menu-backdrop]").addEventListener("click", function () { setMenu(false); });

    var sections = menu.querySelectorAll("[data-mobile-section]");
    sections.forEach(function (section) {
      var toggle = section.querySelector("[data-mobile-toggle]");
      if (!toggle) return;
      toggle.addEventListener("click", function () {
        var willOpen = toggle.getAttribute("aria-expanded") !== "true";
        // One section open at a time, as in the design.
        sections.forEach(function (other) {
          var otherToggle = other.querySelector("[data-mobile-toggle]");
          if (!otherToggle) return;
          var open = other === section && willOpen;
          otherToggle.setAttribute("aria-expanded", open ? "true" : "false");
          var label = otherToggle.getAttribute("aria-label").replace(/^(Expand|Collapse) /, "");
          otherToggle.setAttribute("aria-label", (open ? "Collapse " : "Expand ") + label);
          rotate(otherToggle.querySelector("svg"), open);
          swap(other.querySelector("[data-mobile-panel]"), "open", open);
        });
      });
    });

    menu.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () { setMenu(false); });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeAll();
        setMenu(false);
      }
    });
  }

  /* -------------------------------------------------------------- Search
     The header's search button opens a panel that asks /search.json as you
     type. Without JavaScript the button is a plain link to /search. */

  var search = document.querySelector("[data-search]");
  if (search) {
    var searchInput = search.querySelector("[data-search-input]");
    var searchList = search.querySelector("[data-search-results]");
    var searchStatus = search.querySelector("[data-search-status]");
    var searchAll = search.querySelector("[data-search-all]");
    var searchTimer = null;
    var searchRequest = null;
    var searchOpener = null;
    var searchOpen = false;

    var escapeHtml = function (text) {
      return String(text).replace(/[&<>"']/g, function (c) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
      });
    };

    var setStatus = function (text) {
      searchStatus.textContent = text;
      searchStatus.classList.toggle("hidden", !text);
    };

    var showResults = function (data) {
      var query = data.query;
      searchList.innerHTML = data.results.map(function (r) {
        return '<a href="' + escapeHtml(r.url) + '" class="group block border-t border-hairline py-4 first:mt-4 focus:outline-none focus-visible:bg-buff/60">' +
          '<span class="flex flex-wrap items-center gap-3">' +
            '<span class="inline-flex items-center rounded-full bg-accent-soft px-3 py-1 text-[0.7rem] font-medium tracking-[0.08em] text-accent-dark uppercase">' + escapeHtml(r.type) + "</span>" +
            (r.meta ? '<span class="text-[0.8rem] text-muted">' + escapeHtml(r.meta) + "</span>" : "") +
          "</span>" +
          '<span class="mt-2 block font-display text-[1.2rem] leading-snug text-ink transition-colors duration-300 group-hover:text-accent-dark group-focus-visible:text-accent-dark">' + escapeHtml(r.title) + "</span>" +
          (r.snippet ? '<span class="mt-1.5 block text-[0.9rem] leading-relaxed text-body">' + r.snippet + "</span>" : "") +
        "</a>";
      }).join("");
      setStatus(data.results.length ? "" : search.getAttribute("data-no-results").replace("{query}", query));
      searchAll.classList.toggle("hidden", !data.results.length);
      searchAll.setAttribute("href", "/search?q=" + encodeURIComponent(query));
      searchAll.querySelector("[data-search-total]").textContent = data.total;
    };

    var runSearch = function () {
      var query = searchInput.value.trim();
      if (searchRequest) searchRequest.abort();
      if (query.length < 2) {
        searchList.innerHTML = "";
        searchAll.classList.add("hidden");
        setStatus(search.getAttribute("data-prompt"));
        return;
      }
      searchRequest = "AbortController" in window ? new AbortController() : null;
      fetch("/search.json?q=" + encodeURIComponent(query), {
        headers: { Accept: "application/json" },
        signal: searchRequest ? searchRequest.signal : undefined,
      })
        .then(function (response) { return response.json(); })
        .then(function (data) { if (searchInput.value.trim() === data.query) showResults(data); })
        .catch(function () {});
    };

    var setSearch = function (open) {
      searchOpen = open;
      swap(search, "open", open);
      swap(search.querySelector("[data-search-backdrop]"), "open", open);
      swap(search.querySelector("[data-search-panel]"), "open", open);
      search.setAttribute("aria-hidden", open ? "false" : "true");
      document.body.style.overflow = open ? "hidden" : "";
      if (open) {
        setTimeout(function () { searchInput.focus(); searchInput.select(); }, 30);
      } else if (searchOpener) {
        searchOpener.focus();
      }
    };

    document.querySelectorAll("[data-search-open]").forEach(function (button) {
      button.addEventListener("click", function (event) {
        event.preventDefault();
        searchOpener = button;
        setSearch(true);
      });
    });
    search.querySelector("[data-search-close]").addEventListener("click", function () { setSearch(false); });
    search.querySelector("[data-search-backdrop]").addEventListener("click", function () { setSearch(false); });

    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(runSearch, 180);
    });

    // Arrow keys move between the input and the results.
    search.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        setSearch(false);
        return;
      }
      if (event.key !== "ArrowDown" && event.key !== "ArrowUp") return;
      var links = [searchInput].concat(Array.prototype.slice.call(searchList.querySelectorAll("a")));
      if (!searchAll.classList.contains("hidden")) links.push(searchAll);
      var index = links.indexOf(document.activeElement);
      if (index === -1) return;
      event.preventDefault();
      var next = links[Math.max(0, Math.min(links.length - 1, index + (event.key === "ArrowDown" ? 1 : -1)))];
      next.focus();
    });

    // Keep keyboard focus inside the open panel.
    document.addEventListener("focusin", function (event) {
      if (searchOpen && !search.contains(event.target)) searchInput.focus();
    });
  }

  /* ------------------------------------------------------------ Count up
     Only the number animates; unit and label are static. The final string
     reserves the width, so nothing reflows while it counts. */

  var NUMERIC = /^([^\d]*)(\d[\d,]*)([\s\S]*)$/;

  document.querySelectorAll("[data-countup]").forEach(function (el) {
    var value = el.getAttribute("data-countup");
    var match = NUMERIC.exec(value);
    var textEl = el.querySelector("[data-countup-text]");
    if (!match || !textEl || reducedMotion || !("IntersectionObserver" in window)) return;

    var target = Number(match[2].replace(/,/g, ""));
    var grouped = match[2].indexOf(",") !== -1;
    var format = function (n) {
      return match[1] + (grouped ? n.toLocaleString("en-GB") : String(n)) + match[3];
    };
    var delay = Number(el.getAttribute("data-delay") || 0);

    textEl.textContent = format(0);

    var observer = new IntersectionObserver(function (entries) {
      if (!entries[0].isIntersecting) return;
      observer.disconnect();
      setTimeout(function () {
        var start = performance.now();
        var step = function (now) {
          var t = Math.min(1, (now - start) / 1100);
          var eased = 1 - Math.pow(1 - t, 3);
          textEl.textContent = format(Math.round(target * eased));
          if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      }, delay);
    }, { threshold: 0.4 });
    observer.observe(el);
  });

  /* ----------------------------------------------------------- Archive
     Filters every card client-side; the first option of each select is "all".
     A filter can arrive pre-applied in the query string: /projects?theme=… */

  document.querySelectorAll("[data-archive]").forEach(function (archive) {
    var pageSize = Number(archive.getAttribute("data-page-size") || 6);
    var selects = Array.prototype.slice.call(archive.querySelectorAll("[data-filter]"));
    var items = Array.prototype.slice.call(archive.querySelectorAll("[data-archive-item]"));
    var reset = archive.querySelector("[data-archive-reset]");
    var count = archive.querySelector("[data-archive-count]");
    var empty = archive.querySelector("[data-archive-empty]");
    var grid = archive.querySelector("[data-archive-grid]");
    var more = archive.querySelector("[data-archive-more]");
    var visible = pageSize;

    var facets = items.map(function (item) {
      try { return JSON.parse(item.getAttribute("data-facets")); } catch (e) { return {}; }
    });

    var params = new URLSearchParams(window.location.search);
    selects.forEach(function (select) {
      var requested = params.get(select.getAttribute("data-filter"));
      if (!requested) return;
      // Only honour a value the filter actually offers.
      Array.prototype.forEach.call(select.options, function (option) {
        if (option.value && option.value.toLowerCase() === requested.toLowerCase()) select.value = option.value;
      });
    });

    var render = function () {
      var matched = [];
      items.forEach(function (item, i) {
        var ok = selects.every(function (select) {
          var value = select.value;
          if (!value) return true;
          var facet = facets[i][select.getAttribute("data-filter")];
          return Array.isArray(facet) ? facet.indexOf(value) !== -1 : facet === value;
        });
        if (ok) matched.push(item);
        item.hidden = true;
      });

      matched.slice(0, visible).forEach(function (item, index) {
        item.hidden = false;
        grid.appendChild(item); // keep matched items in order at the front
        var card = item.querySelector(".reveal");
        if (card) card.style.transitionDelay = (index % 3) * 110 + "ms";
      });
      observeReveals(grid);

      var filtered = selects.some(function (select) { return select.value; });
      reset.classList.toggle("hidden", !filtered);
      count.textContent = matched.length + " " + (matched.length === 1 ? count.getAttribute("data-one") : count.getAttribute("data-many"));
      empty.classList.toggle("hidden", matched.length > 0);
      grid.classList.toggle("hidden", matched.length === 0);
      more.classList.toggle("hidden", visible >= matched.length);
    };

    selects.forEach(function (select) {
      select.addEventListener("change", function () { visible = pageSize; render(); });
    });
    reset.addEventListener("click", function () {
      selects.forEach(function (select) { select.value = ""; });
      visible = pageSize;
      render();
    });
    more.querySelector("button").addEventListener("click", function () {
      visible += pageSize;
      render();
    });

    render();
  });

  /* ------------------------------------------------------ Year accordion */

  document.querySelectorAll("[data-accordion]").forEach(function (accordion) {
    var rows = accordion.querySelectorAll("[data-accordion-item]");
    rows.forEach(function (row) {
      row.querySelector("[data-accordion-toggle]").addEventListener("click", function () {
        var willOpen = this.getAttribute("aria-expanded") !== "true";
        rows.forEach(function (other) {
          var open = other === row && willOpen;
          var button = other.querySelector("[data-accordion-toggle]");
          button.setAttribute("aria-expanded", open ? "true" : "false");
          rotate(button.querySelector("svg"), open);
          var panel = other.querySelector("[data-accordion-panel]");
          panel.classList.toggle("grid-rows-[1fr]", open);
          panel.classList.toggle("grid-rows-[0fr]", !open);
        });
      });
    });
  });

  /* --------------------------------------------------------------- Chart
     The population graph is drawn server-side as SVG; this only adds the
     tooltip, which follows whichever data point is hovered or focused. There
     is one point per actual estimate, so nothing is read off the curve
     between them. */

  document.querySelectorAll("[data-chart]").forEach(function (chart) {
    var tooltip = chart.querySelector("[data-chart-tooltip]");
    var valueEl = chart.querySelector("[data-chart-value]");
    var yearEl = chart.querySelector("[data-chart-year]");
    if (!tooltip || !valueEl || !yearEl) return;

    var show = function (point) {
      var dot = point.querySelector("[data-chart-dot]");
      var box = dot.getBoundingClientRect();
      var frame = chart.getBoundingClientRect();
      valueEl.textContent = point.getAttribute("data-value");
      yearEl.textContent = point.getAttribute("data-year");
      tooltip.style.left = box.left + box.width / 2 - frame.left + "px";
      tooltip.style.top = box.top - frame.top - 14 + "px";
      tooltip.classList.remove("hidden");
      dot.setAttribute("r", "6.5");
    };

    var hide = function (point) {
      tooltip.classList.add("hidden");
      point.querySelector("[data-chart-dot]").setAttribute("r", "4.5");
    };

    chart.querySelectorAll("[data-chart-point]").forEach(function (point) {
      ["mouseenter", "focus"].forEach(function (name) {
        point.addEventListener(name, function () { show(point); });
      });
      ["mouseleave", "blur"].forEach(function (name) {
        point.addEventListener(name, function () { hide(point); });
      });
    });
  });

  /* --------------------------------------------------------------- Forms */

  function postLocal(form) {
    return fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json" },
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (!result.ok) throw new Error(result.error || "Something went wrong.");
        return result;
      });
  }

  /*
   * Mailchimp's embedded-form endpoint, called as JSONP so the visitor stays
   * on the page. The form action is the list's usual
   * …list-manage.com/subscribe/post?u=…&id=… URL, as set in the admin.
   */
  /*
   * Extra fields for Mailchimp, as set in the admin: usually a group from the
   * embedded form code, e.g. "group[12345][1]" (sent as =1) or "group[12345]=4".
   */
  function mailchimpExtra(setting) {
    return (setting || "").split(/[&\s,]+/).filter(Boolean).map(function (part) {
      var eq = part.indexOf("=");
      var name = eq < 0 ? part : part.slice(0, eq);
      var value = eq < 0 ? "1" : part.slice(eq + 1);
      return "&" + encodeURIComponent(name) + "=" + encodeURIComponent(value);
    }).join("");
  }

  function subscribeMailchimp(action, email, extra) {
    return new Promise(function (resolve, reject) {
      var url = action.replace("/subscribe/post?", "/subscribe/post-json?");
      var params = new URL(url, window.location.href).searchParams;
      var callback = "scaMailchimp" + Date.now();
      var script = document.createElement("script");
      var timer = setTimeout(function () {
        cleanup();
        reject(new Error("The sign-up service did not respond. Please try again."));
      }, 15000);
      function cleanup() {
        clearTimeout(timer);
        delete window[callback];
        script.remove();
      }
      window[callback] = function (data) {
        cleanup();
        var message = String(data.msg || "").replace(/<[^>]*>/g, "").replace(/^\d+\s*-\s*/, "");
        if (data.result === "success" || /already subscribed/i.test(message)) resolve();
        else reject(new Error(message || "That address could not be subscribed."));
      };
      script.src = url
        + "&EMAIL=" + encodeURIComponent(email)
        + mailchimpExtra(extra)
        + "&b_" + params.get("u") + "_" + params.get("id") + "="
        + "&c=" + callback;
      script.onerror = function () {
        cleanup();
        reject(new Error("The sign-up service could not be reached. Please try again."));
      };
      document.body.appendChild(script);
    });
  }

  document.querySelectorAll("form[data-ajax-form]").forEach(function (form) {
    var errorEl = form.querySelector("[data-form-error]");
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      var button = form.querySelector("button[type=submit]");
      if (button) button.disabled = true;
      if (errorEl) errorEl.classList.add("hidden");

      var mailchimp = form.getAttribute("data-mailchimp");
      var honeypot = form.querySelector("input[name=website]");
      var request;
      if (mailchimp && !(honeypot && honeypot.value)) {
        var email = form.querySelector("input[type=email]").value;
        request = subscribeMailchimp(mailchimp, email, form.getAttribute("data-mailchimp-group")).then(function () {
          // Keep a copy in the admin as well; a failure here does not matter.
          postLocal(form).catch(function () {});
        });
      } else {
        request = postLocal(form);
      }

      request
        .then(function () {
          if (form.hasAttribute("data-contact")) {
            var wrap = form.closest("[data-contact-wrap]");
            form.classList.add("hidden");
            wrap.querySelector("[data-contact-done]").classList.remove("hidden");
          } else {
            var done = document.createElement("div");
            done.className = form.getAttribute("data-done-class");
            done.setAttribute("role", "status");
            done.textContent = form.getAttribute("data-done-text");
            form.replaceWith(done);
          }
        })
        .catch(function (error) {
          if (button) button.disabled = false;
          if (errorEl) {
            errorEl.textContent = error.message;
            errorEl.classList.remove("hidden");
          } else {
            window.alert(error.message);
          }
        });
    });
  });
  /*
   * "Analytics opt-out" in the footer. Remembers the choice for a year in a
   * cookie (sca_analytics=off), stops Google Analytics on this page straight
   * away and removes its cookies; the layout does not load it again.
   */
  document.querySelectorAll("[data-analytics-toggle]").forEach(function (button) {
    var id = button.getAttribute("data-id");
    var note = document.querySelector("[data-analytics-note]");
    var isOff = function () { return /(?:^|; )sca_analytics=off/.test(document.cookie); };
    var secure = location.protocol === "https:" ? "; Secure" : "";
    var show = function () {
      button.textContent = button.getAttribute(isOff() ? "data-label-off" : "data-label-on");
      button.setAttribute("aria-pressed", isOff() ? "true" : "false");
    };
    show();
    button.addEventListener("click", function () {
      if (isOff()) {
        document.cookie = "sca_analytics=; Max-Age=0; Path=/; SameSite=Lax" + secure;
        window["ga-disable-" + id] = false;
      } else {
        document.cookie = "sca_analytics=off; Max-Age=31536000; Path=/; SameSite=Lax" + secure;
        window["ga-disable-" + id] = true;
        if (typeof window.gtag === "function") window.gtag("consent", "update", { analytics_storage: "denied" });
        // Remove the Google Analytics cookies (_ga, _ga_XXXX, _gid) on every level of the domain.
        var parts = location.hostname.split(".");
        document.cookie.split("; ").forEach(function (pair) {
          var name = pair.split("=")[0];
          if (!/^_ga|^_gid$|^_gat/.test(name)) return;
          for (var i = 0; i < parts.length; i++) {
            var domain = parts.slice(i).join(".");
            document.cookie = name + "=; Max-Age=0; Path=/; Domain=" + domain;
            document.cookie = name + "=; Max-Age=0; Path=/; Domain=." + domain;
          }
          document.cookie = name + "=; Max-Age=0; Path=/";
        });
      }
      show();
      if (note) {
        note.textContent = button.getAttribute(isOff() ? "data-note-off" : "data-note-on");
        note.classList.remove("hidden");
      }
    });
  });
})();
