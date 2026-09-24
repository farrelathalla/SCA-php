/*
 * Admin form behaviour: list items (add / remove / reorder), image and file
 * pickers, and turning the form back into JSON on save.
 *
 * Form structure (rendered by app/admin/fields.php):
 *   [data-node=group]  an object — children with data-key become its fields
 *   [data-node=list]   an array  — its [data-items] hold one node per item
 *   [data-node=value]  a leaf    — the [data-input] inside holds the value
 */
(function () {
  "use strict";

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || "";
  var iconPaths = {};
  try { iconPaths = JSON.parse(document.getElementById("icon-paths").textContent); } catch (e) {}
  var blockLabels = {};
  try { blockLabels = JSON.parse(document.getElementById("block-labels").textContent); } catch (e) {}

  /* ----------------------------------------------------------- Serialize */

  function childNodes(node) {
    return Array.prototype.filter.call(node.querySelectorAll("[data-node]"), function (el) {
      return el.parentElement.closest("[data-node]") === node;
    });
  }

  function read(node) {
    var type = node.getAttribute("data-node");
    if (type === "group") {
      var obj = {};
      childNodes(node).forEach(function (child) {
        var key = child.getAttribute("data-key");
        if (key !== null) obj[key] = read(child);
      });
      return obj;
    }
    if (type === "list") {
      return childNodes(node).map(read);
    }
    var input = node.querySelector("[data-input]");
    if (node.getAttribute("data-type") === "bool") return !!(input && input.checked);
    return input ? input.value : "";
  }

  document.querySelectorAll("form[data-content-form]").forEach(function (form) {
    var dirty = false;
    var note = form.querySelector("[data-dirty-note]");
    var markDirty = function () {
      dirty = true;
      if (note) note.textContent = "Unsaved changes";
    };
    form.addEventListener("input", markDirty);
    form.addEventListener("change", markDirty);

    form.addEventListener("submit", function () {
      var root = form.querySelector("[data-root]");
      form.querySelector("[data-json]").value = JSON.stringify(read(root));
      dirty = false;
    });

    window.addEventListener("beforeunload", function (event) {
      if (dirty) {
        event.preventDefault();
        event.returnValue = "";
      }
    });

    form.addEventListener("click", function (event) {
      var target = event.target.closest("button");
      if (!target || !form.contains(target)) return;

      // + Add new block: the picker chooses which template to clone.
      if (target.hasAttribute("data-add-block")) {
        var list = target.closest("[data-node=list]");
        var picker = list.querySelector("[data-block-picker]");
        var blockTemplate = list.querySelector('template[data-block-template="' + picker.value + '"]');
        if (!blockTemplate) return;
        var blockFragment = blockTemplate.content.cloneNode(true);
        var block = blockFragment.querySelector("[data-item]");
        list.querySelector(":scope > [data-items]").appendChild(blockFragment);
        if (block.tagName === "DETAILS") block.open = true;
        block.scrollIntoView({ block: "nearest" });
        markDirty();
        return;
      }

      // + Add
      if (target.hasAttribute("data-add-item")) {
        var list = target.closest("[data-node=list]");
        var template = list.querySelector(":scope > template[data-item-template]");
        var items = list.querySelector(":scope > [data-items]");
        var fragment = template.content.cloneNode(true);
        var item = fragment.querySelector("[data-item]");
        // Lists kept newest-first (e.g. the reports archive) grow at the top.
        if (target.getAttribute("data-add-item") === "top") items.insertBefore(fragment, items.firstChild);
        else items.appendChild(fragment);
        if (item.tagName === "DETAILS") item.open = true;
        var first = item.querySelector("[data-input]");
        if (first) first.focus();
        markDirty();
        return;
      }

      var row = target.closest("[data-item]");
      if (!row) return;

      if (target.hasAttribute("data-remove-item")) {
        event.preventDefault();
        if (window.confirm("Remove this item?")) {
          row.remove();
          markDirty();
        }
        return;
      }

      var move = target.getAttribute("data-move");
      if (move) {
        event.preventDefault();
        if (move === "-1" && row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
        if (move === "1" && row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
        markDirty();
      }
    });

    // Keep collapsed item titles in step with what is typed.
    form.addEventListener("input", function (event) {
      var details = event.target.closest("details[data-item]");
      if (!details) return;
      var summary = details.querySelector(":scope > summary [data-item-summary]");
      var group = details.querySelector(":scope > div > [data-node=group]");
      if (!summary || !group) return;
      var data = read(group);
      if (data.block) {
        var name = blockLabels[data.block] || data.block;
        var first = (data.title || data.text || "").trim();
        summary.textContent = first ? name + " — " + first : name;
        return;
      }
      var keys = ["title", "label", "name", "year", "project", "question", "amount", "value"];
      for (var i = 0; i < keys.length; i++) {
        if (typeof data[keys[i]] === "string" && data[keys[i]].trim()) {
          summary.textContent = data[keys[i]] + (keys[i] === "year" && data.title ? " — " + data.title : "");
          return;
        }
      }
    });

    // One line of help under the block picker, for whichever type is selected.
    form.addEventListener("change", function (event) {
      if (!event.target.hasAttribute("data-block-picker")) return;
      var option = event.target.options[event.target.selectedIndex];
      var hint = event.target.closest("[data-node=list]").querySelector("[data-block-hint]");
      if (hint && option) hint.textContent = option.getAttribute("data-hint") || "";
    });

    // Icon preview
    form.addEventListener("change", function (event) {
      if (!event.target.hasAttribute("data-icon-select")) return;
      showIcon(event.target.parentElement.querySelector("[data-icon-preview]"), event.target.value);
    });

    // Slug from title, for new entries until the slug is typed by hand.
    var slug = form.querySelector("[data-slug]");
    if (slug && !slug.value) {
      var titleInput = form.querySelector('[data-root] > [data-key="title"] [data-input], [data-root] > section [data-key="title"] [data-input]');
      var touched = false;
      slug.addEventListener("input", function () { touched = true; });
      if (titleInput) {
        titleInput.addEventListener("input", function () {
          if (touched) return;
          slug.value = titleInput.value.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");
        });
      }
    }
  });

  // Built-in icons are SVG paths; an uploaded icon (an image path) is shown as
  // a mask in the text colour, exactly as the site draws it.
  function showIcon(preview, value) {
    if (!preview) return;
    if (value.charAt(0) === "/") {
      var span = document.createElement("span");
      span.className = "inline-block h-5 w-5 bg-current";
      var mask = "url('" + value + "') center / contain no-repeat";
      span.style.webkitMask = mask;
      span.style.mask = mask;
      preview.innerHTML = "";
      preview.appendChild(span);
    } else if (iconPaths[value]) {
      preview.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">' + iconPaths[value] + "</svg>";
    }
  }

  /* ------------------------------------------------------------- Uploads */

  function upload(file, kind) {
    var body = new FormData();
    body.append("file", file);
    body.append("kind", kind);
    body.append("_csrf", csrf);
    return fetch("/admin/media/upload", { method: "POST", body: body, headers: { Accept: "application/json" } })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (!result.ok) throw new Error(result.error || "Upload failed.");
        return result.url;
      });
  }

  function pickFile(accept, callback) {
    var input = document.createElement("input");
    input.type = "file";
    input.accept = accept;
    input.addEventListener("change", function () {
      if (input.files[0]) callback(input.files[0]);
    });
    input.click();
  }

  function setImage(field, url) {
    var input = field.querySelector("[data-input]");
    var preview = field.querySelector("[data-image-preview]");
    input.value = url;
    preview.src = url;
    preview.classList.toggle("hidden", !url);
    input.dispatchEvent(new Event("change", { bubbles: true }));
  }

  document.addEventListener("click", function (event) {
    var button = event.target.closest("button");
    if (!button) return;

    var imageField = button.closest("[data-image-field]");
    if (imageField && button.hasAttribute("data-image-upload")) {
      pickFile("image/*", function (file) {
        button.textContent = "Uploading…";
        button.disabled = true;
        upload(file, "image")
          .then(function (url) { setImage(imageField, url); })
          .catch(function (error) { window.alert(error.message); })
          .then(function () { button.textContent = "Upload…"; button.disabled = false; });
      });
    }
    if (imageField && button.hasAttribute("data-image-clear")) setImage(imageField, "");
    if (imageField && button.hasAttribute("data-image-library")) openLibrary(function (url) { setImage(imageField, url); });
    if (imageField && button.hasAttribute("data-image-crop")) {
      var current = imageField.querySelector("[data-input]").value.trim();
      if (!current) window.alert("Choose or upload an image first, then crop it.");
      else if (!/\.(jpe?g|png|webp)$/i.test(current)) window.alert("Only JPG, PNG and WebP images can be cropped.");
      else openCropper(current, function (url) { setImage(imageField, url); });
    }
    // Media library: crop, then show the new copy with its details open.
    if (button.hasAttribute("data-crop")) {
      openCropper(button.getAttribute("data-crop"), function (url) {
        window.location.href = "/admin/media?open=" + encodeURIComponent(url);
      });
    }

    var iconField = button.closest("[data-icon-field]");
    if (iconField && button.hasAttribute("data-icon-upload")) {
      pickFile("image/png,image/webp,image/gif", function (file) {
        button.textContent = "Uploading…";
        button.disabled = true;
        upload(file, "image")
          .then(function (url) {
            var select = iconField.querySelector("[data-icon-select]");
            var option = document.createElement("option");
            option.value = url;
            option.textContent = "Uploaded: " + url.split("/").pop();
            select.appendChild(option);
            select.value = url;
            select.dispatchEvent(new Event("change", { bubbles: true }));
          })
          .catch(function (error) { window.alert(error.message); })
          .then(function () { button.textContent = "Upload icon…"; button.disabled = false; });
      });
    }

    var linkField = button.closest("[data-link-field]");
    if (linkField && button.hasAttribute("data-file-upload")) {
      pickFile("image/*,.pdf,.doc,.docx", function (file) {
        button.textContent = "Uploading…";
        button.disabled = true;
        upload(file, "file")
          .then(function (url) {
            var input = linkField.querySelector("[data-input]");
            input.value = url;
            input.dispatchEvent(new Event("change", { bubbles: true }));
          })
          .catch(function (error) { window.alert(error.message); })
          .then(function () { button.textContent = "Upload file…"; button.disabled = false; });
      });
    }
  });

  /* ------------------------------------------------------- Image library */

  var modal = document.querySelector("[data-library-modal]");
  var onChoose = null;

  function openLibrary(callback) {
    onChoose = callback;
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    var grid = modal.querySelector("[data-library-grid]");
    fetch("/admin/media/list", { headers: { Accept: "application/json" } })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        grid.innerHTML = "";
        result.files.filter(function (f) { return f.isImage; }).forEach(function (file) {
          var item = document.createElement("button");
          item.type = "button";
          item.className = "group overflow-hidden rounded-lg border border-hairline bg-white text-left hover:border-accent";
          item.innerHTML = '<img loading="lazy" class="aspect-[4/3] w-full object-cover" alt=""><span class="block truncate px-2 py-1 text-[0.7rem] text-body"></span>';
          item.querySelector("img").src = file.url;
          item.querySelector("span").textContent = file.name;
          item.addEventListener("click", function () {
            if (onChoose) onChoose(file.url);
            closeLibrary();
          });
          grid.appendChild(item);
        });
      });
  }

  function closeLibrary() {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }

  if (modal) {
    modal.querySelector("[data-library-close]").addEventListener("click", closeLibrary);
    modal.addEventListener("click", function (event) { if (event.target === modal) closeLibrary(); });
    document.addEventListener("keydown", function (event) { if (event.key === "Escape") closeLibrary(); });
  }

  /* ---------------------------------------------------------- Crop & zoom
     Cropper.js (loaded from cdnjs in the admin layout) picks the rectangle;
     the server cuts it from the original and saves it as a new file. */

  var cropModal = document.querySelector("[data-crop-modal]");
  var cropper = null;
  var cropSource = "";
  var onCropped = null;

  function openCropper(url, callback) {
    if (!window.Cropper) {
      window.alert("The crop tool could not load. Check the internet connection and reload the page.");
      return;
    }
    cropSource = url.split("?")[0];
    onCropped = callback;
    var img = cropModal.querySelector("[data-crop-image]");
    if (cropper) { cropper.destroy(); cropper = null; }
    cropModal.classList.remove("hidden");
    cropModal.classList.add("flex");
    img.onload = function () {
      img.onload = null;
      cropper = new window.Cropper(img, { viewMode: 1, autoCropArea: 1, responsive: true, background: false });
    };
    img.src = cropSource + "?t=" + Date.now();
  }

  function closeCropper() {
    if (cropper) { cropper.destroy(); cropper = null; }
    cropModal.classList.add("hidden");
    cropModal.classList.remove("flex");
  }

  if (cropModal) {
    cropModal.querySelector("[data-crop-close]").addEventListener("click", closeCropper);
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && !cropModal.classList.contains("hidden")) closeCropper();
    });
    cropModal.addEventListener("click", function (event) {
      var button = event.target.closest("button");
      if (!button || !cropper) return;
      if (button.hasAttribute("data-crop-ratio")) cropper.setAspectRatio(parseFloat(button.getAttribute("data-crop-ratio")));
      if (button.hasAttribute("data-crop-zoom")) cropper.zoom(parseFloat(button.getAttribute("data-crop-zoom")));
      if (button.hasAttribute("data-crop-save")) {
        var data = cropper.getData(true);
        var body = new FormData();
        body.append("path", cropSource);
        body.append("x", data.x);
        body.append("y", data.y);
        body.append("width", data.width);
        body.append("height", data.height);
        body.append("_csrf", csrf);
        button.disabled = true;
        button.textContent = "Saving…";
        fetch("/admin/media/crop", { method: "POST", body: body, headers: { Accept: "application/json" } })
          .then(function (response) { return response.json(); })
          .then(function (result) {
            if (!result.ok) throw new Error(result.error || "The crop could not be saved.");
            closeCropper();
            if (onCropped) onCropped(result.url);
          })
          .catch(function (error) { window.alert(error.message); })
          .then(function () { button.disabled = false; button.textContent = "Save cropped copy"; });
      }
    });
  }

  /* ---------------------------------------------------- Media page upload */

  var mediaUpload = document.querySelector("[data-media-upload]");
  if (mediaUpload) {
    mediaUpload.addEventListener("change", function () {
      var files = Array.prototype.slice.call(mediaUpload.files);
      var label = mediaUpload.parentElement;
      label.firstChild.textContent = "Uploading " + files.length + "… ";
      files.reduce(function (chain, file) {
        return chain.then(function () {
          return upload(file, file.type.indexOf("image/") === 0 ? "image" : "file");
        });
      }, Promise.resolve())
        .catch(function (error) { window.alert(error.message); })
        .then(function () { window.location.reload(); });
    });
  }

  /* ------------------------------------------------------ Mobile sidebar */

  var toggle = document.querySelector("[data-admin-menu-toggle]");
  if (toggle) {
    toggle.addEventListener("click", function () {
      document.querySelector("[data-admin-menu]").classList.toggle("hidden");
    });
  }
})();
