{{-- Professional Rich Text & HTML Content Editor Component --}}
@props([
    'name' => 'content',
    'value' => '',
    'uploadUrl' => route('admin.seo.upload-image'),
    'placeholder' => 'Start typing your content here... Use the toolbar above for headings, formatting, and images.',
    'minHeight' => '420px',
])

<div x-data="proContentEditor({
    initialContent: {{ json_encode($value ?? '') }},
    uploadUrl: '{{ $uploadUrl }}',
    name: '{{ $name }}',
    minHeight: '{{ $minHeight }}',
    placeholder: {{ json_encode($placeholder) }}
})" class="pro-editor-container" :class="{ 'pro-editor-fullscreen': isFullscreen }" @keydown.escape.window="exitFullscreen()">
    
    <!-- Hidden input that actually submits the form data -->
    <textarea :name="name" x-model="content" style="display: none;"></textarea>

    <!-- Top Master Bar: Mode Tabs + Font Controls + Text Direction + Undo/Redo -->
    <div class="pro-editor-top-bar">
        <!-- Left: Dual Mode Tabs -->
        <div class="pro-mode-tabs">
            <button type="button" @click="setMode('visual')" class="btn-pro-tab" :class="{ 'active': mode === 'visual' }">
                <i data-lucide="layout" style="width: 14px; height: 14px;"></i>
                <span>Visual Editor</span>
            </button>
            <button type="button" @click="setMode('html')" class="btn-pro-tab" :class="{ 'active': mode === 'html' }">
                <i data-lucide="code-2" style="width: 14px; height: 14px;"></i>
                <span>HTML Source</span>
            </button>
        </div>

        <!-- Center: Font Family & Heading Selectors (Active in Visual Mode) -->
        <div class="pro-center-controls" x-show="mode === 'visual'">
            <!-- Heading Selector -->
            <select @change="applyHeading($event.target.value); $event.target.value=''" class="admin-select pro-select">
                <option value="">Paragraph / Heading</option>
                <option value="p">Paragraph (Normal Text)</option>
                <option value="h1">Heading 1 (Main Title - H1)</option>
                <option value="h2">Heading 2 (Section - H2)</option>
                <option value="h3">Heading 3 (Sub-section - H3)</option>
                <option value="h4">Heading 4 (Minor - H4)</option>
            </select>

            <!-- Font Family Selector -->
            <select x-model="selectedFont" @change="applyFont(selectedFont)" class="admin-select pro-select">
                <option value="'Plus Jakarta Sans', sans-serif">Plus Jakarta Sans (Default)</option>
                <option value="'Inter', sans-serif">Inter (Clean Sans)</option>
                <option value="'Cairo', sans-serif">Cairo (Arabic / English)</option>
                <option value="'Tajawal', sans-serif">Tajawal (Arabic Clean)</option>
                <option value="'JetBrains Mono', monospace">JetBrains Mono (Code)</option>
                <option value="'Georgia', serif">Georgia (Editorial Serif)</option>
            </select>
        </div>

        <!-- Right: Text Direction + Undo/Redo -->
        <div class="pro-right-controls">
            <!-- RTL/LTR Direction Toggle -->
            <button type="button" class="btn-micro" @click="toggleDirection()" :title="direction === 'rtl' ? 'Switch to Left-to-Right' : 'Switch to Right-to-Left (Arabic)'">
                <i data-lucide="languages" style="width: 13px; height: 13px;"></i>
                <span x-text="direction.toUpperCase()" style="font-size: 0.72rem; font-weight: 700;"></span>
            </button>

            <span class="pro-toolbar-sep">&vert;</span>

            <button type="button" class="btn-micro" @click="exec('undo')" title="Undo (Ctrl+Z)"><i data-lucide="undo" style="width: 13px; height: 13px;"></i></button>
            <button type="button" class="btn-micro" @click="exec('redo')" title="Redo (Ctrl+Y)"><i data-lucide="redo" style="width: 13px; height: 13px;"></i></button>
            <button type="button" class="btn-micro" @click="toggleFullscreen()" :title="isFullscreen ? 'Exit fullscreen (Esc)' : 'Enter fullscreen'" :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"><i :data-lucide="isFullscreen ? 'minimize-2' : 'maximize-2'" style="width: 13px; height: 13px;"></i></button>
        </div>
    </div>

    <!-- Secondary Formatting Toolbar (Visual Mode) -->
    <div x-show="mode === 'visual'" class="pro-editor-formatting-toolbar">
        <!-- Text Styles -->
        <button type="button" class="btn-micro" @click="exec('bold')" title="Bold (Ctrl+B)"><i data-lucide="bold" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="exec('italic')" title="Italic (Ctrl+I)"><i data-lucide="italic" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="exec('underline')" title="Underline (Ctrl+U)"><i data-lucide="underline" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="exec('strikeThrough')" title="Strikethrough"><i data-lucide="strikethrough" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="insertInlineCode()" title="Inline Code"><i data-lucide="code" style="width: 14px; height: 14px;"></i></button>
        
        <span class="pro-toolbar-sep">&vert;</span>

        <!-- Text Alignment -->
        <button type="button" class="btn-micro" @click="exec('justifyLeft')" title="Align Left"><i data-lucide="align-left" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="exec('justifyCenter')" title="Align Center"><i data-lucide="align-center" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="exec('justifyRight')" title="Align Right"><i data-lucide="align-right" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="exec('justifyFull')" title="Justify"><i data-lucide="align-justify" style="width: 14px; height: 14px;"></i></button>

        <span class="pro-toolbar-sep">&vert;</span>

        <!-- Lists & Quote -->
        <button type="button" class="btn-micro" @click="exec('insertUnorderedList')" title="Bullet List"><i data-lucide="list" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="exec('insertOrderedList')" title="Numbered List"><i data-lucide="list-ordered" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="insertBlockquote()" title="Blockquote"><i data-lucide="quote" style="width: 14px; height: 14px;"></i></button>

        <span class="pro-toolbar-sep">&vert;</span>

        <!-- Links & Media -->
        <button type="button" class="btn-micro" @click="promptLink()" title="Insert Link"><i data-lucide="link" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="insertCodeBlock()" title="Code Block"><i data-lucide="terminal" style="width: 14px; height: 14px;"></i></button>
        <button type="button" class="btn-micro" @click="insertDivider()" title="Horizontal Divider">&minus;&minus;&minus;</button>

        <span class="pro-toolbar-sep">&vert;</span>

        <!-- Cloudflare R2 Image Upload Trigger -->
        <button type="button" @click="openImageModal()" class="btn-pro-image-trigger">
            <i data-lucide="image-plus" style="width: 14px; height: 14px;"></i>
            <span>Insert Image (R2)</span>
        </button>

        <!-- Clear Formatting -->
        <button type="button" class="btn-micro btn-pro-clear" @click="exec('removeFormat')" title="Clear Formatting">
            <i data-lucide="eraser" style="width: 14px; height: 14px;"></i>
        </button>
    </div>

    <!-- HTML Source Toolbar (HTML Mode) -->
    <div x-show="mode === 'html'" class="pro-editor-html-toolbar">
        <span class="pro-html-badge">HTML Source Mode:</span>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<h2>', '</h2>')">H2</button>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<h3>', '</h3>')">H3</button>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<p>', '</p>')">&lt;p&gt;</button>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<strong>', '</strong>')">&lt;b&gt;</button>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<em>', '</em>')">&lt;i&gt;</button>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<ul>\n  <li>', '</li>\n</ul>')">&lt;ul&gt;</button>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<blockquote>\n  ', '\n</blockquote>')">&lt;quote&gt;</button>
        <button type="button" class="btn-micro" @click="insertHtmlTag('<pre><code>', '</code></pre>')">&lt;code&gt;</button>
        <button type="button" class="btn-micro" @click="insertHtmlText('<hr class=\'article-divider\'>\n')">&lt;hr&gt;</button>
        
        <button type="button" @click="openImageModal()" class="btn-pro-image-trigger" style="margin-left: auto;">
            <i data-lucide="image-plus" style="width: 14px; height: 14px;"></i>
            <span>Upload Image (R2)</span>
        </button>
    </div>

    <!-- Main Editor Canvas Area -->
    <div class="pro-editor-canvas-wrap" @click="focusEditor()">
        <!-- Visual WYSIWYG ContentEditable Area -->
        <div x-show="mode === 'visual'"
             x-ref="visualEditor"
             contenteditable="true"
             @input="syncContentFromVisual()"
             @blur="syncContentFromVisual()"
             @keyup="syncContentFromVisual()"
             @paste="handlePaste($event)"
             @keydown.ctrl.b.prevent="exec('bold')"
             @keydown.ctrl.i.prevent="exec('italic')"
             @keydown.ctrl.u.prevent="exec('underline')"
             @keydown.ctrl.z.prevent="exec('undo')"
             @keydown.ctrl.y.prevent="exec('redo')"
             :dir="direction"
             :data-placeholder="placeholder"
             class="pro-editor-content-area"
             :style="'min-height: ' + minHeight + '; font-family: ' + selectedFont + '; direction: ' + direction + '; text-align: ' + (direction === 'rtl' ? 'right' : 'left') + ';'">
        </div>

        <!-- HTML Source CodeMirror 6 Area with textarea fallback -->
        <div x-show="mode === 'html'" class="pro-editor-html-wrap">
            <div x-ref="codeEditor" class="pro-editor-code-editor" x-show="codeMirrorReady"></div>
            <textarea x-ref="htmlTextarea"
                      :value="content"
                      @input="syncContentFromHtml($event.target.value)"
                      placeholder="Write or paste your clean HTML structure..."
                      class="pro-editor-html-area"
                      x-show="!codeMirrorReady"
                      :style="'min-height: ' + minHeight + ';'"></textarea>
        </div>
    </div>

    <!-- Bottom Status Bar -->
    <div class="pro-editor-footer">
        <div class="pro-footer-stats">
            <span>Words: <strong style="color: #cbd5e1;" x-text="wordCount">0</strong></span>
            <span>Characters: <strong style="color: #cbd5e1;" x-text="charCount">0</strong></span>
            <span>Est. Reading Time: <strong style="color: #38bdf8;" x-text="readingTime + ' min'">1 min</strong></span>
        </div>
        <div class="pro-footer-status">
            <span class="pro-status-live">● Live Synchronized</span>
        </div>
    </div>

    <!-- Image Upload & Insertion Modal (Cloudflare R2) -->
    <div x-show="showImageModal" x-cloak class="pro-modal-backdrop" @click.self="showImageModal = false">
        <div class="pro-modal-box">
            <div class="pro-modal-header">
                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #f8fafc; font-size: 1.05rem;">
                    <i data-lucide="cloud-upload" style="width: 20px; height: 20px; color: #c084fc;"></i>
                    <span>Upload & Insert Image (Cloudflare R2)</span>
                </div>
                <button type="button" @click="showImageModal = false" class="pro-modal-close-btn">
                    <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                </button>
            </div>

            <!-- Drag & Drop / File Select Box -->
            <div style="margin-bottom: 16px;">
                <input type="file" x-ref="modalFileInput" @change="handleModalFileSelect($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                
                <div @click="$refs.modalFileInput.click()" class="pro-upload-dropzone">
                    <template x-if="!modalImagePreview">
                        <div>
                            <i data-lucide="image-plus" style="width: 32px; height: 32px; color: #c084fc; margin: 0 auto 8px;"></i>
                            <div style="font-size: 0.88rem; font-weight: 600; color: #f8fafc;">Click to Choose Image or Drag Here</div>
                            <div style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px;">Supports PNG, JPEG, WebP, GIF up to 10MB</div>
                        </div>
                    </template>
                    <template x-if="modalImagePreview">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                            <img :src="modalImagePreview" alt="Preview" style="max-height: 140px; max-width: 100%; border-radius: 8px; object-fit: contain;">
                            <span style="font-size: 0.76rem; color: #38bdf8;">Click to change selected image</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Image Alt Text (SEO Crucial) -->
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">
                    Alt Text (Description for SEO & Accessibility) <span style="color: var(--admin-cyan);">*</span>
                </label>
                <input type="text" x-model="modalImageAlt" placeholder="Descriptive text for search engines..." class="admin-input" style="width: 100%; box-sizing: border-box;">
            </div>

            <!-- Caption (Optional) -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">
                    Caption (Optional - Displayed below image)
                </label>
                <input type="text" x-model="modalImageCaption" placeholder="e.g. Generated with Wan 2.2 neural synthesis" class="admin-input" style="width: 100%; box-sizing: border-box;">
            </div>

            <!-- Error Banner -->
            <div x-show="modalError" class="pro-modal-error" x-text="modalError" style="margin-bottom: 14px;"></div>

            <!-- Modal Action Buttons -->
            <div class="pro-modal-actions">
                <button type="button" @click="showImageModal = false" class="btn-admin btn-admin-secondary" :disabled="modalUploading">
                    Cancel
                </button>
                <button type="button" @click="uploadAndInsertImage()" class="btn-admin btn-admin-primary" :disabled="modalUploading || !modalFile">
                    <span x-show="!modalUploading" style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                        <span>Upload & Insert</span>
                    </span>
                    <span x-show="modalUploading">Uploading to R2...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Pro Editor Dedicated Styles */
.pro-editor-container {
    border: 1px solid var(--admin-border, rgba(255, 255, 255, 0.08));
    border-radius: 12px;
    overflow: hidden;
    background: #0b0f1a;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    width: 100%;
}

.pro-editor-top-bar {
    padding: 10px 14px;
    background: rgba(15, 20, 34, 0.98);
    border-bottom: 1px solid var(--admin-border, rgba(255, 255, 255, 0.08));
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.pro-mode-tabs {
    display: flex;
    align-items: center;
    gap: 4px;
    background: rgba(8, 11, 20, 0.8);
    padding: 3px;
    border-radius: 8px;
    border: 1px solid var(--admin-border, rgba(255, 255, 255, 0.08));
}

.btn-pro-tab {
    padding: 5px 12px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #94a3b8;
    background: transparent;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    font-family: inherit;
}

.btn-pro-tab:hover {
    color: #f8fafc;
    background: rgba(255, 255, 255, 0.05);
}

.btn-pro-tab.active {
    background: rgba(99, 102, 241, 0.2);
    color: #818cf8;
    border: 1px solid rgba(99, 102, 241, 0.35);
}

.pro-center-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.pro-select {
    padding: 4px 10px !important;
    font-size: 0.78rem !important;
    height: 32px !important;
    background: rgba(8, 11, 20, 0.8) !important;
    border-color: var(--admin-border, rgba(255, 255, 255, 0.08)) !important;
    color: #f8fafc !important;
    border-radius: 6px !important;
}

.pro-right-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pro-toolbar-sep {
    color: var(--admin-border, rgba(255, 255, 255, 0.15));
    margin: 0 2px;
}

.pro-editor-formatting-toolbar {
    padding: 8px 14px;
    background: rgba(11, 15, 26, 0.9);
    border-bottom: 1px solid var(--admin-border, rgba(255, 255, 255, 0.08));
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.btn-pro-image-trigger {
    background: rgba(192, 132, 252, 0.15);
    color: #c084fc;
    border: 1px solid rgba(192, 132, 252, 0.35);
    font-weight: 700;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 0.78rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    font-family: inherit;
    transition: all 0.2s ease;
}

.btn-pro-image-trigger:hover {
    background: rgba(192, 132, 252, 0.25);
    border-color: rgba(192, 132, 252, 0.5);
    color: #ffffff;
}

.btn-pro-clear {
    margin-left: auto;
    color: #94a3b8;
}

.pro-editor-html-toolbar {
    padding: 8px 14px;
    background: rgba(11, 15, 26, 0.9);
    border-bottom: 1px solid var(--admin-border, rgba(255, 255, 255, 0.08));
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.pro-html-badge {
    font-size: 0.76rem;
    color: #38bdf8;
    font-family: monospace;
    font-weight: 700;
}

/* Canvas Area */
.pro-editor-canvas-wrap {
    position: relative;
    background: #07090e;
    min-height: 420px;
    cursor: text;
    display: flex;
    flex-direction: column;
}

.pro-editor-content-area {
    width: 100%;
    min-height: 420px;
    padding: 24px 28px;
    outline: none !important;
    border: none !important;
    color: #f1f5f9;
    font-size: 1.02rem;
    line-height: 1.8;
    cursor: text;
    box-sizing: border-box;
    flex: 1;
}

.pro-editor-content-area:empty:before {
    content: attr(data-placeholder);
    color: #64748b;
    pointer-events: none;
    display: block;
}

.pro-editor-content-area h1,
.pro-editor-content-area h2,
.pro-editor-content-area h3,
.pro-editor-content-area h4 {
    color: #f8fafc;
    font-weight: 800;
    margin-top: 1.2em;
    margin-bottom: 0.5em;
    line-height: 1.3;
}

.pro-editor-content-area h1 { font-size: 1.8rem; }
.pro-editor-content-area h2 { font-size: 1.45rem; color: #38bdf8; }
.pro-editor-content-area h3 { font-size: 1.2rem; color: #c084fc; }
.pro-editor-content-area h4 { font-size: 1.05rem; }

.pro-editor-content-area p {
    margin-bottom: 1em;
    line-height: 1.8;
}

.pro-editor-content-area ul,
.pro-editor-content-area ol {
    margin: 1em 0 1em 1.5em;
    padding-left: 1em;
}

.pro-editor-content-area li {
    margin-bottom: 0.4em;
}

.pro-editor-content-area blockquote {
    border-left: 3px solid #6366f1;
    margin: 1.2em 0;
    padding: 10px 18px;
    background: rgba(99, 102, 241, 0.08);
    border-radius: 0 8px 8px 0;
    color: #cbd5e1;
    font-style: italic;
}

.pro-editor-content-area pre {
    background: #0f1422;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 14px 18px;
    overflow-x: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.88rem;
    color: #38bdf8;
    margin: 1.2em 0;
}

.pro-editor-content-area code {
    background: rgba(255, 255, 255, 0.08);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9em;
    color: #c084fc;
}

.pro-editor-content-area img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1em 0;
    display: block;
}

.pro-editor-content-area figure {
    margin: 1.4em 0;
    text-align: center;
}

.pro-editor-content-area figcaption {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 6px;
}

.pro-editor-content-area hr.article-divider {
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    margin: 2em 0;
}

/* HTML Source Area */
.pro-editor-html-wrap {
    width: 100%;
    flex: 1;
    display: flex;
}

.pro-editor-html-area {
    width: 100%;
    min-height: 420px;
    padding: 24px 28px;
    background: #05070c;
    border: none;
    outline: none;
    color: #38bdf8;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9rem;
    line-height: 1.7;
    resize: vertical;
    display: block;
    box-sizing: border-box;
}

/* Footer Status Bar */
.pro-editor-footer {
    padding: 8px 16px;
    background: rgba(15, 20, 34, 0.95);
    border-top: 1px solid var(--admin-border, rgba(255, 255, 255, 0.08));
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.74rem;
    color: #64748b;
}

.pro-footer-stats {
    display: flex;
    align-items: center;
    gap: 14px;
}

.pro-status-live {
    color: #10b981;
    font-weight: 600;
}

/* Modal */
.pro-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
}

.pro-modal-box {
    width: 100%;
    max-width: 520px;
    background: #0c101d;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
    border-radius: 16px;
    padding: 24px;
    margin: 16px;
    box-sizing: border-box;
}

.pro-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    border-bottom: 1px solid var(--admin-border, rgba(255, 255, 255, 0.08));
    padding-bottom: 12px;
}

.pro-modal-close-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pro-modal-close-btn:hover {
    color: #ffffff;
}

.pro-upload-dropzone {
    border: 2px dashed rgba(192, 132, 252, 0.35);
    background: rgba(192, 132, 252, 0.05);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pro-upload-dropzone:hover {
    border-color: rgba(192, 132, 252, 0.6);
    background: rgba(192, 132, 252, 0.08);
}

.pro-modal-error {
    color: #f87171;
    font-size: 0.8rem;
    padding: 8px 12px;
    border-radius: 6px;
    background: rgba(248, 113, 113, 0.1);
    border: 1px solid rgba(248, 113, 113, 0.25);
}

.pro-modal-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 16px;
}

/* Light document surfaces intentionally stay scoped to this editor component. */
.pro-editor-container {
    --editor-ink: #172033;
    --editor-muted: #64748b;
    --editor-border: #dbe3ee;
    --editor-accent: #2563eb;
    background: #f8fafc;
    border: 1px solid var(--editor-border);
    border-radius: 10px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    overflow: hidden;
    position: relative;
    z-index: 1;
}

.pro-editor-container.pro-editor-fullscreen {
    position: fixed;
    inset: 16px;
    z-index: 1000;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
}

body.pro-editor-body-lock { overflow: hidden; }

.pro-editor-top-bar,
.pro-editor-formatting-toolbar,
.pro-editor-html-toolbar,
.pro-editor-footer {
    background: #f8fafc;
    border-color: var(--editor-border);
    color: var(--editor-muted);
}

.pro-editor-top-bar { padding: 12px 16px; }
.pro-editor-formatting-toolbar,
.pro-editor-html-toolbar { padding: 9px 16px; }

.pro-mode-tabs {
    background: #eaf0f7;
    border-color: var(--editor-border);
}

.btn-pro-tab { color: #526176; }
.btn-pro-tab:hover { color: var(--editor-ink); background: #e2e8f0; }
.btn-pro-tab.active { background: #ffffff; color: var(--editor-accent); border-color: #bfdbfe; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); }

.pro-select {
    background: #ffffff !important;
    border-color: var(--editor-border) !important;
    color: var(--editor-ink) !important;
}

.pro-toolbar-sep { color: #cbd5e1; }
.pro-editor-formatting-toolbar .btn-micro,
.pro-editor-html-toolbar .btn-micro,
.pro-right-controls .btn-micro {
    color: #475569;
    background: transparent;
    border-color: transparent;
}

.pro-editor-formatting-toolbar .btn-micro:hover,
.pro-editor-html-toolbar .btn-micro:hover,
.pro-right-controls .btn-micro:hover {
    color: var(--editor-accent);
    background: #eaf2ff;
    border-color: #bfdbfe;
}

.pro-editor-canvas-wrap { background: #ffffff; min-height: 420px; }
.pro-editor-content-area {
    background: #ffffff;
    color: var(--editor-ink);
    max-width: 900px;
    margin: 0 auto;
    width: 100%;
    font-family: Georgia, 'Times New Roman', serif !important;
    font-size: 1.06rem;
    line-height: 1.8;
    padding: 42px clamp(22px, 6vw, 76px);
}

.pro-editor-content-area:focus { box-shadow: inset 0 0 0 2px rgba(37, 99, 235, 0.12); }
.pro-editor-content-area:empty:before { color: #94a3b8; }
.pro-editor-content-area h1,
.pro-editor-content-area h2,
.pro-editor-content-area h3,
.pro-editor-content-area h4 { color: #172033; }
.pro-editor-content-area h2 { color: #1e3a8a; }
.pro-editor-content-area h3 { color: #334155; }
.pro-editor-content-area blockquote { background: #f1f5f9; color: #475569; border-left-color: #2563eb; }
.pro-editor-content-area pre { background: #f1f5f9; border-color: var(--editor-border); color: #1d4ed8; }
.pro-editor-content-area code { background: #eef2ff; color: #4338ca; }
.pro-editor-content-area figcaption { color: #64748b; }
.pro-editor-content-area hr.article-divider { border-color: #cbd5e1; }

.pro-editor-html-wrap { background: #f8fafc; min-height: 420px; }
.pro-editor-code-editor,
.pro-editor-html-area {
    min-height: 420px;
    width: 100%;
    box-sizing: border-box;
    background: #ffffff;
    color: #172033;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.88rem;
    line-height: 1.65;
}

.pro-editor-code-editor { overflow: hidden; }
.pro-editor-code-editor .cm-editor { min-height: 420px; height: 100%; outline: none; }
.pro-editor-code-editor .cm-scroller { overflow: auto; font-family: 'JetBrains Mono', monospace; }
.pro-editor-code-editor .cm-activeLine { background: #f8fafc; }
.pro-editor-code-editor .cm-activeLineGutter { background: #eff6ff; color: #2563eb; }
.pro-editor-code-editor .cm-gutters { background: #f8fafc; border-right: 1px solid var(--editor-border); color: #94a3b8; }
.pro-editor-html-area { padding: 24px 28px; border: 0; resize: vertical; outline: none; }
.pro-editor-html-area:focus { box-shadow: inset 0 0 0 2px rgba(37, 99, 235, 0.16); }

.pro-editor-footer { border-top-color: var(--editor-border); }
.pro-editor-footer strong { color: #334155 !important; }
.pro-status-live { color: #059669; }

@media (max-width: 720px) {
    .pro-editor-top-bar { align-items: stretch; }
    .pro-mode-tabs, .pro-center-controls, .pro-right-controls { width: 100%; }
    .pro-center-controls .pro-select { flex: 1; min-width: 0; }
    .pro-editor-content-area { padding: 28px 20px; }
    .pro-editor-footer { align-items: flex-start; flex-direction: column; gap: 6px; }
    .pro-footer-stats { flex-wrap: wrap; gap: 8px 12px; }
    .pro-editor-container.pro-editor-fullscreen { inset: 0; border-radius: 0; }
}
</style>

<script>
// Define proContentEditor globally so it is always ready for Alpine
window.proContentEditor = function(config) {
    return {
        name: config.name || 'content',
        content: config.initialContent || '',
        uploadUrl: config.uploadUrl || '',
        minHeight: config.minHeight || '420px',
        placeholder: config.placeholder || 'Start typing your content here...',
        mode: 'visual',
        direction: 'ltr',
        selectedFont: "'Plus Jakarta Sans', sans-serif",
        isFullscreen: false,
        codeMirrorReady: false,
        codeMirrorLoading: false,
        codeMirrorView: null,
        
        // Image Modal State
        showImageModal: false,
        modalFile: null,
        modalImagePreview: '',
        modalImageAlt: '',
        modalImageCaption: '',
        modalUploading: false,
        modalError: '',
        savedVisualRange: null,

        init() {
            this.$nextTick(() => {
                if (this.$refs.visualEditor) {
                    if (this.content && this.content.trim()) {
                        this.$refs.visualEditor.innerHTML = this.content;
                    } else {
                        this.$refs.visualEditor.innerHTML = '';
                    }
                }
                this.initCodeMirror();
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        },

        focusEditor() {
            if (this.mode === 'visual' && this.$refs.visualEditor) {
                this.$refs.visualEditor.focus();
            }
        },

        setMode(newMode) {
            if (newMode === this.mode) return;

            if (newMode === 'html') {
                this.syncContentFromVisual();
                this.mode = 'html';
                this.$nextTick(() => {
                    this.initCodeMirror();
                    if (this.codeMirrorView) {
                        this.setSourceValue(this.content);
                        this.codeMirrorView.focus();
                    }
                    else if (this.$refs.htmlTextarea) this.$refs.htmlTextarea.focus();
                });
            } else {
                this.mode = 'visual';
                this.$nextTick(() => {
                    if (this.$refs.visualEditor) {
                        this.$refs.visualEditor.innerHTML = this.content || '';
                        this.$refs.visualEditor.focus();
                    }
                });
            }

            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        toggleDirection() {
            this.direction = (this.direction === 'ltr') ? 'rtl' : 'ltr';
        },

        syncContentFromVisual() {
            if (this.$refs.visualEditor) {
                const html = this.$refs.visualEditor.innerHTML;
                if (html === '<p><br></p>' || html === '<br>' || html.trim() === '') {
                    this.content = '';
                } else {
                    this.content = html;
                }
            }
        },

        syncContentFromHtml() {
            const source = arguments.length ? arguments[0] : this.getSourceValue();
            this.content = source || '';
            if (this.$refs.visualEditor) this.$refs.visualEditor.innerHTML = this.content;
        },

        async initCodeMirror() {
            if (this.codeMirrorReady || !this.$refs.codeEditor || this.codeMirrorLoading) return;
            this.codeMirrorLoading = true;

            try {
                const [{ basicSetup }, { html }, { EditorState }, { EditorView, keymap }, { defaultKeymap, indentWithTab }, { searchKeymap }] = await Promise.all([
                    import('https://esm.sh/codemirror@6.0.1'),
                    import('https://esm.sh/@codemirror/lang-html@6.4.9'),
                    import('https://esm.sh/@codemirror/state@6.5.2'),
                    import('https://esm.sh/@codemirror/view@6.36.5'),
                    import('https://esm.sh/@codemirror/commands@6.8.1'),
                    import('https://esm.sh/@codemirror/search@6.5.10'),
                ]);

                const updateListener = EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        this.content = update.state.doc.toString();
                        if (this.$refs.htmlTextarea) this.$refs.htmlTextarea.value = this.content;
                    }
                });

                this.codeMirrorView = new EditorView({
                    state: EditorState.create({
                        doc: this.content || '',
                        extensions: [
                            basicSetup,
                            html(),
                            keymap.of([...defaultKeymap, ...searchKeymap, indentWithTab]),
                            updateListener,
                        ],
                    }),
                    parent: this.$refs.codeEditor,
                });
                this.codeMirrorReady = true;
            } catch (error) {
                console.warn('CodeMirror 6 could not be loaded; using the HTML source fallback.', error);
            } finally {
                this.codeMirrorLoading = false;
            }
        },

        getSourceValue() {
            return this.codeMirrorView ? this.codeMirrorView.state.doc.toString() : (this.$refs.htmlTextarea?.value || this.content || '');
        },

        setSourceValue(value, selectionStart = null, selectionEnd = null) {
            this.content = value;
            if (this.codeMirrorView) {
                this.codeMirrorView.dispatch({
                    changes: { from: 0, to: this.codeMirrorView.state.doc.length, insert: value },
                    selection: selectionStart === null ? undefined : { anchor: selectionStart, head: selectionEnd ?? selectionStart },
                });
            } else if (this.$refs.htmlTextarea) {
                this.$refs.htmlTextarea.value = value;
                if (selectionStart !== null) {
                    this.$refs.htmlTextarea.focus();
                    this.$refs.htmlTextarea.setSelectionRange(selectionStart, selectionEnd ?? selectionStart);
                }
            }
        },

        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            document.body.classList.toggle('pro-editor-body-lock', this.isFullscreen);
            this.$nextTick(() => {
                if (this.codeMirrorView) this.codeMirrorView.requestMeasure();
            });
        },

        exitFullscreen() {
            if (!this.isFullscreen) return;
            this.isFullscreen = false;
            document.body.classList.remove('pro-editor-body-lock');
        },

        exec(command, value = null) {
            if (this.mode !== 'visual') return;
            this.$refs.visualEditor.focus();
            document.execCommand(command, false, value);
            this.syncContentFromVisual();
        },

        applyHeading(tag) {
            if (!tag || this.mode !== 'visual') return;
            this.exec('formatBlock', '<' + tag + '>');
        },

        applyFont(font) {
            if (this.mode !== 'visual') return;
            this.exec('fontName', font);
        },

        insertInlineCode() {
            if (this.mode !== 'visual') return;
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                const selectedText = range.toString() || 'code';
                const codeNode = document.createElement('code');
                codeNode.textContent = selectedText;
                range.deleteContents();
                range.insertNode(codeNode);
                this.syncContentFromVisual();
            }
        },

        insertBlockquote() {
            if (this.mode !== 'visual') return;
            this.exec('formatBlock', '<blockquote>');
        },

        insertCodeBlock() {
            if (this.mode === 'visual') {
                const pre = document.createElement('pre');
                const code = document.createElement('code');
                code.textContent = '// Insert code here\n';
                pre.appendChild(code);
                this.insertNodeAtCursor(pre);
                this.insertNodeAtCursor(document.createElement('p'));
                this.syncContentFromVisual();
            }
        },

        insertDivider() {
            if (this.mode === 'visual') {
                const hr = document.createElement('hr');
                hr.className = 'article-divider';
                this.insertNodeAtCursor(hr);
                this.insertNodeAtCursor(document.createElement('p'));
                this.syncContentFromVisual();
            }
        },

        promptLink() {
            if (this.mode !== 'visual') return;
            const selection = window.getSelection();
            const defaultText = selection.toString();
            const url = prompt('Enter destination URL (e.g. https://example.com or /tools/image-generator):', 'https://');
            if (url && url.trim() && url !== 'https://') {
                if (defaultText) {
                    this.exec('createLink', url);
                } else {
                    const text = prompt('Enter Link Label Text:', 'Click Here') || url;
                    const a = document.createElement('a');
                    a.href = url;
                    a.textContent = text;
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    this.insertNodeAtCursor(a);
                    this.syncContentFromVisual();
                }
            }
        },

        insertNodeAtCursor(node) {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                range.deleteContents();
                range.insertNode(node);
                range.collapse(false);
            } else if (this.$refs.visualEditor) {
                this.$refs.visualEditor.appendChild(node);
            }
        },

        handlePaste(e) {
            // Let normal HTML paste happen, then clean and sync
            this.$nextTick(() => {
                this.syncContentFromVisual();
            });
        },

        insertHtmlTag(openTag, closeTag) {
            const source = this.getSourceValue();
            const textarea = this.$refs.htmlTextarea;
            const start = this.codeMirrorView ? this.codeMirrorView.state.selection.main.from : (textarea?.selectionStart ?? source.length);
            const end = this.codeMirrorView ? this.codeMirrorView.state.selection.main.to : (textarea?.selectionEnd ?? start);
            const selectedText = source.substring(start, end);
            const replacement = openTag + selectedText + closeTag;
            this.setSourceValue(source.substring(0, start) + replacement + source.substring(end), start + openTag.length, start + openTag.length + selectedText.length);
        },

        insertHtmlText(text) {
            const source = this.getSourceValue();
            const textarea = this.$refs.htmlTextarea;
            const start = this.codeMirrorView ? this.codeMirrorView.state.selection.main.head : (textarea?.selectionStart ?? source.length);
            this.setSourceValue(source.substring(0, start) + text + source.substring(start), start + text.length, start + text.length);
        },

        // Image Modal Actions
        openImageModal() {
            this.saveVisualSelection();
            this.modalFile = null;
            this.modalImagePreview = '';
            this.modalImageAlt = '';
            this.modalImageCaption = '';
            this.modalError = '';
            this.modalUploading = false;
            this.showImageModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        saveVisualSelection() {
            this.savedVisualRange = null;
            if (this.mode !== 'visual' || !this.$refs.visualEditor) return;

            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return;

            const range = selection.getRangeAt(0);
            const container = range.commonAncestorContainer.nodeType === Node.ELEMENT_NODE
                ? range.commonAncestorContainer
                : range.commonAncestorContainer.parentElement;

            if (container && this.$refs.visualEditor.contains(container)) {
                this.savedVisualRange = range.cloneRange();
            }
        },

        insertImageIntoVisual(html) {
            const editor = this.$refs.visualEditor;
            if (!editor) return false;

            const range = this.savedVisualRange && editor.contains(this.savedVisualRange.commonAncestorContainer)
                ? this.savedVisualRange.cloneRange()
                : document.createRange();

            if (!this.savedVisualRange || !editor.contains(range.commonAncestorContainer)) {
                range.selectNodeContents(editor);
                range.collapse(false);
            }

            const fragment = range.createContextualFragment(html);
            const lastNode = fragment.lastChild;
            range.deleteContents();
            range.insertNode(fragment);

            const nextSelection = document.createRange();
            if (lastNode && editor.contains(lastNode)) {
                nextSelection.setStartAfter(lastNode);
            } else {
                nextSelection.selectNodeContents(editor);
                nextSelection.collapse(false);
            }
            nextSelection.collapse(true);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(nextSelection);
            this.savedVisualRange = nextSelection.cloneRange();

            return true;
        },

        handleModalFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                this.modalError = 'File exceeds maximum 10MB limit.';
                return;
            }

            this.modalFile = file;
            this.modalError = '';
            this.modalImageAlt = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');

            const reader = new FileReader();
            reader.onload = (evt) => {
                this.modalImagePreview = evt.target.result;
            };
            reader.readAsDataURL(file);
        },

        async uploadAndInsertImage() {
            if (!this.modalFile) {
                this.modalError = 'Please choose an image file first.';
                return;
            }

            if (!this.uploadUrl) {
                this.modalError = 'Upload endpoint not configured.';
                return;
            }

            this.modalUploading = true;
            this.modalError = '';

            const formData = new FormData();
            formData.append('image', this.modalFile);
            formData.append('alt_text', this.modalImageAlt);
            formData.append('caption', this.modalImageCaption);

            try {
                const response = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success && data.url) {
                    if (!/^https:\/\//i.test(data.url)) {
                        this.modalError = 'Image uploaded, but the returned URL is not a public HTTPS URL.';
                        return;
                    }

                    const altEscaped = this.escapeHtml(this.modalImageAlt.trim());
                    const capEscaped = this.escapeHtml(this.modalImageCaption.trim());
                    
                    let figureHtml = `<figure class="article-figure"><img src="${data.url}" alt="${altEscaped}" loading="lazy" class="article-img">`;
                    if (capEscaped) {
                        figureHtml += `<figcaption>${capEscaped}</figcaption>`;
                    }
                    figureHtml += `</figure><p></p>`;

                    if (this.mode === 'visual') {
                        if (!this.insertImageIntoVisual(figureHtml)) {
                            this.modalError = 'Image uploaded successfully, but could not be inserted into the editor.';
                            return;
                        }
                        this.syncContentFromVisual();
                    } else {
                        this.insertHtmlText(figureHtml + '\n');
                        this.content = this.getSourceValue();
                    }

                    console.debug('CMS editor image inserted', { mode: this.mode, hasCaption: Boolean(capEscaped) });
                    this.showImageModal = false;
                } else {
                    this.modalError = data.message || 'Image upload to Cloudflare R2 failed.';
                }
            } catch (err) {
                this.modalError = 'Network error during image upload: ' + err.message;
            } finally {
                this.modalUploading = false;
            }
        },

        escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        },

        get wordCount() {
            const text = (this.content || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            return text ? text.split(' ').length : 0;
        },

        get charCount() {
            const text = (this.content || '').replace(/<[^>]*>/g, ' ').trim();
            return text.length;
        },

        get readingTime() {
            return Math.max(1, Math.ceil(this.wordCount / 200));
        }
    };
};
</script>
