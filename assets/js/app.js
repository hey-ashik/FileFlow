/**
 * FileFlow - Main Application JavaScript
 */

let isUploading = false;
let globalUploads = [];
let activeUploadCount = 0;
const MAX_CONCURRENT_UPLOADS = 5;

let isGlobalInitDone = false;

window.formatTextarea = function (textarea, type) {
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const selected = text.substring(start, end);
    let replacement = '';
    let cursorOffset = 0;

    if (type === 'bold') {
        replacement = `**${selected}**`;
        cursorOffset = selected ? replacement.length : 2;
    } else if (type === 'italic') {
        replacement = `*${selected}*`;
        cursorOffset = selected ? replacement.length : 1;
    } else if (type === 'underline') {
        replacement = `<u>${selected}</u>`;
        cursorOffset = selected ? replacement.length : 3;
    } else if (type === 'link') {
        const url = prompt('Enter link URL:');
        if (url) {
            replacement = `[${selected || 'link'}](${url})`;
            cursorOffset = replacement.length;
        } else {
            return;
        }
    }

    textarea.value = text.substring(0, start) + replacement + text.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + cursorOffset, start + cursorOffset);
};

window.customLinkPrompt = function (title, placeholderValue, callback, onRemove) {
    let modal = document.getElementById('custom-link-prompt-modal');
    if (modal) modal.remove();

    modal = document.createElement('div');
    modal.id = 'custom-link-prompt-modal';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(15, 23, 42, 0.45)';
    modal.style.backdropFilter = 'blur(4px)';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '99999';
    modal.style.opacity = '0';
    modal.style.transition = 'opacity 0.2s ease-out';

    const content = document.createElement('div');
    content.style.backgroundColor = '#ffffff';
    content.style.borderRadius = '16px';
    content.style.padding = '1.25rem';
    content.style.width = '90%';
    content.style.maxWidth = '400px';
    content.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
    content.style.transform = 'scale(0.95)';
    content.style.transition = 'transform 0.2s cubic-bezier(0.16, 1, 0.3, 1)';
    content.style.border = '1px solid #e2e8f0';

    const titleEl = document.createElement('h3');
    titleEl.textContent = title;
    titleEl.style.fontSize = '1.1rem';
    titleEl.style.fontWeight = '600';
    titleEl.style.color = '#0f172a';
    titleEl.style.margin = '0 0 1rem 0';
    titleEl.style.fontFamily = 'inherit';

    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = placeholderValue;
    input.style.width = '100%';
    input.style.border = '1px solid #cbd5e1';
    input.style.borderRadius = '10px';
    input.style.padding = '0.625rem 0.875rem';
    input.style.fontSize = '0.95rem';
    input.style.fontFamily = 'inherit';
    input.style.outline = 'none';
    input.style.transition = 'border-color 0.2s';
    input.style.marginBottom = '1.25rem';
    input.style.boxSizing = 'border-box';
    input.onfocus = () => input.style.borderColor = 'var(--green-600)';
    input.onblur = () => input.style.borderColor = '#cbd5e1';

    const actions = document.createElement('div');
    actions.style.display = 'flex';
    actions.style.gap = '6px';
    actions.style.justifyContent = onRemove ? 'space-between' : 'flex-end';
    actions.style.flexWrap = 'nowrap';

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.style.padding = '0.45rem 0.85rem';
    cancelBtn.style.borderRadius = '9999px';
    cancelBtn.style.background = '#f1f5f9';
    cancelBtn.style.color = '#475569';
    cancelBtn.style.border = '1px solid #e2e8f0';
    cancelBtn.style.fontWeight = '600';
    cancelBtn.style.fontSize = '0.85rem';
    cancelBtn.style.cursor = 'pointer';
    cancelBtn.style.transition = 'background 0.2s';
    cancelBtn.onmouseover = () => cancelBtn.style.background = '#e2e8f0';
    cancelBtn.onmouseout = () => cancelBtn.style.background = '#f1f5f9';
    actions.appendChild(cancelBtn);

    let removeBtn = null;
    if (onRemove) {
        removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove';
        removeBtn.style.padding = '0.45rem 0.85rem';
        removeBtn.style.borderRadius = '9999px';
        removeBtn.style.background = '#fef2f2';
        removeBtn.style.color = '#ef4444';
        removeBtn.style.border = '1px solid #fee2e2';
        removeBtn.style.fontWeight = '600';
        removeBtn.style.fontSize = '0.85rem';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.transition = 'background 0.2s';
        removeBtn.onmouseover = () => removeBtn.style.background = '#fde2e2';
        removeBtn.onmouseout = () => removeBtn.style.background = '#fef2f2';
        actions.appendChild(removeBtn);
    }

    const okBtn = document.createElement('button');
    okBtn.type = 'button';
    okBtn.textContent = 'Add';
    okBtn.style.padding = '0.45rem 0.85rem';
    okBtn.style.borderRadius = '9999px';
    okBtn.style.background = 'var(--green-600)';
    okBtn.style.color = '#ffffff';
    okBtn.style.border = 'none';
    okBtn.style.fontWeight = '600';
    okBtn.style.fontSize = '0.85rem';
    okBtn.style.cursor = 'pointer';
    okBtn.style.transition = 'opacity 0.2s';
    okBtn.onmouseover = () => okBtn.style.opacity = '0.9';
    okBtn.onmouseout = () => okBtn.style.opacity = '1';
    actions.appendChild(okBtn);

    content.appendChild(titleEl);
    content.appendChild(input);
    content.appendChild(actions);
    modal.appendChild(content);
    document.body.appendChild(modal);

    setTimeout(() => {
        modal.style.opacity = '1';
        content.style.transform = 'scale(1)';
    }, 10);

    const close = () => {
        modal.style.opacity = '0';
        content.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.remove();
        }, 200);
    };

    cancelBtn.onclick = close;

    if (removeBtn) {
        removeBtn.onclick = () => {
            onRemove();
            close();
        };
    }

    okBtn.onclick = () => {
        const val = input.value.trim();
        if (val) {
            callback(val);
        }
        close();
    };

    input.onkeydown = (e) => {
        if (e.key === 'Enter') {
            okBtn.click();
        } else if (e.key === 'Escape') {
            cancelBtn.click();
        }
    };

    input.focus();
};

window.initializeRichTextEditor = function (container, textareaId, placeholder, initialValue, styleOptions = {}) {
    // 1. Create hidden textarea
    const hiddenTextarea = document.createElement('textarea');
    hiddenTextarea.id = textareaId;
    hiddenTextarea.name = textareaId;
    hiddenTextarea.style.display = 'none';

    // 2. Create toolbar
    const toolbar = document.createElement('div');
    toolbar.style.display = 'flex';
    toolbar.style.gap = '0.25rem';
    toolbar.style.alignItems = 'center';
    toolbar.style.background = '#f8fafc';
    toolbar.style.border = '1px solid #cbd5e1';
    toolbar.style.borderBottom = 'none';
    toolbar.style.borderRadius = '8px 8px 0 0';
    toolbar.style.padding = '4px 8px';

    const boldBtn = document.createElement('button');
    boldBtn.type = 'button';
    boldBtn.textContent = 'B';
    boldBtn.style.fontWeight = 'bold';
    boldBtn.style.background = 'none';
    boldBtn.style.border = 'none';
    boldBtn.style.borderRadius = '4px';
    boldBtn.style.width = '28px';
    boldBtn.style.height = '28px';
    boldBtn.style.cursor = 'pointer';
    boldBtn.style.fontSize = '0.95rem';
    boldBtn.style.color = 'var(--gray-700)';
    boldBtn.style.transition = 'background 0.2s';
    boldBtn.onmouseover = () => boldBtn.style.background = '#e2e8f0';
    boldBtn.onmouseout = () => boldBtn.style.background = 'none';
    boldBtn.title = 'Bold (Ctrl+B)';

    const italicBtn = document.createElement('button');
    italicBtn.type = 'button';
    italicBtn.textContent = 'I';
    italicBtn.style.fontFamily = "'Georgia', serif";
    italicBtn.style.fontStyle = 'italic';
    italicBtn.style.fontWeight = 'bold';
    italicBtn.style.background = 'none';
    italicBtn.style.border = 'none';
    italicBtn.style.borderRadius = '4px';
    italicBtn.style.width = '28px';
    italicBtn.style.height = '28px';
    italicBtn.style.cursor = 'pointer';
    italicBtn.style.fontSize = '0.95rem';
    italicBtn.style.color = 'var(--gray-700)';
    italicBtn.style.transition = 'background 0.2s';
    italicBtn.onmouseover = () => italicBtn.style.background = '#e2e8f0';
    italicBtn.onmouseout = () => italicBtn.style.background = 'none';
    italicBtn.title = 'Italic (Ctrl+I)';

    const underlineBtn = document.createElement('button');
    underlineBtn.type = 'button';
    underlineBtn.textContent = 'U';
    underlineBtn.style.textDecoration = 'underline';
    underlineBtn.style.fontWeight = 'bold';
    underlineBtn.style.background = 'none';
    underlineBtn.style.border = 'none';
    underlineBtn.style.borderRadius = '4px';
    underlineBtn.style.width = '28px';
    underlineBtn.style.height = '28px';
    underlineBtn.style.cursor = 'pointer';
    underlineBtn.style.fontSize = '0.95rem';
    underlineBtn.style.color = 'var(--gray-700)';
    underlineBtn.style.transition = 'background 0.2s';
    underlineBtn.onmouseover = () => underlineBtn.style.background = '#e2e8f0';
    underlineBtn.onmouseout = () => underlineBtn.style.background = 'none';
    underlineBtn.title = 'Underline (Ctrl+U)';

    const linkBtn = document.createElement('button');
    linkBtn.type = 'button';
    linkBtn.innerHTML = '<i class="fa-solid fa-link" style="font-size: 0.85rem; color: var(--gray-600);"></i>';
    linkBtn.style.background = 'none';
    linkBtn.style.border = 'none';
    linkBtn.style.borderRadius = '4px';
    linkBtn.style.width = '28px';
    linkBtn.style.height = '28px';
    linkBtn.style.cursor = 'pointer';
    linkBtn.style.fontSize = '0.9rem';
    linkBtn.style.color = 'var(--gray-700)';
    linkBtn.style.transition = 'background 0.2s';
    linkBtn.onmouseover = () => linkBtn.style.background = '#e2e8f0';
    linkBtn.onmouseout = () => linkBtn.style.background = 'none';
    linkBtn.title = 'Insert Link';

    const fontSelect = document.createElement('select');
    fontSelect.className = 'font-select-arrow';
    fontSelect.style.border = '1px solid #cbd5e1';
    fontSelect.style.borderRadius = '6px';
    fontSelect.style.padding = '2px 4px';
    fontSelect.style.fontSize = '0.8rem';
    fontSelect.style.color = 'var(--gray-700)';
    fontSelect.style.background = '#ffffff';
    fontSelect.style.cursor = 'pointer';
    fontSelect.style.marginLeft = '0.5rem';
    fontSelect.style.fontFamily = 'inherit';
    fontSelect.style.outline = 'none';
    fontSelect.title = 'Select Font';

    const fonts = [
        { name: 'Default Font', value: '' },
        { name: 'Inter (Sans)', value: 'Inter, sans-serif' },
        { name: 'Georgia (Serif)', value: 'Georgia, serif' },
        { name: 'Courier (Mono)', value: 'Courier New, monospace' },
        { name: 'Cursive', value: 'cursive' }
    ];

    fonts.forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.value;
        opt.textContent = f.name;
        fontSelect.appendChild(opt);
    });

    toolbar.appendChild(boldBtn);
    toolbar.appendChild(italicBtn);
    toolbar.appendChild(underlineBtn);
    toolbar.appendChild(linkBtn);
    toolbar.appendChild(fontSelect);

    // 3. Create contenteditable editor div
    const editor = document.createElement('div');
    editor.contentEditable = 'true';
    editor.className = 'rich-editor';
    editor.style.width = styleOptions.width || '100%';
    editor.style.minHeight = styleOptions.minHeight || '80px';
    editor.style.maxHeight = '300px';
    editor.style.overflowY = 'auto';
    editor.style.padding = '0.75rem';
    editor.style.borderRadius = '0 0 8px 8px';
    editor.style.border = '1px solid #cbd5e1';
    editor.style.fontFamily = 'inherit';
    editor.style.fontSize = '1rem';
    editor.style.outline = 'none';
    editor.style.background = '#ffffff';
    editor.style.color = initialValue ? 'var(--gray-900)' : '#94a3b8';

    // Convert functions
    function convertMarkdownToHtml(md) {
        if (!md) return '';
        // Escape HTML
        let html = md
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        html = html.replace(/&lt;u&gt;(.*?)&lt;\/u&gt;/gi, '<u>$1</u>');
        html = html.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" style="color: var(--green-600); text-decoration: underline;">$1</a>');
        html = html.replace(/&lt;span\s+style=(?:&quot;|"|')font-family:\s*(.*?);?(?:&quot;|"|')&gt;(.*?)&lt;\/span&gt;/gi, '<span style="font-family: $1;">$2</span>');
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    function convertHtmlToMarkdown(html) {
        if (!html || (placeholder && html === placeholder)) return '';
        let md = html;

        // 1. Convert supported structures to safe markers
        md = md.replace(/<(strong|b)[^>]*>(.*?)<\/\1>/gi, '**$2**');
        md = md.replace(/<(em|i)[^>]*>(.*?)<\/\1>/gi, '*$2*');
        md = md.replace(/<u[^>]*>(.*?)<\/u>/gi, '__U_START__$1__U_END__');
        md = md.replace(/<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/gi, '__LINK_START_[$1]__$2__LINK_END__');
        md = md.replace(/<font\s+face="([^"]+)"[^>]*>(.*?)<\/font>/gi, '__FONT_START_[$1]__$2__FONT_END__');
        md = md.replace(/<span\s+style="font-family:\s*([^";]+);?"[^>]*>(.*?)<\/span>/gi, '__FONT_START_[$1]__$2__FONT_END__');

        // Convert block tags and line breaks to plain newlines
        md = md.replace(/<br\s*\/?>/gi, '\n');
        md = md.replace(/<div[^>]*>(.*?)<\/div>/gi, '\n$1');
        md = md.replace(/<p[^>]*>(.*?)<\/p>/gi, '\n$1');

        // 2. Strip all remaining HTML tags
        const temp = document.createElement('div');
        temp.innerHTML = md;
        let text = temp.textContent || temp.innerText || '';

        // 3. Convert markers back to final markdown format
        text = text.replace(/__U_START__(.*?)__U_END__/gi, '<u>$1</u>');
        text = text.replace(/__LINK_START_\[(.*?)\]__(.*?)__LINK_END__/gi, '[$2]($1)');
        text = text.replace(/__FONT_START_\[(.*?)\]__(.*?)__FONT_END__/gi, '<span style="font-family: $1;">$2</span>');

        return text.trim();
    }

    if (placeholder) {
        editor.innerHTML = initialValue ? convertMarkdownToHtml(initialValue) : placeholder;

        editor.addEventListener('focus', () => {
            if (editor.innerHTML === placeholder) {
                editor.innerHTML = '';
                editor.style.color = 'var(--gray-900)';
            }
        });
        editor.addEventListener('blur', () => {
            if (!editor.innerHTML.replace(/<br\s*\/?>/gi, '').trim()) {
                editor.innerHTML = placeholder;
                editor.style.color = '#94a3b8';
            }
        });
    } else {
        editor.innerHTML = convertMarkdownToHtml(initialValue || '');
    }

    // Safe prototype descriptor helper
    function getProtoPropertyDescriptor(obj, prop) {
        let desc;
        while (obj) {
            desc = Object.getOwnPropertyDescriptor(obj, prop);
            if (desc) return desc;
            obj = Object.getPrototypeOf(obj);
        }
        return null;
    }
    const originalValueProp = getProtoPropertyDescriptor(HTMLTextAreaElement.prototype, 'value');

    if (originalValueProp && originalValueProp.set) {
        Object.defineProperty(hiddenTextarea, 'value', {
            get() {
                return originalValueProp.get.call(hiddenTextarea);
            },
            set(val) {
                originalValueProp.set.call(hiddenTextarea, val);
                if (val) {
                    editor.innerHTML = convertMarkdownToHtml(val);
                    editor.style.color = 'var(--gray-900)';
                } else {
                    editor.innerHTML = placeholder || '';
                    editor.style.color = placeholder ? '#94a3b8' : 'var(--gray-900)';
                }
            }
        });
    } else {
        Object.defineProperty(hiddenTextarea, 'value', {
            get() {
                return hiddenTextarea.getAttribute('value') || '';
            },
            set(val) {
                hiddenTextarea.setAttribute('value', val);
                if (val) {
                    editor.innerHTML = convertMarkdownToHtml(val);
                    editor.style.color = 'var(--gray-900)';
                } else {
                    editor.innerHTML = placeholder || '';
                    editor.style.color = placeholder ? '#94a3b8' : 'var(--gray-900)';
                }
            }
        });
    }

    const syncValue = () => {
        const markdown = convertHtmlToMarkdown(editor.innerHTML);
        if (originalValueProp && originalValueProp.set) {
            originalValueProp.set.call(hiddenTextarea, markdown);
        } else {
            hiddenTextarea.value = markdown;
        }
    };

    editor.addEventListener('input', syncValue);

    editor.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'u') {
            e.preventDefault();
            document.execCommand('underline', false, null);
            syncValue();
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
            e.preventDefault();
            document.execCommand('bold', false, null);
            syncValue();
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'i') {
            e.preventDefault();
            document.execCommand('italic', false, null);
            syncValue();
        }
    });

    // B/I click formatting
    boldBtn.onclick = () => {
        editor.focus();
        document.execCommand('bold', false, null);
        syncValue();
    };
    italicBtn.onclick = () => {
        editor.focus();
        document.execCommand('italic', false, null);
        syncValue();
    };
    underlineBtn.onclick = () => {
        editor.focus();
        document.execCommand('underline', false, null);
        syncValue();
    };
    linkBtn.onclick = () => {
        editor.focus();

        // Save current text selection range
        const selection = window.getSelection();
        let savedRange = null;
        if (selection.rangeCount > 0) {
            savedRange = selection.getRangeAt(0).cloneRange();
        }

        window.customLinkPrompt('Enter link URL:', 'https://example.com', (url) => {
            // Restore selection range
            if (savedRange) {
                selection.removeAllRanges();
                selection.addRange(savedRange);
            }

            document.execCommand('createLink', false, url);
            // Style links cleanly
            const links = editor.getElementsByTagName('a');
            for (let link of links) {
                link.target = '_blank';
                link.style.color = 'var(--green-600)';
                link.style.textDecoration = 'underline';
            }
            syncValue();
        }, () => {
            // Restore selection range
            if (savedRange) {
                selection.removeAllRanges();
                selection.addRange(savedRange);
            }
            document.execCommand('unlink', false, null);
            syncValue();
        });
    };
    fontSelect.onchange = () => {
        editor.focus();
        const selectedFont = fontSelect.value;
        if (selectedFont) {
            document.execCommand('fontName', false, selectedFont);
        } else {
            document.execCommand('removeFormat', false, null);
        }
        syncValue();
    };

    // Set initial value
    hiddenTextarea.value = initialValue || '';

    // Append elements
    container.appendChild(hiddenTextarea);
    container.appendChild(toolbar);
    container.appendChild(editor);
};

document.addEventListener('DOMContentLoaded', () => {
    initGlobal();
    initApp();
    initSpaNavigation();
});

function initGlobal() {
    if (isGlobalInitDone) return;
    initNavbar();
    initUserDropdown();

    // Prevent accidental navigation during uploads
    window.addEventListener('beforeunload', (e) => {
        if (isUploading) {
            const msg = 'An upload is currently in progress. If you leave this page, your upload will be cancelled.';
            e.preventDefault();
            e.returnValue = msg;
            return msg;
        }
    });

    // Global shortcut delegation for comment & reply inputs
    document.addEventListener('keydown', (e) => {
        const target = e.target;
        if (target && target.id && (target.id.startsWith('comment-input-') || target.id.startsWith('reply-input-'))) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                e.preventDefault();
                window.formatTextarea(target, 'bold');
            }
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'i') {
                e.preventDefault();
                window.formatTextarea(target, 'italic');
            }
        }
    });

    isGlobalInitDone = true;
}

function initApp() {
    initCreateForm();
    initUpload();
    initHistory();
    initQR();
    initAuthForms();

    // Show refresh update toast on dashboard page
    const path = window.location.pathname;
    if (path === '/dashboard') {
        setTimeout(() => {
            showToast("Click Refresh Button !", "info-no-icon", 5000);
        }, 500);
    }
}

/* ===== SPA NAVIGATION (Next.js Style) ===== */
const spaCache = new Map();

function initSpaNavigation() {
    const loader = document.getElementById('spa-loader-fill');

    // Intercept all internal link clicks
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link || !link.href) return;

        // If it's a download link, let the browser handle it naturally
        if (link.hasAttribute('download')) return;

        const url = new URL(link.href);
        const isInternal = url.origin === window.location.origin;
        const isSelf = link.getAttribute('target') === '_self' || !link.getAttribute('target');
        const isNotSpecial = !link.href.includes('#') && !link.href.startsWith('mailto:') && !link.href.startsWith('tel:') && !link.href.includes('/logout');
        const isApi = url.pathname.startsWith('/api/');

        if (isInternal && isSelf && isNotSpecial && !isApi) {
            // Check for data-no-spa attribute
            if (link.getAttribute('data-no-spa') === 'true') return;

            // Bypass SPA for chat links to ensure stability and correct message sending
            if (url.search.includes('chat=')) {
                window.location.href = link.href;
                return;
            }

            e.preventDefault();
            if (window.location.href === link.href) return;
            handleSpaLink(link.href);
        }
    });

    // Prefetch on hover
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a');
        if (!link || !link.href) return;
        if (link.hasAttribute('download')) return;

        const url = new URL(link.href);
        const isInternal = url.origin === window.location.origin;
        const isNotSpecial = !link.href.includes('#') && !link.href.includes('/logout');
        const isApi = url.pathname.startsWith('/api/');

        if (isInternal && isNotSpecial && !isApi && !spaCache.has(link.href)) {
            // Don't prefetch chat links as they might trigger a refresh
            if (url.search.includes('chat=')) return;
            prefetchSpaLink(link.href);
        }
    });

    // Handle browser back/forward
    window.spaCurrentPath = window.location.pathname + window.location.search;
    window.addEventListener('popstate', () => {
        const newPath = window.location.pathname + window.location.search;
        if (newPath !== window.spaCurrentPath) {
            // If navigating to a chat via back/forward, force a reload
            if (window.location.search.includes('chat=')) {
                window.location.reload();
                return;
            }
            window.spaCurrentPath = newPath;
            handleSpaLink(window.location.href, false);
        }
    });
}

async function prefetchSpaLink(url) {
    try {
        const response = await fetch(url);
        if (response.ok) {
            const html = await response.text();
            spaCache.set(url, html);
        }
    } catch (err) { }
}

async function handleSpaLink(url, push = true) {
    const loader = document.getElementById('spa-loader-fill');
    if (loader) {
        loader.style.width = '30%';
        loader.style.opacity = '1';
    }

    try {
        let html = spaCache.get(url);
        if (!html) {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to load page');
            html = await response.text();
        }

        if (loader) loader.style.width = '70%';
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Update Title and Content
        document.title = doc.title;
        const newContent = doc.querySelector('.main-content');
        const currentContent = document.querySelector('.main-content');

        if (newContent && currentContent) {
            currentContent.innerHTML = newContent.innerHTML;

            // Execute scripts inside new content
            const scripts = currentContent.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Update Body Classes (Home vs Inner)
            document.body.className = doc.body.className;

            // Update Navbar Active States
            updateNavbarActive(url);

            // Update URL
            if (push) history.pushState({}, '', url);
            window.spaCurrentPath = window.location.pathname + window.location.search;

            // Re-initialize scripts for new content
            initApp();

            // Scroll to top
            window.scrollTo(0, 0);
        }

        if (loader) {
            loader.style.width = '100%';
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.width = '0%', 300);
            }, 200);
        }
    } catch (err) {
        console.error('SPA Load Error:', err);
        window.location.href = url; // Fallback to normal load
    }
}

function updateNavbarActive(url) {
    const path = new URL(url).pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkPath = new URL(link.href).pathname;
        if (linkPath === path) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

/* ===== TOAST NOTIFICATIONS ===== */
function showToast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = {
        success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
        'info-no-icon': ''
    };
    const iconHtml = icons[type] !== undefined ? icons[type] : icons.info;
    toast.innerHTML = `${iconHtml}<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        toast.style.transition = 'all .3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/* ===== CSRF TOKEN ===== */
function getCSRF() {
    return document.getElementById('csrf-token')?.value || '';
}
function updateCSRF(newToken) {
    const el = document.getElementById('csrf-token');
    if (el && newToken) el.value = newToken;
    if (typeof CSRF_TOKEN !== 'undefined' && newToken) window.CSRF_TOKEN = newToken;
}

/* ===== NAVBAR ===== */
function initNavbar() {
    const toggle = document.getElementById('nav-toggle');
    const links = document.getElementById('nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            links.classList.toggle('open');
            toggle.classList.toggle('active');
        });
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !links.contains(e.target)) {
                links.classList.remove('open');
                toggle.classList.remove('active');
            }
        });

        // Auto-close menu when an option is selected on mobile
        const navItems = links.querySelectorAll('.nav-link, .nav-dropdown-item');
        navItems.forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    links.classList.remove('open');
                    toggle.classList.remove('active');
                }
            });
        });
    }
    // Navbar scroll effect
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        if (!navbar) return;
        const scroll = window.scrollY;
        if (scroll > 50) {
            navbar.style.boxShadow = '0 1px 3px rgba(0,0,0,.1)';
        } else {
            navbar.style.boxShadow = 'none';
        }
        lastScroll = scroll;
    });
}

function scrollToCreate(e) {
    e.preventDefault();
    const section = document.getElementById('create-section');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => document.getElementById('fld_slug_box')?.focus(), 500);
    }
}

function scrollToFeatures(e) {
    e.preventDefault();
    const section = document.getElementById('features-section');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function setCreateMode(mode) {
    const fields = document.getElementById('advanced-options-fields');
    const normalBtn = document.getElementById('mode-normal');
    const advancedBtn = document.getElementById('mode-advanced');

    if (!fields || !normalBtn || !advancedBtn) return;

    if (mode === 'advanced') {
        fields.style.display = 'block';
        advancedBtn.classList.add('active');
        normalBtn.classList.remove('active');

        // Focus password field if empty (disabled to prevent keyboard popup)
        // const passInput = document.getElementById('folder-password');
        // if (passInput && !passInput.value) passInput.focus();
    } else {
        fields.style.display = 'none';
        advancedBtn.classList.remove('active');
        normalBtn.classList.add('active');

        // Clear advanced values when switching back to normal for safety
        const passInput = document.getElementById('folder-password');
        const expirySelect = document.getElementById('folder-expiry');
        if (passInput) passInput.value = '';
        if (expirySelect) expirySelect.value = 'never';
    }
}
window.setCreateMode = setCreateMode;

/* ===== CREATE FOLDER ===== */
function initCreateForm() {
    const form = document.getElementById('create-folder-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('fld_slug_box');
        const btn = document.getElementById('btn-create-folder');
        const hint = document.getElementById('folder-hint');

        if (!input) {
            console.error('Folder input not found!');
            return;
        }

        const folderName = input.value.trim();

        if (!folderName) {
            hint.textContent = 'Please enter a folder name.';
            hint.className = 'input-hint error';
            input.focus();
            return;
        }

        // Disable button
        btn.querySelector('.btn-text').style.display = 'none';
        btn.querySelector('.btn-loader').style.display = 'flex';
        btn.disabled = true;

        try {
            const formData = new FormData(form);
            formData.append('csrf_token', getCSRF());

            const res = await fetch('/api/create-folder', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                updateCSRF(data.csrf_token);
                showCreateSuccess(data.folder);
                saveFolderToHistory(data.folder);
                showToast('Folder created successfully!');
            } else {
                hint.textContent = data.errors?.[0] || 'Failed to create folder.';
                hint.className = 'input-hint error';
            }
        } catch (err) {
            showToast('Network error. Please try again.', 'error');
        } finally {
            btn.querySelector('.btn-text').style.display = '';
            btn.querySelector('.btn-loader').style.display = 'none';
            btn.disabled = false;
        }
    });
}

function showCreateSuccess(folder) {
    const form = document.getElementById('create-folder-form');
    const icon = document.querySelector('.create-icon');
    const success = document.getElementById('create-success');
    const urlInput = document.getElementById('success-url');
    const gotoBtn = document.getElementById('btn-goto-folder');

    if (form) form.style.display = 'none';
    if (icon) icon.style.display = 'none';
    if (success) success.style.display = 'block';
    if (urlInput) urlInput.value = folder.url;
    if (gotoBtn) gotoBtn.href = '/' + folder.slug;

    // Generate QR
    const qrBox = document.getElementById('qr-code');
    if (qrBox && typeof QRCode !== 'undefined') {
        qrBox.innerHTML = '';
        new QRCode(qrBox, { text: folder.url, width: 128, height: 128, colorDark: '#166534', colorLight: '#ffffff' });
    }
}

function resetCreateForm() {
    const form = document.getElementById('create-folder-form');
    const icon = document.querySelector('.create-icon');
    const success = document.getElementById('create-success');
    const hint = document.getElementById('folder-hint');
    const input = document.getElementById('fld_slug_box');

    if (form) { form.style.display = ''; form.reset(); }
    if (icon) icon.style.display = '';
    if (success) success.style.display = 'none';
    if (hint) {
        hint.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg> Use letters, numbers, hyphens, or underscores.`;
        hint.className = 'input-hint';
    }
    if (input) input.focus();
}

function copyFolderUrl() {
    const input = document.getElementById('success-url');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        showToast('Link copied to clipboard!');
    }).catch(() => {
        input.select();
        document.execCommand('copy');
        showToast('Link copied!');
    });
}

/* ===== FOLDER SHARE ===== */
function shareFolderUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        showToast('Folder link copied to clipboard!');
    }).catch(() => {
        showToast('Failed to copy link.', 'error');
    });
}

function toggleQR() {
    const panel = document.getElementById('qr-panel');
    if (!panel) return;
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        const qrBox = document.getElementById('folder-qr-code');
        const folderUrl = document.getElementById('folder-url')?.value;
        if (qrBox && folderUrl && !qrBox.hasChildNodes() && typeof QRCode !== 'undefined') {
            new QRCode(qrBox, { text: folderUrl, width: 160, height: 160, colorDark: '#166534', colorLight: '#ffffff' });
        }
    }
}

/* ===== FILE UPLOAD ===== */
function initUpload() {
    const dropzone = document.getElementById('upload-dropzone');
    const fileInput = document.getElementById('file-input');
    if (!dropzone || !fileInput) return;

    // Click to upload
    dropzone.addEventListener('click', (e) => {
        if (e.target !== fileInput) fileInput.click();
    });

    // Drag and drop
    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => { e.preventDefault(); dropzone.classList.add('drag-over'); });
    });
    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => { e.preventDefault(); dropzone.classList.remove('drag-over'); });
    });
    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length) handleFiles(files);
    });

    // File input change
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) handleFiles(fileInput.files);
    });
}

function ensurePersistentWidget() {
    let widget = document.getElementById('persistent-upload-widget');
    if (!widget) {
        widget = document.createElement('div');
        widget.id = 'persistent-upload-widget';
        widget.className = 'persistent-upload-widget';
        widget.innerHTML = `
            <div class="widget-header" id="widget-header-bar">
                <h4>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <span id="widget-title-text">Uploading...</span>
                </h4>
                <div class="widget-controls">
                    <button type="button" class="widget-btn btn-minimize-widget" id="btn-minimize-widget" title="Minimize/Maximize">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline></svg>
                    </button>
                    <button type="button" class="widget-btn btn-close-widget" id="btn-close-widget" style="display:none;" title="Close Panel">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>
            <div class="widget-body">
                <div class="widget-overall-progress">
                    <div class="widget-progress-info">
                        <span>Overall Progress</span>
                        <span class="widget-progress-pct" id="widget-progress-pct">0%</span>
                    </div>
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" id="widget-progress-bar"></div>
                    </div>
                </div>
                <div class="widget-file-list" id="widget-file-list"></div>
            </div>
        `;
        document.body.appendChild(widget);

        // Add event listener for header click to minimize/maximize
        const header = widget.querySelector('#widget-header-bar');
        header.addEventListener('click', (e) => {
            if (e.target.closest('.widget-controls')) return;
            widget.classList.toggle('minimized');
        });

        // Add event listener for minimize button
        widget.querySelector('#btn-minimize-widget').addEventListener('click', () => {
            widget.classList.toggle('minimized');
        });

        // Add event listener for close button
        widget.querySelector('#btn-close-widget').addEventListener('click', () => {
            widget.classList.remove('active');
            widget.classList.remove('minimized');
        });
    }
    return widget;
}

function updateOverallProgress() {
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');
    const progressArea = document.getElementById('upload-progress-area');
    const progressHeader = progressArea?.querySelector('h4');

    const activeOrFinished = globalUploads.filter(u => u.status !== 'cancelled');
    const total = activeOrFinished.reduce((sum, u) => sum + u.totalBytes, 0);
    const loaded = activeOrFinished.reduce((sum, u) => sum + u.loadedBytes, 0);

    const overallPct = total > 0 ? Math.min(Math.round((loaded / total) * 100), 100) : 0;

    if (progressBar) progressBar.style.width = overallPct + '%';
    if (progressText) progressText.textContent = overallPct + '%';

    const activeCount = globalUploads.filter(u => u.status === 'uploading').length;
    const pendingCount = globalUploads.filter(u => u.status === 'pending').length;
    isUploading = activeCount > 0 || pendingCount > 0;

    // Update Persistent Widget
    if (globalUploads.length > 0) {
        const widget = ensurePersistentWidget();
        widget.classList.add('active');

        const wPct = widget.querySelector('#widget-progress-pct');
        const wBar = widget.querySelector('#widget-progress-bar');
        const wTitle = widget.querySelector('#widget-title-text');
        const wClose = widget.querySelector('#btn-close-widget');

        if (wPct) wPct.textContent = overallPct + '%';
        if (wBar) wBar.style.width = overallPct + '%';

        if (activeCount > 0 || pendingCount > 0) {
            if (wTitle) wTitle.textContent = `Uploading ${activeCount + pendingCount} file(s)...`;
            if (wClose) wClose.style.display = 'none';
        } else {
            const hasFailed = globalUploads.some(u => u.status === 'failed');
            if (wTitle) wTitle.textContent = hasFailed ? 'Upload Finished (with errors)' : 'All Uploads Complete';
            if (wClose) wClose.style.display = 'flex';

            // Auto-hide persistent widget after 8 seconds of idle complete (only if not minimized or errors exist)
            if (!hasFailed && !widget.classList.contains('minimized')) {
                if (widget.hideTimeout) clearTimeout(widget.hideTimeout);
                widget.hideTimeout = setTimeout(() => {
                    const currentActive = globalUploads.filter(u => u.status === 'uploading').length;
                    const currentPending = globalUploads.filter(u => u.status === 'pending').length;
                    if (currentActive === 0 && currentPending === 0) {
                        widget.classList.remove('active');
                        globalUploads = [];
                        const wFileList = widget.querySelector('#widget-file-list');
                        if (wFileList) wFileList.innerHTML = '';
                    }
                }, 8000);
            }
        }
    }

    if (progressHeader) {
        if (activeCount > 0 || pendingCount > 0) {
            progressHeader.textContent = `Uploading ${activeCount + pendingCount} file(s)...`;
        } else {
            progressHeader.textContent = 'Upload Complete';

            // Auto hide inline progress panel after 4 seconds of idle
            setTimeout(() => {
                const currentActive = globalUploads.filter(u => u.status === 'uploading').length;
                const currentPending = globalUploads.filter(u => u.status === 'pending').length;
                if (currentActive === 0 && currentPending === 0) {
                    const currentProgressArea = document.getElementById('upload-progress-area');
                    if (currentProgressArea) currentProgressArea.style.display = 'none';
                    const fileList = document.getElementById('upload-file-list');
                    if (fileList) fileList.innerHTML = '';
                }
            }, 4000);
        }
    }
}

function processQueue(folderId) {
    const pendingTasks = globalUploads.filter(u => u.status === 'pending');
    if (pendingTasks.length === 0) {
        updateOverallProgress();
        return;
    }

    while (activeUploadCount < MAX_CONCURRENT_UPLOADS && pendingTasks.length > 0) {
        const task = pendingTasks.shift();
        task.status = 'uploading';
        activeUploadCount++;
        uploadFileInChunks(task.file, task, folderId);
    }
    updateOverallProgress();
}

function uploadFileInChunks(file, uploadTask, folderId) {
    const CHUNK_SIZE = typeof UPLOAD_CHUNK_SIZE !== 'undefined' ? UPLOAD_CHUNK_SIZE : 2 * 1024 * 1024;
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const fileUuid = 'ff-' + Math.random().toString(36).substring(2, 11) + '-' + Date.now().toString(36);
    let chunkIndex = 0;
    const startTime = Date.now();

    function uploadNextChunk() {
        if (uploadTask.aborted) return;

        const start = chunkIndex * CHUNK_SIZE;
        const end = Math.min(file.size, start + CHUNK_SIZE);
        const chunk = file.slice(start, end);

        const formData = new FormData();
        formData.append('folder_id', folderId);
        formData.append('csrf_token', getCSRF());
        formData.append('files[]', chunk, file.name);
        formData.append('chunk_index', chunkIndex);
        formData.append('total_chunks', totalChunks);
        formData.append('file_uuid', fileUuid);
        formData.append('file_name', file.name);
        formData.append('file_size', file.size);

        const _csrf = document.getElementById('csrf-token');
        const token = _csrf ? _csrf.value : (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');
        formData.append('csrf_token', token);

        const xhr = new XMLHttpRequest();
        uploadTask.xhr = xhr;

        xhr.open('POST', '/api/upload');

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && !uploadTask.aborted) {
                const chunkLoaded = e.loaded;
                uploadTask.loadedBytes = start + chunkLoaded;
                updateOverallProgress();

                // Calculate speed and ETA
                const pct = Math.round((uploadTask.loadedBytes / file.size) * 100);
                const timeElapsed = (Date.now() - startTime) / 1000;
                if (timeElapsed > 0.5 && uploadTask.loadedBytes > 0) {
                    const speedBps = uploadTask.loadedBytes / timeElapsed;
                    const bytesRemaining = file.size - uploadTask.loadedBytes;
                    const timeRemainingSec = Math.max(0, bytesRemaining / speedBps);

                    let timeStr = "";
                    if (timeRemainingSec >= 3600) {
                        timeStr = Math.floor(timeRemainingSec / 3600) + "h " + Math.floor((timeRemainingSec % 3600) / 60) + "m";
                    } else if (timeRemainingSec >= 60) {
                        timeStr = Math.floor(timeRemainingSec / 60) + "m " + Math.floor(timeRemainingSec % 60) + "s";
                    } else {
                        timeStr = Math.floor(timeRemainingSec) + "s";
                    }
                    const statusText = `Uploading... ${pct}% (${timeStr} remaining)`;
                    if (uploadTask.statusElement) uploadTask.statusElement.textContent = statusText;
                    if (uploadTask.widgetStatusElement) uploadTask.widgetStatusElement.textContent = statusText;
                } else {
                    const statusText = `Uploading... ${pct}%`;
                    if (uploadTask.statusElement) uploadTask.statusElement.textContent = statusText;
                    if (uploadTask.widgetStatusElement) uploadTask.widgetStatusElement.textContent = statusText;
                }
            }
        });

        xhr.addEventListener('load', () => {
            if (uploadTask.aborted) return;

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.csrf_token) updateCSRF(data.csrf_token);

                    if (data.success) {
                        if (data.chunk_uploaded) {
                            chunkIndex++;
                            uploadNextChunk();
                        } else if (data.results && data.results[0]?.success) {
                            uploadTask.status = 'success';
                            uploadTask.loadedBytes = file.size;
                            if (uploadTask.domElement) {
                                uploadTask.domElement.className = 'upload-file-item success';
                                uploadTask.statusElement.textContent = 'Uploaded';
                                uploadTask.cancelBtn.style.display = 'none';
                            }
                            if (uploadTask.widgetDomElement) {
                                uploadTask.widgetDomElement.className = 'widget-file-item success';
                                uploadTask.widgetStatusElement.textContent = 'Uploaded';
                                uploadTask.widgetCancelBtn.style.display = 'none';
                            }
                            addFileCard(data.results[0].file);

                            // Update count on page
                            const countEl = document.getElementById('file-count');
                            if (countEl) countEl.textContent = parseInt(countEl.textContent) + 1;
                            const empty = document.getElementById('files-empty');
                            if (empty) empty.remove();

                            activeUploadCount--;
                            processQueue(folderId);
                        } else {
                            handleUploadError(data.errors?.[0] || data.results?.[0]?.errors?.[0] || 'Upload failed.');
                        }
                    } else {
                        handleUploadError(data.errors?.[0] || 'Upload failed.');
                    }
                } catch (err) {
                    handleUploadError('Invalid server response.');
                }
            } else {
                handleUploadError(`Server returned status ${xhr.status}`);
            }
        });

        xhr.addEventListener('error', () => {
            if (uploadTask.aborted) return;
            handleUploadError('Network error.');
        });

        xhr.addEventListener('abort', () => {
            uploadTask.status = 'cancelled';
            if (uploadTask.domElement) {
                uploadTask.domElement.className = 'upload-file-item error';
                uploadTask.statusElement.textContent = 'Cancelled';
                uploadTask.cancelBtn.style.display = 'none';
            }
            if (uploadTask.widgetDomElement) {
                uploadTask.widgetDomElement.className = 'widget-file-item error';
                uploadTask.widgetStatusElement.textContent = 'Cancelled';
                uploadTask.widgetCancelBtn.style.display = 'none';
            }
            activeUploadCount--;
            processQueue(folderId);
        });

        xhr.send(formData);
    }

    function handleUploadError(errMsg) {
        uploadTask.status = 'failed';
        if (uploadTask.domElement) {
            uploadTask.domElement.className = 'upload-file-item error';
            uploadTask.statusElement.textContent = errMsg;
            uploadTask.cancelBtn.style.display = 'none';
        }
        if (uploadTask.widgetDomElement) {
            uploadTask.widgetDomElement.className = 'widget-file-item error';
            uploadTask.widgetStatusElement.textContent = errMsg;
            uploadTask.widgetCancelBtn.style.display = 'none';
        }
        showToast(`Failed to upload "${file.name}": ${errMsg}`, 'error');
        activeUploadCount--;
        processQueue(folderId);
    }

    uploadNextChunk();
}

async function handleFiles(files) {
    const folderId = document.getElementById('folder-id')?.value;
    if (!folderId) return;

    const validFiles = [];
    for (let i = 0; i < files.length && i < MAX_FILES_PER_UPLOAD; i++) {
        const file = files[i];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!ALLOWED_EXTENSIONS.includes(ext)) {
            showToast(`"${file.name}" is not a supported file type.`, 'error');
            continue;
        }
        if (file.size > MAX_FILE_SIZE) {
            showToast(`"${file.name}" exceeds the size limit.`, 'error');
            continue;
        }
        validFiles.push(file);
    }

    if (!validFiles.length) return;

    const progressArea = document.getElementById('upload-progress-area');
    const fileList = document.getElementById('upload-file-list');

    if (progressArea) progressArea.style.display = 'block';

    const widget = ensurePersistentWidget();
    const wFileList = widget.querySelector('#widget-file-list');
    if (widget.hideTimeout) {
        clearTimeout(widget.hideTimeout);
    }

    const currentBatchTasks = [];

    for (let i = 0; i < validFiles.length; i++) {
        const file = validFiles[i];

        let item = null;
        let cancelBtn = null;
        if (fileList) {
            item = document.createElement('div');
            item.className = 'upload-file-item';
            item.style.display = 'flex';
            item.style.alignItems = 'center';
            item.style.justifyContent = 'space-between';
            item.innerHTML = `
                <div style="flex:1; min-width:0; margin-right:10px; display:flex; flex-direction:column; gap:4px;">
                    <div class="upload-file-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500;" title="${file.name}">${file.name}</div>
                    <div class="file-status" style="font-size:0.8rem; color:var(--gray-500);">Waiting...</div>
                </div>
                <button type="button" class="btn-cancel-upload" style="background:none; border:none; color:var(--red-500); cursor:pointer; padding:4px; flex-shrink:0;" title="Cancel Upload">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            `;
            fileList.appendChild(item);
            cancelBtn = item.querySelector('.btn-cancel-upload');
        }

        const wItem = document.createElement('div');
        wItem.className = 'widget-file-item';
        wItem.innerHTML = `
            <div class="widget-file-info">
                <div class="widget-file-name" title="${file.name}">${file.name}</div>
                <div class="widget-file-status">Waiting...</div>
            </div>
            <button type="button" class="widget-btn btn-cancel-widget-upload" style="color:var(--red-500); padding:2px; flex-shrink:0;" title="Cancel">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        `;
        if (wFileList) wFileList.appendChild(wItem);
        const wCancelBtn = wItem.querySelector('.btn-cancel-widget-upload');

        const task = {
            id: Math.random().toString(36).substring(2, 11) + '-' + Date.now().toString(36),
            file: file,
            fileName: file.name,
            fileSize: file.size,
            status: 'pending',
            loadedBytes: 0,
            totalBytes: file.size,
            xhr: null,
            domElement: item,
            statusElement: item?.querySelector('.file-status'),
            cancelBtn: cancelBtn,
            widgetDomElement: wItem,
            widgetStatusElement: wItem.querySelector('.widget-file-status'),
            widgetCancelBtn: wCancelBtn,
            aborted: false
        };

        const cancelHandler = () => {
            if (task.status === 'uploading') {
                task.aborted = true;
                if (task.xhr) {
                    task.xhr.abort();
                }
            } else if (task.status === 'pending') {
                task.status = 'cancelled';
                if (task.domElement) {
                    task.domElement.className = 'upload-file-item error';
                    task.statusElement.textContent = 'Cancelled';
                    task.cancelBtn.style.display = 'none';
                }
                if (task.widgetDomElement) {
                    task.widgetDomElement.className = 'widget-file-item error';
                    task.widgetStatusElement.textContent = 'Cancelled';
                    task.widgetCancelBtn.style.display = 'none';
                }
                updateOverallProgress();
            }
        };

        if (cancelBtn) cancelBtn.addEventListener('click', cancelHandler);
        if (wCancelBtn) wCancelBtn.addEventListener('click', cancelHandler);

        globalUploads.push(task);
        currentBatchTasks.push(task);
    }

    const fileInput = document.getElementById('file-input');
    if (fileInput) fileInput.value = '';

    processQueue(folderId);
}

function addFileCard(file) {
    const grid = document.getElementById('files-grid');
    if (!grid) return;

    const card = document.createElement('div');
    card.className = `file-card file-card-${file.category}`;
    card.id = `file-${file.id}`;
    const isOwner = document.getElementById('is-owner')?.value === '1';
    const deleteBtn = isOwner ? `
        <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${file.id})" title="Delete File" style="padding: 0.5rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
        </button>` : '';

    card.innerHTML = `
        <div class="file-card-icon">
            <span class="file-type-badge">${file.extension.toUpperCase()}</span>
        </div>
        <div class="file-card-info">
            <h4 class="file-name" title="${file.name}">${file.name}</h4>
            <div class="file-meta">
                <span class="file-size">${file.size}</span>
                <span class="file-date">${file.uploaded_at}</span>
            </div>
        </div>
        <div class="file-card-actions" style="display:flex; gap:6px;">
            <a href="/api/download?id=${file.id}&preview=1" class="btn btn-sm btn-outline" title="Preview" target="_blank" style="padding: 0.5rem; background: var(--gray-50); border: 1px solid var(--gray-200); color: var(--gray-600); display:flex; align-items:center; justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </a>
            <a href="/api/download?id=${file.id}" class="btn btn-sm btn-download" title="Download" id="btn-download-${file.id}" download>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </a>
            ${deleteBtn}
        </div>`;
    grid.insertBefore(card, grid.firstChild);
}

/* ===== FOLDER HISTORY (localStorage) ===== */
function initHistory() {
    const grid = document.getElementById('history-grid');
    if (!grid) return;
    renderHistory();
}

function getHistory() {
    try {
        return JSON.parse(localStorage.getItem('fileflow_history') || '[]');
    } catch { return []; }
}

function saveFolderToHistory(folder) {
    let history = getHistory();
    // Remove if exists
    history = history.filter(h => h.slug !== folder.slug);
    // Add to front
    history.unshift({
        name: folder.name,
        slug: folder.slug,
        url: folder.url,
        created: new Date().toISOString()
    });
    // Keep max 20
    history = history.slice(0, 20);
    localStorage.setItem('fileflow_history', JSON.stringify(history));
    renderHistory();
}

function removeFromHistory(slug) {
    let history = getHistory();
    history = history.filter(h => h.slug !== slug);
    localStorage.setItem('fileflow_history', JSON.stringify(history));
    renderHistory();
    showToast('Removed from history.', 'info');
}

function renderHistory() {
    const grid = document.getElementById('history-grid');
    if (!grid) return;

    const history = getHistory();
    const empty = document.getElementById('history-empty');

    if (history.length === 0) {
        grid.innerHTML = '';
        if (empty) grid.appendChild(empty);
        return;
    }

    grid.innerHTML = history.map(h => `
        <div class="history-card" onclick="window.location.href='/${h.slug}'">
            <div class="history-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="history-card-info">
                <div class="history-card-name">${h.name}</div>
                <div class="history-card-url">${h.url}</div>
            </div>
            <button class="history-card-remove" onclick="event.stopPropagation();removeFromHistory('${h.slug}')" title="Remove">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    `).join('');
}

/* ===== QR CODE INIT ===== */
function initQR() {
    // Auto-generate QR on folder page if panel exists
    const folderUrl = document.getElementById('folder-url')?.value;
    if (!folderUrl) return;
    // QR will be generated on toggle click
}

/* ===== GLOBAL HELPERS ===== */
window.scrollToCreate = scrollToCreate;
window.scrollToFeatures = scrollToFeatures;
window.copyFolderUrl = copyFolderUrl;
window.resetCreateForm = resetCreateForm;
window.shareFolderUrl = shareFolderUrl;
window.toggleQR = toggleQR;
window.removeFromHistory = removeFromHistory;
window.togglePasswordVisibility = togglePasswordVisibility;
window.setCreateMode = setCreateMode;

/* ===== USER DROPDOWN ===== */
function initUserDropdown() {
    const avatarBtn = document.getElementById('nav-avatar-btn');
    const dropdown = document.getElementById('nav-dropdown');
    if (!avatarBtn || !dropdown) return;

    avatarBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && !avatarBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

/* ===== AUTH FORMS ===== */
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

function initAuthForms() {
    // Login
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-login');
            const errEl = document.getElementById('login-error');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';

            const formData = new FormData(loginForm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/login', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    showToast('Login successful! Redirecting...');
                    setTimeout(() => window.location.href = data.is_admin ? '/admin' : '/dashboard', 800);
                } else {
                    errEl.textContent = data.errors?.[0] || 'Login failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }

    // Register
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-register');
            const errEl = document.getElementById('register-error');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';

            const pass = document.getElementById('reg-password').value;
            const confirm = document.getElementById('reg-confirm').value;
            if (pass !== confirm) {
                errEl.textContent = 'Passwords do not match.';
                errEl.style.display = 'flex';
                setBtnLoading(btn, false);
                return;
            }

            const formData = new FormData(registerForm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/register', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    showToast('Account created! Redirecting...');
                    setTimeout(() => window.location.href = '/dashboard', 800);
                } else {
                    errEl.textContent = data.errors?.[0] || 'Registration failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }

    // Forgot Password
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-forgot');
            const errEl = document.getElementById('forgot-error');
            const successEl = document.getElementById('forgot-success');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';
            successEl.style.display = 'none';

            const formData = new FormData(forgotForm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/forgot-password', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    let msg = data.message || 'Reset link sent.';
                    if (data.reset_link) {
                        msg += '<br><br><strong>Reset Link:</strong><br><a href="' + data.reset_link + '" style="word-break:break-all;color:var(--green-600)">' + data.reset_link + '</a>';
                    }
                    successEl.innerHTML = msg;
                    successEl.style.display = 'block';
                    showToast('Reset link generated!');
                } else {
                    errEl.textContent = data.errors?.[0] || 'Failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }

    // Reset Password
    const resetForm = document.getElementById('reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-reset');
            const errEl = document.getElementById('reset-error');
            const successEl = document.getElementById('reset-success');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';
            successEl.style.display = 'none';

            const pass = document.getElementById('reset-password').value;
            const confirm = document.getElementById('reset-confirm').value;
            if (pass !== confirm) {
                errEl.textContent = 'Passwords do not match.';
                errEl.style.display = 'flex';
                setBtnLoading(btn, false);
                return;
            }

            const formData = new FormData();
            formData.append('token', document.getElementById('reset-token').value);
            formData.append('password', pass);
            formData.append('confirm_password', confirm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/reset-password', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    successEl.textContent = data.message || 'Password reset! Redirecting to login...';
                    successEl.style.display = 'block';
                    showToast('Password reset successfully!');
                    setTimeout(() => window.location.href = '/login', 2000);
                } else {
                    errEl.textContent = data.errors?.[0] || 'Reset failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }
}

function setBtnLoading(btn, loading) {
    if (!btn) return;
    const text = btn.querySelector('.btn-text');
    const loader = btn.querySelector('.btn-loader');
    if (loading) {
        if (text) text.style.display = 'none';
        if (loader) loader.style.display = 'flex';
        btn.disabled = true;
    } else {
        if (text) text.style.display = '';
        if (loader) loader.style.display = 'none';
        btn.disabled = false;
    }
}

function getCSRF() {
    return document.getElementById('csrf-token')?.value || '';
}

function updateCSRF(token) {
    const el = document.getElementById('csrf-token');
    if (el && token) el.value = token;
}

window.saveThoughtWithProgress = function(saveBtn, thoughtId, formData, successCallback, errorCallback) {
    const files = [];
    if (formData.getAll) {
        const mediaEntries = formData.getAll('media[]');
        for (let file of mediaEntries) {
            if (file instanceof File) {
                files.push(file);
            }
        }
    }

    const widget = window.ensurePersistentWidget ? window.ensurePersistentWidget() : null;
    if (widget) {
        widget.classList.add('active');
        widget.classList.remove('minimized');
        const wTitle = widget.querySelector('#widget-title-text');
        if (wTitle) wTitle.textContent = `Saving updates...`;
        const wClose = widget.querySelector('#btn-close-widget');
        if (wClose) wClose.style.display = 'none';
        const wFileList = widget.querySelector('#widget-file-list');
        if (wFileList) {
            wFileList.innerHTML = '';
            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    wFileList.innerHTML += `
                        <div class="widget-file-item" id="thought-edit-upload-file-${i}" style="display: flex; flex-direction: column; gap: 0.25rem; padding: 0.5rem; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem; margin-bottom: 0.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; display: block; font-weight: 500;">${files[i].name}</span>
                                <span class="pct" style="font-weight: 600; color: var(--green-600);">Pending</span>
                            </div>
                        </div>
                    `;
                }
            } else {
                wFileList.innerHTML = `
                    <div class="widget-file-item" id="thought-edit-upload-text" style="display: flex; flex-direction: column; gap: 0.25rem; padding: 0.5rem; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem; margin-bottom: 0.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; display: block; font-weight: 500;">Updating post content...</span>
                            <span class="pct" style="font-weight: 600; color: var(--green-600);">Saving</span>
                        </div>
                    </div>
                `;
            }
        }
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/thoughts', true);

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            if (widget) {
                const wPct = widget.querySelector('#widget-progress-pct');
                const wBar = widget.querySelector('#widget-progress-bar');
                if (wPct) wPct.textContent = percentComplete + '%';
                if (wBar) wBar.style.width = percentComplete + '%';
                
                if (files.length > 0) {
                    for (let i = 0; i < files.length; i++) {
                        const fileItem = widget.querySelector(`#thought-edit-upload-file-${i} .pct`);
                        if (fileItem) {
                            fileItem.textContent = percentComplete === 100 ? 'Finishing...' : `${percentComplete}%`;
                        }
                    }
                } else {
                    const textItem = widget.querySelector(`#thought-edit-upload-text .pct`);
                    if (textItem) {
                        textItem.textContent = percentComplete === 100 ? 'Finishing...' : `${percentComplete}%`;
                    }
                }
            }
        }
    });

    xhr.addEventListener('load', () => {
        let data = {};
        try {
            data = JSON.parse(xhr.responseText);
        } catch (err) {}

        if (xhr.status === 200 && data.success) {
            if (widget) {
                const wTitle = widget.querySelector('#widget-title-text');
                if (wTitle) wTitle.textContent = 'Save Complete';
                const wPct = widget.querySelector('#widget-progress-pct');
                const wBar = widget.querySelector('#widget-progress-bar');
                if (wPct) wPct.textContent = '100%';
                if (wBar) wBar.style.width = '100%';
                const wClose = widget.querySelector('#btn-close-widget');
                if (wClose) wClose.style.display = 'flex';
                
                if (files.length > 0) {
                    for (let i = 0; i < files.length; i++) {
                        const fileItem = widget.querySelector(`#thought-edit-upload-file-${i} .pct`);
                        if (fileItem) {
                            fileItem.textContent = 'Uploaded';
                            fileItem.style.color = 'var(--green-600)';
                        }
                    }
                } else {
                    const textItem = widget.querySelector(`#thought-edit-upload-text .pct`);
                    if (textItem) {
                        textItem.textContent = 'Saved';
                        textItem.style.color = 'var(--green-600)';
                    }
                }
                
                setTimeout(() => {
                    widget.classList.remove('active');
                    const wFileList = widget.querySelector('#widget-file-list');
                    if (wFileList) wFileList.innerHTML = '';
                }, 4000);
            }
            successCallback(data);
        } else {
            if (widget) {
                widget.classList.remove('active');
            }
            errorCallback(data.message || 'Error editing post.');
        }
    });

    xhr.addEventListener('error', () => {
        if (widget) {
            widget.classList.remove('active');
        }
        errorCallback('Failed to edit post.');
    });

    xhr.send(formData);
};
