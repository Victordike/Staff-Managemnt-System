/**
 * Advanced Loading Handler
 * Manages dynamic loading screen text based on user actions
 */

class LoadingHandler {
    constructor() {
        this.loadingScreen = document.getElementById('loadingScreen');
        this.loadingText = document.querySelector('.loading-text');
        this.loadingSubtext = document.querySelector('.loading-subtext');
        this.defaultMessages = {
            page: 'Loading page',
            form: 'Processing request',
            upload: 'Uploading file',
            delete: 'Deleting item',
            save: 'Saving changes',
            connect: 'Connecting to server',
            authenticate: 'Authenticating',
            register: 'Creating account',
            import: 'Importing data',
            export: 'Exporting data',
            search: 'Searching',
            default: 'Loading'
        };
    }

    /**
     * Show loading screen with custom message
     * @param {string} message - Custom message to display
     */
    show(message = null) {
        if (!this.loadingScreen) return;
        
        message = message || this.defaultMessages.default;
        this.updateText(message);
        
        this.loadingScreen.style.opacity = '1';
        this.loadingScreen.style.display = 'flex';
        this.loadingScreen.classList.remove('hidden');
    }

    /**
     * Hide loading screen
     */
    hide() {
        if (!this.loadingScreen) return;
        
        this.loadingScreen.style.opacity = '0';
        this.loadingScreen.style.transition = 'opacity 0.5s ease-out';
        
        setTimeout(() => {
            this.loadingScreen.style.display = 'none';
        }, 500);
    }

    /**
     * Update loading text
     * @param {string} message - Message to display
     */
    updateText(message) {
        if (this.loadingText) {
            this.loadingText.textContent = 'Staff Management System';
        }
        if (this.loadingSubtext) {
            this.loadingSubtext.innerHTML = `${message}<span class="loading-dots"></span>`;
        }
    }

    /**
     * Auto-hide after page load
     */
    autoHideOnLoad() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                this.hide();
            }, 500);
        });
    }

    /**
     * Setup form submission handlers
     * Automatically show loading on form submission
     */
    setupFormHandlers() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            let message = this.defaultMessages.form;

            // Detect form type from data attributes or form ID
            if (form.dataset.loadingMessage) {
                message = form.dataset.loadingMessage;
            } else if (form.id === 'registrationForm') {
                message = this.defaultMessages.register;
            } else if (form.id === 'uploadForm') {
                message = this.defaultMessages.upload;
            } else if (form.id === 'searchForm') {
                message = this.defaultMessages.search;
            }

            this.show(message);
        });
    }

    /**
     * Setup link click handlers for navigation
     */
    setupLinkHandlers() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            // Only intercept internal links (not external or empty)
            if (!href || href.startsWith('http') || href.startsWith('//') || href.startsWith('mailto:')) {
                return;
            }

            let message = this.defaultMessages.page;

            // Detect message from data attributes
            if (link.dataset.loadingMessage) {
                message = link.dataset.loadingMessage;
            }
            // Detect from link text or common patterns
            else if (href.includes('login')) {
                message = this.defaultMessages.connect;
            } else if (href.includes('register')) {
                message = this.defaultMessages.register;
            } else if (href.includes('upload')) {
                message = this.defaultMessages.upload;
            } else if (href.includes('dashboard')) {
                message = this.defaultMessages.page;
            }

            this.show(message);
        });
    }

    /**
     * Setup AJAX request handlers
     */
    setupAjaxHandlers() {
        document.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action]');
            if (!button) return;

            const action = button.dataset.action;
            const messages = {
                delete: this.defaultMessages.delete,
                save: this.defaultMessages.save,
                upload: this.defaultMessages.upload,
                import: this.defaultMessages.import,
                export: this.defaultMessages.export,
                search: this.defaultMessages.search,
            };

            if (messages[action]) {
                this.show(messages[action]);
            }
        });
    }

    /**
     * Initialize all handlers
     */
    init() {
        this.autoHideOnLoad();
        this.setupFormHandlers();
        this.setupLinkHandlers();
        this.setupAjaxHandlers();
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const loader = new LoadingHandler();
    loader.init();
    // Make it globally accessible
    window.loadingHandler = loader;
});
