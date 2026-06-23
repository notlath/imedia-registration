/**
 * IMedia Forms — Admin Builder (v3.0)
 * Drag-and-drop form builder with collapsible field settings, validation, and column-aware drag indicators.
 */
(function () {
  "use strict";

  /* ============================================
       FIELD REGISTRY
       ============================================ */
  const FIELD_REGISTRY = {
    text: {
      label: "Single Line Text",
      icon: "━",
      group: "standard",
      desc: "Allows users to submit a single line of text.",
    },
    textarea: {
      label: "Paragraph Text",
      icon: "☰",
      group: "standard",
      desc: "Allows users to submit multiple lines of text.",
    },
    select: {
      label: "Drop Down",
      icon: "▾",
      group: "standard",
      desc: "Allows users to select from a list of options.",
    },
    number: {
      label: "Number",
      icon: "#",
      group: "standard",
      desc: "Allows users to enter a numeric value.",
    },
    checkbox: {
      label: "Checkboxes",
      icon: "☑",
      group: "standard",
      desc: "Allows users to select multiple options.",
    },
    radio: {
      label: "Radio Buttons",
      icon: "◉",
      group: "standard",
      desc: "Allows users to select one option from a list.",
    },
    hidden: {
      label: "Hidden",
      icon: "◌",
      group: "standard",
      desc: "A hidden field not visible to users.",
    },
    section: {
      label: "Section Divider",
      icon: "—",
      group: "standard",
      desc: "A visual divider to organize your form.",
    },
    multiple_choice: {
      label: "Multi Choice",
      icon: "☷",
      group: "standard",
      desc: "Allows users to select multiple choices.",
    },
    name: {
      label: "Name",
      icon: "Aa",
      group: "advanced",
      desc: "Collects first and last name.",
    },
    email: {
      label: "Email",
      icon: "@",
      group: "advanced",
      desc: "Collects an email address with optional confirmation.",
    },
    date: {
      label: "Date",
      icon: "◫",
      group: "advanced",
      desc: "Allows users to select a date.",
    },
    time: {
      label: "Time",
      icon: "◷",
      group: "advanced",
      desc: "Allows users to select a time.",
    },
    phone: {
      label: "Phone",
      icon: "☏",
      group: "advanced",
      desc: "Collects a phone number.",
    },
    address: {
      label: "Address",
      icon: "⌂",
      group: "advanced",
      desc: "Collects a full mailing address.",
    },
    file: {
      label: "File Upload",
      icon: "⇪",
      group: "advanced",
      desc: "Allows users to upload a file.",
    },
    multiselect: {
      label: "Multi Select",
      icon: "≡",
      group: "advanced",
      desc: "Allows users to select multiple options from a dropdown.",
    },
  };

  /* ============================================
       STATE
       ============================================ */
  let fields = [];
  let selectedId = null;
  let deleteTargetId = null;

  // Track which settings sections are collapsed
  const collapsedSections = {};

  const canvas = document.getElementById("imf-canvas");
  const canvasEmpty = document.getElementById("imf-canvas-empty");
  const dataInput = document.getElementById("imf_form_data_input");
  const deleteModal = document.getElementById("imf-delete-modal");
  const previewModal = document.getElementById("imf-preview-modal");

  if (!canvas || !dataInput) return;

  /* ============================================
       INIT — LOAD SAVED FIELDS
       ============================================ */
  try {
    const raw = dataInput.value.trim();
    if (raw && raw !== "[]") fields = JSON.parse(raw);
  } catch (e) {
    console.error("Failed to parse form data", e);
  }

  /* ============================================
       HELPERS
       ============================================ */
  function uid() {
    return (
      "f" + Date.now().toString(36) + Math.random().toString(36).substr(2, 5)
    );
  }

  function escHtml(str) {
    const d = document.createElement("div");
    d.appendChild(document.createTextNode(str || ""));
    return d.innerHTML;
  }

  function serializeData() {
    dataInput.value = JSON.stringify(fields);
  }

  function updateEmptyState() {
    if (canvasEmpty) {
      canvasEmpty.style.display = fields.length ? "none" : "flex";
    }
  }

  /* ============================================
       ADD FIELD
       ============================================ */
  function addField(type, atIndex) {
    const reg = FIELD_REGISTRY[type];
    if (!reg) return;

    const id = uid();
    const field = {
      id: id,
      type: type,
      label: reg.label,
      name: type + "_" + id.substr(1, 6),
      required: false,
      width: "100",
      placeholder: "",
      options: [
        "select",
        "radio",
        "checkbox",
        "multiple_choice",
        "multiselect",
      ].includes(type)
        ? "First Choice\nSecond Choice\nThird Choice"
        : "",
      confirm_email: false,
      default_value: "",
      accepted_formats: "",
      custom_class: "",
      date_input_type: "date_picker",
      rows: type === "textarea" ? 3 : "",
      validation: { min_length: "", max_length: "", custom_error: "" },
    };

    if (
      typeof atIndex === "number" &&
      atIndex >= 0 &&
      atIndex <= fields.length
    ) {
      fields.splice(atIndex, 0, field);
    } else {
      fields.push(field);
    }
    renderCanvas();
    selectField(id);
  }

  /* ============================================
       RENDER CANVAS — Flex-wrap row layout
       ============================================ */
  function renderCanvas() {
    canvas.innerHTML = "";
    updateEmptyState();

    fields.forEach((field) => {
      const card = document.createElement("div");
      const widthClass =
        field.width && field.width !== "100" ? " imf-card-w" + field.width : "";
      card.className =
        "imf-canvas-card" +
        widthClass +
        (field.id === selectedId ? " selected" : "");
      card.dataset.id = field.id;

      const reg = FIELD_REGISTRY[field.type] || {};
      const reqBadge = field.required
        ? '<span class="imf-field-req">*</span>'
        : "";
      const widthTag =
        field.width !== "100"
          ? `<span class="imf-field-width-tag">${field.width}%</span>`
          : "";

      let preview = buildFieldPreview(field);

      card.innerHTML = `
                <div class="imf-card-header">
                    <span class="imf-card-icon">${reg.icon || ""}</span>
                    <span class="imf-card-label">${escHtml(field.label)} ${reqBadge} ${widthTag}</span>
                    <div class="imf-card-actions">
                        <button type="button" class="imf-card-btn imf-card-delete" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg></button>
                    </div>
                </div>
                <div class="imf-card-body">${preview}</div>
            `;

      card.addEventListener("click", (e) => {
        if (e.target.closest(".imf-card-delete")) {
          deleteTargetId = field.id;
          deleteModal.querySelector(".imf-delete-field-name").textContent =
            field.label;
          deleteModal.style.display = "flex";
          return;
        }
        selectField(field.id);
      });

      canvas.appendChild(card);
    });

    serializeData();
  }

  /* ============================================
       FIELD PREVIEW (canvas card body)
       ============================================ */
  function buildFieldPreview(field) {
    const t = field.type;
    const ph = escHtml(field.placeholder || "");

    switch (t) {
      case "text":
      case "phone":
        return `<input type="text" placeholder="${ph || "Enter text..."}" disabled />`;
      case "textarea":
        return `<textarea rows="${field.rows || 2}" placeholder="${ph || "Enter text..."}" disabled></textarea>`;
      case "number":
        return `<input type="number" placeholder="${ph || "0"}" disabled />`;
      case "email":
        if (field.confirm_email) {
          return `<div class="imf-preview-row"><div class="imf-preview-half"><input type="text" placeholder="Enter Email" disabled /><small>Enter Email</small></div><div class="imf-preview-half"><input type="text" placeholder="Confirm Email" disabled /><small>Confirm Email</small></div></div>`;
        }
        return `<input type="email" placeholder="${ph || "email@example.com"}" disabled />`;
      case "date": {
        const dit = field.date_input_type || "date_picker";
        if (dit === "date_field") {
          return `<div class="imf-preview-date-fields">
                        <div class="imf-preview-date-part"><input type="text" placeholder="MM" maxlength="2" disabled /><small>Month</small></div>
                        <span class="imf-preview-date-sep">/</span>
                        <div class="imf-preview-date-part"><input type="text" placeholder="DD" maxlength="2" disabled /><small>Day</small></div>
                        <span class="imf-preview-date-sep">/</span>
                        <div class="imf-preview-date-part imf-preview-date-year"><input type="text" placeholder="YYYY" maxlength="4" disabled /><small>Year</small></div>
                    </div>`;
        }
        if (dit === "date_dropdown") {
          return `<div class="imf-preview-date-dropdowns">
                        <div class="imf-preview-date-dd"><div class="imf-preview-select-wrap"><span class="imf-preview-select-text">Month</span><span class="imf-preview-select-arrow">▾</span></div><small>Month</small></div>
                        <div class="imf-preview-date-dd"><div class="imf-preview-select-wrap"><span class="imf-preview-select-text">Day</span><span class="imf-preview-select-arrow">▾</span></div><small>Day</small></div>
                        <div class="imf-preview-date-dd"><div class="imf-preview-select-wrap"><span class="imf-preview-select-text">Year</span><span class="imf-preview-select-arrow">▾</span></div><small>Year</small></div>
                    </div>`;
        }
        // date_picker (default)
        return '<div class="imf-preview-date-picker"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span class="imf-preview-date-text">mm/dd/yyyy</span></div>';
      }
      case "time":
        return '<div class="imf-preview-date-wrap"><span class="imf-preview-date-icon">⏰</span><span class="imf-preview-date-text">--:-- --</span></div>';
      case "name":
        return '<div class="imf-preview-row"><div class="imf-preview-half"><input type="text" placeholder="First" disabled /><small>First</small></div><div class="imf-preview-half"><input type="text" placeholder="Last" disabled /><small>Last</small></div></div>';
      case "address":
        return '<div class="imf-preview-address"><input type="text" placeholder="Street Address" disabled /><div class="imf-preview-row"><div class="imf-preview-half"><input type="text" placeholder="City" disabled /><small>City</small></div><div class="imf-preview-half"><input type="text" placeholder="ZIP" disabled /><small>ZIP / Postal Code</small></div></div></div>';
      case "select":
        return `<div class="imf-preview-select-wrap"><span class="imf-preview-select-text">${escHtml(field.placeholder || "Please select")}</span><span class="imf-preview-select-arrow">▾</span></div>`;
      case "multiselect":
        return `<div class="imf-preview-select-wrap imf-preview-multi"><span class="imf-preview-select-text">${escHtml(field.placeholder || "Select options...")}</span><span class="imf-preview-select-arrow">▾</span></div>`;
      case "checkbox":
      case "radio":
      case "multiple_choice": {
        const inputType = t === "checkbox" ? "checkbox" : "radio";
        const opts = (field.options || "")
          .split("\n")
          .filter((o) => o.trim() && !o.trim().startsWith("@"))
          .slice(0, 4);
        let html = '<div class="imf-preview-choices">';
        opts.forEach((o) => {
          if (o.trim().startsWith("#")) {
            html += `<div class="imf-preview-choice-group">${escHtml(o.substr(1).trim())}</div>`;
          } else {
            html += `<label class="imf-preview-choice"><span class="imf-preview-${inputType}"></span> ${escHtml(o.trim())}</label>`;
          }
        });
        if (
          (field.options || "").split("\n").filter((o) => o.trim()).length > 4
        ) {
          html += '<span class="imf-preview-more">…more options</span>';
        }
        html += "</div>";
        return html;
      }
      case "hidden":
        return '<div class="imf-preview-hidden">Hidden field — not visible to users</div>';
      case "section":
        return (
          `<div class="imf-preview-section-line"></div>` +
          (field.default_value
            ? `<div class="imf-preview-section-desc">${escHtml(field.default_value)}</div>`
            : "")
        );
      case "file":
        return '<div class="imf-preview-file"><span class="imf-preview-file-icon">📎</span><span>Choose file...</span></div>';
      default:
        return '<div style="color:#94a3b8;font-size:13px;">Unknown field</div>';
    }
  }

  /* ============================================
       SELECT FIELD & SETTINGS
       ============================================ */
  function selectField(id) {
    selectedId = id;
    renderCanvas();
    switchTab("settings");
    renderFieldSettings();
  }

  /* ============================================
       COLLAPSIBLE SECTION BUILDER
       ============================================ */
  function buildSection(key, title, contentHtml) {
    const isCollapsed = collapsedSections[key] === true;
    return `
            <div class="imf-settings-section${isCollapsed ? " collapsed" : ""}" data-section="${escHtml(key)}">
                <div class="imf-settings-section-header">
                    <span>${escHtml(title)}</span>
                    <svg class="imf-section-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="imf-settings-section-body">
                    ${contentHtml}
                </div>
            </div>
        `;
  }

  /* ============================================
       RENDER FIELD SETTINGS — Collapsible Sections
       ============================================ */
  function renderFieldSettings() {
    const panel = document.getElementById("imf-settings-form");
    const empty = document.getElementById("imf-settings-empty");

    const field = fields.find((f) => f.id === selectedId);
    if (!field) {
      panel.style.display = "none";
      empty.style.display = "block";
      return;
    }
    panel.style.display = "block";
    empty.style.display = "none";

    const t = field.type;
    const reg = FIELD_REGISTRY[t] || {};
    const fieldIndex = fields.indexOf(field) + 1;

    let html = "";

    // ── Field Info Header (always visible, not collapsible) ──
    html += `
            <div class="imf-field-settings-header">
                <div class="imf-fsh-icon">${reg.icon || "?"}</div>
                <div class="imf-fsh-info">
                    <div class="imf-fsh-title">${escHtml(reg.label)}<span class="imf-fsh-id">ID: ${fieldIndex}</span></div>
                    <div class="imf-fsh-desc">${escHtml(reg.desc || "")}</div>
                </div>
            </div>
        `;

    // ── GENERAL section ──
    let generalHtml = "";

    // Field Label
    generalHtml += `<div class="imf-setting-group"><label>Field Label</label><input type="text" id="imf-set-label" class="imf-setting-input" value="${escHtml(field.label)}" /></div>`;

    // Field Name
    if (t !== "section") {
      generalHtml += `<div class="imf-setting-group"><label>Field Name</label><input type="text" id="imf-set-name" class="imf-setting-input" value="${escHtml(field.name)}" /></div>`;
    }

    // Required
    if (!["section", "hidden"].includes(t)) {
      generalHtml += `<div class="imf-setting-group"><label class="imf-checkbox-label"><input type="checkbox" id="imf-set-required" ${field.required ? "checked" : ""} /> Required Field</label></div>`;
    }

    // Date Input Type (for date fields only)
    if (t === "date") {
      const dit = field.date_input_type || "date_picker";
      generalHtml += `<div class="imf-setting-group"><label>Date Input Type</label><select id="imf-set-date-input-type" class="imf-setting-input">
                <option value="date_picker" ${dit === "date_picker" ? "selected" : ""}>Date Picker</option>
                <option value="date_field" ${dit === "date_field" ? "selected" : ""}>Date Field</option>
                <option value="date_dropdown" ${dit === "date_dropdown" ? "selected" : ""}>Date Dropdown</option>
            </select><div class="imf-setting-desc">Choose how the date input is displayed.</div></div>`;
    }

    // Placeholder
    if (
      [
        "text",
        "textarea",
        "number",
        "email",
        "phone",
        "select",
        "multiselect",
        "date",
        "time",
      ].includes(t)
    ) {
      generalHtml += `<div class="imf-setting-group"><label>Placeholder</label><input type="text" id="imf-set-placeholder" class="imf-setting-input" value="${escHtml(field.placeholder || "")}" /><div class="imf-setting-desc">Text shown when the field is empty.</div></div>`;
    }

    // Options (choices)
    if (
      [
        "select",
        "radio",
        "checkbox",
        "multiple_choice",
        "multiselect",
      ].includes(t)
    ) {
      generalHtml += `<div class="imf-setting-group"><label>Choices</label><textarea id="imf-set-options" class="imf-setting-input" rows="5">${escHtml(field.options || "")}</textarea><div class="imf-setting-desc">One choice per line. Use # for group headers. Use @slug for category groups.</div></div>`;
    }

    // Confirm Email
    if (t === "email") {
      generalHtml += `<div class="imf-setting-group"><label class="imf-checkbox-label"><input type="checkbox" id="imf-set-confirm-email" ${field.confirm_email ? "checked" : ""} /> Enable Email Confirmation</label></div>`;
    }

    // Default Value (for section = description, hidden = value)
    if (t === "section") {
      generalHtml += `<div class="imf-setting-group"><label>Description</label><textarea id="imf-set-default" class="imf-setting-input" rows="2">${escHtml(field.default_value || "")}</textarea></div>`;
    }
    if (t === "hidden") {
      generalHtml += `<div class="imf-setting-group"><label>Default Value</label><input type="text" id="imf-set-default" class="imf-setting-input" value="${escHtml(field.default_value || "")}" /></div>`;
    }

    // File accepted formats
    if (t === "file") {
      generalHtml += `<div class="imf-setting-group"><label>Accepted File Types</label><input type="text" id="imf-set-formats" class="imf-setting-input" value="${escHtml(field.accepted_formats || "")}" /><div class="imf-setting-desc">e.g., .pdf,.jpg,.png</div></div>`;
    }

    html += buildSection("general", "General", generalHtml);

    // ── APPEARANCE section ──
    let appearanceHtml = "";

    // Width
    appearanceHtml += `<div class="imf-setting-group"><label>Field Layout Width</label><select id="imf-set-width" class="imf-setting-input">
            <option value="100" ${field.width === "100" ? "selected" : ""}>100% (Full Width)</option>
            <option value="50" ${field.width === "50" ? "selected" : ""}>50% (Half)</option>
            <option value="33" ${field.width === "33" ? "selected" : ""}>33% (Third)</option>
            <option value="25" ${field.width === "25" ? "selected" : ""}>25% (Quarter)</option>
        </select><div class="imf-setting-desc">Set columns by adjusting field width.</div></div>`;

    // Height (Span) for textarea
    if (t === "textarea") {
      appearanceHtml += `<div class="imf-setting-group"><label>Height (Span)</label><select id="imf-set-rows" class="imf-setting-input">
                <option value="2" ${field.rows == "2" ? "selected" : ""}>Small (2 Rows)</option>
                <option value="3" ${field.rows == "3" || !field.rows ? "selected" : ""}>Normal (3 Rows)</option>
                <option value="5" ${field.rows == "5" ? "selected" : ""}>Medium (5 Rows)</option>
                <option value="8" ${field.rows == "8" ? "selected" : ""}>Large (8 Rows)</option>
                <option value="12" ${field.rows == "12" ? "selected" : ""}>Extra Large (12 Rows)</option>
            </select><div class="imf-setting-desc">Adjust the vertical size of the paragraph box.</div></div>`;
    }

    appearanceHtml += `<div class="imf-setting-group"><label>Custom CSS Class</label><input type="text" id="imf-set-custom-class" class="imf-setting-input" value="${escHtml(field.custom_class || "")}" placeholder="e.g. my-custom-field" /></div>`;

    html += buildSection("appearance", "Appearance", appearanceHtml);

    // ── ADVANCED section ──
    if (!["section", "hidden", "file"].includes(t)) {
      let advancedHtml = "";
      const v = field.validation || {};

      if (["text", "textarea", "phone", "email"].includes(t)) {
        advancedHtml += `<div class="imf-setting-group"><label>Min Length</label><input type="number" id="imf-set-vmin" class="imf-setting-input" value="${v.min_length || ""}" min="0" /></div>`;
        advancedHtml += `<div class="imf-setting-group"><label>Max Length</label><input type="number" id="imf-set-vmax" class="imf-setting-input" value="${v.max_length || ""}" min="0" /></div>`;
      }

      advancedHtml += `<div class="imf-setting-group"><label>Custom Error Message</label><input type="text" id="imf-set-verror" class="imf-setting-input" value="${escHtml(v.custom_error || "")}" placeholder="e.g., Please enter a valid value" /></div>`;

      html += buildSection("advanced", "Advanced", advancedHtml);
    }

    panel.innerHTML = html;

    // Bind section toggle listeners
    panel.querySelectorAll(".imf-settings-section-header").forEach((header) => {
      header.addEventListener("click", () => {
        const section = header.closest(".imf-settings-section");
        const key = section.dataset.section;
        section.classList.toggle("collapsed");
        collapsedSections[key] = section.classList.contains("collapsed");
      });
    });

    bindFieldSettings(field);
  }

  /* ============================================
       BIND FIELD SETTINGS LISTENERS
       ============================================ */
  function bindFieldSettings(field) {
    const b = (id, key) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener("input", () => {
        field[key] = el.value;
        updateField();
      });
    };
    const bc = (id, key) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener("change", () => {
        field[key] = el.checked;
        updateField(true);
      });
    };

    b("imf-set-label", "label");
    b("imf-set-name", "name");
    b("imf-set-placeholder", "placeholder");
    b("imf-set-rows", "rows");

    const widthEl = document.getElementById("imf-set-width");
    if (widthEl)
      widthEl.addEventListener("change", () => {
        field.width = widthEl.value;
        updateField();
      });

    b("imf-set-custom-class", "custom_class");

    // Date input type
    const ditEl = document.getElementById("imf-set-date-input-type");
    if (ditEl)
      ditEl.addEventListener("change", () => {
        field.date_input_type = ditEl.value;
        updateField(true);
      });

    bc("imf-set-required", "required");
    bc("imf-set-confirm-email", "confirm_email");

    const optEl = document.getElementById("imf-set-options");
    if (optEl)
      optEl.addEventListener("input", () => {
        field.options = optEl.value;
        updateField();
      });

    const defEl = document.getElementById("imf-set-default");
    if (defEl)
      defEl.addEventListener("input", () => {
        field.default_value = defEl.value;
        updateField();
      });

    const fmtEl = document.getElementById("imf-set-formats");
    if (fmtEl)
      fmtEl.addEventListener("input", () => {
        field.accepted_formats = fmtEl.value;
        updateField();
      });

    // Validation
    const vb = (id, key) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener("input", () => {
        if (!field.validation) field.validation = {};
        field.validation[key] = el.value;
        serializeData();
      });
    };
    vb("imf-set-vmin", "min_length");
    vb("imf-set-vmax", "max_length");
    vb("imf-set-verror", "custom_error");
  }

  function updateField(reRenderSettings) {
    renderCanvas();
    if (reRenderSettings) renderFieldSettings();
  }

  /* ============================================
       TABS
       ============================================ */
  function switchTab(tab) {
    document
      .querySelectorAll(".imf-tab")
      .forEach((t) => t.classList.toggle("active", t.dataset.tab === tab));
    document
      .getElementById("imf-tab-add")
      .classList.toggle("active", tab === "add");
    document
      .getElementById("imf-tab-settings")
      .classList.toggle("active", tab === "settings");
    document
      .getElementById("imf-tab-emails")
      .classList.toggle("active", tab === "emails");
    
    // Fix for TinyMCE rendering in hidden tabs
    if (tab === "emails" && typeof tinymce !== 'undefined') {
      setTimeout(() => {
        ['imf_admin_body_editor', 'imf_user_body_editor'].forEach(id => {
          const editor = tinymce.get(id);
          if (editor) {
            editor.show();
            editor.render();
          }
        });
      }, 50);
    }

    document
      .getElementById("imf-tab-form-settings")
      .classList.toggle("active", tab === "form-settings");
  }

  document.querySelectorAll(".imf-tab").forEach((tab) => {
    tab.addEventListener("click", () => switchTab(tab.dataset.tab));
  });

  /* ============================================
       SIDEBAR — ADD FIELD BUTTONS (click only)
       ============================================ */
  document.querySelectorAll(".imf-add-btn").forEach((btn) => {
    btn.addEventListener("click", () => addField(btn.dataset.type));
  });

  /* ============================================
       COLLAPSIBLE FIELD GROUPS
       ============================================ */
  document.querySelectorAll(".imf-field-group h4").forEach((h) => {
    h.addEventListener("click", () => {
      const grp = h.closest(".imf-field-group");
      grp.classList.toggle("collapsed");
    });
  });

  /* ============================================
       DROP INDICATOR ELEMENT
       ============================================ */
  const dropIndicator = document.createElement("div");
  dropIndicator.className = "imf-drop-indicator";
  dropIndicator.style.display = "none";
  canvas.parentElement.appendChild(dropIndicator);

  function showDropIndicator(x, y, width, height, orientation) {
    dropIndicator.style.display = "block";
    if (orientation === "vertical") {
      dropIndicator.style.left = x + "px";
      dropIndicator.style.top = y + "px";
      dropIndicator.style.width = "3px";
      dropIndicator.style.height = height + "px";
      dropIndicator.classList.add("vertical");
      dropIndicator.classList.remove("horizontal");
    } else {
      dropIndicator.style.left = x + "px";
      dropIndicator.style.top = y + "px";
      dropIndicator.style.width = width + "px";
      dropIndicator.style.height = "3px";
      dropIndicator.classList.add("horizontal");
      dropIndicator.classList.remove("vertical");
    }
  }

  function hideDropIndicator() {
    dropIndicator.style.display = "none";
    // Remove all drop-side classes
    canvas.querySelectorAll(".imf-drop-left, .imf-drop-right").forEach((el) => {
      el.classList.remove("imf-drop-left", "imf-drop-right");
    });
  }

  /* ============================================
       SORTABLE — Canvas with column-aware drop indicators
       ============================================ */
  // Sidebar buttons: drag to clone into canvas
  document.querySelectorAll(".imf-field-buttons").forEach((container) => {
    new Sortable(container, {
      group: { name: "imf-fields", pull: "clone", put: false },
      sort: false,
      animation: 150,
    });
  });

  // Canvas: accepts drops from sidebar + reorder within
  new Sortable(canvas, {
    group: { name: "imf-fields", pull: false, put: true },
    animation: 200,
    ghostClass: "imf-drag-ghost",
    chosenClass: "imf-drag-chosen",
    handle: ".imf-card-header",
    onAdd: function (e) {
      // Sidebar button was dragged into canvas
      const type = e.item.dataset.type;
      const insertIndex = e.newIndex;
      e.item.remove(); // Remove the cloned sidebar button from DOM
      if (type) {
        addField(type, insertIndex);
      }
      hideDropIndicator();
    },
    onEnd: function (e) {
      // Reorder existing fields within canvas
      if (e.from === canvas && e.to === canvas) {
        const movedId = e.item.dataset.id;
        const oldIdx = fields.findIndex((f) => f.id === movedId);
        if (oldIdx === -1) return;
        const [moved] = fields.splice(oldIdx, 1);
        fields.splice(e.newIndex, 0, moved);
        renderCanvas();
      }
      hideDropIndicator();
    },
    onMove: function (evt) {
      const related = evt.related;
      if (!related || !related.classList.contains("imf-canvas-card")) return;

      // Get mouse position relative to the related element
      const rect = related.getBoundingClientRect();
      const canvasRect = canvas.getBoundingClientRect();
      const mouseX = evt.originalEvent
        ? evt.originalEvent.clientX
        : evt.dragged
          ? evt.dragged.getBoundingClientRect().left
          : rect.left;
      const midX = rect.left + rect.width / 2;

      // Clear previous indicators
      canvas.querySelectorAll(".imf-drop-left, .imf-drop-right").forEach((el) => {
        el.classList.remove("imf-drop-left", "imf-drop-right");
      });

      // Determine left or right
      if (mouseX < midX) {
        related.classList.add("imf-drop-left");
        // Show vertical indicator on the left side
        showDropIndicator(
          rect.left - canvasRect.left - 2,
          rect.top - canvasRect.top,
          3,
          rect.height,
          "vertical",
        );
      } else {
        related.classList.add("imf-drop-right");
        // Show vertical indicator on the right side
        showDropIndicator(
          rect.right - canvasRect.left - 1,
          rect.top - canvasRect.top,
          3,
          rect.height,
          "vertical",
        );
      }
    },
    onUnchoose: function () {
      hideDropIndicator();
    },
  });

  // Global dragend cleanup
  document.addEventListener("dragend", hideDropIndicator);

  /* ============================================
       DELETE MODAL
       ============================================ */
  deleteModal.querySelector(".imf-btn-cancel").addEventListener("click", () => {
    deleteTargetId = null;
    deleteModal.style.display = "none";
  });

  deleteModal
    .querySelector(".imf-modal-overlay")
    .addEventListener("click", () => {
      deleteTargetId = null;
      deleteModal.style.display = "none";
    });

  deleteModal
    .querySelector(".imf-btn-confirm-delete")
    .addEventListener("click", () => {
      if (deleteTargetId) {
        fields = fields.filter((f) => f.id !== deleteTargetId);
        if (selectedId === deleteTargetId) {
          selectedId = null;
          const panel = document.getElementById("imf-settings-form");
          const empty = document.getElementById("imf-settings-empty");
          panel.style.display = "none";
          empty.style.display = "block";
        }
        deleteTargetId = null;
        renderCanvas();
      }
      deleteModal.style.display = "none";
    });

  /* ============================================
       PREVIEW MODAL
       ============================================ */
  document.getElementById("imf-btn-preview").addEventListener("click", () => {
    buildPreviewForm();
    previewModal.style.display = "flex";
  });

  previewModal
    .querySelector(".imf-modal-close")
    .addEventListener("click", () => {
      previewModal.style.display = "none";
    });
  previewModal
    .querySelector(".imf-modal-overlay")
    .addEventListener("click", () => {
      previewModal.style.display = "none";
    });

  function buildPreviewForm() {
    const container = document.getElementById("imf-live-preview-container");
    const titleEl = document.getElementById("imf-live-preview-title");
    const submitBtn = document.getElementById("imf-live-preview-submit-btn");
    const submitWrap = document.getElementById("imf-live-preview-submit-wrap");

    const formTitle = document.getElementById("title").value || "Untitled Form";
    titleEl.textContent = formTitle;

    // Apply appearance settings
    const titleColorInput = document.getElementById("imf-appear-title-color");
    const submitBgInput = document.getElementById("imf-appear-submit-bg");
    const submitTextInput = document.getElementById("imf-appear-submit-text");
    const submitWidthInput = document.getElementById("imf-appear-submit-width");
    const submitAlignInput = document.getElementById(
      "imf-appear-submit-alignment",
    );

    if (titleColorInput) titleEl.style.color = titleColorInput.value;
    if (submitBgInput) submitBtn.style.backgroundColor = submitBgInput.value;
    if (submitTextInput) submitBtn.style.color = submitTextInput.value;
    if (submitWidthInput)
      submitBtn.style.width =
        submitWidthInput.value === "full" ? "100%" : "auto";
    if (submitAlignInput) submitWrap.style.textAlign = submitAlignInput.value;

    container.innerHTML = "";
    fields.forEach((field) => {
      const wrap = document.createElement("div");
      wrap.className = "imf-live-field";
      wrap.style.width =
        field.width === "50"
          ? "calc(50% - 10px)"
          : field.width === "33"
            ? "calc(33.333% - 14px)"
            : field.width === "25"
              ? "calc(25% - 15px)"
              : "100%";

      let h = "";
      const t = field.type;
      const reqMark = field.required
        ? '<span style="color:#ef4444;"> *</span>'
        : "";
      const ph = escHtml(field.placeholder || "");

      if (t !== "hidden") {
        h += `<label style="display:block;font-size:14px;font-weight:600;color:#1e293b;margin-bottom:6px;">${escHtml(field.label)}${reqMark}</label>`;
      }

      switch (t) {
        case "text":
        case "phone":
          h += `<input type="text" placeholder="${ph || "Enter text..."}" />`;
          break;
        case "textarea":
          h += `<textarea rows="${field.rows || 3}" placeholder="${ph || ""}" style="resize:vertical;"></textarea>`;
          break;
        case "number":
          h += `<input type="number" placeholder="${ph || "0"}" />`;
          break;
        case "email":
          if (field.confirm_email) {
            h += `<div style="display:flex;gap:10px;"><div style="flex:1"><input type="email" placeholder="Enter Email" /><span style="font-size:12px;color:#94a3b8;margin-top:4px;display:block;">Enter Email</span></div><div style="flex:1"><input type="email" placeholder="Confirm Email" /><span style="font-size:12px;color:#94a3b8;margin-top:4px;display:block;">Confirm Email</span></div></div>`;
          } else {
            h += `<input type="email" placeholder="${ph || "email@example.com"}" />`;
          }
          break;
        case "date": {
          const pdit = field.date_input_type || "date_picker";
          if (pdit === "date_field") {
            h += `<div class="imf-live-date-fields">
                            <div class="imf-live-date-part"><input type="text" placeholder="MM" maxlength="2" /><span>Month</span></div>
                            <div class="imf-live-date-sep">/</div>
                            <div class="imf-live-date-part"><input type="text" placeholder="DD" maxlength="2" /><span>Day</span></div>
                            <div class="imf-live-date-sep">/</div>
                            <div class="imf-live-date-part imf-live-date-year"><input type="text" placeholder="YYYY" maxlength="4" /><span>Year</span></div>
                        </div>`;
          } else if (pdit === "date_dropdown") {
            h += `<div class="imf-live-date-dropdowns">
                            <div class="imf-live-date-dd"><select><option>Month</option></select><span>Month</span></div>
                            <div class="imf-live-date-dd"><select><option>Day</option></select><span>Day</span></div>
                            <div class="imf-live-date-dd"><select><option>Year</option></select><span>Year</span></div>
                        </div>`;
          } else {
            h += `<div class="imf-live-datepicker-wrap">
                            <input type="text" placeholder="${ph || "mm/dd/yyyy"}" readonly />
                            <svg class="imf-live-dp-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>`;
          }
          break;
        }
        case "time":
          h += `<input type="time" />`;
          break;
        case "name":
          h += `<div style="display:flex;gap:10px;"><div style="flex:1"><input type="text" placeholder="First" /><span style="font-size:12px;color:#94a3b8;display:block;margin-top:4px;">First</span></div><div style="flex:1"><input type="text" placeholder="Last" /><span style="font-size:12px;color:#94a3b8;display:block;margin-top:4px;">Last</span></div></div>`;
          break;
        case "address":
          h += `<div style="display:flex;flex-wrap:wrap;gap:10px;">
                        <div style="width:100%;"><input type="text" placeholder="Street Address" /><span style="font-size:12px;color:#94a3b8;display:block;margin-top:2px;">Street Address</span></div>
                        <div style="width:calc(50% - 5px);"><input type="text" placeholder="City" /><span style="font-size:12px;color:#94a3b8;display:block;margin-top:2px;">City</span></div>
                        <div style="width:calc(50% - 5px);"><input type="text" placeholder="ZIP" /><span style="font-size:12px;color:#94a3b8;display:block;margin-top:2px;">ZIP / Postal Code</span></div>
                    </div>`;
          break;
        case "select":
        case "multiselect": {
          const opts = (field.options || "")
            .split("\n")
            .filter((o) => o.trim());
          const multi = t === "multiselect" ? ' multiple size="4"' : "";
          h += `<select${multi}>`;
          if (t !== "multiselect")
            h += `<option>${escHtml(field.placeholder || "Please select")}</option>`;
          let inGroup = false;
          opts.forEach((o) => {
            const trimmed = o.trim();
            if (trimmed.startsWith("@")) return;
            if (trimmed.startsWith("#")) {
              if (inGroup) h += "</optgroup>";
              h += `<optgroup label="${escHtml(trimmed.substr(1))}">`;
              inGroup = true;
            } else {
              h += `<option>${escHtml(trimmed)}</option>`;
            }
          });
          if (inGroup) h += "</optgroup>";
          h += "</select>";
          break;
        }
        case "checkbox":
        case "radio":
        case "multiple_choice": {
          const inputType = t === "checkbox" ? "checkbox" : "radio";
          const radioName = "preview_" + field.id;
          const opts = (field.options || "")
            .split("\n")
            .filter((o) => o.trim());
          h +=
            '<div style="display:flex;flex-direction:column;gap:8px;margin-top:4px;">';
          opts.forEach((o) => {
            const trimmed = o.trim();
            if (trimmed.startsWith("@")) return;
            if (trimmed.startsWith("#")) {
              h += `<div style="font-weight:700;font-size:13px;color:#475569;border-bottom:1px solid #e2e8f0;padding-bottom:4px;margin-top:6px;">${escHtml(trimmed.substr(1))}</div>`;
            } else {
              const nameAttr =
                inputType === "radio" ? ` name="${radioName}"` : "";
              h += `<label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#334155;cursor:pointer;"><input type="${inputType}"${nameAttr} style="width:18px;height:18px;accent-color:#3b82f6;flex-shrink:0;" /> ${escHtml(trimmed)}</label>`;
            }
          });
          h += "</div>";
          break;
        }
        case "file":
          h += `<input type="file" />`;
          break;
        case "section":
          h += `<div style="border-bottom:2px solid #e2e8f0;padding-bottom:4px;">${field.default_value ? '<p style="font-size:13px;color:#64748b;margin:4px 0 0;">' + escHtml(field.default_value) + "</p>" : ""}</div>`;
          break;
        case "hidden":
          break;
      }

      wrap.innerHTML = h;
      container.appendChild(wrap);
    });

    // Make container flex wrap
    container.style.display = "flex";
    container.style.flexWrap = "wrap";
    container.style.gap = "0 20px";
  }

  /* ============================================
       SAVE FORM
       ============================================ */
  document.getElementById("imf-btn-save").addEventListener("click", () => {
    serializeData();
    const form = document.getElementById("post");
    if (form) form.submit();
  });

  /* ============================================
       SHORTCODE TAG COPY
       ============================================ */
  document.querySelectorAll(".imf-shortcode-tag").forEach((tag) => {
    tag.addEventListener("click", () => {
      navigator.clipboard.writeText(tag.textContent.trim()).then(() => {
        const orig = tag.textContent;
        tag.textContent = "Copied!";
        setTimeout(() => {
          tag.textContent = orig;
        }, 1500);
      });
    });
  });

  /* ============================================
       API SCHEMA PREVIEW
       ============================================ */
  function renderApiSchema() {
    const schemaContainer = document.getElementById("imf-api-schema");
    if (!schemaContainer) return;

    const relevantFields = fields.filter(
      (f) => f.type !== "section" && f.type !== "hidden",
    );
    if (!relevantFields.length) {
      schemaContainer.innerHTML =
        '<div style="color:#94a3b8;font-size:14px;text-align:center;padding:12px;">Add fields to see auto-generated API schema</div>';
      return;
    }

    // Express route
    let expressLines = relevantFields
      .map((f) => `    ${f.name}: req.body.${f.name},`)
      .join("\n");
    const expressCode = `router.post('/submit', async (req, res) => {\n  try {\n    const entry = new FormEntry({\n${expressLines}\n    });\n    await entry.save();\n    res.json({ success: true });\n  } catch (err) {\n    res.status(400).json({ error: err.message });\n  }\n});`;

    // Mongoose schema
    let schemaLines = relevantFields
      .map((f) => {
        let mongoType = "String";
        if (f.type === "number") mongoType = "Number";
        if (["checkbox", "multiselect"].includes(f.type))
          mongoType = "[String]";
        if (f.type === "date") mongoType = "Date";
        return `  ${f.name}: { type: ${mongoType}${f.required ? ", required: true" : ""} },`;
      })
      .join("\n");
    const mongooseCode = `const formEntrySchema = new mongoose.Schema({\n${schemaLines}\n}, { timestamps: true });`;

    // JSON payload
    let payloadObj = {};
    relevantFields.forEach((f) => {
      if (["checkbox", "multiselect"].includes(f.type))
        payloadObj[f.name] = ["value1"];
      else if (f.type === "number") payloadObj[f.name] = 0;
      else payloadObj[f.name] = "";
    });

    schemaContainer.innerHTML = "";
  }

  // Re-render schema when switching to form-settings tab
  document
    .querySelector('.imf-tab[data-tab="form-settings"]')
    .addEventListener("click", () => {
      setTimeout(renderApiSchema, 50);
    });

  /* ============================================
       RESIZER LOGIC
       ============================================ */
  const sidebarResizer = document.getElementById("imf-resizer");
  const sidebarArea = document.querySelector(".imf-sidebar-area");

  if (sidebarResizer && sidebarArea) {
    let isResizing = false;
    let initialX;
    let initialWidth;

    sidebarResizer.addEventListener("mousedown", function (e) {
      isResizing = true;
      initialX = e.clientX;
      initialWidth = sidebarArea.offsetWidth;
      sidebarResizer.classList.add("is-resizing");
      document.body.style.userSelect = "none";
      document.body.style.cursor = "col-resize";
    });

    document.addEventListener("mousemove", function (e) {
      if (!isResizing) return;
      // Sidebar is on the right, so moving left increases width
      const dx = initialX - e.clientX;
      const newWidth = initialWidth + dx;

      if (newWidth >= 300 && newWidth <= 800) {
        sidebarArea.style.width = newWidth + "px";
      }
    });

    document.addEventListener("mouseup", function () {
      if (isResizing) {
        isResizing = false;
        sidebarResizer.classList.remove("is-resizing");
        document.body.style.userSelect = "";
        document.body.style.cursor = "";

        // Force TinyMCE editors to repaint if they exist
        if (typeof tinymce !== "undefined") {
          tinymce.editors.forEach((editor) => {
            if (editor.theme && editor.theme.resizeTo) {
              editor.theme.resizeTo("100%", editor.getContainer().style.height);
            }
          });
        }
      }
    });
  }

  /* ============================================
       INITIAL RENDER
       ============================================ */
  renderCanvas();
})();
