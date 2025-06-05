jQuery(document).ready(function($) {
    const validator = {
        init() {
            this.cacheElements();
            this.bindEvents();
            this.$container.append('<button class="zb-debug-btn">Debug Info</button>');
            this.$container.on('click', '.zb-debug-btn', () => {
                console.log('Current State:', this);
            });
        },

        cacheElements() {
            this.$container = $('.zb-email-validator');
            this.$emailInput = this.$container.find('.zb-email-input');
            this.$validateBtn = this.$container.find('.zb-validate-btn');
            this.$statusContainer = this.$container.find('.zb-validation-status');
            this.$resultsContainer = this.$container.find('.zb-validation-results');
            this.$statusValue = this.$container.find('.zb-status-value');
            this.$progressBar = this.$container.find('.zb-progress-bar');
            this.$resultEmail = this.$container.find('.zb-result-email');
            this.$validityBadge = this.$container.find('.zb-validity-badge');
            this.$existsStatus = this.$container.find('.zb-exists-status');
            this.$disposableStatus = this.$container.find('.zb-disposable-status');
            this.$acceptallStatus = this.$container.find('.zb-acceptall-status');
        },

        bindEvents() {
            this.$validateBtn.on('click', () => this.validateEmail());
            this.$emailInput.on('keypress', (e) => {
                if (e.which === 13) this.validateEmail();
            });
        },

        validateEmail() {
            const email = this.$emailInput.val().trim();

            // Basic email validation
            if (!this.isValidEmail(email)) {
                this.showError(zbEmailValidator.strings.invalid_email);
                return;
            }

            // Reset UI
            this.resetPreviousResults();
            this.$statusContainer.show();
            this.$statusValue.text(zbEmailValidator.strings.processing);

            // Create validation task
            $.ajax({
                url: zbEmailValidator.ajax_url,
                type: 'POST',
                data: {
                    action: 'zb_create_validation_task',
                    security: zbEmailValidator.nonce,
                    email: email
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        if (response.data.status === 'completed') {
                            this.showResults(response.data.result);
                        } else {
                            this.monitorTaskProgress(
                                response.data.task_id,
                                response.data.cache_key,
                                response.data.is_pro
                            );
                        }
                    } else {
                        this.showError(response.data?.message || zbEmailValidator.strings.error);
                    }
                },
                error: () => {
                    this.showError(zbEmailValidator.strings.error);
                }
            });
        },

        // monitorTaskProgress
        monitorTaskProgress(taskId, cacheKey, isPro) {
            const polling = setInterval(() => {
                $.ajax({
                    url: zbEmailValidator.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zb_check_validation_status',
                        security: zbEmailValidator.nonce,
                        task_id: taskId,
                        is_pro: isPro
                    },
                    dataType: 'json',
                    success: (response) => {
                        if (response.success) {
                            const status = response.data.status;
                            const progress = response.data.progress || 0;

                            this.updateProgress(progress);

                            // status work
                            if (status === 'completed') {
                                clearInterval(polling);
                                this.fetchResults(taskId, cacheKey, isPro);
                            }
                            else if (status === 'failed' || status === 'error') {
                                clearInterval(polling);
                                this.showError('Validation failed: ' + (response.data.message || status));
                            }
                            else if (status === 'pending' || status === 'processing') {
                                // not ready yet
                            }
                            else {
                                clearInterval(polling);
                                this.showError('Unknown status: ' + status);
                            }
                        } else {
                            clearInterval(polling);

                            // errors handling
                            let errorMsg = 'Status check failed';
                            if (response.data && response.data.message) {
                                errorMsg += ': ' + response.data.message;
                            }
                            if (response.data && response.data.body) {
                                console.error('API Response:', response.data.body);
                            }

                            this.showError(errorMsg);
                        }
                    },
                    error: (xhr) => {
                        clearInterval(polling);
                        this.showError('Server error: ' + xhr.statusText);
                    }
                });
            }, 2000);
        },

        fetchResults(taskId, cacheKey, isPro) {
            $.ajax({
                url: zbEmailValidator.ajax_url,
                type: 'POST',
                data: {
                    action: 'zb_get_validation_result',
                    security: zbEmailValidator.nonce,
                    task_id: taskId,
                    cache_key: cacheKey,
                    is_pro: isPro
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.showResults(response.data.result);
                    } else {
                        this.showError(response.data?.message || 'Failed to get results');
                    }
                },
                error: () => {
                    this.showError('Connection error');
                }
            });
        },

        showResults(data) {
            this.$statusContainer.hide();
            this.$resultsContainer.show();

            this.$resultEmail.text(data.email);

            // Format validity
            const isValid = data.valid;
            this.$validityBadge
                .text(isValid ? 'Valid' : 'Invalid')
                .removeClass('zb-valid zb-invalid zb-unknown')
                .addClass(isValid ? 'zb-valid' : 'zb-invalid');

            // Mailbox exists
            const existsStatus = data.exists;
            if (existsStatus !== null && existsStatus !== undefined) {
                this.$existsStatus
                    .text(existsStatus ? 'Yes' : 'No')
                    .removeClass('zb-status-yes zb-status-no')
                    .addClass(existsStatus ? 'zb-status-yes' : 'zb-status-no');
            } else {
                this.$existsStatus
                    .text('Not checked')
                    .addClass('zb-status-unknown');
            }

            // Disposable
            const isDisposable = data.disposable;
            this.$disposableStatus
                .text(isDisposable ? 'Yes' : 'No')
                .removeClass('zb-status-yes zb-status-no')
                .addClass(isDisposable ? 'zb-status-no' : 'zb-status-yes');

            // Accept All
            let acceptAllStatus = 'Unknown';
            let acceptAllClass = 'zb-status-unknown';
            let tooltip = '';

            if (data.permanent_error) {
                acceptAllStatus = 'Permanent error';
                acceptAllClass = 'zb-status-error';
                tooltip = 'Validation cannot be completed due to permanent error';
            } else if (data.error_category === 'accept_all' || data.smtp_error === 'server_accepts_all') {
                acceptAllStatus = 'Yes (unreliable)';
                acceptAllClass = 'zb-status-warning';
                tooltip = 'This mail server accepts all addresses, making verification unreliable';
            } else if (data.accept_all !== undefined) {
                acceptAllStatus = data.accept_all ? 'Yes (unreliable)' : 'No';
                acceptAllClass = data.accept_all ? 'zb-status-warning' : 'zb-status-yes';
                if (data.accept_all) {
                    tooltip = 'This mail server accepts all addresses, making verification unreliable';
                }
            }

            this.$acceptallStatus
                .text(acceptAllStatus)
                .removeClass('zb-status-yes zb-status-no zb-status-warning zb-status-error zb-status-unknown')
                .addClass(acceptAllClass);

            if (tooltip) {
                this.$acceptallStatus.attr('title', tooltip);
            } else {
                this.$acceptallStatus.removeAttr('title');
            }
        },

        updateProgress(percentage) {
            this.$progressBar.css('width', percentage + '%');
            this.$statusValue.text(`Processing (${percentage}%)`);
        },

        resetPreviousResults() {
            this.$progressBar.css('width', '0%');
            this.$statusValue.text('❔ Waiting');
            this.$resultsContainer.hide();

            // Clear only values, keep labels
            this.$validityBadge.text('').removeClass('zb-valid zb-invalid zb-unknown');
            this.$existsStatus.text('').removeClass('zb-status-yes zb-status-no zb-status-unknown');
            this.$disposableStatus.text('').removeClass('zb-status-yes zb-status-no');
            this.$acceptallStatus.text('').removeClass('zb-status-yes zb-status-no zb-status-warning zb-status-error zb-status-unknown');
        },

        showError(message) {
            this.$statusValue.text(message).css('color', '#d93025');
        },

        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    };

    // Initialize
    if ($('.zb-email-validator').length) {
        validator.init();
    }
});