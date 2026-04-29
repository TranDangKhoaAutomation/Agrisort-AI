(function () {
  var copyText = function (value, onDone) {
    if (!value) {
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(function () {
        if (typeof onDone === "function") {
          onDone();
        }
      });
      return;
    }

    var temp = document.createElement("textarea");
    temp.value = value;
    temp.style.position = "fixed";
    temp.style.opacity = "0";
    document.body.appendChild(temp);
    temp.select();
    document.execCommand("copy");
    document.body.removeChild(temp);
    if (typeof onDone === "function") {
      onDone();
    }
  };

  var triggerDownload = function (href, filename) {
    if (!href) {
      return;
    }

    var link = document.createElement("a");
    link.href = href;
    if (filename) {
      link.download = filename;
    }
    link.rel = "noopener";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  var downloadPngFromSource = function (source, filename) {
    if (!source) {
      return;
    }

    var image = new Image();
    if (!/^data:/i.test(source)) {
      image.crossOrigin = "anonymous";
    }

    image.onload = function () {
      var width = image.naturalWidth || image.width || 960;
      var height = image.naturalHeight || image.height || 960;

      var canvas = document.createElement("canvas");
      canvas.width = width;
      canvas.height = height;

      var ctx = canvas.getContext("2d");
      if (!ctx) {
        triggerDownload(source, filename || "qr.png");
        return;
      }

      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, width, height);
      ctx.drawImage(image, 0, 0, width, height);

      if (typeof canvas.toBlob === "function") {
        canvas.toBlob(
          function (blob) {
            if (!blob) {
              triggerDownload(source, filename || "qr.png");
              return;
            }
            var objectUrl = URL.createObjectURL(blob);
            triggerDownload(objectUrl, filename || "qr.png");
            setTimeout(function () {
              URL.revokeObjectURL(objectUrl);
            }, 1200);
          },
          "image/png",
          0.96
        );
        return;
      }

      triggerDownload(canvas.toDataURL("image/png"), filename || "qr.png");
    };

    image.onerror = function () {
      triggerDownload(source, filename || "qr.png");
    };

    image.src = source;
  };

  var hash = window.location.hash;
  if (hash) {
    var target = document.querySelector(hash);
    if (target) {
      setTimeout(function () {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 70);
    }
  }

  var setupPasswordToggles = function () {
    var passwordInputs = document.querySelectorAll('input[type="password"]');
    if (!passwordInputs.length) {
      return;
    }

    var eyeIcon =
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12c2.2-4 5.2-6 10-6s7.8 2 10 6c-2.2 4-5.2 6-10 6s-7.8-2-10-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    var eyeOffIcon =
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12c2.2-4 5.2-6 10-6s7.8 2 10 6c-2.2 4-5.2 6-10 6s-7.8-2-10-6Z"></path><circle cx="12" cy="12" r="3"></circle><path d="M4 4l16 16"></path></svg>';

    passwordInputs.forEach(function (input) {
      if (input.closest(".password-input-wrap")) {
        return;
      }

      var wrapper = document.createElement("span");
      wrapper.className = "password-input-wrap";

      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);

      var toggle = document.createElement("button");
      toggle.type = "button";
      toggle.className = "password-toggle";
      toggle.setAttribute("aria-label", "Show password");
      toggle.setAttribute("aria-pressed", "false");
      toggle.innerHTML =
        '<span class="icon-eye">' +
        eyeIcon +
        '</span><span class="icon-eye-off">' +
        eyeOffIcon +
        "</span>";

      wrapper.appendChild(toggle);

      toggle.addEventListener("click", function () {
        var reveal = input.type === "password";
        input.type = reveal ? "text" : "password";
        toggle.classList.toggle("is-visible", reveal);
        toggle.setAttribute("aria-pressed", reveal ? "true" : "false");
        toggle.setAttribute(
          "aria-label",
          reveal ? "Hide password" : "Show password"
        );
        input.focus({ preventScroll: true });
      });
    });
  };

  setupPasswordToggles();

  var setupAppSidebar = function () {
    var shell = document.querySelector(".app-shell");
    if (!shell) {
      return;
    }

    var toggle = shell.querySelector("[data-app-sidebar-toggle]");
    var closeBtn = shell.querySelector("[data-app-sidebar-close]");
    var backdrop = shell.querySelector("[data-app-sidebar-backdrop]");
    var mobileQuery = window.matchMedia("(max-width: 760px)");

    var closeSidebar = function () {
      shell.classList.remove("is-sidebar-open");
    };

    var openSidebar = function () {
      if (!mobileQuery.matches) {
        return;
      }
      shell.classList.add("is-sidebar-open");
    };

    if (toggle) {
      toggle.addEventListener("click", function () {
        if (!mobileQuery.matches) {
          return;
        }
        shell.classList.toggle("is-sidebar-open");
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener("click", closeSidebar);
    }

    if (backdrop) {
      backdrop.addEventListener("click", closeSidebar);
    }

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeSidebar();
      }
    });

    var syncSidebarState = function () {
      if (!mobileQuery.matches) {
        closeSidebar();
      }
    };

    if (typeof mobileQuery.addEventListener === "function") {
      mobileQuery.addEventListener("change", syncSidebarState);
    } else if (typeof mobileQuery.addListener === "function") {
      mobileQuery.addListener(syncSidebarState);
    }

    window.addEventListener("resize", syncSidebarState, { passive: true });

    if (mobileQuery.matches) {
      closeSidebar();
    } else {
      openSidebar();
    }
  };

  setupAppSidebar();

  var setupAdminBlogModal = function () {
    var manager = document.querySelector("[data-admin-blog-manager]");
    if (!manager) {
      return;
    }

    var modal = manager.querySelector("[data-admin-blog-modal]");
    var form = manager.querySelector("[data-admin-blog-form]");
    var modalTitle = manager.querySelector("[data-admin-blog-title]");
    var addButton = manager.querySelector("[data-admin-blog-add]");
    var idInput = manager.querySelector("[data-admin-blog-id]");
    var slugInput = manager.querySelector("[data-admin-blog-slug]");
    var titleViInput = manager.querySelector("[data-admin-blog-title-vi]");
    var titleEnInput = manager.querySelector("[data-admin-blog-title-en]");
    var contentViInput = manager.querySelector("[data-admin-blog-content-vi]");
    var contentEnInput = manager.querySelector("[data-admin-blog-content-en]");
    var statusInput = manager.querySelector("[data-admin-blog-status]");
    var closeButtons = manager.querySelectorAll(
      "[data-admin-blog-close], [data-admin-blog-close-btn], [data-admin-blog-cancel]"
    );
    var editButtons = manager.querySelectorAll("[data-admin-blog-edit]");
    var seedNode = manager.querySelector("#admin-blog-seed");

    if (
      !modal ||
      !form ||
      !modalTitle ||
      !idInput ||
      !slugInput ||
      !titleViInput ||
      !titleEnInput ||
      !contentViInput ||
      !contentEnInput ||
      !statusInput
    ) {
      return;
    }

    var seed = Object.create(null);
    if (seedNode) {
      try {
        var parsed = JSON.parse(seedNode.textContent || "[]");
        if (Array.isArray(parsed)) {
          parsed.forEach(function (post) {
            var key = String(post && post.id ? post.id : "");
            if (!key) {
              return;
            }
            seed[key] = post;
          });
        }
      } catch (error) {
        seed = Object.create(null);
      }
    }

    var mode = "create";
    var slugLocked = false;

    var slugify = function (value) {
      var text = String(value || "").toLowerCase();
      if (typeof text.normalize === "function") {
        text = text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
      } else {
        text = text
          .replace(/[\u00e0\u00e1\u1ea1\u1ea3\u00e3\u00e2\u1ea7\u1ea5\u1ead\u1ea9\u1eab\u0103\u1eb1\u1eaf\u1eb7\u1eb3\u1eb5]/g, "a")
          .replace(/[\u00e8\u00e9\u1eb9\u1ebb\u1ebd\u00ea\u1ec1\u1ebf\u1ec7\u1ec3\u1ec5]/g, "e")
          .replace(/[\u00ec\u00ed\u1ecb\u1ec9\u0129]/g, "i")
          .replace(/[\u00f2\u00f3\u1ecd\u1ecf\u00f5\u00f4\u1ed3\u1ed1\u1ed9\u1ed5\u1ed7\u01a1\u1edd\u1edb\u1ee3\u1edf\u1ee1]/g, "o")
          .replace(/[\u00f9\u00fa\u1ee5\u1ee7\u0169\u01b0\u1eeb\u1ee9\u1ef1\u1eed\u1eef]/g, "u")
          .replace(/[\u1ef3\u00fd\u1ef5\u1ef7\u1ef9]/g, "y");
      }
      text = text.replace(/\u0111/g, "d");
      text = text.replace(/[^a-z0-9]+/g, "-");
      text = text.replace(/^-+|-+$/g, "");
      return text;
    };

    var openModal = function () {
      modal.hidden = false;
      modal.classList.add("is-open");
      document.body.classList.add("is-admin-blog-modal-open");
      setTimeout(function () {
        titleViInput.focus();
      }, 20);
    };

    var closeModal = function () {
      modal.classList.remove("is-open");
      modal.hidden = true;
      document.body.classList.remove("is-admin-blog-modal-open");
      modalTitle.textContent = "Th?m b?i vi?t";

    var openCreate = function () {
      mode = "create";
      slugLocked = false;
      form.reset();
      idInput.value = "";
      statusInput.value = "draft";
      modalTitle.textContent = "Thêm bài viết";
      openModal();
    };

    var openEdit = function (postId) {
      var post = seed[String(postId)] || null;
      if (!post) {
        return;
      }

      mode = "edit";
      slugLocked = true;
      idInput.value = String(post.id || "");
      slugInput.value = String(post.slug || "");
      titleViInput.value = String(post.title_vi || "");
      titleEnInput.value = String(post.title_en || "");
      contentViInput.value = String(post.content_vi || "");
      contentEnInput.value = String(post.content_en || "");
      statusInput.value =
        post.status === "published" ? "published" : "draft";
      modalTitle.textContent = "Sửa bài viết";
      openModal();
    };

    if (addButton) {
      addButton.addEventListener("click", openCreate);
    }

    editButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        var postId = button.getAttribute("data-admin-blog-edit") || "";
        openEdit(postId);
      });
    });

    closeButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        closeModal();
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") {
        return;
      }
      if (!modal.classList.contains("is-open")) {
        return;
      }
      closeModal();
    });

    titleViInput.addEventListener("input", function () {
      if (mode !== "create" || slugLocked) {
        return;
      }
      slugInput.value = slugify(titleViInput.value);
    });

    slugInput.addEventListener("input", function () {
      if (mode !== "create") {
        return;
      }
      slugLocked = slugInput.value.trim() !== "";
    });
  };

  setupAdminBlogModal();
  var setupTraceScanner = function () {
    return;
  };

  setupTraceScanner();

  var watched = document.querySelectorAll(
    ".hero-panel, .card, .kpi, .form-card, .section-head, .metric-card, .hero-floating-card, .hero-process-step, .spotlight-card, .story-item, .auth-support-card, .auth-spotlight-card, .studio-editor-card, .studio-list-item, .dashboard-hero-panel, .dashboard-mini-stats > div"
  );

  if (watched.length) {
    watched.forEach(function (el, idx) {
      el.setAttribute("data-reveal", "");
      el.style.transitionDelay = String((idx % 7) * 35) + "ms";
    });

    if ("IntersectionObserver" in window) {
      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add("is-visible");
              observer.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.16, rootMargin: "0px 0px -30px 0px" }
      );

      watched.forEach(function (el) {
        observer.observe(el);
      });
    } else {
      watched.forEach(function (el) {
        el.classList.add("is-visible");
      });
    }
  }

  var setupPointerGlow = function () {
    var glowTargets = document.querySelectorAll(
      ".nav-wrap, .app-sidebar, .app-topbar, .card, .kpi, .btn, .site-footer-brand, .site-footer-cta"
    );

    if (!glowTargets.length) {
      return;
    }

    glowTargets.forEach(function (el) {
      el.addEventListener("pointermove", function (event) {
        var rect = el.getBoundingClientRect();
        if (!rect.width || !rect.height) {
          return;
        }

        var x = ((event.clientX - rect.left) / rect.width) * 100;
        var y = ((event.clientY - rect.top) / rect.height) * 100;

        el.style.setProperty("--glow-x", x.toFixed(2) + "%");
        el.style.setProperty("--glow-y", y.toFixed(2) + "%");
      });

      el.addEventListener("pointerleave", function () {
        el.style.removeProperty("--glow-x");
        el.style.removeProperty("--glow-y");
      });
    });
  };

  setupPointerGlow();

  var onScroll = function () {
    if (window.scrollY > 8) {
      document.body.classList.add("is-scrolled");
    } else {
      document.body.classList.remove("is-scrolled");
    }
  };

  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  var copyButtons = document.querySelectorAll(".copy-url-btn");
  copyButtons.forEach(function (btn) {
    var originalText = btn.textContent;
    var copiedText = btn.getAttribute("data-copied-label") || "Copied";

    btn.addEventListener("click", function () {
      var value = btn.getAttribute("data-url") || "";
      copyText(value, function () {
        btn.textContent = copiedText;
        setTimeout(function () {
          btn.textContent = originalText;
        }, 1200);
      });
    });
  });

  var printButtons = document.querySelectorAll(".print-qr-btn");
  printButtons.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var url = btn.getAttribute("data-url") || "";
      var img = btn.getAttribute("data-img") || "";
      var printWin = window.open("", "_blank", "noopener,width=520,height=640");
      if (!printWin) {
        return;
      }

      var html =
        '<!doctype html><html><head><meta charset="UTF-8"><title>QR Label</title>' +
        '<style>body{font-family:Arial,sans-serif;padding:20px;text-align:center;background:#fff;color:#111}h2{margin:0 0 12px}img{max-width:380px;width:100%;height:auto;border:1px solid #d5d5d5;border-radius:10px;padding:16px;background:#fff;image-rendering:crisp-edges}p{word-break:break-all;font-size:13px;line-height:1.4;margin:10px 0 0}</style>' +
        "</head><body>" +
        '<h2 style="margin-top:0">AGRISORT-AI QR</h2>' +
        (img ? '<img src="' + img + '" alt="QR">' : "") +
        (url ? '<p>' + url + "</p>" : "") +
        "</body></html>";

      printWin.document.open();
      printWin.document.write(html);
      printWin.document.close();
      printWin.focus();
      setTimeout(function () {
        printWin.print();
      }, 160);
    });
  });

  var pngButtons = document.querySelectorAll(".download-png-btn");
  pngButtons.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var imageSource = btn.getAttribute("data-img") || "";
      var filename = btn.getAttribute("data-filename") || "qr.png";
      downloadPngFromSource(imageSource, filename);
    });
  });

  var docsCodeBlocks = document.querySelectorAll(".docs-markdown pre");
  docsCodeBlocks.forEach(function (pre) {
    var code = pre.querySelector("code");
    if (!code) {
      return;
    }

    var button = document.createElement("button");
    button.type = "button";
    button.className = "copy-code-btn";
    button.textContent = "Copy";
    button.setAttribute("aria-label", "Copy code");
    pre.appendChild(button);

    button.addEventListener("click", function () {
      copyText(code.textContent || "", function () {
        button.textContent = "Copied";
        setTimeout(function () {
          button.textContent = "Copy";
        }, 1200);
      });
    });
  });
})();


