(function () {
  var setupTraceScanner = function () {
    var scanner = document.querySelector("[data-trace-scanner]");
    if (!scanner) {
      return;
    }

    var message = function (datasetKey, fallback) {
      var value = scanner.dataset[datasetKey];
      if (value && value.trim() !== "") {
        return value;
      }
      return fallback;
    };

    var uploadEndpoint = scanner.getAttribute("data-upload-endpoint") || "";
    var lookupEndpoint = scanner.getAttribute("data-lookup-endpoint") || "";
    var traceBase = scanner.getAttribute("data-trace-base") || "/trace/";

    var video = scanner.querySelector("[data-trace-video]");
    var cameraStatus = scanner.querySelector("[data-trace-camera-status]");
    var uploadStatus = scanner.querySelector("[data-trace-upload-status]");
    var manualStatus = scanner.querySelector("[data-trace-manual-status]");
    var cameraStartBtn = scanner.querySelector("[data-trace-camera-start]");
    var cameraStopBtn = scanner.querySelector("[data-trace-camera-stop]");
    var uploadForm = scanner.querySelector("[data-trace-upload-form]");
    var uploadInput = scanner.querySelector("[data-trace-upload-input]");
    var manualForm = scanner.querySelector("[data-trace-manual-form]");
    var manualInput = scanner.querySelector("[data-trace-token-input]");
    var resultCard = scanner.querySelector("[data-trace-result-card]");
    var resultToken = scanner.querySelector("[data-trace-result-token]");
    var resultEntity = scanner.querySelector("[data-trace-result-entity]");
    var resultSource = scanner.querySelector("[data-trace-result-source]");
    var openLink = scanner.querySelector("[data-trace-open-link]");
    var resetBtn = scanner.querySelector("[data-trace-reset]");
    var overlayCanvas = scanner.querySelector("[data-trace-overlay]");
    var toastWrap = scanner.querySelector("[data-trace-toast-wrap]");
    var historyBody = scanner.querySelector("[data-trace-history-body]");
    var historyClearBtn = scanner.querySelector("[data-trace-history-clear]");

    var cameraStream = null;
    var scanInterval = null;
    var detectBusy = false;
    var lastCameraInvalidAt = 0;
    var detector = null;
    var jsQRDecoder = null;
    var overlayClearTimer = null;
    var dedupeWindowMs = 2000;
    var historyRows = [];
    var historyByKey = Object.create(null);
    var entityLookupCache = Object.create(null);
    var entityLookupPending = Object.create(null);

    if (typeof window.BarcodeDetector === "function") {
      try {
        detector = new window.BarcodeDetector({ formats: ["qr_code"] });
      } catch (error) {
        detector = null;
      }
    }

    if (typeof window.jsQR === "function") {
      jsQRDecoder = window.jsQR;
    }

    var hostname = String(window.location.hostname || "").toLowerCase();
    var isLocalHost =
      hostname === "localhost" ||
      hostname === "127.0.0.1" ||
      hostname === "::1" ||
      hostname === "[::1]";
    var hasSecureContext =
      typeof window.isSecureContext === "boolean"
        ? window.isSecureContext
        : window.location.protocol === "https:" || isLocalHost;
    var hasGetUserMedia =
      !!navigator.mediaDevices &&
      typeof navigator.mediaDevices.getUserMedia === "function";
    var canUseClientDetector = detector !== null;
    var canUseJsQrDecoder = jsQRDecoder !== null;
    var canUseClientDecoder = canUseClientDetector || canUseJsQrDecoder;
    var canUseServerFallback = uploadEndpoint !== "" && uploadForm !== null;
    var canUseCamera =
      hasSecureContext &&
      hasGetUserMedia &&
      (canUseClientDecoder || canUseServerFallback);
    var snapshotCanvas = document.createElement("canvas");
    var snapshotContext = snapshotCanvas.getContext
      ? snapshotCanvas.getContext("2d")
      : null;
    var clientDecodeCanvas = document.createElement("canvas");
    var clientDecodeContext = clientDecodeCanvas.getContext
      ? clientDecodeCanvas.getContext("2d", { willReadFrequently: true })
      : null;
    var overlayContext = overlayCanvas && overlayCanvas.getContext
      ? overlayCanvas.getContext("2d")
      : null;

    var labelUnknown = message("labelEntityUnknown", "Unknown");
    var labelStatusValid = message("labelStatusValid", "Valid");
    var labelStatusInvalid = message("labelStatusInvalid", "Invalid");
    var labelOpenTrace = message("labelOpenTrace", "Open trace");
    var cameraOpeningMessage = message(
      "msgCameraOpening",
      "Opening camera..."
    );
    var cameraInsecureMessage = message(
      "msgCameraInsecure",
      "This page is not in a valid secure context for camera access."
    );

    var cameraAccessMessage = function (error) {
      if (!hasSecureContext) {
        return cameraInsecureMessage;
      }

      var errorName = error && error.name ? String(error.name) : "";
      if (errorName === "NotAllowedError" || errorName === "PermissionDeniedError") {
        return message(
          "msgCameraDenied",
          "Camera permission denied. Use file upload or manual token input."
        );
      }

      if (errorName === "NotFoundError" || errorName === "DevicesNotFoundError") {
        return message(
          "msgCameraNotFound",
          "No camera device was found on this device."
        );
      }

      if (errorName === "NotReadableError" || errorName === "TrackStartError") {
        return message(
          "msgCameraBusy",
          "The camera is being used by another app or tab. Close it and try again."
        );
      }

      return message(
        "msgCameraUnsupported",
        "Cannot access camera on this browser."
      );
    };

    var cameraConstraintSets = function () {
      return [
        {
          audio: false,
          video: {
            facingMode: { ideal: "environment" },
            width: { ideal: 1280 },
            height: { ideal: 720 },
          },
        },
        {
          audio: false,
          video: {
            width: { ideal: 1280 },
            height: { ideal: 720 },
          },
        },
        {
          audio: false,
          video: true,
        },
      ];
    };

    var requestCameraStream = function (index, lastError) {
      var variants = cameraConstraintSets();
      if (index >= variants.length) {
        return Promise.reject(lastError || new Error("Camera unavailable"));
      }

      return navigator.mediaDevices
        .getUserMedia(variants[index])
        .catch(function (error) {
          var errorName = error && error.name ? String(error.name) : "";
          var isRetryable =
            errorName === "OverconstrainedError" ||
            errorName === "ConstraintNotSatisfiedError" ||
            errorName === "NotFoundError" ||
            errorName === "DevicesNotFoundError" ||
            errorName === "AbortError";

          if (isRetryable) {
            return requestCameraStream(index + 1, error);
          }

          return Promise.reject(error);
        });
    };

    var attachStreamToVideo = function (stream) {
      if (!video) {
        return Promise.resolve();
      }

      video.muted = true;
      video.setAttribute("muted", "muted");
      video.setAttribute("playsinline", "playsinline");
      video.setAttribute("autoplay", "autoplay");
      video.srcObject = stream;

      return new Promise(function (resolve) {
        var tryPlay = function () {
          if (typeof video.play !== "function") {
            resolve();
            return;
          }

          var playPromise = video.play();
          if (playPromise && typeof playPromise.then === "function") {
            playPromise.then(
              function () {
                resolve();
              },
              function () {
                resolve();
              }
            );
            return;
          }

          resolve();
        };

        if ((video.readyState || 0) >= 1) {
          tryPlay();
          return;
        }

        video.addEventListener(
          "loadedmetadata",
          function () {
            tryPlay();
          },
          { once: true }
        );
      });
    };

    var setStatusText = function (el, text, isError) {
      if (!el) {
        return;
      }
      el.textContent = text;
      el.classList.toggle("is-error", !!isError);
    };

    var decodePart = function (value) {
      try {
        return decodeURIComponent(value);
      } catch (error) {
        return value;
      }
    };

    var isValidToken = function (token) {
      return /^[A-Za-z0-9][A-Za-z0-9._-]{2,120}$/.test(token);
    };

    var extractToken = function (rawText) {
      var text = String(rawText || "").trim();
      if (!text) {
        return "";
      }

      var pathMatch = text.match(/\/trace\/([^/?#\s]+)/i);
      if (pathMatch && pathMatch[1]) {
        var tokenFromPath = decodePart(pathMatch[1]);
        if (isValidToken(tokenFromPath)) {
          return tokenFromPath;
        }
      }

      var queryMatch = text.match(/(?:\?|&)(?:qr_token|token)=([^&#\s]+)/i);
      if (queryMatch && queryMatch[1]) {
        var tokenFromQuery = decodePart(queryMatch[1]);
        if (isValidToken(tokenFromQuery)) {
          return tokenFromQuery;
        }
      }

      if (isValidToken(text)) {
        return text;
      }

      return "";
    };

    var classifyDecodedText = function (rawText) {
      var text = String(rawText || "").trim();
      if (!text) {
        return { state: "none", token: "", raw: "", overlay: null };
      }

      var token = extractToken(text);
      if (token) {
        return { state: "valid", token: token, raw: text, overlay: null };
      }

      return { state: "invalid", token: "", raw: text, overlay: null };
    };

    var attemptBarcodeDetectorDecode = function (source, sourceWidth, sourceHeight) {
      if (!detector) {
        return Promise.resolve({ state: "none", token: "", raw: "", overlay: null });
      }

      return detector
        .detect(source)
        .then(function (codes) {
          if (!codes || !codes.length) {
            return { state: "none", token: "", raw: "", overlay: null };
          }
          var code = codes[0];
          var classified = classifyDecodedText(code.rawValue || "");
          if (code.cornerPoints && code.cornerPoints.length >= 4) {
            classified.overlay = {
              kind: "polygon",
              points: code.cornerPoints.map(function (point) {
                return {
                  x: Number(point.x || 0),
                  y: Number(point.y || 0),
                };
              }),
              sourceWidth: sourceWidth,
              sourceHeight: sourceHeight,
            };
          } else if (code.boundingBox) {
            classified.overlay = {
              kind: "rect",
              x: Number(code.boundingBox.x || 0),
              y: Number(code.boundingBox.y || 0),
              width: Number(code.boundingBox.width || 0),
              height: Number(code.boundingBox.height || 0),
              sourceWidth: sourceWidth,
              sourceHeight: sourceHeight,
            };
          }
          return classified;
        })
        .catch(function () {
          return { state: "none", token: "", raw: "", overlay: null };
        });
    };

    var drawVariantToDecodeCanvas = function (source, sourceWidth, sourceHeight, variant) {
      if (!clientDecodeContext || sourceWidth <= 0 || sourceHeight <= 0) {
        return null;
      }

      var cropRatio = variant.cropRatio || 1;
      var cropWidth = Math.max(20, Math.floor(sourceWidth * cropRatio));
      var cropHeight = Math.max(20, Math.floor(sourceHeight * cropRatio));
      var sx = Math.floor((sourceWidth - cropWidth) / 2);
      var sy = Math.floor((sourceHeight - cropHeight) / 2);

      var targetWidth = Math.min(1400, Math.max(280, cropWidth));
      var targetHeight = Math.min(
        1400,
        Math.max(280, Math.round((cropHeight / cropWidth) * targetWidth))
      );

      clientDecodeCanvas.width = targetWidth;
      clientDecodeCanvas.height = targetHeight;
      clientDecodeContext.save();
      clientDecodeContext.clearRect(0, 0, targetWidth, targetHeight);

      if (variant.mirrorX) {
        clientDecodeContext.translate(targetWidth, 0);
        clientDecodeContext.scale(-1, 1);
      }

      clientDecodeContext.drawImage(
        source,
        sx,
        sy,
        cropWidth,
        cropHeight,
        0,
        0,
        targetWidth,
        targetHeight
      );
      clientDecodeContext.restore();

      return {
        sx: sx,
        sy: sy,
        cropWidth: cropWidth,
        cropHeight: cropHeight,
        targetWidth: targetWidth,
        targetHeight: targetHeight,
        mirrored: !!variant.mirrorX,
      };
    };

    var attemptJsQrDecode = function (source, sourceWidth, sourceHeight) {
      if (!jsQRDecoder || !clientDecodeContext || sourceWidth <= 0 || sourceHeight <= 0) {
        return { state: "none", token: "", raw: "", overlay: null };
      }

      var variants = [
        { cropRatio: 1, mirrorX: false },
        { cropRatio: 0.8, mirrorX: false },
        { cropRatio: 0.6, mirrorX: false },
        { cropRatio: 1, mirrorX: true },
        { cropRatio: 0.8, mirrorX: true },
        { cropRatio: 0.6, mirrorX: true },
      ];

      var firstInvalid = null;
      for (var i = 0; i < variants.length; i++) {
        var drawMeta = drawVariantToDecodeCanvas(source, sourceWidth, sourceHeight, variants[i]);
        if (!drawMeta) {
          continue;
        }
        var imageData;
        try {
          imageData = clientDecodeContext.getImageData(
            0,
            0,
            clientDecodeCanvas.width,
            clientDecodeCanvas.height
          );
        } catch (error) {
          continue;
        }

        var decoded = null;
        try {
          decoded = jsQRDecoder(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: "attemptBoth",
          });
        } catch (error) {
          decoded = null;
        }

        if (!decoded || !decoded.data) {
          continue;
        }

        var classified = classifyDecodedText(decoded.data);
        if (decoded.location) {
          var loc = decoded.location;
          var points = [
            loc.topLeftCorner,
            loc.topRightCorner,
            loc.bottomRightCorner,
            loc.bottomLeftCorner,
          ]
            .filter(Boolean)
            .map(function (point) {
              var px = Number(point.x || 0);
              if (drawMeta.mirrored) {
                px = drawMeta.targetWidth - px;
              }
              return {
                x: drawMeta.sx + (px / drawMeta.targetWidth) * drawMeta.cropWidth,
                y: drawMeta.sy + (Number(point.y || 0) / drawMeta.targetHeight) * drawMeta.cropHeight,
              };
            });
          if (points.length >= 4) {
            classified.overlay = {
              kind: "polygon",
              points: points,
              sourceWidth: sourceWidth,
              sourceHeight: sourceHeight,
            };
          }
        }

        if (classified.state === "valid") {
          return classified;
        }
        if (classified.state === "invalid" && firstInvalid === null) {
          firstInvalid = classified;
        }
      }

      return firstInvalid || { state: "none", token: "", raw: "", overlay: null };
    };

    var decodeClientSource = function (source, sourceWidth, sourceHeight) {
      var invalidCandidate = null;

      return attemptBarcodeDetectorDecode(source, sourceWidth, sourceHeight).then(function (detectorResult) {
        if (detectorResult.state === "valid") {
          return detectorResult;
        }
        if (detectorResult.state === "invalid") {
          invalidCandidate = detectorResult;
        }

        var jsqrResult = attemptJsQrDecode(source, sourceWidth, sourceHeight);
        if (jsqrResult.state === "valid") {
          return jsqrResult;
        }
        if (jsqrResult.state === "invalid") {
          return jsqrResult;
        }

        return invalidCandidate || { state: "none", token: "", raw: "", overlay: null };
      });
    };

    var buildTraceUrl = function (token) {
      var base = traceBase;
      if (base.charAt(base.length - 1) !== "/") {
        base += "/";
      }
      return base + encodeURIComponent(token);
    };

    var clearStatuses = function () {
      setStatusText(uploadStatus, "", false);
      setStatusText(manualStatus, "", false);
    };

    var shorten = function (text, maxLen) {
      var value = String(text || "");
      if (value.length <= maxLen) {
        return value;
      }
      return value.slice(0, Math.max(0, maxLen - 3)) + "...";
    };

    var shortToken = function (token) {
      var value = String(token || "");
      if (value.length <= 20) {
        return value;
      }
      return value.slice(0, 9) + "..." + value.slice(-6);
    };

    var escapeHtml = function (value) {
      return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
    };

    var formatTime = function (timestamp) {
      var dt = new Date(timestamp);
      try {
        return dt.toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
        });
      } catch (error) {
        return dt.toTimeString().slice(0, 8);
      }
    };

    var parseServerResponse = function (response) {
      return response.text().then(function (text) {
        var payload = {};
        try {
          payload = JSON.parse(text);
        } catch (error) {
          payload = {};
        }

        return {
          ok: response.ok,
          status: response.status,
          payload: payload,
        };
      });
    };

    var requestServerDecode = function (formData) {
      return fetch(uploadEndpoint, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      }).then(parseServerResponse);
    };

    var requestTokenLookup = function (token) {
      var normalizedToken = String(token || "").trim();
      if (!normalizedToken || !lookupEndpoint) {
        return Promise.resolve(null);
      }

      if (entityLookupCache[normalizedToken]) {
        return Promise.resolve(entityLookupCache[normalizedToken]);
      }

      if (entityLookupPending[normalizedToken]) {
        return entityLookupPending[normalizedToken];
      }

      var separator = lookupEndpoint.indexOf("?") === -1 ? "?" : "&";
      var request = fetch(
        lookupEndpoint + separator + "token=" + encodeURIComponent(normalizedToken),
        {
          method: "GET",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
          },
        }
      )
        .then(parseServerResponse)
        .then(function (result) {
          if (!result.ok || !result.payload || !result.payload.success) {
            return null;
          }

          var resolved = {
            entityLabel: result.payload.entity_label || labelUnknown,
            traceUrl: result.payload.trace_url || buildTraceUrl(normalizedToken),
          };
          entityLookupCache[normalizedToken] = resolved;
          return resolved;
        })
        .catch(function () {
          return null;
        })
        .then(function (resolved) {
          delete entityLookupPending[normalizedToken];
          return resolved;
        });

      entityLookupPending[normalizedToken] = request;
      return request;
    };

    var showToast = function (text, type) {
      if (!toastWrap || !text) {
        return;
      }

      var toast = document.createElement("div");
      toast.className = "trace-toast " + (type === "error" ? "error" : "success");
      toast.textContent = text;
      toastWrap.appendChild(toast);

      window.requestAnimationFrame(function () {
        toast.classList.add("is-visible");
      });

      setTimeout(function () {
        toast.classList.remove("is-visible");
        setTimeout(function () {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
        }, 180);
      }, 1800);

      while (toastWrap.children.length > 4) {
        toastWrap.removeChild(toastWrap.children[0]);
      }
    };

    var setOpenLinkState = function (isValid, token) {
      if (!openLink) {
        return;
      }
      if (isValid && token) {
        openLink.href = buildTraceUrl(token);
        openLink.classList.remove("is-disabled");
        openLink.removeAttribute("aria-disabled");
      } else {
        openLink.href = "#";
        openLink.classList.add("is-disabled");
        openLink.setAttribute("aria-disabled", "true");
      }
    };

    var clearCurrentResult = function () {
      if (resultCard) {
        resultCard.classList.add("is-hidden");
      }
      if (resultToken) {
        resultToken.textContent = "-";
      }
      if (resultEntity) {
        resultEntity.textContent = labelUnknown;
      }
      if (resultSource) {
        resultSource.textContent = "";
      }
      setOpenLinkState(false, "");
    };

    var renderCurrentResult = function (record) {
      if (!record) {
        clearCurrentResult();
        return;
      }
      if (resultToken) {
        resultToken.textContent = record.status === "valid"
          ? (record.token || "-")
          : shorten(record.raw || "-", 42);
      }
      if (resultEntity) {
        resultEntity.textContent = record.entityLabel || labelUnknown;
      }
      if (resultSource) {
        resultSource.textContent = record.sourceLabel || "";
      }
      if (resultCard) {
        resultCard.classList.remove("is-hidden");
      }
      setOpenLinkState(record.status === "valid", record.token || "");
      if (manualInput && record.status === "valid" && record.token) {
        manualInput.value = record.token;
      }
    };

    var renderHistory = function () {
      if (!historyBody) {
        return;
      }
      if (!historyRows.length) {
        historyBody.innerHTML =
          '<tr><td class="trace-history-empty" colspan="6">' +
          escapeHtml(message("msgHistoryEmpty", "No scanned item yet.")) +
          "</td></tr>";
        return;
      }

      historyBody.innerHTML = historyRows.map(function (row) {
        var entityText = escapeHtml(row.entityLabel || labelUnknown);
        var tokenText = row.status === "valid"
          ? escapeHtml(shortToken(row.token))
          : escapeHtml(shorten(row.raw || "-", 28));
        var tokenTitle = row.status === "valid"
          ? escapeHtml(row.token)
          : escapeHtml(row.raw || "-");
        var statusLabel = row.status === "valid" ? labelStatusValid : labelStatusInvalid;
        var statusClass = row.status === "valid" ? "valid" : "invalid";
        var actionHtml = row.status === "valid"
          ? '<a class="trace-action-link" href="' + escapeHtml(row.traceUrl) + '">' + escapeHtml(labelOpenTrace) + "</a>"
          : '<span class="trace-action-link is-disabled">-</span>';

        return (
          "<tr>" +
          "<td>" + entityText + "</td>" +
          '<td><span class="trace-token-chip" title="' + tokenTitle + '">' + tokenText + "</span></td>" +
          '<td><span class="trace-status-badge ' + statusClass + '">' + escapeHtml(statusLabel) + "</span></td>" +
          "<td>" + escapeHtml(formatTime(row.lastSeenAt)) + "</td>" +
          "<td>" + escapeHtml(String(row.count)) + "</td>" +
          "<td>" + actionHtml + "</td>" +
          "</tr>"
        );
      }).join("");
    };

    var historyKey = function (status, token, raw) {
      if (status === "valid" && token) {
        return "valid:" + token;
      }
      var normalizedRaw = String(raw || "").trim().toLowerCase();
      if (!normalizedRaw) {
        normalizedRaw = "unknown";
      }
      return "invalid:" + normalizedRaw;
    };

    var registerScanResult = function (payload) {
      var now = Date.now();
      var status = payload.status === "valid" ? "valid" : "invalid";
      var token = String(payload.token || "");
      var raw = String(payload.raw || "");
      var key = historyKey(status, token, raw);
      var row = historyByKey[key] || null;
      var isBurstDuplicate = false;

      if (!row) {
        row = {
          key: key,
          status: status,
          token: token,
          raw: raw,
          entityLabel: payload.entityLabel || labelUnknown,
          sourceLabel: payload.sourceLabel || "",
          traceUrl: status === "valid" && token ? (payload.traceUrl || buildTraceUrl(token)) : "",
          count: 1,
          lastSeenAt: now,
        };
        historyByKey[key] = row;
        historyRows.push(row);
      } else {
        isBurstDuplicate = now - row.lastSeenAt < dedupeWindowMs;
        if (!isBurstDuplicate) {
          row.count += 1;
        }
        row.lastSeenAt = now;
        row.sourceLabel = payload.sourceLabel || row.sourceLabel;
        row.entityLabel = payload.entityLabel || row.entityLabel;
        if (status === "valid" && token) {
          row.token = token;
          row.raw = token;
          row.traceUrl = payload.traceUrl || buildTraceUrl(token);
        } else if (raw) {
          row.raw = raw;
        }
      }

      historyRows.sort(function (a, b) {
        return b.lastSeenAt - a.lastSeenAt;
      });

      renderCurrentResult(row);
      renderHistory();

      return {
        row: row,
        isBurstDuplicate: isBurstDuplicate,
      };
    };

    var reportResult = function (payload, toastType) {
      var outcome = registerScanResult(payload);
      if (!outcome.isBurstDuplicate) {
        var toastMessage = payload.status === "valid"
          ? message("msgToastValid", "Scan success.") + " " + shortToken(payload.token || "")
          : message("msgToastInvalid", "QR detected but token is invalid.");
        showToast(toastMessage, toastType || (payload.status === "valid" ? "success" : "error"));
      }
      return outcome;
    };

    var clearHistory = function () {
      historyRows = [];
      historyByKey = Object.create(null);
      renderHistory();
    };

    var syncOverlayCanvas = function () {
      if (!overlayCanvas || !overlayContext) {
        return;
      }
      var rect = overlayCanvas.getBoundingClientRect();
      if (!rect.width || !rect.height) {
        return;
      }
      var ratio = window.devicePixelRatio || 1;
      overlayCanvas.width = Math.round(rect.width * ratio);
      overlayCanvas.height = Math.round(rect.height * ratio);
      overlayContext.setTransform(ratio, 0, 0, ratio, 0, 0);
    };

    var clearOverlay = function () {
      if (!overlayCanvas || !overlayContext) {
        return;
      }
      if (overlayClearTimer) {
        clearTimeout(overlayClearTimer);
        overlayClearTimer = null;
      }
      overlayContext.clearRect(0, 0, overlayCanvas.clientWidth, overlayCanvas.clientHeight);
    };

    var getCoverProjection = function (sourceWidth, sourceHeight) {
      if (!overlayCanvas || sourceWidth <= 0 || sourceHeight <= 0) {
        return null;
      }
      var drawWidth = overlayCanvas.clientWidth;
      var drawHeight = overlayCanvas.clientHeight;
      if (drawWidth <= 0 || drawHeight <= 0) {
        return null;
      }

      var scale = Math.max(drawWidth / sourceWidth, drawHeight / sourceHeight);
      var contentWidth = sourceWidth * scale;
      var contentHeight = sourceHeight * scale;
      var offsetX = (drawWidth - contentWidth) / 2;
      var offsetY = (drawHeight - contentHeight) / 2;

      return {
        scale: scale,
        offsetX: offsetX,
        offsetY: offsetY,
      };
    };

    var mapPoint = function (x, y, projection) {
      return {
        x: x * projection.scale + projection.offsetX,
        y: y * projection.scale + projection.offsetY,
      };
    };

    var drawOverlay = function (overlay, isValid, sourceWidth, sourceHeight) {
      if (!overlayCanvas || !overlayContext) {
        return;
      }
      syncOverlayCanvas();
      clearOverlay();

      var canvasWidth = overlayCanvas.clientWidth;
      var canvasHeight = overlayCanvas.clientHeight;
      if (!canvasWidth || !canvasHeight) {
        return;
      }

      var projection = getCoverProjection(
        overlay && overlay.sourceWidth ? overlay.sourceWidth : sourceWidth,
        overlay && overlay.sourceHeight ? overlay.sourceHeight : sourceHeight
      );
      if (!projection) {
        return;
      }

      overlayContext.lineWidth = 3;
      overlayContext.strokeStyle = isValid ? "rgba(27, 210, 122, 0.95)" : "rgba(238, 73, 58, 0.95)";
      overlayContext.fillStyle = isValid ? "rgba(27, 210, 122, 0.18)" : "rgba(238, 73, 58, 0.18)";

      if (overlay && overlay.kind === "polygon" && overlay.points && overlay.points.length >= 4) {
        overlayContext.beginPath();
        overlay.points.forEach(function (point, idx) {
          var mapped = mapPoint(point.x, point.y, projection);
          if (idx === 0) {
            overlayContext.moveTo(mapped.x, mapped.y);
          } else {
            overlayContext.lineTo(mapped.x, mapped.y);
          }
        });
        overlayContext.closePath();
        overlayContext.fill();
        overlayContext.stroke();
      } else if (overlay && overlay.kind === "rect") {
        var p1 = mapPoint(overlay.x, overlay.y, projection);
        var p2 = mapPoint(overlay.x + overlay.width, overlay.y + overlay.height, projection);
        var width = p2.x - p1.x;
        var height = p2.y - p1.y;
        overlayContext.fillRect(p1.x, p1.y, width, height);
        overlayContext.strokeRect(p1.x, p1.y, width, height);
      } else {
        var rectWidth = canvasWidth * 0.56;
        var rectHeight = canvasHeight * 0.56;
        var rectX = (canvasWidth - rectWidth) / 2;
        var rectY = (canvasHeight - rectHeight) / 2;
        overlayContext.fillRect(rectX, rectY, rectWidth, rectHeight);
        overlayContext.strokeRect(rectX, rectY, rectWidth, rectHeight);
      }

      overlayClearTimer = setTimeout(function () {
        clearOverlay();
      }, 800);
    };

    var stopCamera = function (showStoppedMessage) {
      if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
      }

      if (cameraStream) {
        cameraStream.getTracks().forEach(function (track) {
          track.stop();
        });
        cameraStream = null;
      }

      if (video) {
        video.srcObject = null;
      }

      detectBusy = false;
      clearOverlay();

      if (cameraStartBtn) {
        cameraStartBtn.disabled = !canUseCamera;
      }
      if (cameraStopBtn) {
        cameraStopBtn.disabled = true;
      }

      if (showStoppedMessage) {
        setStatusText(
          cameraStatus,
          message("msgCameraStopped", "Camera stopped."),
          false
        );
      }
    };

    var applyResolvedEntity = function (token, resolved) {
      if (!token || !resolved) {
        return;
      }

      var key = historyKey("valid", token, token);
      var row = historyByKey[key] || null;
      if (!row) {
        return;
      }

      var changed = false;
      if (resolved.entityLabel && row.entityLabel !== resolved.entityLabel) {
        row.entityLabel = resolved.entityLabel;
        changed = true;
      }
      if (resolved.traceUrl && row.traceUrl !== resolved.traceUrl) {
        row.traceUrl = resolved.traceUrl;
        changed = true;
      }

      if (changed) {
        renderCurrentResult(row);
        renderHistory();
      }
    };

    var showResult = function (token, sourceLabel, entityLabel, options) {
      var extra = options || {};
      var payload = {
        status: extra.status === "invalid" ? "invalid" : "valid",
        token: token || "",
        raw: extra.raw || token || "",
        entityLabel: entityLabel || labelUnknown,
        sourceLabel: sourceLabel || "",
        traceUrl: extra.traceUrl || (token ? buildTraceUrl(token) : ""),
      };

      var outcome = reportResult(payload, payload.status === "valid" ? "success" : "error");
      if (!outcome.isBurstDuplicate && payload.status === "valid" && cameraStream) {
        setStatusText(cameraStatus, message("msgCameraScanning", "Scanning from camera..."), false);
      }

      if (payload.status === "valid" && payload.token) {
        if (payload.entityLabel && payload.entityLabel !== labelUnknown) {
          entityLookupCache[payload.token] = {
            entityLabel: payload.entityLabel,
            traceUrl: payload.traceUrl || buildTraceUrl(payload.token),
          };
        } else {
          requestTokenLookup(payload.token).then(function (resolved) {
            applyResolvedEntity(payload.token, resolved);
          });
        }
      }

      clearStatuses();
    };

    var runDetectFromVideo = function () {
      if (!video || video.readyState < 2 || detectBusy || !canUseClientDecoder) {
        return;
      }

      var sourceWidth = video.videoWidth || 0;
      var sourceHeight = video.videoHeight || 0;
      if (sourceWidth < 64 || sourceHeight < 64) {
        return;
      }

      detectBusy = true;
      decodeClientSource(video, sourceWidth, sourceHeight)
        .then(function (decoded) {
          if (decoded.state === "valid") {
            drawOverlay(decoded.overlay || null, true, sourceWidth, sourceHeight);
            showResult(
              decoded.token,
              message("msgResultCamera", "Token detected from camera."),
              labelUnknown,
              {
                raw: decoded.raw || decoded.token,
              }
            );
            return;
          }

          if (decoded.state === "invalid") {
            var now = Date.now();
            drawOverlay(decoded.overlay || null, false, sourceWidth, sourceHeight);
            if (now - lastCameraInvalidAt > 2200) {
              lastCameraInvalidAt = now;
              setStatusText(
                cameraStatus,
                message(
                  "msgTokenInvalid",
                  "QR detected but token is invalid. Please check again."
                ),
                true
              );
              showResult(
                "",
                message("msgResultCamera", "Token detected from camera."),
                labelUnknown,
                {
                  status: "invalid",
                  raw: decoded.raw || "invalid-token",
                }
              );
            }
          }
        })
        .catch(function () {
          // Keep silent here; scanner can continue.
        })
        .then(
          function () {
            detectBusy = false;
          },
          function () {
            detectBusy = false;
          }
        );
    };

    var runDetectFromVideoServer = function () {
      if (!video || video.readyState < 2 || detectBusy || !snapshotContext) {
        return;
      }

      var videoWidth = video.videoWidth || 0;
      var videoHeight = video.videoHeight || 0;
      if (videoWidth < 64 || videoHeight < 64) {
        return;
      }

      detectBusy = true;

      var maxWidth = 1280;
      var targetWidth = videoWidth > maxWidth ? maxWidth : videoWidth;
      var targetHeight = Math.round((videoHeight / videoWidth) * targetWidth);

      snapshotCanvas.width = targetWidth;
      snapshotCanvas.height = targetHeight;
      snapshotContext.drawImage(video, 0, 0, targetWidth, targetHeight);

      if (typeof snapshotCanvas.toBlob !== "function") {
        detectBusy = false;
        return;
      }

      snapshotCanvas.toBlob(
        function (blob) {
          if (!blob) {
            detectBusy = false;
            return;
          }

          var formData = new FormData();
          formData.append("trace_file", blob, "camera-frame.jpg");

          if (uploadForm) {
            var csrfInput = uploadForm.querySelector('input[name="_token"]');
            if (csrfInput && csrfInput.value) {
              formData.append("_token", csrfInput.value);
            }
          }

          requestServerDecode(formData)
            .then(function (result) {
              if (result.ok && result.payload && result.payload.success) {
                drawOverlay(null, true, videoWidth, videoHeight);
                showResult(
                  result.payload.token,
                  message("msgResultCamera", "Token detected from camera."),
                  result.payload.entity_label || labelUnknown,
                  {
                    raw: result.payload.token,
                    traceUrl: result.payload.trace_url || buildTraceUrl(result.payload.token),
                  }
                );
                return;
              }

              if (result.status === 422) {
                if (
                  result.payload &&
                  result.payload.qr_detected === true &&
                  result.payload.token_valid === false
                ) {
                  setStatusText(
                    cameraStatus,
                    result.payload.message ||
                      message(
                        "msgTokenInvalid",
                        "QR detected but token is invalid. Please check again."
                      ),
                    true
                  );
                  drawOverlay(null, false, videoWidth, videoHeight);
                  showResult(
                    "",
                    message("msgResultCamera", "Token detected from camera."),
                    labelUnknown,
                    {
                      status: "invalid",
                      raw: result.payload.message || "invalid-token",
                    }
                  );
                }
                return;
              }

              // CSRF/session issue should stop scanning and ask user to refresh.
              if (result.status === 419) {
                setStatusText(
                  cameraStatus,
                  "Phien lam viec da het han. Vui long tai lai trang de quet lai.",
                  true
                );
                showToast(message("msgToastSystem", "Cannot process at this time. Please retry."), "error");
                stopCamera(false);
                return;
              }

              if (result.payload && result.payload.message) {
                setStatusText(cameraStatus, result.payload.message, true);
              }
            })
            .catch(function () {
              showToast(message("msgToastSystem", "Cannot process at this time. Please retry."), "error");
            })
            .then(
              function () {
                detectBusy = false;
              },
              function () {
                detectBusy = false;
              }
            );
        },
        "image/jpeg",
        0.9
      );
    };

    var startCamera = function () {
      if (!canUseCamera) {
        setStatusText(
          cameraStatus,
          message(
            "msgCameraUnsupported",
            "Browser does not support camera scanning."
          ),
          true
        );
        return;
      }

      if (cameraStream) {
        return;
      }

      if (cameraStartBtn) {
        cameraStartBtn.disabled = true;
      }
      setStatusText(cameraStatus, cameraOpeningMessage, false);

      requestCameraStream(0, null)
        .then(function (stream) {
          cameraStream = stream;
          return attachStreamToVideo(stream).then(function () {
            if (cameraStartBtn) {
              cameraStartBtn.disabled = true;
            }
            if (cameraStopBtn) {
              cameraStopBtn.disabled = false;
            }

            syncOverlayCanvas();
            setStatusText(cameraStatus, message("msgCameraScanning", "Scanning from camera..."), false);

            scanInterval = setInterval(
              canUseClientDecoder ? runDetectFromVideo : runDetectFromVideoServer,
              canUseClientDecoder ? 700 : 1400
            );
          });
        })
        .catch(function (error) {
          stopCamera(false);
          setStatusText(
            cameraStatus,
            cameraAccessMessage(error),
            true
          );
        });
    };

    var detectFromImageFile = function (file) {
      return new Promise(function (resolve) {
        if (!canUseClientDecoder || typeof window.createImageBitmap !== "function") {
          resolve({ state: "none", token: "", raw: "", overlay: null });
          return;
        }

        window
          .createImageBitmap(file)
          .then(function (bitmap) {
            decodeClientSource(
              bitmap,
              bitmap.width || 0,
              bitmap.height || 0
            ).then(function (decoded) {
              if (typeof bitmap.close === "function") {
                bitmap.close();
              }
              resolve(decoded);
            });
          })
          .catch(function () {
            resolve({ state: "none", token: "", raw: "", overlay: null });
          });
      });
    };

    if (cameraStartBtn) {
      cameraStartBtn.addEventListener("click", startCamera);
      cameraStartBtn.disabled = !canUseCamera;
    }

    if (cameraStopBtn) {
      cameraStopBtn.addEventListener("click", function () {
        stopCamera(true);
      });
      cameraStopBtn.disabled = true;
    }

    if (historyClearBtn) {
      historyClearBtn.addEventListener("click", function () {
        clearHistory();
        showToast(message("msgHistoryEmpty", "No scanned item yet."), "success");
      });
    }

    if (uploadForm) {
      uploadForm.addEventListener("submit", function (event) {
        event.preventDefault();

        var file = uploadInput && uploadInput.files ? uploadInput.files[0] : null;
        if (!file) {
          setStatusText(
            uploadStatus,
            message("msgUploadServerFailed", "Please choose a file first."),
            true
          );
          showToast(message("msgToastSystem", "Cannot process at this time. Please retry."), "error");
          return;
        }

        setStatusText(
          uploadStatus,
          message("msgUploadProcessing", "Processing uploaded file..."),
          false
        );

        var isImageFile = /^image\//i.test(file.type || "");
        var tryClient = isImageFile;

        detectFromImageFile(file).then(function (decoded) {
          if (decoded.state === "valid") {
            showResult(
              decoded.token,
              message(
                "msgResultUploadClient",
                "Token detected from image on browser."
              ),
              labelUnknown,
              {
                raw: decoded.raw || decoded.token,
              }
            );
            setStatusText(
              uploadStatus,
              message(
                "msgResultUploadClient",
                "Token detected from image on browser."
              ),
              false
            );
            return;
          }

          if (decoded.state === "invalid") {
            setStatusText(
              uploadStatus,
              message(
                "msgTokenInvalid",
                "QR detected but token is invalid. Please check again."
              ),
              true
            );
            showResult(
              "",
              message("msgResultUploadClient", "Token detected from image on browser."),
              labelUnknown,
              {
                status: "invalid",
                raw: decoded.raw || "invalid-token",
              }
            );
            return;
          }

          if (tryClient) {
            setStatusText(
              uploadStatus,
              message(
                "msgUploadClientFailed",
                "Client decoding failed. Switching to server fallback."
              ),
              false
            );
          }

          var formData = new FormData(uploadForm);

          requestServerDecode(formData)
            .then(function (result) {
              if (!result.ok || !result.payload.success) {
                var isTokenInvalid =
                  result.payload &&
                  result.payload.qr_detected === true &&
                  result.payload.token_valid === false;

                setStatusText(
                  uploadStatus,
                  isTokenInvalid
                    ? result.payload.message ||
                        message(
                          "msgTokenInvalid",
                          "QR detected but token is invalid. Please check again."
                        )
                    : result.payload.message ||
                        message(
                          "msgUploadServerFailed",
                          "Cannot decode token from uploaded file."
                        ),
                  true
                );
                if (isTokenInvalid) {
                  showResult(
                    "",
                    message("msgResultUploadServer", "Token detected from server upload fallback."),
                    labelUnknown,
                    {
                      status: "invalid",
                      raw: result.payload.message || "invalid-token",
                    }
                  );
                } else {
                  showToast(message("msgToastSystem", "Cannot process at this time. Please retry."), "error");
                }
                return;
              }

              showResult(
                result.payload.token,
                message(
                  "msgResultUploadServer",
                  "Token detected from server upload fallback."
                ),
                result.payload.entity_label || labelUnknown,
                {
                  raw: result.payload.token,
                  traceUrl: result.payload.trace_url || buildTraceUrl(result.payload.token),
                }
              );
              setStatusText(
                uploadStatus,
                result.payload.message ||
                  message(
                    "msgResultUploadServer",
                    "Token detected from server upload fallback."
                  ),
                false
              );
            })
            .catch(function () {
              setStatusText(
                uploadStatus,
                message(
                  "msgUploadServerFailed",
                  "Cannot decode token from uploaded file."
                ),
                true
              );
              showToast(message("msgToastSystem", "Cannot process at this time. Please retry."), "error");
            });
        });
      });
    }

    if (manualForm) {
      manualForm.addEventListener("submit", function (event) {
        event.preventDefault();

        var rawInput = manualInput ? manualInput.value : "";
        var token = extractToken(rawInput);
        if (!token) {
          setStatusText(
            manualStatus,
            message("msgTokenInvalid", "Invalid token. Please check again."),
            true
          );
          showResult(
            "",
            message("msgTokenInvalid", "Invalid token. Please check again."),
            labelUnknown,
            {
              status: "invalid",
              raw: rawInput || "invalid-token",
            }
          );
          return;
        }

        showResult(
          token,
          message("msgResultManual", "Token accepted from manual input."),
          labelUnknown,
          {
            raw: token,
          }
        );
        setStatusText(
          manualStatus,
          message("msgResultManual", "Token accepted from manual input."),
          false
        );
      });
    }

    if (resetBtn) {
      resetBtn.addEventListener("click", function () {
        clearCurrentResult();
        clearStatuses();
        clearOverlay();
      });
    }

    if (video) {
      video.addEventListener("loadedmetadata", syncOverlayCanvas);
    }
    window.addEventListener("resize", syncOverlayCanvas);

    clearCurrentResult();
    renderHistory();

    if (!canUseCamera) {
      setStatusText(
        cameraStatus,
        !hasSecureContext
          ? cameraInsecureMessage
          : message(
              "msgCameraUnsupported",
              "Browser does not support camera scanning. Use file upload instead."
            ),
        true
      );
    } else {
      setStatusText(
        cameraStatus,
        message(
          "msgCameraReady",
          "Ready to scan. Click \"Start camera\" to begin."
        ),
        false
      );
    }
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


