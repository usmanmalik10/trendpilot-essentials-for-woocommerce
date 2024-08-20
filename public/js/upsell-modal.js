jQuery(document).ready(function($) {
    // Check if upsellProduct is defined
    if (typeof upsellProduct !== 'undefined') {
        // Check if a flag in local storage indicates that the popup should be shown
        var shouldShowPopup = localStorage.getItem('showUpsellPopup');
        if (shouldShowPopup === 'true') {
            // Dynamically create upsell content
            var upsellContent = '<div class="ae-upsell-card">' +
                                '<h2 class="upsell-heading">Why not upgrade your order?</h2>' +
                                '<hr class="ae-separator">' +
                                '<div class="ae-upsell-container">' +
                                '<div class="ae-upsell-left"><img src="' + upsellProduct.imageUrl + '" alt="' + upsellProduct.name + '"></div>' +
                                '<div class="ae-upsell-right">' +
                                '<h2>' + upsellProduct.name + '</h2>' +
                                '<p class="upsell-price">' + upsellProduct.price + '</p>' +
                                '<a href="' + upsellProduct.cartUrl + '&_wpnonce=' + upsellNonce + '" class="yes-please-button button">Add to Cart</a>' +
                                '<a href="' + upsellProduct.checkoutUrl + '" class="no-thanks-btn">No Thanks</a>' +
                                '</div></div></div>';

            // Populate the modal with the content and show it
            $('#upsellModal .modal-content').html(upsellContent);
            $('#upsellModal').show();

            // Remove the flag from local storage to prevent showing the popup again
            localStorage.removeItem('showUpsellPopup');
        }
    }

    // Event handler for 'Add to Cart' form submission
    $(document).on('submit', 'form.cart', function(event) {
        // Set the flag in local storage to show the popup on the next page load
        localStorage.setItem('showUpsellPopup', 'true');
    });

    // Close button functionality for the modal
    $('#upsellModal').on('click', '.modal-close', function() {
        $('#upsellModal').hide();
    });
});
