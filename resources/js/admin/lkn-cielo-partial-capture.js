jQuery(document).ready(function($) {
    var buttonFound = false;
    var buttonInitialized = false;
    
    // Function to initialize button and tooltip
    function initializePartialCaptureButton() {
        var $button = $("#lkn-partial-capture-btn");
        var $helpIcon = $("#lkn-capture-help-icon");
        
        if ($button.length > 0 && !$button.hasClass('lkn-capture-initialized')) {
            // Mark button as found and initialized
            buttonFound = true;
            buttonInitialized = true;
            $button.addClass('lkn-capture-initialized');
            
            // Initialize tooltip for help icon
            if ($helpIcon.length > 0) {
                var helpTooltip = lknCieloPartialCapture.messages.helpTooltip.replace(
                    '%s', 
                    lknCieloPartialCapture.currencySymbol + ' ' + parseFloat(lknCieloPartialCapture.orderTotal).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })
                );
                $helpIcon.attr("title", helpTooltip);
            }
            
            // Remove any existing event handlers to avoid duplicates
            $button.off("click.partialCapture");
            
            // Add click event handler
            $button.on("click.partialCapture", function(e) {
                e.preventDefault();
                
                var orderId = $(this).data("order-id");
                var transactionId = $(this).data("transaction-id");
                var orderTotal = parseFloat($(this).data("order-total"));
                var captureAmount = parseFloat($("#lkn-capture-amount").val());
                
                if (captureAmount <= 0 || captureAmount > orderTotal) {
                    alert(lknCieloPartialCapture.messages.invalidAmount);
                    return;
                }
                
                var remainingAmount = orderTotal - captureAmount;
                var confirmMessage = lknCieloPartialCapture.messages.confirmCapture + " " + captureAmount.toFixed(2) + "?";
                
                if (remainingAmount > 0) {
                    confirmMessage += "\n" + lknCieloPartialCapture.messages.discountWillBeApplied + " " + remainingAmount.toFixed(2) + ".";
                }
                
                if (!confirm(confirmMessage)) {
                    return;
                }
                
                var button = $(this);
                button.prop("disabled", true).text(lknCieloPartialCapture.messages.processing);
                
                $.ajax({
                    url: lknCieloPartialCapture.ajaxurl,
                    type: "POST",
                    data: {
                        action: "lkn_cielo_partial_capture",
                        order_id: orderId,
                        transaction_id: transactionId,
                        capture_amount: captureAmount,
                        nonce: lknCieloPartialCapture.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            window.location.reload();
                        } else {
                            alert(lknCieloPartialCapture.messages.error + " " + response.data);
                            button.prop("disabled", false).text(lknCieloPartialCapture.messages.buttonText);
                        }
                    },
                    error: function() {
                        alert(lknCieloPartialCapture.messages.processError);
                        button.prop("disabled", false).text(lknCieloPartialCapture.messages.buttonText);
                    }
                });
            });
        }
    }
    
    // Function to check if button still exists and has our control class
    function checkButtonStatus() {
        var $button = $("#lkn-partial-capture-btn");
        
        if (buttonFound && buttonInitialized) {
            // If button exists but doesn't have our control class, it was recreated
            if ($button.length === 0 || !$button.hasClass('lkn-capture-initialized')) {
                buttonFound = false;
                buttonInitialized = false;
            }
        }
    }
    
    // Create MutationObserver to watch for DOM changes
    var observer = new MutationObserver(function(mutations) {
        var shouldCheck = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Check if any nodes were added or removed
                if (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0) {
                    shouldCheck = true;
                }
            }
        });
        
        if (shouldCheck) {
            // Check current button status
            checkButtonStatus();
            
            // Try to initialize if not found or not initialized
            if (!buttonFound || !buttonInitialized) {
                setTimeout(function() {
                    initializePartialCaptureButton();
                }, 100);
            }
        }
    });
    
    // Start observing the order items container and surrounding areas
    var targetElements = [
        document.getElementById('woocommerce-order-items'),
        document.getElementById('order_line_items'),
        document.querySelector('.woocommerce_order_items_wrapper'),
        document.body
    ].filter(function(el) { return el !== null; });
    
    targetElements.forEach(function(target) {
        if (target) {
            observer.observe(target, {
                childList: true,
                subtree: true,
                attributes: false
            });
        }
    });
    
    // Initial initialization
    initializePartialCaptureButton();
    
    // Periodic check every 2 seconds as fallback
    setInterval(function() {
        checkButtonStatus();
        if (!buttonFound || !buttonInitialized) {
            initializePartialCaptureButton();
        }
    }, 2000);
});