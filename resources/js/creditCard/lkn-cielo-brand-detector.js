/**
 * Cielo Card Brand Detector
 * 
 * Monitors card number input and applies visual effects to brand icons.
 * Handles WooCommerce checkout integration and submit button synchronization.
 * 
 * Features:
 * - Real-time card brand detection (6+ digits)
 * - Visual feedback on brand icons (grayscale filters)
 * - Focus effects for modern form fields
 * - Submit button synchronization with WooCommerce
 * - MutationObserver for dynamic content
 * - WooCommerce events integration (updated_checkout)
 * 
 * @package Lkn\WCCieloPaymentGateway
 * @since 1.0.0
 * @author Link Nacional
 * 
 * Used with: lkn-cielo-credit-payment-fields-modern-layout.php
 * CSS Support: lkn-cielo-modern-layout.css
 */

(function() {
    'use strict';
    
    let isInitialized = false;
    let cardNumberInput = null;
    let brandIcons = null;
    
    // Card brand detection patterns (converted from PHP regex)
    const cardPatterns = [
        {
            name: 'visa',
            regex: /^4[0-9]{2,15}$/
        },
        {
            name: 'elo',
            regex: /^(431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/
        },
        {
            name: 'hipercard',
            regex: /^(606282|3841)\d{0,13}$/
        },
        {
            name: 'diners',
            regex: /^3(?:0[0-5]|[68][0-9])[0-9]{0,11}$/
        },
        {
            name: 'discover',
            regex: /^6(?:011|5[0-9]{2})[0-9]{0,12}$/
        },
        {
            name: 'jcb',
            regex: /^(?:2131|1800|35\d{2})\d{0,11}$/
        },
        {
            name: 'aura',
            regex: /^50[0-9]{2,17}$/
        },
        {
            name: 'amex',
            regex: /^3[47][0-9]{2,13}$/
        },
        {
            name: 'mastercard',
            regex: /^5[1-5]\d{0,14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{0,12}$/
        }
    ];
    
    /**
     * Detect card brand based on card number
     * @param {string} number - Card number (cleaned)
     * @returns {string|null} - Brand name or null if not found
     */
    function detectCardBrand(number) {
        const cleanNumber = number.replace(/\s+/g, '');
        
        // Need at least 6 digits to detect
        if (cleanNumber.length < 6) {
            return null;
        }
        
        // Test against each pattern
        for (const pattern of cardPatterns) {
            if (pattern.regex.test(cleanNumber)) {
                return pattern.name;
            }
        }
        
        return null;
    }
    
    /**
     * Apply visual effects to brand icons
     * @param {string|null} detectedBrand - Brand name or null
     */
    function updateBrandIcons(detectedBrand) {
        // Só manipular ícones se estiverem habilitados
        if (typeof lknCieloCreditBrandConfig !== 'undefined' && lknCieloCreditBrandConfig.show_card_brand_icons !== 'yes') {
            return; // Não manipular os ícones se não estiverem habilitados
        }
        
        if (!brandIcons || brandIcons.length === 0) {
            brandIcons = document.querySelectorAll('#cielo-credit-card-brands .card-brand-icon');
        }
        
        brandIcons.forEach(icon => {
            const iconBrand = icon.getAttribute('data-brand');
            
            if (detectedBrand === null) {
                // No brand detected - remove all effects  
                icon.style.filter = '';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            } else if (iconBrand === detectedBrand) {
                // Highlight detected brand - remove grayscale only
                icon.style.filter = 'grayscale(0%) opacity(1)';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            } else {
                // Gray out other brands  
                icon.style.filter = 'grayscale(100%) opacity(0.4)';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            }
        });
    }
    
    /**
     * Handle card number input changes
     */
    function handleCardInput() {
        if (!cardNumberInput) return;
        
        const cardNumber = cardNumberInput.value;
        const cleanNumber = cardNumber.replace(/\s+/g, '');
        
        // Só aplicar efeitos nos ícones se estiverem habilitados
        if (typeof lknCieloCreditBrandConfig !== 'undefined' && lknCieloCreditBrandConfig.show_card_brand_icons === 'yes') {
            // Apply gray filter when user starts typing (1+ digits)
            if (cleanNumber.length >= 1 && cleanNumber.length < 6) {
                // Gray out all brands when typing but not enough digits to detect
                applyGrayFilterToAll();
            } else {
                // Detect brand only when 6+ digits
                const detectedBrand = detectCardBrand(cardNumber);
                updateBrandIcons(detectedBrand);
            }
        }
        
        // Outras funcionalidades do input continuam funcionando independentemente
    }
    
    /**
     * Apply gray filter to all brand icons
     */
    function applyGrayFilterToAll() {
        // Só manipular ícones se estiverem habilitados
        if (typeof lknCieloCreditBrandConfig !== 'undefined' && lknCieloCreditBrandConfig.show_card_brand_icons !== 'yes') {
            return; // Não manipular os ícones se não estiverem habilitados
        }
        
        if (!brandIcons || brandIcons.length === 0) {
            brandIcons = document.querySelectorAll('#cielo-credit-card-brands .card-brand-icon');
        }
        
        brandIcons.forEach(icon => {
            icon.style.filter = 'grayscale(100%) opacity(0.4)';
            icon.style.transform = '';
            icon.style.transition = 'all 0.3s ease-in-out';
        });
    }
    
    /**
     * Initialize the brand detector
     */
    function initializeBrandDetector() {
        // Get elements - credit specific ID
        cardNumberInput = document.getElementById('lkn_ccno');
        brandIcons = document.querySelectorAll('#cielo-credit-card-brands .card-brand-icon');
        
        // Setup card input listeners apenas se input existir
        if (cardNumberInput && !cardNumberInput.hasAttribute('data-cielo-initialized')) {
            cardNumberInput.setAttribute('data-cielo-initialized', 'true');
            
            // Event listeners
            cardNumberInput.addEventListener('input', handleCardInput);
            cardNumberInput.addEventListener('keyup', handleCardInput);
            cardNumberInput.addEventListener('paste', function() {
                // Small delay to allow paste content to be processed
                setTimeout(handleCardInput, 100);
            });
            
            // Initial check if there's already a value
            if (cardNumberInput.value) {
                handleCardInput();
            }
        }
        
        // Enhanced focus effects for modern layout - sempre executar
        const modernInputs = document.querySelectorAll('.field-input:not([data-focus-initialized]), .field-select:not([data-focus-initialized])');
        
        modernInputs.forEach(input => {
            input.setAttribute('data-focus-initialized', 'true');
            
            input.addEventListener('focus', function() {
                this.closest('.modern-field').classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.modern-field').classList.remove('focused');
            });
        });
        
        // Initialize submit button synchronization - SEMPRE executar
        initializeSubmitButton();
        
        return true;
    }
    
    /**
     * Initialize submit button synchronization
     */
    function initializeSubmitButton() {
        const cieloSubmitBtn = document.getElementById('cielo-credit-submit-btn');
        
        if (!cieloSubmitBtn) return;
        
        // Find the actual WooCommerce submit button
        const wooSubmitBtn = document.querySelector('#place_order, [name="woocommerce_checkout_place_order"], .wc-block-components-checkout-place-order-button');
        
        if (wooSubmitBtn) {
            // Sync custom button with WooCommerce button state
            const syncButtonState = () => {
                // Don't sync if button is in processing state (custom feedback)
                if (cieloSubmitBtn.textContent === 'Processing...' && cieloSubmitBtn.style.backgroundColor === 'rgb(108, 117, 125)') {
                    return;
                }
                
                if (wooSubmitBtn.disabled) {
                    cieloSubmitBtn.disabled = true;
                    cieloSubmitBtn.textContent = wooSubmitBtn.textContent || 'Processing...';
                } else {
                    cieloSubmitBtn.disabled = false;
                    cieloSubmitBtn.textContent = cieloSubmitBtn.getAttribute('data-original-text') || 'Confirm Payment';
                }
            };
            
            // Store original text
            if (!cieloSubmitBtn.getAttribute('data-original-text')) {
                cieloSubmitBtn.setAttribute('data-original-text', cieloSubmitBtn.textContent);
            }
            
            // Click handler - trigger WooCommerce submit with delay feedback
            cieloSubmitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.disabled && wooSubmitBtn && !wooSubmitBtn.disabled) {
                    // Immediate visual feedback
                    this.disabled = true;
                    this.style.backgroundColor = '#6c757d';
                    this.style.borderColor = '#6c757d';
                    this.style.cursor = 'not-allowed';
                    this.textContent = 'Processing...';
                    
                    // Trigger WooCommerce submit
                    wooSubmitBtn.click();
                    
                    // If no checkout error occurs, restore after 4 seconds
                    const restoreButton = () => {
                        setTimeout(() => {
                            if (this.disabled) {
                                this.disabled = false;
                                this.style.backgroundColor = '';
                                this.style.borderColor = '';
                                this.style.cursor = '';
                                this.textContent = this.getAttribute('data-original-text') || 'Confirm Payment';
                            }
                        }, 4000);
                    };
                    
                    restoreButton();
                }
            });
            
            // Monitor WooCommerce button changes
            const observer = new MutationObserver(syncButtonState);
            observer.observe(wooSubmitBtn, { 
                attributes: true, 
                attributeFilter: ['disabled', 'class'],
                childList: true,
                subtree: true 
            });
            
            // Initial sync
            syncButtonState();
            
            // Also listen for form validation changes
            document.addEventListener('checkout_error', () => {
                // Restore button immediately on error
                if (cieloSubmitBtn.disabled) {
                    cieloSubmitBtn.disabled = false;
                    cieloSubmitBtn.style.backgroundColor = '';
                    cieloSubmitBtn.style.borderColor = '';
                    cieloSubmitBtn.style.cursor = '';
                    cieloSubmitBtn.textContent = cieloSubmitBtn.getAttribute('data-original-text') || 'Confirm Payment';
                }
                syncButtonState();
            });
            
            document.addEventListener('updated_checkout', () => {
                setTimeout(syncButtonState, 100);
            });
        }
    }
    
    /**
     * Setup MutationObserver to watch for DOM changes
     */
    function setupObserver() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if any new nodes contain our target elements
                    const hasTargetElements = Array.from(mutation.addedNodes).some(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            return node.querySelector('#lkn_ccno, #cielo-credit-card-brands .card-brand-icon') ||
                                   node.id === 'lkn_ccno' ||
                                   node.classList?.contains('card-brand-icon');
                        }
                        return false;
                    });
                    
                    if (hasTargetElements) {
                        setTimeout(initializeBrandDetector, 100);
                    }
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initialize on WooCommerce checkout update
    jQuery(document.body).on('updated_checkout', function() {
        setTimeout(initializeBrandDetector, 200);
    });
    
    // Initialize on DOMContentLoaded as fallback
    document.addEventListener('DOMContentLoaded', function() {
        initializeBrandDetector();
        setupObserver();
    });
    
    // Initialize immediately if DOM is already loaded
    if (document.readyState === 'loading') {
        // Do nothing, DOMContentLoaded will fire
    } else {
        initializeBrandDetector();
        setupObserver();
    }
})();