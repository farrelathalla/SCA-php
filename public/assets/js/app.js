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
  function subscribeMailchimp(action, email) {
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
        request = subscribeMailchimp(mailchimp, email).then(function () {
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
})();
